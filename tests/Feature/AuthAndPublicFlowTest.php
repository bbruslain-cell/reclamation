<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthAndPublicFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_locks_account_after_five_failed_attempts(): void
    {
        $this->seed();

        $email = 'accueil@anbg.ga';

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->post('/login', [
                'email' => $email,
                'password' => 'MauvaisMotDePasse',
            ])->assertSessionHasErrors('email');

            $row = DB::table('utilisateurs')->where('email', $email)->first();
            $this->assertSame($attempt, (int) $row->tentatives_echouees);
            $this->assertNull($row->bloque_jusqua);
        }

        $this->post('/login', [
            'email' => $email,
            'password' => 'MauvaisMotDePasse',
        ])->assertSessionHasErrors('email');

        $row = DB::table('utilisateurs')->where('email', $email)->first();
        $this->assertSame(0, (int) $row->tentatives_echouees);
        $this->assertNotNull($row->bloque_jusqua);
        $this->assertGreaterThanOrEqual(14 * 60, now()->diffInSeconds($row->bloque_jusqua, false));
        $this->assertDatabaseHas('historique_actions', [
            'id_utilisateur' => (int) DB::table('utilisateurs')->where('email', $email)->value('id_utilisateur'),
            'type_action' => 'AUTH_LOGIN_LOCKOUT',
        ]);

        $this->post('/login', [
            'email' => $email,
            'password' => 'ChangeMe@123',
        ])->assertSessionHasErrors('email');
    }

    public function test_internal_pages_require_login_and_accueil_can_login(): void
    {
        $this->seed();

        $this->get('/pilotage')->assertRedirect('/login');

        $this->post('/login', [
            'email' => 'accueil@anbg.ga',
            'password' => 'ChangeMe@123',
        ])->assertRedirect('/mot-de-passe/nouveau');

        $this->post('/mot-de-passe/nouveau', [
            'ancien_mdp' => 'ChangeMe@123',
            'password' => 'ChangeMe@124',
            'password_confirmation' => 'ChangeMe@124',
        ])->assertRedirect('/espace');

        $this->get('/espace')->assertRedirect('/accueil/inbox');
        $this->get('/accueil/inbox')
            ->assertOk()
            ->assertSee('Je souhaite connaitre la date du prochain paiement.');
        $this->get('/pilotage')->assertForbidden();
        $this->getJson('/api/overview')->assertUnauthorized();
    }

    public function test_public_submission_creates_demand_and_attachment(): void
    {
        $this->seed();
        Storage::fake('local');
        Storage::fake('public');

        $response = $this->post('/reclamations', [
            'nom' => 'Doe',
            'prenom' => 'Jane',
            'email' => 'jane@example.com',
            'statut_usager' => 'Étudiant',
            'pays' => 'Gabon',
            'etablissement' => 'Université Omar Bongo',
            'qualite' => 'Etudiante',
            'objet' => 'Objet test',
            'message' => 'Message de test suffisamment long.',
            'consentement' => 'on',
            'piece_jointe' => UploadedFile::fake()->create('piece.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect('/reclamations/nouvelle');
        $this->assertDatabaseCount('demandes', 6);
        $this->assertDatabaseCount('pieces_jointes', 1);
        Storage::disk('local')->assertExists((string) DB::table('pieces_jointes')->value('chemin_fichier'));
        Storage::disk('public')->assertMissing((string) DB::table('pieces_jointes')->value('chemin_fichier'));
    }

    public function test_public_form_has_security_headers_and_uses_local_vue_bundle(): void
    {
        $this->seed();

        $response = $this->get('/reclamations/nouvelle');

        $response
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertSee('id="public-demand-state"', false)
            ->assertDontSee('https://unpkg.com/vue', false);

        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
    }

    public function test_public_submission_rejects_tampered_status_value(): void
    {
        $this->seed();

        $this->from('/reclamations/nouvelle')->post('/reclamations', [
            'nom' => 'Zap',
            'prenom' => 'Scanner',
            'email' => 'zap@example.com',
            'statut_usager' => 'Élève AND 1=1 --',
            'pays' => 'Gabon',
            'etablissement' => '',
            'objet' => 'Objet test',
            'message' => 'Message de test suffisamment long.',
            'consentement' => 'on',
        ])
            ->assertRedirect('/reclamations/nouvelle')
            ->assertSessionHasErrors('statut_usager');

        $this->assertDatabaseMissing('usagers', [
            'email' => 'zap@example.com',
        ]);
    }

    public function test_public_submission_with_existing_email_creates_a_new_usager_snapshot(): void
    {
        $this->seed();

        $existingEmail = 'jane@example.com';
        DB::table('usagers')->insert([
            'nom' => 'Premier',
            'prenom' => 'Profil',
            'email' => $existingEmail,
            'qualite' => 'Parent',
            'consentement_rgpd' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $before = DB::table('usagers')->where('email', $existingEmail)->count();

        $this->post('/reclamations', [
            'nom' => 'Deuxieme',
            'prenom' => 'Profil',
            'email' => $existingEmail,
            'statut_usager' => 'Étudiant',
            'pays' => 'Gabon',
            'etablissement' => 'Université Omar Bongo',
            'qualite' => 'Etudiant',
            'objet' => 'Nouvelle demande',
            'message' => 'Message de test suffisamment long pour creer une demande.',
            'consentement' => 'on',
        ])->assertRedirect('/reclamations/nouvelle');

        $after = DB::table('usagers')->where('email', $existingEmail)->count();

        $this->assertSame($before + 1, $after);
        $this->assertDatabaseHas('usagers', [
            'email' => $existingEmail,
            'nom' => 'Premier',
            'prenom' => 'Profil',
        ]);
        $this->assertDatabaseHas('usagers', [
            'email' => $existingEmail,
            'nom' => 'Deuxieme',
            'prenom' => 'Profil',
            'statut_usager' => 'Étudiant',
            'pays' => 'Gabon',
        ]);
    }

    public function test_authenticated_user_can_open_uploaded_attachment_via_secure_route(): void
    {
        $this->seed();
        Storage::fake('local');
        Storage::fake('public');

        $this->post('/reclamations', [
            'nom' => 'Doe',
            'prenom' => 'Jane',
            'email' => 'jane@example.com',
            'statut_usager' => 'Étudiant',
            'pays' => 'Gabon',
            'etablissement' => 'Université Omar Bongo',
            'qualite' => 'Etudiante',
            'objet' => 'Objet test',
            'message' => 'Message de test suffisamment long.',
            'consentement' => 'on',
            'piece_jointe' => UploadedFile::fake()->create('piece.pdf', 100, 'application/pdf'),
        ])->assertRedirect('/reclamations/nouvelle');

        $pieceId = (int) DB::table('pieces_jointes')->value('id_piece_jointe');

        $this->post('/login', [
            'email' => 'accueil@anbg.ga',
            'password' => 'ChangeMe@123',
        ])->assertRedirect('/mot-de-passe/nouveau');

        $this->post('/mot-de-passe/nouveau', [
            'ancien_mdp' => 'ChangeMe@123',
            'password' => 'ChangeMe@124',
            'password_confirmation' => 'ChangeMe@124',
        ])->assertRedirect('/espace');

        $response = $this->get("/pieces-jointes/{$pieceId}");
        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('x-content-type-options', 'nosniff');

        $this->assertStringContainsString(
            'attachment;',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_unrelated_agent_cannot_open_attachment_outside_scope(): void
    {
        $this->seed();
        Storage::fake('local');
        Storage::fake('public');

        $this->post('/reclamations', [
            'nom' => 'Doe',
            'prenom' => 'Jane',
            'email' => 'jane@example.com',
            'statut_usager' => 'Etudiant',
            'pays' => 'Gabon',
            'etablissement' => 'Universite Omar Bongo',
            'objet' => 'Objet test',
            'message' => 'Message de test suffisamment long.',
            'consentement' => 'on',
            'piece_jointe' => UploadedFile::fake()->create('piece.pdf', 100, 'application/pdf'),
        ])->assertRedirect('/reclamations/nouvelle');

        $pieceId = (int) DB::table('pieces_jointes')->value('id_piece_jointe');

        $this->post('/login', [
            'email' => 'agent.daf@anbg.ga',
            'password' => 'ChangeMe@123',
        ])->assertRedirect('/mot-de-passe/nouveau');

        $this->post('/mot-de-passe/nouveau', [
            'ancien_mdp' => 'ChangeMe@123',
            'password' => 'ChangeMe@124',
            'password_confirmation' => 'ChangeMe@124',
        ])->assertRedirect('/espace');

        $this->get("/pieces-jointes/{$pieceId}")
            ->assertForbidden();
    }

    public function test_agent_login_redirects_to_agent_inbox(): void
    {
        $this->seed();

        $agentUserId = (int) DB::table('utilisateurs')
            ->where('email', 'agent.daf@anbg.ga')
            ->value('id_utilisateur');

        $this->post('/login', [
            'email' => 'agent.daf@anbg.ga',
            'password' => 'ChangeMe@123',
        ])->assertRedirect('/mot-de-passe/nouveau');

        $this->post('/mot-de-passe/nouveau', [
            'ancien_mdp' => 'ChangeMe@123',
            'password' => 'ChangeMe@124',
            'password_confirmation' => 'ChangeMe@124',
        ])->assertRedirect('/espace');

        $this->get('/espace')->assertRedirect('/agent/inbox');
        $this->get('/agent/inbox')
            ->assertOk()
            ->assertSee('Message complet')
            ->assertSee('Temps restant 16h')
            ->assertSee('Mes frais de scolarite ne sont pas encore regles.')
            ->assertDontSee('Cloturee')
            ->assertDontSee('Reponse transmise a l usager')
            ->assertDontSee('Recu');
        $this->get('/pilotage')->assertForbidden();
        $this->withHeader('X-User-Id', (string) $agentUserId)
            ->getJson('/api/overview')
            ->assertForbidden();
    }

    public function test_other_agent_account_has_same_restrictions(): void
    {
        $this->seed();

        $agentDafId = (int) DB::table('utilisateurs')
            ->where('email', 'agent.daf@anbg.ga')
            ->value('id_utilisateur');

        $this->post('/login', [
            'email' => 'agent.daf@anbg.ga',
            'password' => 'ChangeMe@123',
        ])->assertRedirect('/mot-de-passe/nouveau');

        $this->post('/mot-de-passe/nouveau', [
            'ancien_mdp' => 'ChangeMe@123',
            'password' => 'ChangeMe@124',
            'password_confirmation' => 'ChangeMe@124',
        ])->assertRedirect('/espace');

        $this->get('/espace')->assertRedirect('/agent/inbox');
        $this->get('/agent/inbox')->assertOk();
        $this->get('/pilotage')->assertForbidden();
        $this->withHeader('X-User-Id', (string) $agentDafId)
            ->getJson('/api/overview')
            ->assertForbidden();
    }

    public function test_chef_direction_role_redirects_to_chef_direction_inbox(): void
    {
        $this->seed();

        $userId = (int) DB::table('utilisateurs')
            ->where('email', 'chef.direction.ds@anbg.ga')
            ->value('id_utilisateur');

        $this->get("/espace?as_user_id={$userId}")->assertRedirect('/chef-direction/inbox');

        $this->withHeader('X-User-Id', (string) $userId)
            ->get('/direction/inbox')
            ->assertRedirect('/chef-direction/inbox');

        $this->withHeader('X-User-Id', (string) $userId)
            ->get('/chef-direction/inbox')
            ->assertOk()
            ->assertSee('Chef de direction')
            ->assertSee('Consultation uniquement')
            ->assertSee('Performance des services')
            ->assertSee('Comparatif des services');

        $this->withHeader('X-User-Id', (string) $userId)
            ->get('/agent/inbox')
            ->assertForbidden();
    }

    public function test_chef_direction_account_can_access_pilotage(): void
    {
        $this->seed();

        $this->post('/login', [
            'email' => 'chef.direction.daf@anbg.ga',
            'password' => 'ChangeMe@123',
        ])->assertRedirect('/mot-de-passe/nouveau');

        $this->post('/mot-de-passe/nouveau', [
            'ancien_mdp' => 'ChangeMe@123',
            'password' => 'ChangeMe@124',
            'password_confirmation' => 'ChangeMe@124',
        ])->assertRedirect('/espace');

        $this->get('/chef-direction/inbox')->assertOk();
        $this->get('/pilotage')->assertOk();
    }

    public function test_lecture_seule_redirects_to_pilotage_without_actions(): void
    {
        $this->seed();

        $this->post('/login', [
            'email' => 'lecture@anbg.ga',
            'password' => 'ChangeMe@123',
        ])->assertRedirect('/mot-de-passe/nouveau');

        $this->post('/mot-de-passe/nouveau', [
            'ancien_mdp' => 'ChangeMe@123',
            'password' => 'ChangeMe@124',
            'password_confirmation' => 'ChangeMe@124',
        ])->assertRedirect('/espace');

        $this->get('/espace')->assertRedirect('/pilotage');
        $this->get('/pilotage')
            ->assertOk()
            ->assertSee('Consultation en lecture seule')
            ->assertSee('Tableau actuel du suivi des réclamations')
            ->assertSee('tableau de répartition des réclamations par service');
        $this->get('/agent/inbox')->assertForbidden();
        $this->get('/pilotage')
            ->assertOk()
            ->assertDontSee('Telecharger PNG')
            ->assertDontSee('id="ciq-tracking-export-xls"', false)
            ->assertDontSee('id="ciq-tracking-export-pdf"', false);
        $this->get('/pilotage/export/ciq-tracking/xlsx')->assertForbidden();
        $this->get('/pilotage/export/ciq-tracking/pdf')->assertForbidden();
    }

    public function test_ciq_agent_can_cumulate_global_supervision_and_agent_work(): void
    {
        $this->seed();

        $userId = (int) DB::table('utilisateurs')
            ->where('email', 'ciq@anbg.ga')
            ->value('id_utilisateur');
        $agentRoleId = (int) DB::table('roles')->where('code', 'agent')->value('id_role');
        $ciqRoleId = (int) DB::table('roles')->where('code', 'ciq')->value('id_role');

        $this->post('/login', [
            'email' => 'ciq@anbg.ga',
            'password' => 'ChangeMe@123',
        ])->assertRedirect('/mot-de-passe/nouveau');

        $this->post('/mot-de-passe/nouveau', [
            'ancien_mdp' => 'ChangeMe@123',
            'password' => 'ChangeMe@124',
            'password_confirmation' => 'ChangeMe@124',
        ])->assertRedirect('/espace');

        $this->get('/espace')
            ->assertOk()
            ->assertSee('Choisissez votre espace')
            ->assertSee('Agent')
            ->assertSee('Pilotage');
        $this->get('/agent/inbox')->assertOk();
        $this->get('/pilotage')
            ->assertOk()
            ->assertSee('Registre de controle interne')
            ->assertSee('ANBG-2026-0004')
            ->assertSee('Diagramme en batons par direction')
            ->assertSee('Diagramme PNG')
            ->assertSee('Camembert PNG')
            ->assertSee('chart.umd.min.js')
            ->assertSee('Annexe 3 - tableau de repartition de l ensemble des reclamations de la cellule par direction')
            ->assertSee('tableau de repartition des reclamations par direction et par service')
            ->assertSee('tableau de repartition des reclamations par service')
            ->assertSee('Total reclamations');

        $this->assertDatabaseHas('utilisateur_role', [
            'id_utilisateur' => $userId,
            'id_role' => $agentRoleId,
        ]);
        $this->assertDatabaseHas('utilisateur_role', [
            'id_utilisateur' => $userId,
            'id_role' => $ciqRoleId,
        ]);
        $this->assertDatabaseHas('model_has_roles', [
            'model_type' => \App\Models\Utilisateur::class,
            'model_id' => $userId,
            'id_role' => $agentRoleId,
        ]);
        $this->assertDatabaseHas('model_has_roles', [
            'model_type' => \App\Models\Utilisateur::class,
            'model_id' => $userId,
            'id_role' => $ciqRoleId,
        ]);
    }

    public function test_multi_role_chef_service_can_choose_between_chef_and_pilotage(): void
    {
        $this->seed();

        $this->post('/login', [
            'email' => 'chef.sciq@anbg.ga',
            'password' => 'ChangeMe@123',
        ])->assertRedirect('/mot-de-passe/nouveau');

        $this->post('/mot-de-passe/nouveau', [
            'ancien_mdp' => 'ChangeMe@123',
            'password' => 'ChangeMe@124',
            'password_confirmation' => 'ChangeMe@124',
        ])->assertRedirect('/espace');

        $this->get('/espace')
            ->assertOk()
            ->assertSee('Choisissez votre espace')
            ->assertSee('Chef de service')
            ->assertSee('Pilotage');

        $this->get('/chef/inbox')
            ->assertOk()
            ->assertSee('Temps restant 16h');
        $this->get('/pilotage')->assertOk();
    }

    public function test_agent_can_respond_with_attachment_and_close(): void
    {
        $this->seed();
        config(['queue.default' => 'sync']);
        Mail::fake();
        Storage::fake('local');
        Storage::fake('public');

        $this->post('/login', [
            'email' => 'agent.daf@anbg.ga',
            'password' => 'ChangeMe@123',
        ])->assertRedirect('/mot-de-passe/nouveau');

        $this->post('/mot-de-passe/nouveau', [
            'ancien_mdp' => 'ChangeMe@123',
            'password' => 'ChangeMe@124',
            'password_confirmation' => 'ChangeMe@124',
        ])->assertRedirect('/espace');

        $demandId = (int) DB::table('demandes')
            ->where('numero_suivi', 'ANBG-2026-0003')
            ->value('id_demande');

        $actorId = (int) DB::table('utilisateurs')
            ->where('email', 'agent.daf@anbg.ga')
            ->value('id_utilisateur');

        $response = $this->post("/agent/demandes/{$demandId}/envoyer", [
            '_method' => 'PUT',
            'contenu_reponse' => 'Reponse detaillee de la direction DS.',
            'pieces_jointes' => [
                UploadedFile::fake()->create('avis-direction.pdf', 80, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect();

        $statusCloturee = (int) DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->where('code', 'cloturee')
            ->value('id_parametre');
        $typeFinale = (int) DB::table('parametres')
            ->where('famille', 'type_reponse')
            ->where('code', 'finale')
            ->value('id_parametre');

        $this->assertDatabaseHas('demandes', [
            'id_demande' => $demandId,
            'id_statut' => $statusCloturee,
        ]);
        $this->assertDatabaseHas('reponses', [
            'id_demande' => $demandId,
            'id_type_reponse' => $typeFinale,
            'id_redacteur' => $actorId,
        ]);

        $responseId = (int) DB::table('reponses')
            ->where('id_demande', $demandId)
            ->max('id_reponse');

        $this->assertDatabaseHas('reponse_piece_jointe', [
            'id_reponse' => $responseId,
        ]);
    }

    public function test_accueil_can_send_direct_response_for_reclamation_and_close_it(): void
    {
        $this->seed();
        config(['queue.default' => 'sync']);
        Mail::fake();
        Storage::fake('local');

        $this->post('/reclamations', [
            'nom' => 'Mouila',
            'prenom' => 'Sandra',
            'email' => 'sandra.mouila@example.com',
            'statut_usager' => 'Parent / Tuteur',
            'pays' => 'Gabon',
            'etablissement' => '',
            'categorie' => 'Paiement bourse',
            'objet' => 'Réclamation directe accueil',
            'message' => 'Je souhaite un retour immédiat sur le non versement observé.',
            'consentement' => 'on',
        ])->assertRedirect('/reclamations/nouvelle');

        $demandId = (int) DB::table('demandes')
            ->where('objet', 'Réclamation directe accueil')
            ->value('id_demande');
        $accueilId = (int) DB::table('utilisateurs')
            ->where('email', 'accueil@anbg.ga')
            ->value('id_utilisateur');
        $ucasServiceId = (int) DB::table('services')
            ->where('code', 'UCAS')
            ->value('id_service');
        $statusCloturee = (int) DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->where('code', 'cloturee')
            ->value('id_parametre');
        $typeDirecte = (int) DB::table('parametres')
            ->where('famille', 'type_reponse')
            ->where('code', 'directe')
            ->value('id_parametre');

        $this->post('/login', [
            'email' => 'accueil@anbg.ga',
            'password' => 'ChangeMe@123',
        ])->assertRedirect('/mot-de-passe/nouveau');

        $this->post('/mot-de-passe/nouveau', [
            'ancien_mdp' => 'ChangeMe@123',
            'password' => 'ChangeMe@124',
            'password_confirmation' => 'ChangeMe@124',
        ])->assertRedirect('/espace');

        $this->post("/accueil/demandes/{$demandId}/reponse-directe", [
            '_method' => 'PUT',
            'contenu_reponse' => "Votre réclamation a été vérifiée et traitée immédiatement par l'accueil.",
            'pieces_jointes' => [
                UploadedFile::fake()->create('reponse-accueil.pdf', 80, 'application/pdf'),
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('demandes', [
            'id_demande' => $demandId,
            'id_statut' => $statusCloturee,
            'id_agent_accueil' => $accueilId,
            'id_service_courant' => $ucasServiceId,
        ]);
        $this->assertDatabaseHas('reponses', [
            'id_demande' => $demandId,
            'id_type_reponse' => $typeDirecte,
            'id_redacteur' => $accueilId,
            'id_envoyeur' => $accueilId,
        ]);
        $this->assertDatabaseHas('historique_actions', [
            'id_demande' => $demandId,
            'type_action' => 'reponse_directe_accueil',
            'id_utilisateur' => $accueilId,
        ]);
    }

    public function test_agent_inbox_shows_user_attachment_after_chef_assignment(): void
    {
        $this->seed();
        Storage::fake('local');
        Storage::fake('public');

        $this->post('/reclamations', [
            'nom' => 'Doe',
            'prenom' => 'Jane',
            'email' => 'jane.agent-piece@example.com',
            'statut_usager' => 'Étudiant',
            'pays' => 'Gabon',
            'etablissement' => 'Université Omar Bongo',
            'qualite' => 'Etudiante',
            'objet' => 'Verification piece jointe agent',
            'message' => 'Demande avec piece jointe qui doit rester visible apres affectation a un agent.',
            'consentement' => 'on',
            'piece_jointe' => UploadedFile::fake()->create('piece-usager.pdf', 100, 'application/pdf'),
        ])->assertRedirect('/reclamations/nouvelle');

        $demandId = (int) DB::table('demandes')
            ->where('objet', 'Verification piece jointe agent')
            ->value('id_demande');

        $serviceId = (int) DB::table('services')
            ->where('code', 'CS_FC')
            ->value('id_service');
        $directionId = (int) DB::table('services')
            ->where('id_service', $serviceId)
            ->value('id_direction');

        $this->post('/login', [
            'email' => 'accueil@anbg.ga',
            'password' => 'ChangeMe@123',
        ])->assertRedirect('/mot-de-passe/nouveau');

        $this->post('/mot-de-passe/nouveau', [
            'ancien_mdp' => 'ChangeMe@123',
            'password' => 'ChangeMe@124',
            'password_confirmation' => 'ChangeMe@124',
        ])->assertRedirect('/espace');

        $this->post("/accueil/demandes/{$demandId}/affecter", [
            '_method' => 'PUT',
            'id_direction' => $directionId,
            'id_service' => $serviceId,
        ])->assertRedirect();

        Auth::guard('web')->logout();

        $agentId = (int) DB::table('utilisateurs')
            ->where('email', 'agent.daf@anbg.ga')
            ->value('id_utilisateur');

        $this->post('/login', [
            'email' => 'chef.daf@anbg.ga',
            'password' => 'ChangeMe@123',
        ])->assertRedirect('/mot-de-passe/nouveau');

        $this->post('/mot-de-passe/nouveau', [
            'ancien_mdp' => 'ChangeMe@123',
            'password' => 'ChangeMe@124',
            'password_confirmation' => 'ChangeMe@124',
        ])->assertRedirect('/espace');

        $this->post("/chef/demandes/{$demandId}/affecter-agent", [
            '_method' => 'PUT',
            'id_agent' => $agentId,
        ])->assertRedirect();

        Auth::guard('web')->logout();

        $this->post('/login', [
            'email' => 'agent.daf@anbg.ga',
            'password' => 'ChangeMe@123',
        ])->assertRedirect('/mot-de-passe/nouveau');

        $this->post('/mot-de-passe/nouveau', [
            'ancien_mdp' => 'ChangeMe@123',
            'password' => 'ChangeMe@124',
            'password_confirmation' => 'ChangeMe@124',
        ])->assertRedirect('/espace');

        $this->get('/agent/inbox')
            ->assertOk()
            ->assertSee('Verification piece jointe agent')
            ->assertSee('piece-usager.pdf');
    }

    public function test_chef_can_reply_directly_and_close(): void
    {
        $this->seed();
        config(['queue.default' => 'sync']);
        Mail::fake();
        Storage::fake('local');
        Storage::fake('public');

        $this->post('/login', [
            'email' => 'chef.ds@anbg.ga',
            'password' => 'ChangeMe@123',
        ])->assertRedirect('/mot-de-passe/nouveau');

        $this->post('/mot-de-passe/nouveau', [
            'ancien_mdp' => 'ChangeMe@123',
            'password' => 'ChangeMe@124',
            'password_confirmation' => 'ChangeMe@124',
        ])->assertRedirect('/espace');

        $demandId = (int) DB::table('demandes')
            ->where('numero_suivi', 'ANBG-2026-0002')
            ->value('id_demande');

        $actorId = (int) DB::table('utilisateurs')
            ->where('email', 'chef.ds@anbg.ga')
            ->value('id_utilisateur');

        $response = $this->post("/chef/demandes/{$demandId}/reponse-directe", [
            '_method' => 'PUT',
            'contenu_reponse' => 'Le chef de service prend la demande en charge directement.',
            'pieces_jointes' => [
                UploadedFile::fake()->create('reponse-chef.pdf', 64, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect();

        $statusCloturee = (int) DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->where('code', 'cloturee')
            ->value('id_parametre');
        $typeFinale = (int) DB::table('parametres')
            ->where('famille', 'type_reponse')
            ->where('code', 'finale')
            ->value('id_parametre');

        $this->assertDatabaseHas('demandes', [
            'id_demande' => $demandId,
            'id_statut' => $statusCloturee,
        ]);

        $this->assertDatabaseHas('reponses', [
            'id_demande' => $demandId,
            'id_type_reponse' => $typeFinale,
            'id_redacteur' => $actorId,
            'id_envoyeur' => $actorId,
        ]);
    }

    public function test_chef_cannot_reply_directly_after_assigning_an_agent(): void
    {
        $this->seed();
        Storage::fake('local');
        Storage::fake('public');

        $this->post('/login', [
            'email' => 'chef.ds@anbg.ga',
            'password' => 'ChangeMe@123',
        ])->assertRedirect('/mot-de-passe/nouveau');

        $this->post('/mot-de-passe/nouveau', [
            'ancien_mdp' => 'ChangeMe@123',
            'password' => 'ChangeMe@124',
            'password_confirmation' => 'ChangeMe@124',
        ])->assertRedirect('/espace');

        $demandId = (int) DB::table('demandes')
            ->where('numero_suivi', 'ANBG-2026-0002')
            ->value('id_demande');

        $agentId = (int) DB::table('utilisateurs')
            ->where('email', 'agent.ds@anbg.ga')
            ->value('id_utilisateur');

        $this->post("/chef/demandes/{$demandId}/affecter-agent", [
            '_method' => 'PUT',
            'id_agent' => $agentId,
        ])->assertRedirect();

        $response = $this->post("/chef/demandes/{$demandId}/reponse-directe", [
            '_method' => 'PUT',
            'contenu_reponse' => 'Tentative de reponse directe apres affectation agent.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $statusAffecteeAgent = (int) DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->where('code', 'affectee_agent')
            ->value('id_parametre');

        $this->assertDatabaseHas('demandes', [
            'id_demande' => $demandId,
            'id_statut' => $statusAffecteeAgent,
            'id_agent_traitant' => $agentId,
        ]);
    }

    public function test_accueil_cannot_assign_service_outside_selected_direction(): void
    {
        $this->seed();

        $this->post('/login', [
            'email' => 'accueil@anbg.ga',
            'password' => 'ChangeMe@123',
        ])->assertRedirect('/mot-de-passe/nouveau');

        $this->post('/mot-de-passe/nouveau', [
            'ancien_mdp' => 'ChangeMe@123',
            'password' => 'ChangeMe@124',
            'password_confirmation' => 'ChangeMe@124',
        ])->assertRedirect('/espace');

        $this->get('/accueil/inbox')->assertOk();

        $demandId = (int) DB::table('demandes')
            ->where('numero_suivi', 'ANBG-2026-0001')
            ->value('id_demande');

        $dsDirection = (int) DB::table('directions')->where('code', 'DS')->value('id_direction');
        $dafService = (int) DB::table('services')->where('code', 'CS_FC')->value('id_service');

        $response = $this->post("/accueil/demandes/{$demandId}/affecter", [
            '_method' => 'PUT',
            'id_direction' => $dsDirection,
            'id_service' => $dafService,
            'commentaire' => 'Test mismatch direction/service',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('affectations', [
            'id_demande' => $demandId,
            'id_service' => $dafService,
        ]);
    }

    public function test_admin_can_create_chef_direction_with_selected_direction(): void
    {
        $this->seed();

        $adminId = (int) DB::table('utilisateurs')
            ->where('email', 'admin@anbg.ga')
            ->value('id_utilisateur');
        DB::table('utilisateurs')
            ->where('id_utilisateur', $adminId)
            ->update(['changement_mdp_requis' => false]);

        $roleId = (int) DB::table('roles')
            ->where('code', 'chef_direction')
            ->value('id_role');
        $directionId = (int) DB::table('directions')
            ->where('code', 'DS')
            ->value('id_direction');

        $response = $this->withHeader('X-User-Id', (string) $adminId)
            ->post('/admin/utilisateurs', [
                'nom' => 'Nkoghe',
                'prenom' => 'Clarisse',
                'email' => 'clarisse.chef-direction@anbg.ga',
                'id_role' => $roleId,
                'id_direction' => $directionId,
            ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $response->assertSessionMissing('error');

        $userId = (int) DB::table('utilisateurs')
            ->where('email', 'clarisse.chef-direction@anbg.ga')
            ->value('id_utilisateur');

        $this->assertDatabaseHas('utilisateur_role', [
            'id_utilisateur' => $userId,
            'id_role' => $roleId,
        ]);
        $this->assertDatabaseHas('perimetre_direction', [
            'id_utilisateur' => $userId,
            'id_direction' => $directionId,
        ]);
        $this->assertDatabaseMissing('perimetre_service', [
            'id_utilisateur' => $userId,
        ]);
    }

    public function test_admin_space_resolves_from_legacy_role_even_when_spatie_pivot_is_missing(): void
    {
        $this->seed();

        $adminId = (int) DB::table('utilisateurs')
            ->where('email', 'admin@anbg.ga')
            ->value('id_utilisateur');

        DB::table('utilisateurs')
            ->where('id_utilisateur', $adminId)
            ->update(['changement_mdp_requis' => false]);

        DB::table('model_has_roles')
            ->where('model_type', \App\Models\Utilisateur::class)
            ->where('model_id', $adminId)
            ->delete();

        $this->withHeader('X-User-Id', (string) $adminId)
            ->get('/espace')
            ->assertRedirect('/admin');
    }

    public function test_authenticated_user_without_space_gets_forbidden_instead_of_login_loop(): void
    {
        $this->seed();

        $userId = (int) DB::table('utilisateurs')->insertGetId([
            'nom' => 'Sans',
            'prenom' => 'Role',
            'email' => 'sans.role@example.com',
            'password_hash' => bcrypt('Password@123'),
            'actif' => true,
            'changement_mdp_requis' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_utilisateur');

        $this->withHeader('X-User-Id', (string) $userId)
            ->get('/espace')
            ->assertForbidden();
    }

    public function test_admin_forces_ucas_service_for_accueil_role(): void
    {
        $this->seed();

        $adminId = (int) DB::table('utilisateurs')
            ->where('email', 'admin@anbg.ga')
            ->value('id_utilisateur');
        $accueilRoleId = (int) DB::table('roles')
            ->where('code', 'accueil')
            ->value('id_role');
        $wrongServiceId = (int) DB::table('services')
            ->where('code', 'CS_FC')
            ->value('id_service');
        $ucasServiceId = (int) DB::table('services')
            ->where('code', 'UCAS')
            ->value('id_service');

        $response = $this->withHeader('X-User-Id', (string) $adminId)
            ->post('/admin/utilisateurs', [
                'nom' => 'Alice',
                'prenom' => 'Accueil',
                'email' => 'alice.accueil@anbg.ga',
                'id_service' => $wrongServiceId,
                'id_roles' => [$accueilRoleId],
            ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('utilisateurs', [
            'email' => 'alice.accueil@anbg.ga',
            'id_service' => $ucasServiceId,
        ]);
    }

    public function test_admin_can_create_user_with_multiple_roles(): void
    {
        $this->seed();

        $adminId = (int) DB::table('utilisateurs')
            ->where('email', 'admin@anbg.ga')
            ->value('id_utilisateur');
        $agentRoleId = (int) DB::table('roles')->where('code', 'agent')->value('id_role');
        $ciqRoleId = (int) DB::table('roles')->where('code', 'ciq')->value('id_role');
        $serviceId = (int) DB::table('services')->where('code', 'SCIQ')->value('id_service');

        $response = $this->withHeader('X-User-Id', (string) $adminId)
            ->post('/admin/utilisateurs', [
                'nom' => 'Ella',
                'prenom' => 'Nguema',
                'email' => 'ella.sciq@anbg.ga',
                'id_service' => $serviceId,
                'id_roles' => [$agentRoleId, $ciqRoleId],
            ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $userId = (int) DB::table('utilisateurs')
            ->where('email', 'ella.sciq@anbg.ga')
            ->value('id_utilisateur');

        $this->assertDatabaseHas('utilisateur_role', [
            'id_utilisateur' => $userId,
            'id_role' => $agentRoleId,
        ]);
        $this->assertDatabaseHas('utilisateur_role', [
            'id_utilisateur' => $userId,
            'id_role' => $ciqRoleId,
        ]);
        $this->assertDatabaseHas('model_has_roles', [
            'model_type' => \App\Models\Utilisateur::class,
            'model_id' => $userId,
            'id_role' => $agentRoleId,
        ]);
        $this->assertDatabaseHas('model_has_roles', [
            'model_type' => \App\Models\Utilisateur::class,
            'model_id' => $userId,
            'id_role' => $ciqRoleId,
        ]);
    }

    public function test_admin_can_update_user_by_stable_identifier_without_changing_it(): void
    {
        $this->seed();

        $adminId = (int) DB::table('utilisateurs')
            ->where('email', 'admin@anbg.ga')
            ->value('id_utilisateur');
        $agentRoleId = (int) DB::table('roles')->where('code', 'agent')->value('id_role');
        $serviceId = (int) DB::table('services')->where('code', 'CS_FC')->value('id_service');

        $createResponse = $this->withHeader('X-User-Id', (string) $adminId)
            ->post('/admin/utilisateurs', [
                'nom' => 'Testeur',
                'prenom' => 'Initial',
                'email' => 'testeur.initial@anbg.ga',
                'id_service' => $serviceId,
                'id_roles' => [$agentRoleId],
            ]);

        $createResponse->assertRedirect();
        $createResponse->assertSessionDoesntHaveErrors();

        $userId = (int) DB::table('utilisateurs')
            ->where('email', 'testeur.initial@anbg.ga')
            ->value('id_utilisateur');

        $updateResponse = $this->withHeader('X-User-Id', (string) $adminId)
            ->post('/admin/utilisateurs', [
                'user_id' => $userId,
                'nom' => 'Testeur',
                'prenom' => 'Modifie',
                'email' => 'testeur.modifie@anbg.ga',
                'id_service' => $serviceId,
                'id_roles' => [$agentRoleId],
            ]);

        $updateResponse->assertRedirect();
        $updateResponse->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('utilisateurs', [
            'id_utilisateur' => $userId,
            'prenom' => 'Modifie',
            'email' => 'testeur.modifie@anbg.ga',
        ]);
        $this->assertDatabaseMissing('utilisateurs', [
            'email' => 'testeur.initial@anbg.ga',
        ]);
    }

    public function test_admin_cannot_create_chef_direction_without_direction(): void
    {
        $this->seed();

        $adminId = (int) DB::table('utilisateurs')
            ->where('email', 'admin@anbg.ga')
            ->value('id_utilisateur');
        $roleId = (int) DB::table('roles')
            ->where('code', 'chef_direction')
            ->value('id_role');

        $response = $this->from('/admin')
            ->withHeader('X-User-Id', (string) $adminId)
            ->post('/admin/utilisateurs', [
                'nom' => 'Nkoghe',
                'prenom' => 'Clarisse',
                'email' => 'clarisse.sans-direction@anbg.ga',
                'id_role' => $roleId,
            ]);

        $response->assertRedirect('/admin');
        $response->assertSessionHasErrors('id_direction');

        $this->assertDatabaseMissing('utilisateurs', [
            'email' => 'clarisse.sans-direction@anbg.ga',
        ]);
    }

    public function test_admin_can_sync_role_permissions_without_server_error(): void
    {
        $this->seed();

        $adminId = (int) DB::table('utilisateurs')
            ->where('email', 'admin@anbg.ga')
            ->value('id_utilisateur');
        DB::table('utilisateurs')
            ->where('id_utilisateur', $adminId)
            ->update(['changement_mdp_requis' => false]);
        $roleId = (int) DB::table('roles')
            ->where('code', 'dg')
            ->value('id_role');
        $exportPermissionId = (int) DB::table('permissions')
            ->where('name', 'dashboard.export')
            ->value('id_permission');
        $viewPermissionId = (int) DB::table('permissions')
            ->where('name', 'dashboard.view')
            ->value('id_permission');

        $response = $this->withHeader('X-User-Id', (string) $adminId)
            ->post("/admin/roles/{$roleId}/permissions", [
                'permissions' => ['dashboard.export'],
            ]);

        $response->assertRedirect('/admin/roles');
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('permission_role', [
            'id_role' => $roleId,
            'id_permission' => $exportPermissionId,
        ]);
        $this->assertDatabaseMissing('permission_role', [
            'id_role' => $roleId,
            'id_permission' => $viewPermissionId,
        ]);
    }

    public function test_admin_cannot_delete_user_with_open_demands(): void
    {
        $this->seed();

        $adminId = (int) DB::table('utilisateurs')
            ->where('email', 'admin@anbg.ga')
            ->value('id_utilisateur');
        DB::table('utilisateurs')
            ->where('id_utilisateur', $adminId)
            ->update(['changement_mdp_requis' => false]);
        $agentId = (int) DB::table('utilisateurs')
            ->where('email', 'agent.daf@anbg.ga')
            ->value('id_utilisateur');

        $response = $this->from('/admin/utilisateurs')
            ->withHeader('X-User-Id', (string) $adminId)
            ->post("/admin/utilisateurs/{$agentId}/delete");

        $response->assertRedirect('/admin/utilisateurs');
        $response->assertSessionHasErrors('user');

        $this->assertDatabaseHas('utilisateurs', [
            'id_utilisateur' => $agentId,
        ]);
    }

    public function test_admin_avatar_upload_rejects_svg_files(): void
    {
        $this->seed();
        Storage::fake('public');

        $adminId = (int) DB::table('utilisateurs')
            ->where('email', 'admin@anbg.ga')
            ->value('id_utilisateur');
        DB::table('utilisateurs')
            ->where('id_utilisateur', $adminId)
            ->update(['changement_mdp_requis' => false]);

        $response = $this->from('/admin')
            ->withHeader('X-User-Id', (string) $adminId)
            ->post('/admin/avatar', [
                'avatar' => UploadedFile::fake()->create('avatar.svg', 10, 'image/svg+xml'),
            ]);

        $response->assertRedirect('/admin');
        $response->assertSessionHasErrors('avatar');
    }

    public function test_anbg_services_catalogue_is_seeded_with_full_structure(): void
    {
        $this->seed();

        $dgDirectionId = (int) DB::table('directions')
            ->where('code', 'DG')
            ->value('id_direction');
        $ucasId = (int) DB::table('services')
            ->where('code', 'UCAS')
            ->value('id_service');
        $sciqId = (int) DB::table('services')
            ->where('code', 'SCIQ')
            ->value('id_service');

        $this->assertDatabaseHas('services', [
            'id_direction' => $dgDirectionId,
            'code' => 'UCAS',
            'libelle' => 'Unite Courrier, Accueil et Securite',
        ]);
        $this->assertDatabaseHas('services', [
            'id_direction' => $dgDirectionId,
            'code' => 'SCIQ',
            'libelle' => 'Service Controle Interne et Qualite',
        ]);
        $this->assertDatabaseHas('services', [
            'id_direction' => $dgDirectionId,
            'code' => 'CABINET_DG',
            'libelle' => 'Cabinet - DG',
        ]);
        $this->assertDatabaseMissing('services', ['code' => 'ACC']);
        $this->assertDatabaseMissing('services', ['code' => 'CAB_DG']);

        $this->assertDatabaseHas('utilisateurs', [
            'email' => 'accueil@anbg.ga',
            'id_service' => $ucasId,
        ]);
        $this->assertDatabaseHas('utilisateurs', [
            'email' => 'ciq@anbg.ga',
            'id_service' => $sciqId,
        ]);

        $this->assertDatabaseHas('services', [
            'code' => 'CS_SIRS',
            'libelle' => 'Systemes d Informations, Reseaux et Securite',
        ]);
        $this->assertDatabaseHas('services', [
            'code' => 'CS_JSP',
            'libelle' => 'Communication et Relation Publique',
        ]);
        $this->assertDatabaseHas('services', [
            'code' => 'CS_GDS',
            'libelle' => 'Gestion Documentaire et Statistiques',
        ]);
        $this->assertDatabaseHas('services', [
            'code' => 'CS_AJARH',
            'libelle' => 'Affaires Juridiques et RH',
        ]);
        $this->assertDatabaseHas('services', [
            'code' => 'CS_FC',
            'libelle' => 'Financier et Comptable',
        ]);
        $this->assertDatabaseHas('services', [
            'code' => 'CS_AMG',
            'libelle' => 'Approvisionnement et Moyens Generaux',
        ]);
        $this->assertDatabaseHas('services', [
            'code' => 'CS_SNB',
            'libelle' => 'Etudiants non Boursiers',
        ]);
        $this->assertDatabaseHas('services', [
            'code' => 'CS_SENB',
            'libelle' => 'Etudiants Boursiers',
        ]);
        $this->assertDatabaseHas('services', [
            'code' => 'CS_P',
            'libelle' => 'Planification',
        ]);

        $this->assertDatabaseMissing('services', ['code' => 'SSIRS']);
        $this->assertDatabaseMissing('services', ['code' => 'SCRP']);
        $this->assertDatabaseMissing('services', ['code' => 'SGDS']);
        $this->assertDatabaseMissing('services', ['code' => 'SAJARH']);
        $this->assertDatabaseMissing('services', ['code' => 'SFC']);
        $this->assertDatabaseMissing('services', ['code' => 'SAMG']);
        $this->assertDatabaseMissing('services', ['code' => 'ETUD_NON_BOURS']);
        $this->assertDatabaseMissing('services', ['code' => 'ETUD_BOURS']);
        $this->assertDatabaseMissing('services', ['code' => 'PLANIF']);
    }
}
