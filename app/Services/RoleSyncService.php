<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RoleSyncService
{
    public function syncUserRolesByIds(int|Utilisateur $user, array $roleIds): array
    {
        $userModel = $this->resolveUser($user);
        $roleIds = array_values(array_unique(array_map(static fn ($value) => (int) $value, $roleIds)));

        $roles = Role::query()
            ->whereIn('id_role', $roleIds)
            ->where('actif', true)
            ->orderBy('code')
            ->get(['id_role', 'code', 'name']);

        if ($roles->count() !== count($roleIds)) {
            throw new InvalidArgumentException('Un ou plusieurs roles sont invalides ou inactifs.');
        }

        $roleNames = $roles->pluck('name')->filter()->values()->all();
        if ($roleNames === []) {
            throw new InvalidArgumentException('Aucun role exploitable n a ete fourni.');
        }

        $userModel->syncRoles($roleNames);
        $this->syncLegacyPivot((int) $userModel->getKey(), $roles->pluck('id_role')->all());

        return $roles->pluck('code')->all();
    }

    public function syncLegacyPivot(int $userId, array $roleIds): void
    {
        DB::table('utilisateur_role')->where('id_utilisateur', $userId)->delete();

        foreach (array_values(array_unique(array_map(static fn ($value) => (int) $value, $roleIds))) as $roleId) {
            DB::table('utilisateur_role')->insert([
                'id_utilisateur' => $userId,
                'id_role' => $roleId,
            ]);
        }
    }

    public function roleCodesByIds(array $roleIds): array
    {
        $roleIds = array_values(array_unique(array_map(static fn ($value) => (int) $value, $roleIds)));

        return Role::query()
            ->whereIn('id_role', $roleIds)
            ->orderBy('code')
            ->pluck('code')
            ->map(static fn ($value) => (string) $value)
            ->all();
    }

    private function resolveUser(int|Utilisateur $user): Utilisateur
    {
        if ($user instanceof Utilisateur) {
            return $user;
        }

        return Utilisateur::query()->findOrFail($user);
    }
}
