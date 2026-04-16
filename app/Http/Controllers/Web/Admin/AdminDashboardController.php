<?php

namespace App\Http\Controllers\Web\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminDashboardController extends BaseAdminController
{
    public function index(Request $request): View
    {
        $actor = $this->access->requireActor($request);
        $this->assertCanManageAdmin($actor);

        $utilisateursCount = DB::table('utilisateurs')->count();
        $directionsCount = DB::table('directions')->count();
        $servicesCount = DB::table('services')->count();
        $demandesArchiveesCount = DB::table('demandes')->whereNotNull('date_cloture')->count();

        $recentLogs = DB::table('historique_actions as h')
            ->leftJoin('utilisateurs as u', 'u.id_utilisateur', '=', 'h.id_utilisateur')
            ->where('h.type_action', 'like', 'ADMIN\_%')
            ->select(
                'h.id_action',
                'h.id_utilisateur',
                'h.type_action',
                'h.commentaire',
                'h.date_action',
                'h.id_demande',
                'u.nom as admin_nom',
                'u.prenom as admin_prenom',
                'u.email as admin_email'
            )
            ->orderByDesc('h.date_action')
            ->limit(30)
            ->get()
            ->map(function ($log) {
                $rawType = Str::after((string) $log->type_action, 'ADMIN_');
                $log->action_libelle = ucwords(strtolower(str_replace('_', ' ', $rawType)));
                $log->admin_display_name = trim(($log->admin_prenom ?? '').' '.($log->admin_nom ?? ''));
                if ($log->admin_display_name === '') {
                    $log->admin_display_name = 'Admin inconnu';
                }

                return $log;
            });

        return view('admin.dashboard', [
            'actor' => $actor,
            'avatarUrl' => $actor->avatar_path ? Storage::disk('public')->url($actor->avatar_path) : null,
            'counts' => [
                'utilisateurs' => $utilisateursCount,
                'directions' => $directionsCount,
                'services' => $servicesCount,
                'demandes_archivees' => $demandesArchiveesCount,
            ],
            'recentLogs' => $recentLogs,
        ]);
    }

    public function uploadAvatar(Request $request): RedirectResponse
    {
        $actor = $this->access->requireActor($request);
        $this->assertCanManageAdmin($actor);

        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        $current = DB::table('utilisateurs')->where('id_utilisateur', $actor->id_utilisateur)->value('avatar_path');
        if ($current) {
            Storage::disk('public')->delete($current);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        DB::table('utilisateurs')->where('id_utilisateur', $actor->id_utilisateur)->update([
            'avatar_path' => $path,
            'updated_at' => now(),
        ]);

        $this->recordAdminAudit((int) $actor->id_utilisateur, 'AVATAR_UPLOAD', 'Mise à jour de la photo de profil administrateur');

        return redirect()->back()->with('success', 'Photo de profil mise à jour.');
    }

    public function removeAvatar(Request $request): RedirectResponse
    {
        $actor = $this->access->requireActor($request);
        $this->assertCanManageAdmin($actor);

        $current = DB::table('utilisateurs')->where('id_utilisateur', $actor->id_utilisateur)->value('avatar_path');

        if ($current) {
            Storage::disk('public')->delete($current);
        }

        DB::table('utilisateurs')->where('id_utilisateur', $actor->id_utilisateur)->update([
            'avatar_path' => null,
            'updated_at' => now(),
        ]);

        $this->recordAdminAudit((int) $actor->id_utilisateur, 'AVATAR_REMOVE', 'Suppression de la photo de profil administrateur');

        return redirect()->back()->with('success', 'Photo de profil supprimée.');
    }
}
