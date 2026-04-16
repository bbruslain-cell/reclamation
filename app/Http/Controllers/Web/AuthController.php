<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AccessControlService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCK_MINUTES = 1;

    public function __construct(private readonly AccessControlService $access)
    {
    }

    public function showLogin(Request $request): View|RedirectResponse
    {
        $actor = $this->access->resolveActor($request);
        if ($actor) {
            return redirect('/espace');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $actor = $this->access->findActorByEmail($payload['email']);
        if (!$actor || !$actor->actif) {
            throw ValidationException::withMessages([
                'email' => 'Identifiants invalides.',
            ]);
        }

        if ($actor->bloque_jusqua && now()->lt($actor->bloque_jusqua)) {
            $seconds = max(1, (int) ceil(now()->diffInSeconds($actor->bloque_jusqua)));

            throw ValidationException::withMessages([
                'email' => "Compte temporairement bloque. Reessayez dans {$seconds} seconde(s).",
            ]);
        }

        if (!Hash::check($payload['password'], $actor->password_hash)) {
            $attempts = ((int) ($actor->tentatives_echouees ?? 0)) + 1;

            if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
                $actor->forceFill([
                    'tentatives_echouees' => 0,
                    'bloque_jusqua' => now()->addMinutes(self::LOCK_MINUTES),
                ])->save();

                throw ValidationException::withMessages([
                    'email' => 'Trop de tentatives. Compte bloque pendant 1 minute.',
                ]);
            }

            $actor->forceFill([
                'tentatives_echouees' => $attempts,
            ])->save();

            $remainingAttempts = self::MAX_LOGIN_ATTEMPTS - $attempts;

            throw ValidationException::withMessages([
                'email' => "Identifiants invalides. Il reste {$remainingAttempts} tentative(s).",
            ]);
        }

        $request->session()->regenerate();
        Auth::guard('web')->login($actor);
        $request->session()->put('agent_id', (int) $actor->id_utilisateur);

        $actor->forceFill([
            'derniere_connexion' => now(),
            'tentatives_echouees' => 0,
            'bloque_jusqua' => null,
        ])->save();

        if ((bool) ($actor->changement_mdp_requis ?? false)) {
            return redirect('/mot-de-passe/nouveau');
        }

        return redirect()->intended('/espace');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->forget('agent_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
   
}
