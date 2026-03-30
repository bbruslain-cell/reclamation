<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AccessControlService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function __construct(private readonly AccessControlService $access)
    {
    }

    public function showForgot(): View
    {
        return view('auth.forgot');
    }

    public function submitForgot(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($payload['email']));
        $userExists = DB::table('utilisateurs')->where('email', $email)->exists();

        // Always respond success to avoid user enumeration
        if ($userExists) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            // In absence of SMTP, surface the one-time link to the admin/operator.
            $request->session()->flash(
                'reset_link',
                url('/mot-de-passe/reinitialiser?token='.urlencode($token).'&email='.urlencode($email))
            );
        }

        return redirect()->back()->with(
            'success',
            'Si le compte existe, un lien de reinitialisation est disponible ci-dessous.'
        );
    }

    public function showReset(Request $request): View|RedirectResponse
    {
        $email = (string) $request->query('email', '');
        $token = (string) $request->query('token', '');

        if (!$email || !$token) {
            return redirect('/mot-de-passe/oubli')->with('error', 'Lien de reinitialisation invalide.');
        }

        if (!$this->isValidToken($email, $token)) {
            return redirect('/mot-de-passe/oubli')->with('error', 'Lien expiré ou invalide.');
        }

        return view('auth.reset', ['email' => $email, 'token' => $token]);
    }

    public function submitReset(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!$this->isValidToken($payload['email'], $payload['token'])) {
            return redirect('/mot-de-passe/oubli')->with('error', 'Lien expiré ou invalide.');
        }

        $user = DB::table('utilisateurs')->where('email', $payload['email'])->first();
        if (!$user) {
            return redirect('/mot-de-passe/oubli')->with('error', 'Compte introuvable.');
        }

        DB::table('utilisateurs')
            ->where('id_utilisateur', $user->id_utilisateur)
            ->update([
                'password_hash' => Hash::make($payload['password']),
                'changement_mdp_requis' => false,
                'updated_at' => now(),
            ]);

        DB::table('password_reset_tokens')->where('email', $payload['email'])->delete();

        return redirect('/login')->with('success', 'Mot de passe mis à jour. Connectez-vous avec le nouveau mot de passe.');
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

        return redirect('/espace')->with('success', 'Mot de passe mis à jour.');
    }

    private function isValidToken(string $email, string $plainToken): bool
    {
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (!$record) {
            return false;
        }

        $createdAt = $record->created_at
            ? CarbonImmutable::parse($record->created_at)
            : CarbonImmutable::now()->subHours(3);

        if ($createdAt->lt(CarbonImmutable::now()->subHours(6))) {
            return false;
        }

        return Hash::check($plainToken, $record->token);
    }
}
