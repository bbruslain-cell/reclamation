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
                'updated_at' => now(),
            ]);

        return $alerts;
    }

    public function refreshOpenDemandAlerts(?array $serviceScopeIds = null): void
    {
        $query = DB::table('demandes')
            ->whereNull('date_cloture');

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
                    'updated_at' => now(),
                ]);
        }
    }

    public function computeAlertsForDemand(object $demand): array
    {
        $configId = (int) $demand->id_config_sla;
        $serviceStartAt = $demand->date_affectation_accueil ?? null;
        $serviceEndAt = $demand->date_envoi_usager ?? ($demand->date_cloture ?? null);

        $alerteAccueil = $this->computeStepAlert(
            'accueil',
            $demand->date_soumission,
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

    private function computeStepAlert(string $stepCode, ?string $startAt, ?string $endAt, int $configId): ?string
    {
        if (!$startAt) {
            return null;
        }

        $thresholds = $this->thresholds($stepCode);
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

        $start = Carbon::parse($startAt);
        $end = $endAt ? Carbon::parse($endAt) : now();

        $elapsed = $this->slaService->calculateElapsedHours($start, $end, $configId);
        $maxHours = $this->globalMaxHours($configId);

        return $this->slaService->classifyAlert($elapsed, $maxHours);
    }

    private function thresholds(string $stepCode): array
    {
        // Seuils validesés : vert <24h ouvrées, orange 24h-72h, rouge >72h ouvrées
        $defaultsByStep = [
            'accueil' => ['warning' => 24, 'deadline' => 72],
            'chef'    => ['warning' => 24, 'deadline' => 72],
            'agent'   => ['warning' => 24, 'deadline' => 72],
            'default' => ['warning' => 24, 'deadline' => 72],
        ];
        $defaults = $defaultsByStep[$stepCode] ?? $defaultsByStep['default'];

        $row = DB::table('parametres')
            ->where('famille', 'seuil_alerte')
            ->where('code', $stepCode)
            ->value('metadata_json');

        if (!$row) {
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

        return [
            'warning'  => isset($payload['warning'])
                ? (int) $payload['warning']
                : (isset($payload['vert']) ? (int) $payload['vert'] : $defaults['warning']),
            'deadline' => isset($payload['deadline'])
                ? (int) $payload['deadline']
                : (
                    isset($payload['rouge'])
                        ? (int) $payload['rouge']
                        : (isset($payload['orange']) ? (int) $payload['orange'] : $defaults['deadline'])
                ),
        ];
    }

    private function globalMaxHours(int $configId): int
    {
        $value = DB::table('config_sla')
            ->where('id_config_sla', $configId)
            ->value('delai_max_heures');

        return max(1, (int) ($value ?: 72));
    }
}
