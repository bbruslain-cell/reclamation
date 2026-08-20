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

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;

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
        $email = trim((string) ($request->input('email') ?: $request->input('username')));
        if ($email !== '') {
            $request->merge(['email' => $email]);
        }

        $payload = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $actor = $this->access->findActorByEmail($payload['email']);
        if (!$actor || !$actor->actif) {
            $this->recordAuthAudit(
                userId: $actor?->id_utilisateur ? (int) $actor->id_utilisateur : null,
                actionType: 'AUTH_LOGIN_FAILED',
                comment: 'Echec de connexion pour '.$payload['email']
            );

            throw ValidationException::withMessages([
                'email' => 'Identifiants invalides.',
            ]);
        }

        if ($actor->bloque_jusqua && now()->lt($actor->bloque_jusqua)) {
            $seconds = max(1, (int) ceil(now()->diffInSeconds($actor->bloque_jusqua)));

            $this->recordAuthAudit(
                userId: (int) $actor->id_utilisateur,
                actionType: 'AUTH_LOGIN_BLOCKED',
                comment: "Tentative pendant verrouillage pour {$actor->email}"
            );

            throw ValidationException::withMessages([
                'email' => "Compte temporairement bloqué. Rééssayez dans {$seconds} seconde(s).",
            ]);
        }

        if (!Hash::check($payload['password'], $actor->password_hash)) {
            $attempts = ((int) ($actor->tentatives_echouees ?? 0)) + 1;

            $this->recordAuthAudit(
                userId: (int) $actor->id_utilisateur,
                actionType: 'AUTH_LOGIN_FAILED',
                comment: "Echec de connexion ({$attempts}/".self::MAX_LOGIN_ATTEMPTS.") pour {$actor->email}"
            );

            if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
                $actor->forceFill([
                    'tentatives_echouees' => 0,
                    'bloque_jusqua' => now()->addMinutes(self::LOCK_MINUTES),
                ])->save();

                $this->recordAuthAudit(
                    userId: (int) $actor->id_utilisateur,
                    actionType: 'AUTH_LOGIN_LOCKOUT',
                    comment: 'Compte verrouillé après trop de tentatives de connexion'
                );

                throw ValidationException::withMessages([
                    'email' => 'Trop de tentatives. Compte bloqué pendant 15 minutes.',
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

        $this->recordAuthAudit(
            userId: (int) $actor->id_utilisateur,
            actionType: 'AUTH_LOGIN_SUCCESS',
            comment: 'Connexion reussie'
        );

        if ((bool) ($actor->changement_mdp_requis ?? false)) {
            return redirect('/mot-de-passe/nouveau');
        }

        return redirect()->intended('/espace');
    }

    public function logout(Request $request): RedirectResponse
    {
        $actor = $this->access->resolveActor($request);
        if ($actor) {
            $this->recordAuthAudit(
                userId: (int) $actor->id_utilisateur,
                actionType: 'AUTH_LOGOUT',
                comment: 'Deconnexion utilisateur'
            );
        }

        Auth::guard('web')->logout();
        $request->session()->forget('agent_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    private function recordAuthAudit(?int $userId, string $actionType, string $comment): void
    {
        DB::table('historique_actions')->insert([
            'id_demande' => null,
            'id_utilisateur' => $userId,
            'type_action' => $actionType,
            'commentaire' => $comment,
            'date_action' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
