<?php

namespace App\Http\Controllers\Web\Concerns;

use App\Services\StepAlertService;
use App\Services\WorkingHoursSlaService;
use Carbon\Carbon;

trait InteractsWithServiceWindow
{
    private function withServiceWindowMeta(
        object $demand,
        WorkingHoursSlaService $slaService
    ): object {
        $thresholds = app(StepAlertService::class)->thresholdsForStep('chef');
        $deadlineHours = (float) $thresholds['deadline'];
        $warningHours = (float) $thresholds['warning'];

        $startAt = property_exists($demand, 'date_affectation_accueil')
            ? $demand->date_affectation_accueil
            : null;
        $configId = property_exists($demand, 'id_config_sla')
            ? (int) ($demand->id_config_sla ?? 0)
            : 0;

        if (!$startAt || $configId <= 0) {
            $demand->service_window_deadline_hours = $deadlineHours;
            $demand->service_window_elapsed_hours = 0.0;
            $demand->service_window_remaining_hours = $deadlineHours;
            $demand->service_window_overdue_hours = 0.0;
            $demand->service_window_tone = 'neutral';
            $demand->service_window_label = 'Fenetre '.$this->formatServiceWindowHours($deadlineHours).' a démarré';
            $demand->service_window_hint = "Le compteur commence après l'affectation de l'accueil.";

            return $demand;
        }

        $endAt = property_exists($demand, 'date_envoi_usager')
            ? $demand->date_envoi_usager
            : null;

        if (!$endAt && property_exists($demand, 'date_cloture')) {
            $endAt = $demand->date_cloture;
        }

        $elapsedHours = $slaService->calculateElapsedHours(
            Carbon::parse($startAt),
            $endAt ? Carbon::parse($endAt) : null,
            $configId
        );

        $remainingHours = round(max(0, $deadlineHours - $elapsedHours), 2);
        $overdueHours = round(max(0, $elapsedHours - $deadlineHours), 2);

        $demand->service_window_deadline_hours = $deadlineHours;
        $demand->service_window_elapsed_hours = $elapsedHours;
        $demand->service_window_remaining_hours = $remainingHours;
        $demand->service_window_overdue_hours = $overdueHours;
        $demand->service_window_tone = match (true) {
            $overdueHours > 0 => 'red',
            $elapsedHours >= $warningHours => 'amber',
            default => 'green',
        };
        $demand->service_window_label = match (true) {
            $overdueHours > 0 => 'Delai depassé de '.$this->formatServiceWindowHours($overdueHours),
            $remainingHours <= 0 => 'Echéance atteinte',
            default => 'Délai restant '.$this->formatServiceWindowHours($remainingHours),
        };
        $demand->service_window_hint = $this->formatServiceWindowHours($elapsedHours)
            .' / '.$this->formatServiceWindowHours($deadlineHours).' consommées';

        return $demand;
    }

    private function formatServiceWindowHours(float $hours): string
    {
        $formatted = number_format($hours, 1, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted.'h';
    }
}
