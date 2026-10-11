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
        return $this->calculateElapsedWorkedHours($startAt, $endAt, $configSlaId);
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

    public function businessDayWorkedHours(int $configSlaId): float
    {
        $windows = DB::table('sla_jours_ouvres')
            ->where('id_config_sla', $configSlaId)
            ->where('actif', true)
            ->get(['heure_debut', 'heure_fin']);

        if ($windows->isEmpty()) {
            return 8.0;
        }

        $durations = $windows
            ->map(function (object $window): float {
                $start = CarbonImmutable::parse($window->heure_debut);
                $end = CarbonImmutable::parse($window->heure_fin);

                return max(0, $start->diffInMinutes($end)) / 60;
            })
            ->filter(fn (float $hours): bool => $hours > 0)
            ->values();

        if ($durations->isEmpty()) {
            return 8.0;
        }

        return round((float) $durations->avg(), 2);
    }

    public function classifyAlert(float $elapsedHours, int $maxHours = 24, ?float $warningHours = null): string
    {
        $deadline = max(1.0, (float) $maxHours);
        $warning = $warningHours !== null
            ? max(0.0, min((float) $warningHours, $deadline))
            : round($deadline / 2, 2);

        if ($elapsedHours > $deadline) {
            return 'en_retard';
        }

        if ($elapsedHours >= $warning) {
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
            ->where(function ($query) use ($start) {
                $query
                    ->whereNull('date_fin')
                    ->orWhere('date_fin', '>=', $start->toDateString());
            })
            ->get();

        $holidays = collect();
        foreach ($holidayRecords as $holidayRecord) {
            $currentDate = CarbonImmutable::parse($holidayRecord->date_ferie);
            $endHoliday = $holidayRecord->date_fin
                ? CarbonImmutable::parse($holidayRecord->date_fin)
                : $currentDate;

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
