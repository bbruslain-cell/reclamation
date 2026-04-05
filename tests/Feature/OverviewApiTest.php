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
                        'informations_recues',
                        'demandes_cloturees',
                    ],
                ],
                'usagers_plus_actifs',
                'historique_usagers',
                'registre_mails',
                'registre_controle_interne',
                'tableau_suivi_annexe',
                'annexe_repartition' => [
                    'informations',
                    'total_informations',
                    'reclamations',
                    'total_reclamations',
                ],
                'annexes_fonctions' => [
                    'global' => [
                        'rows',
                        'totaux',
                    ],
                    'informations' => [
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

    public function test_overview_annexe_repartition_counts_categories_selected_by_usagers(): void
    {
        $this->seed();

        $this->post('/reclamations', [
            'nom' => 'Doe',
            'prenom' => 'Jane',
            'email' => 'jane.category@example.com',
            'telephone' => '060000000',
            'qualite' => 'Etudiante',
            'type_demande_code' => 'demande_information',
            'categorie' => 'Demande d informations diverses',
            'objet' => 'Question complementaire',
            'message' => 'Je souhaite des informations complementaires sur mon dossier en cours.',
            'consentement' => 'on',
        ])->assertRedirect('/reclamations/nouvelle');

        $ciqId = (int) \Illuminate\Support\Facades\DB::table('utilisateurs')
            ->where('email', 'ciq@anbg.ga')
            ->value('id_utilisateur');

        $response = $this->withHeader('X-User-Id', (string) $ciqId)
            ->getJson('/api/overview?periode=all');

        $response->assertOk();

        $informationRows = collect($response->json('annexe_repartition.informations') ?? [])
            ->keyBy('categorie');

        $this->assertTrue($informationRows->has('Demande d informations diverses'));
        $this->assertSame(1, $informationRows->get('Demande d informations diverses')['nombre_mails']);
    }

    public function test_overview_exposes_function_annexes_for_global_and_information_tables(): void
    {
        $this->seed();

        $ciqId = (int) \Illuminate\Support\Facades\DB::table('utilisateurs')
            ->where('email', 'ciq@anbg.ga')
            ->value('id_utilisateur');

        $response = $this->withHeader('X-User-Id', (string) $ciqId)
            ->getJson('/api/overview?periode=all');

        $response->assertOk();

        $globalRows = collect($response->json('annexes_fonctions.global.rows') ?? [])->keyBy('fonction_code');
        $informationRows = collect($response->json('annexes_fonctions.informations.rows') ?? [])->keyBy('fonction_code');

        $this->assertTrue($globalRows->has('DS'));
        $this->assertTrue($globalRows->has('DSIC'));
        $this->assertTrue($globalRows->has('DAF'));
        $this->assertTrue($globalRows->has('UCAS'));

        $this->assertSame(1, $globalRows->get('DSIC')['total_demandes']);
        $this->assertSame(1, $globalRows->get('DSIC')['total_traitees']);
        $this->assertEquals(100.0, $globalRows->get('DSIC')['taux_conformite']);

        $this->assertSame(1, $informationRows->get('DSIC')['total_demandes']);
        $this->assertSame(1, $informationRows->get('DSIC')['total_traitees']);
        $this->assertSame(0, $informationRows->get('UCAS')['total_demandes']);
    }

    public function test_overview_annexe_uses_ucas_for_direct_accueil_reply(): void
    {
        $this->seed();

        $ciqId = (int) DB::table('utilisateurs')
            ->where('email', 'ciq@anbg.ga')
            ->value('id_utilisateur');

        $accueilId = (int) DB::table('utilisateurs')
            ->where('email', 'accueil@anbg.ga')
            ->value('id_utilisateur');

        $ucasServiceId = (int) DB::table('services')
            ->where('code', 'UCAS')
            ->value('id_service');

        $clotureeId = (int) DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->where('code', 'cloturee')
            ->value('id_parametre');

        $demandId = (int) DB::table('demandes')
            ->where('numero_suivi', 'ANBG-2026-0001')
            ->value('id_demande');

        DB::table('demandes')
            ->where('id_demande', $demandId)
            ->update([
                'id_statut' => $clotureeId,
                'id_service_courant' => null,
                'id_agent_accueil' => $accueilId,
                'id_agent_direction' => null,
                'id_agent_traitant' => null,
                'date_affectation_accueil' => now()->subHours(2),
                'date_envoi_usager' => now()->subHour(),
                'date_cloture' => now()->subHour(),
                'updated_at' => now(),
            ]);

        DB::table('historique_actions')->insert([
            'id_demande' => $demandId,
            'id_utilisateur' => $accueilId,
            'type_action' => 'reponse_directe_accueil',
            'ancien_statut_id' => null,
            'nouveau_statut_id' => null,
            'id_service_associe' => $ucasServiceId,
            'date_action' => now()->subHour(),
            'commentaire' => 'Reponse directe accueil',
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $response = $this->withHeader('X-User-Id', (string) $ciqId)
            ->getJson('/api/overview');

        $response->assertOk();

        $annexeRows = collect($response->json('tableau_suivi_annexe') ?? [])->keyBy('numero_suivi');
        $row = $annexeRows->get('ANBG-2026-0001');

        $this->assertNotNull($row);
        $this->assertSame('UCAS', $row['service_direction']);
    }
}
