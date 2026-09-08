<?php

namespace App\Services;

use App\Models\Utilisateur;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AccessControlService
{
    public function resolveActor(Request $request): ?Utilisateur
    {
        // These shortcuts are kept only for automated tests.
        // In the real application, authentication must come from the Laravel session.
        if (app()->environment('testing')) {
            $userId = (int) ($request->header('X-User-Id') ?: $request->input('as_user_id', 0));
            if ($userId > 0) {
                return Utilisateur::query()->find($userId);
            }

            $email = $request->header('X-User-Email') ?: $request->input('as_user_email');
            if ($email) {
                return Utilisateur::query()->where('email', $email)->first();
            }
        }

        $authUser = Auth::guard('web')->user();
        if ($authUser instanceof Utilisateur) {
            return $authUser;
        }

        return null;
    }

    public function requireActor(Request $request): Utilisateur
    {
        $actor = $this->resolveActor($request);

        if (! $actor || ! $actor->actif) {
            throw new AuthorizationException('Utilisateur introuvable ou inactif.');
        }

        return $actor;
    }

    public function findActorByEmail(string $email): ?Utilisateur
    {
        return Utilisateur::query()->where('email', $email)->first();
    }

    public function roleCodes(int $userId): array
    {
        if (! Utilisateur::query()->where('id_utilisateur', $userId)->exists()) {
            return [];
        }

        $spatieRoles = collect();
        if (Schema::hasTable('model_has_roles')) {
            $spatieRoles = DB::table('model_has_roles as mhr')
                ->join('roles as r', 'r.id_role', '=', 'mhr.id_role')
                ->where('mhr.model_type', Utilisateur::class)
                ->where('mhr.model_id', $userId)
                ->where('r.actif', true)
                ->pluck('r.code');
        }

        $legacyRoles = DB::table('utilisateur_role as ur')
            ->join('roles as r', 'r.id_role', '=', 'ur.id_role')
            ->where('ur.id_utilisateur', $userId)
            ->where('r.actif', true)
            ->pluck('r.code');

        return $spatieRoles
            ->merge($legacyRoles)
            ->map(static function ($value) {
                $code = (string) $value;

                return $code === 'direction' ? 'chef_direction' : $code;
            })
            ->unique()
            ->values()
            ->toArray();
    }

    public function scopedServiceIds(int $userId): array
    {
        $serviceScope = DB::table('perimetre_service')
            ->where('id_utilisateur', $userId)
            ->pluck('id_service')
            ->map(static fn ($value) => (int) $value)
            ->toArray();

        $ownService = DB::table('utilisateurs')->where('id_utilisateur', $userId)->value('id_service');
        if ($ownService) {
            $serviceScope[] = (int) $ownService;
        }

        $directionIds = DB::table('perimetre_direction')
            ->where('id_utilisateur', $userId)
            ->pluck('id_direction')
            ->toArray();

        if (! empty($directionIds)) {
            $directionServiceIds = DB::table('services')
                ->whereIn('id_direction', $directionIds)
                ->pluck('id_service')
                ->map(static fn ($value) => (int) $value)
                ->toArray();
            $serviceScope = array_merge($serviceScope, $directionServiceIds);
        }

        return array_values(array_unique($serviceScope));
    }

    public function hasPermission(int $userId, string $permissionCode): bool
    {
        $user = Utilisateur::query()->find($userId);
        if (! $user || ! $user->actif) {
            return false;
        }

        if (in_array('admin', $this->roleCodes($userId), true)) {
            return true;
        }

        $hasLegacyPermission = DB::table('utilisateur_role as ur')
            ->join('roles as r', 'r.id_role', '=', 'ur.id_role')
            ->join('permission_role as pr', 'pr.id_role', '=', 'r.id_role')
            ->join('permissions as p', 'p.id_permission', '=', 'pr.id_permission')
            ->where('ur.id_utilisateur', $userId)
            ->where('r.actif', true)
            ->where(function ($query) use ($permissionCode): void {
                $query
                    ->where('p.code', $permissionCode)
                    ->orWhere('p.name', $permissionCode);
            })
            ->exists();

        if ($hasLegacyPermission) {
            return true;
        }

        try {
            return $user->checkPermissionTo($permissionCode);
        } catch (Throwable) {
            return false;
        }
    }

    public function assertPermission(int $userId, string $permissionCode): void
    {
        if (! $this->hasPermission($userId, $permissionCode)) {
            throw new AuthorizationException("Permission requise: {$permissionCode}");
        }
    }

    public function canAccessDemand(int $userId, int $demandId): bool
    {
        if ($this->hasPermission($userId, 'demande.view.all')) {
            return true;
        }

        $demand = DB::table('demandes')->where('id_demande', $demandId)->first();
        if (! $demand) {
            return false;
        }

        if ((int) ($demand->id_agent_traitant ?? 0) === $userId) {
            return true;
        }

        $isAgentOnly = $this->hasPermission($userId, 'demande.reply.send')
            && ! $this->hasPermission($userId, 'demande.assign')
            && ! $this->hasPermission($userId, 'demande.assign.agent')
            && ! $this->hasPermission($userId, 'demande.view.all');
        if ($isAgentOnly) {
            return false;
        }

        $serviceId = $demand->id_service_courant;
        if (! $serviceId) {
            return false;
        }

        $hasServiceScope = DB::table('perimetre_service')
            ->where('id_utilisateur', $userId)
            ->where('id_service', $serviceId)
            ->exists();

        if ($hasServiceScope) {
            return true;
        }

        $userServiceId = DB::table('utilisateurs')
            ->where('id_utilisateur', $userId)
            ->value('id_service');
        if ((int) $userServiceId === (int) $serviceId) {
            return true;
        }

        $demandDirectionId = DB::table('services')
            ->where('id_service', $serviceId)
            ->value('id_direction');

        return DB::table('perimetre_direction')
            ->where('id_utilisateur', $userId)
            ->where('id_direction', $demandDirectionId)
            ->exists();
    }

    public function assertDemandAccess(int $userId, int $demandId): void
    {
        if (! $this->canAccessDemand($userId, $demandId)) {
            throw new AuthorizationException('Acces refuse sur cette demande.');
        }
    }
}
