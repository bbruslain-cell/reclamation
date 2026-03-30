<?php

namespace App\Services;

use App\Models\Utilisateur;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccessControlService
{
    public function resolveActor(Request $request): ?Utilisateur
    {
        $userId = (int) ($request->header('X-User-Id') ?: $request->input('as_user_id', 0));
        if ($userId > 0) {
            return Utilisateur::query()->find($userId);
        }

        $email = $request->header('X-User-Email') ?: $request->input('as_user_email');
        if ($email) {
            return Utilisateur::query()->where('email', $email)->first();
        }

        $authUser = Auth::guard('web')->user();
        if ($authUser instanceof Utilisateur) {
            return $authUser;
        }

        if ($request->hasSession()) {
            $sessionId = (int) $request->session()->get('agent_id', 0);
            if ($sessionId > 0) {
                return Utilisateur::query()->find($sessionId);
            }
        }

        return null;
    }

    public function requireActor(Request $request): Utilisateur
    {
        $actor = $this->resolveActor($request);

        if (!$actor || !$actor->actif) {
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
        $user = Utilisateur::query()->find($userId);
        if (!$user) {
            return [];
        }

        return $user->roles()
            ->pluck('code')
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

        if (!empty($directionIds)) {
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
        if (!$user || !$user->actif) {
            return false;
        }

        return $user->checkPermissionTo($permissionCode);
    }

    public function assertPermission(int $userId, string $permissionCode): void
    {
        if (!$this->hasPermission($userId, $permissionCode)) {
            throw new AuthorizationException("Permission requise: {$permissionCode}");
        }
    }

    public function canAccessDemand(int $userId, int $demandId): bool
    {
        if ($this->hasPermission($userId, 'demande.view.all')) {
            return true;
        }

        $demand = DB::table('demandes')->where('id_demande', $demandId)->first();
        if (!$demand) {
            return false;
        }

        if ((int) ($demand->id_agent_traitant ?? 0) === $userId) {
            return true;
        }

        $isAgentOnly = $this->hasPermission($userId, 'demande.reply.send')
            && !$this->hasPermission($userId, 'demande.assign')
            && !$this->hasPermission($userId, 'demande.assign.agent')
            && !$this->hasPermission($userId, 'demande.view.all');
        if ($isAgentOnly) {
            return false;
        }

        $serviceId = $demand->id_service_courant;
        if (!$serviceId) {
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
        if (!$this->canAccessDemand($userId, $demandId)) {
            throw new AuthorizationException('Acces refuse sur cette demande.');
        }
    }
}
