<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StepAlertService
{
    public function __construct(private readonly WorkingHoursSlaService $slaService)
    {
    }

    public function refreshDemandAlerts(int $demandId): array
    {
        $demand = DB::table('demandes')->where('id_demande', $demandId)->first();
        if (!$demand) {
            throw new RuntimeException('Demande introuvable.');
        }

        $alerts = $this->computeAlertsForDemand($demand);

        DB::table('demandes')
            ->where('id_demande', $demandId)
            ->update([
                'alerte_accueil' => $alerts['alerte_accueil'],
                'alerte_chef' => $alerts['alerte_chef'],
                'alerte_agent' => $alerts['alerte_agent'],
                'delai_alerte' => $alerts['delai_alerte'],
                'heures_ouvrees_cloture' => $this->computeClosureWorkedHours($demand),
                'updated_at' => now(),
            ]);

        return $alerts;
    }

    public function refreshOpenDemandAlerts(?array $serviceScopeIds = null): void
    {
        $this->refreshDemandSet(onlyOpen: true, serviceScopeIds: $serviceScopeIds);
    }

    public function refreshAllDemandAlerts(?array $serviceScopeIds = null): void
    {
        $this->refreshDemandSet(onlyOpen: false, serviceScopeIds: $serviceScopeIds);
    }

    public function computeAlertsForDemand(object $demand): array
    {
        $configId = (int) $demand->id_config_sla;
        $serviceStartAt = $demand->date_affectation_accueil ?? null;
        $serviceEndAt = $demand->date_envoi_usager ?? ($demand->date_cloture ?? null);

        $alerteAccueil = $this->computeStepAlert(
            'accueil',
            $demand->date_soumission ?? null,
            $demand->date_affectation_accueil ?? null,
            $configId
        );

        $alerteChef = $this->computeStepAlert(
            'chef',
            $serviceStartAt,
            $serviceEndAt,
            $configId
        );

        $alerteAgent = $demand->date_affectation_agent
            ? $this->computeStepAlert(
                'agent',
                $serviceStartAt,
                $serviceEndAt,
                $configId
            )
            : null;

        return [
            'alerte_accueil' => $alerteAccueil,
            'alerte_chef' => $alerteChef,
            'alerte_agent' => $alerteAgent,
            'delai_alerte' => $this->computeGlobalDemandAlert(
                $demand->date_soumission ?? null,
                $serviceEndAt,
                $configId
            ),
        ];
    }

    public function overallAlert(?string ...$alerts): ?string
    {
        $order = ['vert' => 1, 'orange' => 2, 'rouge' => 3];
        $max = 0;
        $selected = null;

        foreach ($alerts as $alert) {
            if (!$alert || !isset($order[$alert])) {
                continue;
            }

            if ($order[$alert] > $max) {
                $max = $order[$alert];
                $selected = $alert;
            }
        }

        return $selected;
    }

    public function legacyAlert(?string $overall): ?string
    {
        return match ($overall) {
            'rouge' => 'en_retard',
            'orange' => 'a_risque',
            'vert' => 'dans_les_delais',
            default => null,
        };
    }

    public function thresholdsForStep(string $stepCode): array
    {
        $defaultsByStep = [
            'accueil' => ['warning' => 4, 'deadline' => 8],
            'chef' => ['warning' => 8, 'deadline' => 16],
            'agent' => ['warning' => 8, 'deadline' => 16],
            'default' => ['warning' => 12, 'deadline' => 24],
        ];

        return $this->thresholds($stepCode, $defaultsByStep[$stepCode] ?? $defaultsByStep['default']);
    }

    public function globalThresholds(int $configId): array
    {
        $deadline = $this->globalMaxHours($configId);
        $defaults = [
            'warning' => round($deadline / 2, 2),
            'deadline' => $deadline,
        ];

        return $this->thresholds('default', $defaults);
    }

    private function computeStepAlert(string $stepCode, ?string $startAt, ?string $endAt, int $configId): ?string
    {
        if (!$startAt) {
            return null;
        }

        $thresholds = $this->thresholdsForStep($stepCode);
        $start = Carbon::parse($startAt);
        $end = $endAt ? Carbon::parse($endAt) : now();

        $elapsed = $this->slaService->calculateElapsedHours($start, $end, $configId);

        if ($elapsed < $thresholds['warning']) {
            return 'vert';
        }

        if ($elapsed <= $thresholds['deadline']) {
            return 'orange';
        }

        return 'rouge';
    }

    private function computeGlobalDemandAlert(?string $startAt, ?string $endAt, int $configId): ?string
    {
        if (!$startAt) {
            return null;
        }

        $thresholds = $this->globalThresholds($configId);
        $start = Carbon::parse($startAt);
        $end = $endAt ? Carbon::parse($endAt) : now();
        $elapsed = $this->slaService->calculateElapsedHours($start, $end, $configId);

        return $this->slaService->classifyAlert(
            $elapsed,
            (int) $thresholds['deadline'],
            (float) $thresholds['warning']
        );
    }

    private function refreshDemandSet(bool $onlyOpen, ?array $serviceScopeIds = null): void
    {
        $query = DB::table('demandes');

        if ($onlyOpen) {
            $query->whereNull('date_cloture');
        }

        if ($serviceScopeIds !== null) {
            $query->whereIn('id_service_courant', !empty($serviceScopeIds) ? $serviceScopeIds : [-1]);
        }

        $rows = $query->get([
            'id_demande',
            'id_config_sla',
            'date_soumission',
            'date_affectation_accueil',
            'date_affectation_agent',
            'date_envoi_usager',
            'date_cloture',
        ]);

        foreach ($rows as $row) {
            $alerts = $this->computeAlertsForDemand($row);
            DB::table('demandes')
                ->where('id_demande', $row->id_demande)
                ->update([
                    'alerte_accueil' => $alerts['alerte_accueil'],
                    'alerte_chef' => $alerts['alerte_chef'],
                    'alerte_agent' => $alerts['alerte_agent'],
                    'delai_alerte' => $alerts['delai_alerte'],
                    'heures_ouvrees_cloture' => $this->computeClosureWorkedHours($row),
                    'updated_at' => now(),
                ]);
        }
    }

    private function computeClosureWorkedHours(object $demand): ?float
    {
        if (
            !$demand->date_soumission
            || !$demand->id_config_sla
            || (!$demand->date_envoi_usager && !$demand->date_cloture)
        ) {
            return null;
        }

        $endAt = $demand->date_envoi_usager ?? $demand->date_cloture;

        return $this->slaService->calculateElapsedHours(
            Carbon::parse($demand->date_soumission),
            Carbon::parse($endAt),
            (int) $demand->id_config_sla
        );
    }

    private function thresholds(string $stepCode, array $defaults): array
    {
        $row = DB::table('parametres')
            ->where('famille', 'seuil_alerte')
            ->where('code', $stepCode)
            ->value('metadata_json');

        if (!$row && $stepCode !== 'default') {
            $row = DB::table('parametres')
                ->where('famille', 'seuil_alerte')
                ->where('code', 'default')
                ->value('metadata_json');
        }

        if (!$row) {
            return $defaults;
        }

        $payload = json_decode((string) $row, true);
        if (!is_array($payload)) {
            return $defaults;
        }

        $warning = isset($payload['warning'])
            ? (float) $payload['warning']
            : (isset($payload['orange'])
                ? (float) $payload['orange']
                : (isset($payload['vert']) ? (float) $payload['vert'] : (float) $defaults['warning']));

        $deadline = isset($payload['deadline'])
            ? (float) $payload['deadline']
            : (isset($payload['rouge']) ? (float) $payload['rouge'] : (float) $defaults['deadline']);

        return [
            'warning' => max(0.0, min($warning, $deadline)),
            'deadline' => max(1.0, $deadline),
        ];
    }

    private function globalMaxHours(int $configId): int
    {
        $value = DB::table('config_sla')
            ->where('id_config_sla', $configId)
            ->value('delai_max_heures');

        return max(1, (int) ($value ?: 24));
    }
}
