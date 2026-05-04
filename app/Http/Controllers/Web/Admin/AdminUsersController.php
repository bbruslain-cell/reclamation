<?php

namespace App\Http\Controllers\Web\Admin;

use App\Models\Direction;
use App\Models\Role;
use App\Models\Service;
use App\Models\Utilisateur;
use App\Services\RoleSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminUsersController extends BaseAdminController
{
    public function __construct(
        \App\Services\AccessControlService $access,
        private readonly RoleSyncService $roles
    ) {
        parent::__construct($access);
    }

    public function index(Request $request): View
    {
        $actor = $this->access->requireActor($request);
        $this->assertCanManageUsers($actor);

        $utilisateurs = Utilisateur::query()
            ->with(['service.direction', 'roles'])
            ->orderBy('utilisateurs.nom')
            ->orderBy('utilisateurs.prenom')
            ->paginate(20);

        $this->prepareUsersForView($utilisateurs);

        $rolesByUser = $utilisateurs->getCollection()
            ->mapWithKeys(fn (Utilisateur $user) => [
                $user->id_utilisateur => $user->roles->pluck('code')->values()->all(),
            ])
            ->all();

        $roleIdsByUser = $utilisateurs->getCollection()
            ->mapWithKeys(fn (Utilisateur $user) => [
                $user->id_utilisateur => $user->roles->pluck('id_role')->map(fn ($id) => (int) $id)->values()->all(),
            ])
            ->all();

        $editingUser = null;
        $editingUserId = (int) $request->integer('edit_user');
        if ($editingUserId > 0) {
            $editingUser = Utilisateur::query()
                ->with(['service.direction', 'roles'])
                ->find($editingUserId);

            if ($editingUser) {
                $this->decorateUserForView($editingUser);
            }
        }

        return view('admin.users.index', [
            'actor' => $actor,
            'utilisateurs' => $utilisateurs,
            'rolesByUser' => $rolesByUser,
            'roleIdsByUser' => $roleIdsByUser,
            'directions' => Direction::query()->orderBy('code')->get(),
            'services' => Service::query()->with('direction')->orderBy('code')->get(),
            'roles' => Role::query()->where('actif', true)->orderBy('code')->get(),
            'editingUser' => $editingUser,
            'editingRoles' => $editingUser ? $editingUser->roles->pluck('id_role')->map(fn ($id) => (int) $id)->values()->all() : [],
            'counts' => [
                'utilisateurs' => Utilisateur::query()->count(),
                'utilisateurs_actifs' => Utilisateur::query()->where('actif', true)->count(),
                'utilisateurs_inactifs' => Utilisateur::query()->where('actif', false)->count(),
            ],
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        return $this->executeAdminAction($request, function () use ($request): void {
            $payload = $request->validate([
                'user_id' => ['nullable', 'integer', 'exists:utilisateurs,id_utilisateur'],
                'nom' => ['required', 'string', 'max:255'],
                'prenom' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'mot_de_passe' => ['nullable', 'string', 'min:8'],
                'id_service' => ['nullable', 'integer', 'exists:services,id_service'],
                'id_direction' => ['nullable', 'integer', 'exists:directions,id_direction'],
                'id_role' => ['nullable', 'integer', 'exists:roles,id_role'],
                'id_roles' => ['nullable', 'array', 'min:1'],
                'id_roles.*' => ['integer', 'exists:roles,id_role'],
                'perimetre_directions' => ['array'],
                'perimetre_directions.*' => ['integer', 'exists:directions,id_direction'],
                'perimetre_services' => ['array'],
                'perimetre_services.*' => ['integer', 'exists:services,id_service'],
            ]);

            $payload['mot_de_passe'] = $payload['mot_de_passe'] ?? null;
            $payload['id_service'] = isset($payload['id_service']) && $payload['id_service'] !== '' ? (int) $payload['id_service'] : null;
            $payload['id_direction'] = isset($payload['id_direction']) && $payload['id_direction'] !== '' ? (int) $payload['id_direction'] : null;
            $payload['id_roles'] = $this->normalizeRoleIds($payload);
            $payload['perimetre_directions'] = $payload['perimetre_directions'] ?? [];
            $payload['perimetre_services'] = $payload['perimetre_services'] ?? [];
            $payload['user_id'] = isset($payload['user_id']) && $payload['user_id'] !== '' ? (int) $payload['user_id'] : null;

            $roleCodes = $this->roles->roleCodesByIds($payload['id_roles']);
            if ($roleCodes === []) {
                throw ValidationException::withMessages(['id_roles' => 'Au moins un rôle actif est requis.']);
            }

            $payload = $this->forceAccueilService($payload, $roleCodes);

            $requiresDirection = in_array('chef_direction', $roleCodes, true);
            $requiresService = array_intersect($roleCodes, ['accueil', 'chef_service', 'agent']) !== [];
            $emailRule = Rule::unique('utilisateurs', 'email');

            if (!empty($payload['user_id'])) {
                $emailRule = $emailRule->ignore((int) $payload['user_id'], 'id_utilisateur');
            }

            $request->validate(['email' => ['required', 'email', 'max:255', $emailRule]]);

            if ($requiresDirection && empty($payload['id_direction'])) {
                throw ValidationException::withMessages(['id_direction' => 'La direction concernée est requise.']);
            }

            if ($requiresService && empty($payload['id_service'])) {
                throw ValidationException::withMessages(['id_service' => 'Le service principal est requis.']);
            }

            $email = strtolower(trim($payload['email']));
            $plainPassword = $payload['mot_de_passe'] ?: null;
            if (empty($payload['user_id']) && !$plainPassword) {
                $plainPassword = $this->generateReadablePassword();
            }

            DB::transaction(function () use ($payload, $email, $plainPassword): void {
                $userId = !empty($payload['user_id'])
                    ? $this->updateExistingUser((int) $payload['user_id'], $payload, $email, $plainPassword)
                    : $this->createNewUser($email, $payload, $plainPassword);

                $this->roles->syncUserRolesByIds($userId, $payload['id_roles']);

                $this->syncPerimetres(
                    $userId,
                    $payload['perimetre_directions'] ?? [],
                    $payload['perimetre_services'] ?? [],
                    $payload['id_direction'] ? [(int) $payload['id_direction']] : [],
                    $payload['id_service'] ? [(int) $payload['id_service']] : [],
                );
            });

            if (!$payload['user_id'] && $plainPassword) {
                session()->flash('generated_password', $plainPassword);
            } elseif (!empty($payload['mot_de_passe'])) {
                session()->flash('generated_password', $plainPassword);
            }
        }, 'users',
        empty($request->input('user_id')) ? 'CREATION_UTILISATEUR' : 'MODIFICATION_UTILISATEUR',
        empty($request->input('user_id'))
            ? "Création de l'utilisateur {$request->input('email')}"
            : "Mise à jour de l'utilisateur {$request->input('email')}"
        );
    }

    public function resetUserPassword(Request $request, int $id): RedirectResponse
    {
        return $this->executeAdminAction($request, function () use ($id): void {
            $user = DB::table('utilisateurs')->where('id_utilisateur', $id)->first();
            if (!$user) {
                throw ValidationException::withMessages(['user' => 'Utilisateur introuvable.']);
            }

            $newPassword = $this->generateReadablePassword();
            DB::table('utilisateurs')->where('id_utilisateur', $id)->update([
                'password_hash' => Hash::make($newPassword),
                'changement_mdp_requis' => true,
                'updated_at' => now(),
            ]);

            session()->flash('generated_password', $newPassword);
        }, 'users', 'RESET_MDP', "Réinitialisation du mot de passe pour U-{$id}");
    }

    public function toggleUser(Request $request, int $id): RedirectResponse
    {
        return $this->executeAdminAction($request, function () use ($id): void {
            $current = DB::table('utilisateurs')->where('id_utilisateur', $id)->value('actif');
            if ($current === null) {
                throw ValidationException::withMessages(['user' => 'Utilisateur introuvable.']);
            }

            DB::table('utilisateurs')->where('id_utilisateur', $id)->update([
                'actif' => !$current,
                'updated_at' => now(),
            ]);
        }, 'users', 'TOGGLE_STATUT', "Changement de statut (actif/inactif) pour U-{$id}");
    }

    public function deleteUser(Request $request, int $id): RedirectResponse
    {
        return $this->executeAdminAction($request, function () use ($id): void {
            DB::transaction(function () use ($id): void {
                $user = DB::table('utilisateurs')->where('id_utilisateur', $id)->first();
                if (!$user) {
                    throw ValidationException::withMessages(['user' => 'Utilisateur introuvable.']);
                }

                $hasOpenDemands = DB::table('demandes')
                    ->whereNull('date_cloture')
                    ->where(function ($query) use ($id): void {
                        $query
                            ->where('id_agent_traitant', $id)
                            ->orWhere('id_agent_direction', $id)
                            ->orWhere('id_agent_accueil', $id);
                    })
                    ->exists();

                if ($hasOpenDemands) {
                    throw ValidationException::withMessages([
                        'user' => 'Impossible de supprimer cet utilisateur : des demandes en cours lui sont encore rattachees.',
                    ]);
                }

                DB::table('utilisateur_role')->where('id_utilisateur', $id)->delete();
                DB::table('model_has_roles')->where('model_type', Utilisateur::class)->where('model_id', $id)->delete();
                DB::table('model_has_permissions')->where('model_type', Utilisateur::class)->where('model_id', $id)->delete();
                DB::table('perimetre_service')->where('id_utilisateur', $id)->delete();
                DB::table('perimetre_direction')->where('id_utilisateur', $id)->delete();

                if ($user->avatar_path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar_path);
                }

                DB::table('utilisateurs')->where('id_utilisateur', $id)->delete();
            });
        }, 'users', 'SUPPRESSION_UTILISATEUR', "Suppression définitive de l'utilisateur U-{$id}");
    }

    private function updateExistingUser(int $userId, array $payload, string $email, ?string $plainPassword): int
    {
        $update = [
            'email' => $email,
            'nom' => trim($payload['nom']),
            'prenom' => trim($payload['prenom']),
            'id_service' => $payload['id_service'] ?: null,
            'updated_at' => now(),
        ];

        if ($plainPassword !== null) {
            $update['password_hash'] = Hash::make($plainPassword);
            $update['changement_mdp_requis'] = true;
        }

        DB::table('utilisateurs')->where('id_utilisateur', $userId)->update($update);

        return $userId;
    }

    private function createNewUser(string $email, array $payload, string $plainPassword): int
    {
        return DB::table('utilisateurs')->insertGetId([
            'email' => $email,
            'nom' => trim($payload['nom']),
            'prenom' => trim($payload['prenom']),
            'password_hash' => Hash::make($plainPassword),
            'id_service' => $payload['id_service'] ?: null,
            'actif' => true,
            'changement_mdp_requis' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_utilisateur');
    }

    private function normalizeRoleIds(array $payload): array
    {
        $roleIds = $payload['id_roles'] ?? [];
        if ($roleIds === [] && !empty($payload['id_role'])) {
            $roleIds = [$payload['id_role']];
        }

        return array_values(array_unique(array_map(static fn ($value) => (int) $value, $roleIds)));
    }

    private function generateReadablePassword(int $length = 10): string
    {
        $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lowercase = 'abcdefghijkmnopqrstuvwxyz';
        $digits = '23456789';
        $pool = $uppercase.$lowercase.$digits;

        $characters = [
            $uppercase[random_int(0, strlen($uppercase) - 1)],
            $lowercase[random_int(0, strlen($lowercase) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
        ];

        while (count($characters) < $length) {
            $characters[] = $pool[random_int(0, strlen($pool) - 1)];
        }

        shuffle($characters);

        return implode('', $characters);
    }

    private function forceAccueilService(array $payload, array $roleCodes): array
    {
        if (!in_array('accueil', $roleCodes, true)) {
            return $payload;
        }

        $payload['id_service'] = $this->ucasServiceId();

        return $payload;
    }

    private function ucasServiceId(): int
    {
        $serviceId = (int) DB::table('services')->where('code', 'UCAS')->value('id_service');
        if ($serviceId <= 0) {
            throw ValidationException::withMessages(['id_service' => 'Le service UCAS doit exister pour attribuer le rôle accueil.']);
        }

        return $serviceId;
    }

    private function syncPerimetres(int $userId, array $perimDirections, array $perimServices, array $extraDirections = [], array $extraServices = []): void
    {
        $allDirections = array_unique(array_merge($perimDirections, $extraDirections));
        $allServices = array_unique(array_merge($perimServices, $extraServices));

        DB::table('perimetre_direction')->where('id_utilisateur', $userId)->delete();
        foreach ($allDirections as $dirId) {
            DB::table('perimetre_direction')->insert([
                'id_utilisateur' => $userId,
                'id_direction' => (int) $dirId,
            ]);
        }

        DB::table('perimetre_service')->where('id_utilisateur', $userId)->delete();
        foreach ($allServices as $srvId) {
            DB::table('perimetre_service')->insert([
                'id_utilisateur' => $userId,
                'id_service' => (int) $srvId,
            ]);
        }
    }

    private function prepareUsersForView(LengthAwarePaginator $utilisateurs): void
    {
        $utilisateurs->getCollection()->transform(function (Utilisateur $user): Utilisateur {
            return $this->decorateUserForView($user);
        });
    }

    private function decorateUserForView(Utilisateur $user): Utilisateur
    {
        $user->setAttribute('service_code', $user->service?->code);
        $user->setAttribute('service_libelle', $user->service?->libelle);
        $user->setAttribute('id_direction', $user->service?->id_direction);

        return $user;
    }
}
