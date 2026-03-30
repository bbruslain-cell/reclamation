<?php

namespace Tests\Feature;

use App\Services\StepAlertService;
use App\Services\WorkingHoursSlaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BusinessSlaRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_full_working_day_counts_as_twenty_four_business_hours_and_global_limits_are_strict(): void
    {
        $this->seed();

        $configId = (int) DB::table('config_sla')->where('actif', true)->value('id_config_sla');
        $service = app(WorkingHoursSlaService::class);

        $start = Carbon::create(2026, 3, 23, 7, 30, 0, 'Africa/Libreville');
        $end = Carbon::create(2026, 3, 23, 15, 30, 0, 'Africa/Libreville');

        $this->assertSame(8.0, $service->calculateElapsedWorkedHours($start, $end, $configId));
        $this->assertSame(24.0, $service->calculateElapsedHours($start, $end, $configId));

        $this->assertSame('dans_les_delais', $service->classifyAlert(48.0, 72));
        $this->assertSame('a_risque', $service->classifyAlert(48.01, 72));
        $this->assertSame('a_risque', $service->classifyAlert(72.0, 72));
        $this->assertSame('en_retard', $service->classifyAlert(72.01, 72));
    }

    public function test_accueil_can_be_overdue_while_global_demand_remains_within_delay(): void
    {
        $this->seed();

        Carbon::setTestNow(Carbon::create(2026, 3, 24, 7, 31, 0, 'Africa/Libreville'));

        $demandId = $this->createDemand([
            'numero_suivi' => 'TEST-SLA-ACCUEIL-001',
            'date_soumission' => Carbon::create(2026, 3, 23, 7, 30, 0, 'Africa/Libreville'),
        ]);

        $alerts = app(StepAlertService::class)->refreshDemandAlerts($demandId);

        $this->assertSame('rouge', $alerts['alerte_accueil']);
        $this->assertNull($alerts['alerte_chef']);
        $this->assertNull($alerts['alerte_agent']);
        $this->assertSame('dans_les_delais', $alerts['delai_alerte']);
    }

    public function test_global_demand_becomes_a_risque_after_forty_eight_business_hours_without_late_service_stage(): void
    {
        $this->seed();

        Carbon::setTestNow(Carbon::create(2026, 3, 25, 7, 31, 0, 'Africa/Libreville'));

        $serviceId = (int) DB::table('services')->where('code', 'CS_SENB')->value('id_service');

        $demandId = $this->createDemand([
            'numero_suivi' => 'TEST-SLA-GLOBAL-002',
            'id_service_courant' => $serviceId,
            'date_soumission' => Carbon::create(2026, 3, 23, 7, 30, 0, 'Africa/Libreville'),
            'date_affectation' => Carbon::create(2026, 3, 23, 8, 0, 0, 'Africa/Libreville'),
            'date_affectation_accueil' => Carbon::create(2026, 3, 23, 8, 0, 0, 'Africa/Libreville'),
        ]);

        $alerts = app(StepAlertService::class)->refreshDemandAlerts($demandId);

        $this->assertSame('vert', $alerts['alerte_accueil']);
        $this->assertSame('orange', $alerts['alerte_chef']);
        $this->assertNull($alerts['alerte_agent']);
        $this->assertSame('a_risque', $alerts['delai_alerte']);
    }

    public function test_global_demand_becomes_en_retard_only_after_more_than_seventy_two_business_hours(): void
    {
        $this->seed();

        Carbon::setTestNow(Carbon::create(2026, 3, 26, 7, 31, 0, 'Africa/Libreville'));

        $serviceId = (int) DB::table('services')->where('code', 'CS_FC')->value('id_service');

        $demandId = $this->createDemand([
            'numero_suivi' => 'TEST-SLA-GLOBAL-003',
            'id_service_courant' => $serviceId,
            'date_soumission' => Carbon::create(2026, 3, 23, 7, 30, 0, 'Africa/Libreville'),
            'date_affectation' => Carbon::create(2026, 3, 23, 8, 0, 0, 'Africa/Libreville'),
            'date_affectation_accueil' => Carbon::create(2026, 3, 23, 8, 0, 0, 'Africa/Libreville'),
        ]);

        $alerts = app(StepAlertService::class)->refreshDemandAlerts($demandId);

        $this->assertSame('rouge', $alerts['alerte_chef']);
        $this->assertNull($alerts['alerte_agent']);
        $this->assertSame('en_retard', $alerts['delai_alerte']);
    }

    private function createDemand(array $overrides = []): int
    {
        $configId = (int) DB::table('config_sla')->where('actif', true)->value('id_config_sla');
        $typeId = (int) DB::table('parametres')
            ->where('famille', 'type_demande')
            ->where('code', 'demande_information')
            ->value('id_parametre');
        $usagerId = (int) DB::table('usagers')->value('id_usager');

        $statusCode = 'nouvelle';
        if (array_key_exists('date_affectation_agent', $overrides) && $overrides['date_affectation_agent'] !== null) {
            $statusCode = 'affectee_agent';
        } elseif (
            (array_key_exists('date_affectation_accueil', $overrides) && $overrides['date_affectation_accueil'] !== null) ||
            (array_key_exists('id_service_courant', $overrides) && $overrides['id_service_courant'] !== null)
        ) {
            $statusCode = 'affectee_service';
        }

        $statusId = (int) DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->where('code', $statusCode)
            ->value('id_parametre');

        return (int) DB::table('demandes')->insertGetId(array_merge([
            'numero_suivi' => 'TEST-SLA-DEFAULT',
            'id_usager' => $usagerId,
            'id_type_demande' => $typeId,
            'id_statut' => $statusId,
            'id_config_sla' => $configId,
            'objet' => 'Demande de test SLA',
            'message' => 'Message de test pour valider les regles SLA.',
            'date_soumission' => Carbon::create(2026, 3, 23, 7, 30, 0, 'Africa/Libreville'),
            'alerte_accueil' => 'vert',
            'delai_alerte' => 'dans_les_delais',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides), 'id_demande');
    }
}
