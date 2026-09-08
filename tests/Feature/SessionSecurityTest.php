<?php

namespace Tests\Feature;

use App\Models\Utilisateur;
use App\Services\SessionSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_uses_only_the_laravel_guard_and_tracks_the_session_version(): void
    {
        $this->seed();

        $actor = Utilisateur::query()->where('email', 'agent.daf@anbg.ga')->firstOrFail();

        $this->post('/login', [
            'email' => $actor->email,
            'password' => 'ChangeMe@123',
        ])->assertRedirect('/mot-de-passe/nouveau')
            ->assertSessionHas(SessionSecurityService::VERSION_KEY, (int) $actor->session_version)
            ->assertSessionMissing('agent_id');

        $this->assertAuthenticatedAs($actor);
    }

    public function test_legacy_agent_id_value_cannot_authenticate_a_request(): void
    {
        $this->seed();

        $actorId = (int) Utilisateur::query()
            ->where('email', 'agent.daf@anbg.ga')
            ->value('id_utilisateur');

        $this->withSession(['agent_id' => $actorId])
            ->get('/espace')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_an_inactive_account_is_logged_out_without_a_redirect_loop(): void
    {
        $this->seed();

        $actor = $this->login('agent.daf@anbg.ga');

        DB::table('utilisateurs')
            ->where('id_utilisateur', $actor->id_utilisateur)
            ->update(['actif' => false]);
        Auth::guard('web')->forgetUser();

        $this->get('/espace')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/login')->assertOk();
    }

    public function test_a_session_version_change_immediately_invalidates_an_existing_session(): void
    {
        $this->seed();

        $actor = $this->login('agent.daf@anbg.ga');

        DB::table('utilisateurs')
            ->where('id_utilisateur', $actor->id_utilisateur)
            ->increment('session_version');
        Auth::guard('web')->forgetUser();

        $this->get('/espace')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_password_change_revokes_other_database_sessions_and_keeps_the_current_one(): void
    {
        config(['session.driver' => 'database']);
        $this->seed();

        $actor = $this->login('agent.daf@anbg.ga');
        $previousVersion = (int) $actor->session_version;

        $this->insertSession('another-device', (int) $actor->id_utilisateur);

        $this->post('/mot-de-passe/nouveau', [
            'ancien_mdp' => 'ChangeMe@123',
            'password' => 'NouveauMotDePasse@124',
            'password_confirmation' => 'NouveauMotDePasse@124',
        ])->assertRedirect('/espace');

        $currentVersion = (int) DB::table('utilisateurs')
            ->where('id_utilisateur', $actor->id_utilisateur)
            ->value('session_version');

        $this->assertSame($previousVersion + 1, $currentVersion);
        $this->assertDatabaseMissing('sessions', ['id' => 'another-device']);
        $this->assertAuthenticated();
        $this->assertSame(
            $currentVersion,
            (int) session(SessionSecurityService::VERSION_KEY)
        );
    }

    public function test_full_revocation_removes_persisted_sessions_and_rotates_security_state(): void
    {
        config(['session.driver' => 'database']);
        $this->seed();

        $actor = Utilisateur::query()->where('email', 'agent.daf@anbg.ga')->firstOrFail();
        $previousVersion = (int) $actor->session_version;
        $previousRememberToken = $actor->remember_token;

        $this->insertSession('first-device', (int) $actor->id_utilisateur);
        $this->insertSession('second-device', (int) $actor->id_utilisateur);

        $nextVersion = app(SessionSecurityService::class)
            ->revokeForUser((int) $actor->id_utilisateur);

        $actor->refresh();

        $this->assertSame($previousVersion + 1, $nextVersion);
        $this->assertSame($nextVersion, (int) $actor->session_version);
        $this->assertNotSame($previousRememberToken, $actor->remember_token);
        $this->assertDatabaseMissing('sessions', ['user_id' => $actor->id_utilisateur]);
    }

    public function test_a_web_login_authenticates_the_internal_api_routes(): void
    {
        $this->seed();

        $this->login('agent.daf@anbg.ga');

        $this->post('/mot-de-passe/nouveau', [
            'ancien_mdp' => 'ChangeMe@123',
            'password' => 'NouveauMotDePasse@124',
            'password_confirmation' => 'NouveauMotDePasse@124',
        ])->assertRedirect('/espace');

        $this->getJson('/api/demandes')->assertOk();
    }

    private function login(string $email): Utilisateur
    {
        $actor = Utilisateur::query()->where('email', $email)->firstOrFail();

        $this->post('/login', [
            'email' => $email,
            'password' => 'ChangeMe@123',
        ])->assertRedirect('/mot-de-passe/nouveau');

        return $actor;
    }

    private function insertSession(string $id, int $userId): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'SessionSecurityTest',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);
    }
}
