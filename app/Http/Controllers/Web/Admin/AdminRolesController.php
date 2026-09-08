<?php

namespace App\Http\Controllers\Web\Admin;

use App\Models\Role;
use App\Models\Utilisateur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminRolesController extends BaseAdminController
{
    public function index(Request $request): View
    {
        $actor = $this->access->requireActor($request);
        $this->assertCanManageUsers($actor);

        $roles = DB::table('roles')->where('actif', true)->orderBy('code')->get();

        $rolesCount = DB::table('model_has_roles')
            ->where('model_type', Utilisateur::class)
            ->select('id_role', DB::raw('count(*) as total'))
            ->groupBy('id_role')
            ->pluck('total', 'id_role')
            ->toArray();

        return view('admin.roles.index', [
            'actor' => $actor,
            'roles' => $roles,
            'rolesCount' => $rolesCount,
        ]);
    }

    public function updateRole(Request $request, int $id): RedirectResponse
    {
        return $this->executeAdminAction($request, function () use ($request, $id): void {
            $payload = $request->validate([
                'libelle' => ['required', 'string', 'max:255'],
                'actif' => ['nullable', 'boolean'],
            ]);

            DB::table('roles')->where('id_role', $id)->update([
                'libelle' => trim($payload['libelle']),
                'actif' => (bool) ($payload['actif'] ?? false),
                'updated_at' => now(),
            ]);
        }, 'users', 'MODIFICATION_ROLE', "Mise a jour du role ID-{$id}");
    }

    public function syncRolePermissions(Request $request, int $id): RedirectResponse
    {
        return $this->executeAdminAction(
            $request,
            function () use ($request, $id): void {
                $payload = $request->validate([
                    'permissions' => ['present', 'array'],
                    'permissions.*' => ['string', Rule::exists('permissions', 'name')],
                ]);

                $role = Role::query()->findOrFail($id);
                $role->syncPermissions($payload['permissions']);
            },
            'users',
            'SYNC_PERMISSIONS',
            "Synchronisation des permissions du role ID-{$id}",
            '/admin/roles'
        );
    }
}
