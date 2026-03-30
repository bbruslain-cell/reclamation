<?php

namespace App\Http\Controllers\Web\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Models\Utilisateur;

class AdminRolesController extends BaseAdminController
{
    public function index(Request $request): View
    {
        $actor = $this->access->requireActor($request);
        $this->assertCanManageUsers((int) $actor->id_utilisateur);

        $roles = DB::table('roles')->where('actif', true)->orderBy('code')->get();
        
        $rolesCount = DB::table('model_has_roles')
            ->where('model_type', Utilisateur::class)
            ->select('id_role', DB::raw('count(*) as total'))
            ->groupBy('id_role')
            ->pluck('total', 'id_role')
            ->toArray();

        return view('admin.roles.index', [
            'actor'      => $actor,
            'roles'      => $roles,
            'rolesCount' => $rolesCount,
        ]);
    }



    public function updateRole(Request $request, int $id): RedirectResponse
    {
        return $this->executeAdminAction($request, function () use ($request, $id): void {
            $payload = $request->validate([
                'libelle' => ['required', 'string', 'max:255'],
                'actif'   => ['nullable', 'boolean'],
            ]);

            DB::table('roles')->where('id_role', $id)->update([
                'libelle'    => trim($payload['libelle']),
                'actif'      => (bool) ($payload['actif'] ?? false),
                'updated_at' => now(),
            ]);
        }, 'users', 'MODIFICATION_ROLE', "Mise à jour du rôle ID-{$id}");
    }
}
