<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OverviewApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_endpoint_returns_expected_structure(): void
    {
        $this->seed();
        $ciqId = (int) \Illuminate\Support\Facades\DB::table('utilisateurs')
            ->where('email', 'ciq@anbg.ga')
            ->value('id_utilisateur');

        $response = $this->withHeader('X-User-Id', (string) $ciqId)
            ->getJson('/api/overview?periode=all');

        $response->assertOk()
            ->assertJsonStructure([
                'generated_at',
                'filters_appliques',
                'kpis' => [
                    'total_demandes',
                    'total_ouvertes',
                    'total_traitees',
                    'total_appliquees',
                    'total_non_appliquees',
                    'global_dans_les_delais',
                    'global_a_risque',
                    'global_en_retard',
                    'total_en_retard',
                    'taux_traitement_dans_delais',
                    'delai_moyen_traitement_heures',
                    'accueil_verts',
                    'accueil_oranges',
                    'accueil_rouges',
                    'chef_rouges',
                    'agent_rouges',
                    'global_verts',
                    'global_oranges',
                    'global_rouges',
                ],
                'sla_active',
                'statuts',
                'par_direction',
                'performance_directions',
                'kpi_services',
                'kpi_agents',
                'par_type',
                'performance_types',
                'evolution_par_type',
                'evolution_temporelle' => [
                    'granularite',
                    'labels',
                    'series' => [
                        'reclamations_recues',
                        'demandes_cloturees',
                    ],
                ],
                'usagers_plus_actifs',
                'historique_usagers',
                'registre_mails',
                'registre_controle_interne',
                'tableau_suivi_annexe',
                'annexe_repartition' => [
                    'reclamations',
                    'total_reclamations',
                ],
                'annexes_fonctions' => [
                    'global' => [
                        'rows',
                        'totaux',
                    ],
                ],
                'actions_recentes',
                'tracabilite_globale',
                'demandes_en_cours',
                'organisation',
                'catalogues' => [
                    'directions',
                    'services',
                    'statuts',
                    'types',
                    'application_states',
                ],
                'phase4_gantt',
            ]);
    }

    public function test_chef_direction_can_view_scoped_overview_only(): void
    {
        $this->seed();

        $db = \Illuminate\Support\Facades\DB::class;
        $userId = (int) $db::table('utilisateurs')
            ->where('email', 'chef.direction.ds@anbg.ga')
            ->value('id_utilisateur');

        $response = $this->withHeader('X-User-Id', (string) $userId)
            ->getJson('/api/overview');

        $response->assertOk()
            ->assertJsonPath('kpis.total_demandes', 1)
            ->assertJsonPath('kpis.total_ouvertes', 1);

        $payload = $response->json();
        $this->assertCount(1, $payload['par_direction']);
        $this->assertSame('Direction de la Scolarite', $payload['par_direction'][0]['direction']);
    }

    public function test_overview_supports_service_and_application_filters_for_ciq(): void
    {
        $this->seed();

        $db = \Illuminate\Support\Facades\DB::class;
        $userId = (int) $db::table('utilisateurs')
            ->where('email', 'ciq@anbg.ga')
            ->value('id_utilisateur');
        $serviceId = (int) $db::table('services')
            ->where('code', 'CS_SIRS')
            ->value('id_service');

        $response = $this->withHeader('X-User-Id', (string) $userId)
            ->getJson("/api/overview?periode=all&service_id={$serviceId}&application_state=appliquee");

        $response->assertOk()
            ->assertJsonPath('filters_appliques.service_id', $serviceId)
            ->assertJsonPath('filters_appliques.application_state', 'appliquee')
            ->assertJsonPath('kpis.total_demandes', 1)
            ->assertJsonPath('kpis.total_traitees', 1)
            ->assertJsonPath('kpis.total_non_appliquees', 0);

        $payload = $response->json();
        $this->assertCount(1, $payload['registre_controle_interne']);
        $this->assertSame('Appliquee', $payload['registre_controle_interne'][0]['statut_application']);
        $this->assertSame('Systemes d Informations, Reseaux et Securite', $payload['registre_controle_interne'][0]['service_affecte']);
    }

    public function test_ciq_overview_exposes_complete_traceability_for_agent_and_chef_flows(): void
    {
        $this->seed();

        $userId = (int) \Illuminate\Support\Facades\DB::table('utilisateurs')
            ->where('email', 'ciq@anbg.ga')
            ->value('id_utilisateur');

        $response = $this->withHeader('X-User-Id', (string) $userId)
            ->getJson('/api/overview?periode=all');

        $response->assertOk();

        $payload = $response->json();
        $traces = collect($payload['tracabilite_globale'] ?? [])->keyBy('numero_suivi');
        $registre = collect($payload['registre_controle_interne'] ?? [])->keyBy('numero_suivi');

        $this->assertTrue($traces->has('ANBG-2026-0004'));
        $this->assertTrue($traces->has('ANBG-2026-0005'));

        $agentFlowActions = collect($traces->get('ANBG-2026-0004')['actions'] ?? [])->pluck('action')->all();
        $chefFlowActions = collect($traces->get('ANBG-2026-0005')['actions'] ?? [])->pluck('action')->all();

        $this->assertSame([
            'Soumission usager',
            'Affectation service',
            'Affectation agent',
            'Reponse redigee',
            'Reponse finale envoyee',
        ], $agentFlowActions);

        $this->assertSame([
            'Soumission usager',
            'Affectation service',
            'Reponse redigee',
            'Reponse finale envoyee',
            'Reponse directe chef',
        ], $chefFlowActions);

        $this->assertSame('Appliquee', $registre->get('ANBG-2026-0004')['statut_application']);
        $this->assertSame('Appliquee', $registre->get('ANBG-2026-0005')['statut_application']);
        $this->assertNotEmpty($registre->get('ANBG-2026-0004')['date_reception']);
        $this->assertNotEmpty($registre->get('ANBG-2026-0004')['date_affectation_direction']);
        $this->assertNotEmpty($registre->get('ANBG-2026-0004')['date_affectation_agent']);
        $this->assertNotEmpty($registre->get('ANBG-2026-0004')['date_reponse']);
        $this->assertSame('Financier et Comptable', $registre->get('ANBG-2026-0005')['service_affecte']);
        $this->assertSame('DAF Chef', $registre->get('ANBG-2026-0005')['acteur_reponse']);
    }

    public function test_overview_exposes_function_annexes_for_global_tables(): void
    {
        $this->seed();

        $ciqId = (int) \Illuminate\Support\Facades\DB::table('utilisateurs')
            ->where('email', 'ciq@anbg.ga')
            ->value('id_utilisateur');

        $response = $this->withHeader('X-User-Id', (string) $ciqId)
            ->getJson('/api/overview?periode=all');

        $response->assertOk();

        $globalRows = collect($response->json('annexes_fonctions.global.rows') ?? [])->keyBy('fonction_code');

        $this->assertTrue($globalRows->has('DS'));
        $this->assertTrue($globalRows->has('DSIC'));
        $this->assertTrue($globalRows->has('DAF'));
        $this->assertTrue($globalRows->has('UCAS'));

        $this->assertSame(1, $globalRows->get('DSIC')['total_demandes']);
        $this->assertSame(1, $globalRows->get('DSIC')['total_traitees']);
        $this->assertEquals(100.0, $globalRows->get('DSIC')['taux_conformite']);
    }

    public function test_overview_reports_direct_accueil_responses_under_ucas(): void
    {
        $this->seed();

        $this->post('/reclamations', [
            'nom' => 'Mouila',
            'prenom' => 'Sandra',
            'email' => 'pilotage.direct.accueil@example.com',
            'statut_usager' => 'Parent / Tuteur',
            'pays' => 'Gabon',
            'etablissement' => '',
            'categorie' => 'Suivi bourse',
            'objet' => 'Réclamation UCAS pilotage',
            'message' => 'Je souhaite une prise en charge directe au niveau accueil.',
            'consentement' => 'on',
        ])->assertRedirect('/reclamations/nouvelle');

        $trackingNumber = (string) DB::table('demandes')
            ->where('objet', 'Réclamation UCAS pilotage')
            ->value('numero_suivi');
        $demandId = (int) DB::table('demandes')
            ->where('numero_suivi', $trackingNumber)
            ->value('id_demande');

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
            'contenu_reponse' => "La réponse a été apportée directement à l'accueil pour clôture immédiate.",
        ])->assertRedirect();

        $ciqId = (int) DB::table('utilisateurs')
            ->where('email', 'ciq@anbg.ga')
            ->value('id_utilisateur');
        DB::table('utilisateurs')
            ->where('id_utilisateur', $ciqId)
            ->update(['changement_mdp_requis' => false]);

        $response = $this->withHeader('X-User-Id', (string) $ciqId)
            ->getJson('/api/overview?periode=all');

        $response->assertOk();

        $serviceRows = collect($response->json('annexes_services.reclamations.rows') ?? [])->keyBy('service_code');
        $trackingRow = collect($response->json('tableau_suivi_annexe') ?? [])->firstWhere('numero_suivi', $trackingNumber);
        $traceRow = collect($response->json('tracabilite_globale') ?? [])->firstWhere('numero_suivi', $trackingNumber);

        $this->assertTrue($serviceRows->has('UCAS'));
        $this->assertNotNull($trackingRow);
        $this->assertSame('UCAS', $trackingRow['service_direction']);
        $this->assertNotNull($traceRow);
        $this->assertContains('Réponse directe accueil', collect($traceRow['actions'] ?? [])->pluck('action')->all());
    }

    public function test_overview_marks_overdue_direct_accueil_response_as_out_of_time(): void
    {
        $this->seed();

        $this->post('/reclamations', [
            'nom' => 'Mouila',
            'prenom' => 'Sandra',
            'email' => 'pilotage.direct.accueil.retard@example.com',
            'statut_usager' => 'Parent / Tuteur',
            'pays' => 'Gabon',
            'etablissement' => '',
            'categorie' => 'Suivi bourse',
            'objet' => 'RÃ©clamation UCAS en retard',
            'message' => 'Je souhaite une prise en charge directe au niveau accueil mais hors dÃ©lai.',
            'consentement' => 'on',
        ])->assertRedirect('/reclamations/nouvelle');

        $trackingNumber = (string) DB::table('demandes')
            ->where('objet', 'RÃ©clamation UCAS en retard')
            ->value('numero_suivi');
        $demandId = (int) DB::table('demandes')
            ->where('numero_suivi', $trackingNumber)
            ->value('id_demande');

        DB::table('demandes')
            ->where('id_demande', $demandId)
            ->update([
                'date_soumission' => now()->subDays(3),
                'updated_at' => now(),
            ]);

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
            'contenu_reponse' => "La rÃ©ponse directe accueil a Ã©tÃ© envoyÃ©e aprÃ¨s dÃ©passement du dÃ©lai.",
        ])->assertRedirect();

        $ciqId = (int) DB::table('utilisateurs')
            ->where('email', 'ciq@anbg.ga')
            ->value('id_utilisateur');
        DB::table('utilisateurs')
            ->where('id_utilisateur', $ciqId)
            ->update(['changement_mdp_requis' => false]);

        $response = $this->withHeader('X-User-Id', (string) $ciqId)
            ->getJson('/api/overview?periode=all');

        $response->assertOk();

        $trackingRow = collect($response->json('tableau_suivi_annexe') ?? [])->firstWhere('numero_suivi', $trackingNumber);
        $serviceRows = collect($response->json('annexes_services.reclamations.rows') ?? [])->keyBy('service_code');

        $this->assertNotNull($trackingRow);
        $this->assertSame('UCAS', $trackingRow['service_direction']);
        $this->assertSame('NON', $trackingRow['respect_delais']);
        $this->assertSame('NON', $trackingRow['delai_transmission_oh']);
        $this->assertTrue($serviceRows->has('UCAS'));
        $this->assertSame(0, (int) ($serviceRows->get('UCAS')['total_traitees_delai'] ?? -1));
    }
}
