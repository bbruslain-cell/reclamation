<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AccessControlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function __construct(private readonly AccessControlService $access)
    {
    }

    public function showChange(Request $request): View
    {
        $actor = $this->access->requireActor($request);

        return view('auth.change', ['actor' => $actor]);
    }

    public function submitChange(Request $request): RedirectResponse
    {
        $actor = $this->access->requireActor($request);

        $payload = $request->validate([
            'ancien_mdp' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($payload['ancien_mdp'], $actor->password_hash)) {
            throw ValidationException::withMessages([
                'ancien_mdp' => 'Ancien mot de passe incorrect.',
            ]);
        }

        DB::table('utilisateurs')
            ->where('id_utilisateur', $actor->id_utilisateur)
            ->update([
                'password_hash' => Hash::make($payload['password']),
                'changement_mdp_requis' => false,
                'updated_at' => now(),
            ]);

        $freshActor = DB::table('utilisateurs')
            ->where('id_utilisateur', $actor->id_utilisateur)
            ->first();

        if ($freshActor) {
            $authActor = \App\Models\Utilisateur::query()->find($actor->id_utilisateur);
            if ($authActor) {
                Auth::guard('web')->setUser($authActor);
            }
        }

        return redirect('/espace')->with('success', 'Mot de passe mis à jour.');
    }
}
