<?php

namespace App\Http\Controllers\Web\Concerns;

use App\Services\WorkingHoursSlaService;
use Carbon\Carbon;

trait InteractsWithServiceWindow
{
    private function withServiceWindowMeta(
        object $demand,
        WorkingHoursSlaService $slaService,
        int $deadlineHours = 48,
        int $warningHours = 24
    ): object {
        $startAt = property_exists($demand, 'date_affectation_accueil')
            ? $demand->date_affectation_accueil
            : null;
        $configId = property_exists($demand, 'id_config_sla')
            ? (int) ($demand->id_config_sla ?? 0)
            : 0;

        if (!$startAt || $configId <= 0) {
            $demand->service_window_deadline_hours = (float) $deadlineHours;
            $demand->service_window_elapsed_hours = 0.0;
            $demand->service_window_remaining_hours = (float) $deadlineHours;
            $demand->service_window_overdue_hours = 0.0;
            $demand->service_window_tone = 'neutral';
            $demand->service_window_label = 'Fenetre 48h a demarrer';
            $demand->service_window_hint = 'Le compteur commence apres affectation accueil.';

            return $demand;
        }

        $endAt = property_exists($demand, 'date_envoi_usager')
            ? $demand->date_envoi_usager
            : null;

        if (!$endAt && property_exists($demand, 'date_cloture')) {
            $endAt = $demand->date_cloture;
        }

        $elapsedHours = $slaService->calculateElapsedBusinessHours(
            Carbon::parse($startAt),
            $endAt ? Carbon::parse($endAt) : null,
            $configId
        );

        $remainingHours = round(max(0, $deadlineHours - $elapsedHours), 2);
        $overdueHours = round(max(0, $elapsedHours - $deadlineHours), 2);

        $demand->service_window_deadline_hours = (float) $deadlineHours;
        $demand->service_window_elapsed_hours = $elapsedHours;
        $demand->service_window_remaining_hours = $remainingHours;
        $demand->service_window_overdue_hours = $overdueHours;
        $demand->service_window_tone = match (true) {
            $overdueHours > 0 => 'red',
            $elapsedHours >= $warningHours => 'amber',
            default => 'green',
        };
        $demand->service_window_label = match (true) {
            $overdueHours > 0 => 'Depassee de '.$this->formatServiceWindowHours($overdueHours).' SLA',
            $remainingHours <= 0 => 'Echeance atteinte',
            default => 'Reste '.$this->formatServiceWindowHours($remainingHours).' SLA',
        };
        $demand->service_window_hint = $this->formatServiceWindowHours($elapsedHours)
            .' / '.$deadlineHours.'h consommees';

        return $demand;
    }

    private function formatServiceWindowHours(float $hours): string
    {
        $formatted = number_format($hours, 1, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted.'h';
    }
}
