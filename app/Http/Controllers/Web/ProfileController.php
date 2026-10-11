<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AccessControlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function __construct(
        private readonly AccessControlService $access
    ) {
    }

    public function uploadAvatar(Request $request): RedirectResponse
    {
        $actor = $this->access->requireActor($request);

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $current = DB::table('utilisateurs')
            ->where('id_utilisateur', $actor->id_utilisateur)
            ->value('avatar_path');

        if ($current) {
            Storage::disk('public')->delete($current);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        DB::table('utilisateurs')
            ->where('id_utilisateur', $actor->id_utilisateur)
            ->update([
                'avatar_path' => $path,
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', 'Photo de profil mise a jour.');
    }

    public function removeAvatar(Request $request): RedirectResponse
    {
        $actor = $this->access->requireActor($request);

        $current = DB::table('utilisateurs')
            ->where('id_utilisateur', $actor->id_utilisateur)
            ->value('avatar_path');

        if ($current) {
            Storage::disk('public')->delete($current);
        }

        DB::table('utilisateurs')
            ->where('id_utilisateur', $actor->id_utilisateur)
            ->update([
                'avatar_path' => null,
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', 'Photo de profil supprimee.');
    }
}
