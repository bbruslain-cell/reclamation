<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class WorkingHoursSlaService
{
    private const BUSINESS_DAY_HOURS = 24.0;

    public function calculateElapsedHours(
        CarbonInterface $startAt,
        ?CarbonInterface $endAt,
        int $configSlaId
    ): float {
        return $this->calculateElapsedBusinessHours($startAt, $endAt, $configSlaId);
    }

    public function calculateElapsedBusinessHours(
        CarbonInterface $startAt,
        ?CarbonInterface $endAt,
        int $configSlaId
    ): float {
        return $this->calculateElapsedWindowHours(
            $startAt,
            $endAt,
            $configSlaId,
            convertToBusinessHours: true
        );
    }

    public function calculateElapsedWorkedHours(
        CarbonInterface $startAt,
        ?CarbonInterface $endAt,
        int $configSlaId
    ): float {
        return $this->calculateElapsedWindowHours(
            $startAt,
            $endAt,
            $configSlaId,
            convertToBusinessHours: false
        );
    }

    public function classifyAlert(float $elapsedHours, int $maxHours = 72): string
    {
        // Seuils validés : vert <24h ouvrées, orange 24h–72h, rouge >72h
        if ($elapsedHours > $maxHours) {
            return 'en_retard';
        }

        if ($elapsedHours >= 24) {
            return 'a_risque';
        }

        return 'dans_les_delais';
    }

    private function calculateElapsedWindowHours(
        CarbonInterface $startAt,
        ?CarbonInterface $endAt,
        int $configSlaId,
        bool $convertToBusinessHours
    ): float {
        $config = DB::table('config_sla')
            ->where('id_config_sla', $configSlaId)
            ->first();

        if (!$config) {
            return 0.0;
        }

        $timezone = $config->fuseau_horaire ?: 'Africa/Libreville';
        $start = CarbonImmutable::parse($startAt)->setTimezone($timezone);
        $end = CarbonImmutable::parse($endAt ?? now())->setTimezone($timezone);

        if ($end->lessThanOrEqualTo($start)) {
            return 0.0;
        }

        $windowsByDay = DB::table('sla_jours_ouvres')
            ->where('id_config_sla', $configSlaId)
            ->where('actif', true)
            ->get()
            ->keyBy('jour_semaine_iso');

        if ($windowsByDay->isEmpty()) {
            return 0.0;
        }

        $holidayRecords = DB::table('sla_jours_feries')
            ->where('id_config_sla', $configSlaId)
            ->where('date_ferie', '<=', $end->toDateString())
            ->where(function($q) use ($start) {
                $q->whereNull('date_fin')->orWhere('date_fin', '>=', $start->toDateString());
            })
            ->get();

        $holidays = collect([]);
        foreach ($holidayRecords as $hr) {
            $currentDate = CarbonImmutable::parse($hr->date_ferie);
            $endHoliday = $hr->date_fin ? CarbonImmutable::parse($hr->date_fin) : $currentDate;
            
            while ($currentDate->lessThanOrEqualTo($endHoliday)) {
                $holidays->put($currentDate->toDateString(), true);
                $currentDate = $currentDate->addDay();
            }
        }

        $minutes = 0.0;
        $cursorDay = $start->startOfDay();
        $lastDay = $end->startOfDay();

        while ($cursorDay->lessThanOrEqualTo($lastDay)) {
            $isoDay = $cursorDay->isoWeekday();
            $dateKey = $cursorDay->toDateString();
            $isHoliday = $holidays->has($dateKey);

            if (!$isHoliday && $windowsByDay->has($isoDay)) {
                $window = $windowsByDay->get($isoDay);
                $dayStart = $cursorDay->setTimeFromTimeString($window->heure_debut);
                $dayEnd = $cursorDay->setTimeFromTimeString($window->heure_fin);

                $segmentStart = $dayStart->greaterThan($start) ? $dayStart : $start;
                $segmentEnd = $dayEnd->lessThan($end) ? $dayEnd : $end;

                if ($segmentEnd->greaterThan($segmentStart)) {
                    $segmentMinutes = (float) $segmentStart->diffInMinutes($segmentEnd);
                    if (!$convertToBusinessHours) {
                        $minutes += $segmentMinutes;
                    } else {
                        $windowMinutes = (float) $dayStart->diffInMinutes($dayEnd);
                        if ($windowMinutes > 0) {
                            $minutes += ($segmentMinutes / $windowMinutes) * (self::BUSINESS_DAY_HOURS * 60);
                        }
                    }
                }
            }

            $cursorDay = $cursorDay->addDay();
        }

        return round($minutes / 60, 2);
    }
}
