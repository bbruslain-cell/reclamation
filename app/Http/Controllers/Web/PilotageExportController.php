<?php

namespace App\Http\Controllers\Web;

use App\Exports\PilotageTableExport;
use App\Http\Controllers\Api\OverviewController as ApiOverviewController;
use App\Http\Controllers\Controller;
use App\Services\AccessControlService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class PilotageExportController extends Controller
{
    public function download(Request $request, AccessControlService $access, string $section, string $format): Response|BinaryFileResponse
    {
        abort_unless(in_array($format, ['pdf', 'xlsx'], true), 404);

        $actor = $access->resolveActor($request);
        if (!$actor) {
            abort(401);
        }

        Gate::forUser($actor)->authorize('dashboard.export');

        if ($section === 'ciq-tracking') {
            $request->merge([
                'tracking_all' => true,
                'tracking_limit' => 5000,
            ]);
        }

        $overviewData = app(ApiOverviewController::class)->index($request, $access)->getData(true);

        if ($section === 'direction-demand-donut') {
            abort_unless($format === 'pdf', 404);

            $downloadName = 'repartition-demandes-par-direction.pdf';
            $rows = $this->buildDirectionDemandDonutRows($overviewData);
            $total = (int) collect($rows)->sum('total');

            $pdf = Pdf::loadView('exports.pilotage.direction-donut', [
                'title' => 'Répartition des demandes par Direction',
                'rows' => $rows,
                'total' => $total,
                'chartImage' => $this->buildDirectionDonutImage($rows, $total),
            ])->setPaper('a4', 'portrait');

            $response = $pdf->download($downloadName);

            $this->traceExport($actor, $format, $downloadName, $section, $request);

            return $response;
        }

        if ($section === 'direction-sla-performance') {
            abort_unless($format === 'pdf', 404);

            $downloadName = 'taux-delais-par-direction.pdf';
            $rows = $this->buildDirectionSlaRows($overviewData);

            $pdf = Pdf::loadView('exports.pilotage.chart', [
                'title' => 'Taux dans les délais par Direction',
                'chartImage' => $this->buildDirectionSlaBarImage($rows),
                'legendHeaders' => ['Code direction', 'Clôturées', 'Dans les délais', 'Taux'],
                'legendRows' => collect($rows)->map(fn (array $row) => [
                    'color' => $row['color'],
                    'cells' => [
                        $row['direction'],
                        number_format((int) $row['total_cloturees'], 0, ',', ' '),
                        number_format((int) $row['total_cloturees_delai'], 0, ',', ' '),
                        number_format((float) $row['taux'], 1, ',', ' ').' %',
                    ],
                ])->all(),
            ])->setPaper('a4', 'landscape');

            $response = $pdf->download($downloadName);

            $this->traceExport($actor, $format, $downloadName, $section, $request);

            return $response;
        }

        if ($section === 'service-sla-performance') {
            abort_unless($format === 'pdf', 404);

            $downloadName = 'taux-delais-par-service.pdf';
            $rows = $this->buildServiceSlaRows($overviewData);

            $pdf = Pdf::loadView('exports.pilotage.chart', [
                'title' => 'Taux dans les délais par Service',
                'chartImage' => $this->buildServiceSlaBarImage($rows),
                'legendHeaders' => ['Code service', 'Clôturées', 'Dans les délais', 'Taux'],
                'legendRows' => collect($rows)->map(fn (array $row) => [
                    'color' => $row['color'],
                    'cells' => [
                        $row['service'],
                        number_format((int) $row['total_cloturees'], 0, ',', ' '),
                        number_format((int) $row['total_cloturees_delai'], 0, ',', ' '),
                        number_format((float) $row['taux'], 1, ',', ' ').' %',
                    ],
                ])->all(),
            ])->setPaper('a4', 'landscape');

            $response = $pdf->download($downloadName);

            $this->traceExport($actor, $format, $downloadName, $section, $request);

            return $response;
        }

        if ($section === 'country-demand-ranking') {
            abort_unless($format === 'pdf', 404);

            $downloadName = 'pays-plus-demandeurs.pdf';
            $rows = $this->buildDemandRankingRows($overviewData, 'demandes_par_pays', 'pays');

            $pdf = Pdf::loadView('exports.pilotage.chart', [
                'title' => 'Pays les plus demandeurs',
                'chartImage' => $this->buildDemandRankingBarImage($rows),
                'legendHeaders' => ['Pays', 'Demandes'],
                'legendRows' => collect($rows)->map(fn (array $row) => [
                    'color' => $row['color'],
                    'cells' => [
                        $row['label'],
                        number_format((int) $row['total_demandes'], 0, ',', ' '),
                    ],
                ])->all(),
            ])->setPaper('a4', 'landscape');

            $response = $pdf->download($downloadName);

            $this->traceExport($actor, $format, $downloadName, $section, $request);

            return $response;
        }

        if ($section === 'establishment-demand-ranking') {
            abort_unless($format === 'pdf', 404);

            $downloadName = 'etablissements-plus-demandeurs.pdf';
            $rows = $this->buildDemandRankingRows($overviewData, 'demandes_par_etablissement', 'etablissement');

            $pdf = Pdf::loadView('exports.pilotage.chart', [
                'title' => 'Établissements les plus demandeurs',
                'chartImage' => $this->buildDemandRankingBarImage($rows),
                'legendHeaders' => ['Établissement', 'Demandes'],
                'legendRows' => collect($rows)->map(fn (array $row) => [
                    'color' => $row['color'],
                    'cells' => [
                        $row['label'],
                        number_format((int) $row['total_demandes'], 0, ',', ' '),
                    ],
                ])->all(),
            ])->setPaper('a4', 'landscape');

            $response = $pdf->download($downloadName);

            $this->traceExport($actor, $format, $downloadName, $section, $request);

            return $response;
        }

        if ($section === 'reclamation-evolution') {
            abort_unless($format === 'pdf', 404);

            $downloadName = 'evolution-reclamations.pdf';
            $chartData = $this->buildReclamationEvolutionData($overviewData);

            $pdf = Pdf::loadView('exports.pilotage.chart', [
                'title' => 'Réclamations reçues, clôturées et non clôturées',
                'chartImage' => $this->buildReclamationEvolutionImage($chartData),
                'legendHeaders' => ['Série', 'Total'],
                'legendRows' => [
                    [
                        'color' => '#3996d3',
                        'cells' => ['Réclamations reçues', number_format((int) array_sum($chartData['recues']), 0, ',', ' ')],
                    ],
                    [
                        'color' => '#8fc043',
                        'cells' => ['Réclamations clôturées', number_format((int) array_sum($chartData['cloturees']), 0, ',', ' ')],
                    ],
                    [
                        'color' => '#f59e0b',
                        'cells' => ['Réclamations non clôturées', number_format((int) array_sum($chartData['non_cloturees']), 0, ',', ' ')],
                    ],
                ],
            ])->setPaper('a4', 'landscape');

            $response = $pdf->download($downloadName);

            $this->traceExport($actor, $format, $downloadName, $section, $request);

            return $response;
        }

        $payload = $this->buildPayload($section, $overviewData);
        $extension = $format === 'xlsx' ? 'xlsx' : 'pdf';
        $downloadName = $payload['filename'].'.'.$extension;

        if ($format === 'xlsx') {
            $response = Excel::download(
                new PilotageTableExport(
                    $payload['sections'],
                    $payload['title'],
                    $payload['sheet_title'] ?? $payload['filename']
                ),
                $downloadName
            );

            $this->traceExport($actor, $format, $downloadName, $section, $request);

            return $response;
        }

        $pdf = Pdf::loadView('exports.pilotage.tables', [
            'title' => $payload['title'],
            'sections' => $payload['sections'],
            'forPdf' => true,
        ])->setPaper('a4', $payload['orientation'] ?? 'landscape');

        $response = $pdf->download($downloadName);

        $this->traceExport($actor, $format, $downloadName, $section, $request);

        return $response;
    }

    private function traceExport(object $actor, string $format, string $downloadName, string $section, Request $request): void
    {
        $formatCode = $format === 'xlsx' ? 'excel' : $format;
        $formatId = DB::table('parametres')
            ->where('famille', 'format_export')
            ->where('code', $formatCode)
            ->value('id_parametre');

        if (!$formatId) {
            $formatId = DB::table('parametres')->insertGetId([
                'famille' => 'format_export',
                'code' => $formatCode,
                'libelle' => strtoupper($formatCode),
                'ordre_affichage' => $formatCode === 'excel' ? 1 : 2,
                'actif' => true,
                'date_debut_validite' => now()->toDateString(),
                'metadata_json' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'id_parametre');
        }

        DB::table('exports')->insert([
            'id_utilisateur' => (int) $actor->id_utilisateur,
            'id_format_export' => (int) $formatId,
            'fichier_export' => $downloadName,
            'filtres_appliques' => json_encode([
                'section' => $section,
                'format' => $format,
                'query' => $request->query(),
            ], JSON_UNESCAPED_UNICODE),
            'date_generation' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function buildPayload(string $section, array $overviewData): array
    {
        return match ($section) {
            'ciq-tracking' => $this->buildCiqTrackingPayload($overviewData),
            'annexe2' => $this->buildAnnexe2Payload($overviewData),
            'function-distribution' => $this->buildFunctionDistributionPayload(
                $overviewData['annexes_fonctions']['reclamations'] ?? [],
                "Tableau de répartition de l'ensemble des réclamations de la cellule par direction.",
                'tableau-repartition-reclamations-par-direction'
            ),
            'reclamation-service-distribution' => $this->buildServiceDistributionPayload(
                $overviewData['annexes_services']['reclamations'] ?? [],
                'Tableau de répartition des réclamations par service.',
                'tableau-repartition-reclamations-par-service'
            ),
            default => abort(404),
        };
    }

    private function buildDirectionDemandDonutRows(array $overviewData): array
    {
        $palette = ['#60a5fa', '#f472b6', '#fbbf24', '#2dd4bf', '#a78bfa', '#fb7185', '#34d399', '#38bdf8', '#f59e0b', '#818cf8'];

        $rows = collect($overviewData['par_direction'] ?? [])
            ->map(function (array $row): array {
                $direction = trim((string) ($row['direction_code'] ?? ($row['direction'] ?? '-')));

                return [
                    'direction' => $direction !== '' ? $direction : '-',
                    'direction_libelle' => trim((string) ($row['direction'] ?? '-')) ?: '-',
                    'total' => (int) ($row['total'] ?? 0),
                ];
            })
            ->filter(function (array $row): bool {
                $label = mb_strtolower($row['direction']);

                return $row['total'] > 0
                    && $row['direction'] !== '-'
                    && !str_contains($label, 'non affect');
            })
            ->values();

        $total = (int) $rows->sum('total');

        return $rows
            ->map(function (array $row, int $index) use ($palette, $total): array {
                $row['color'] = $palette[$index % count($palette)];
                $row['percent'] = $total > 0 ? round(($row['total'] / $total) * 100, 1) : 0.0;

                return $row;
            })
            ->all();
    }

    private function buildDirectionSlaRows(array $overviewData): array
    {
        return collect($overviewData['performance_directions'] ?? [])
            ->map(function (array $row): array {
                $taux = (float) ($row['taux_reponse_dans_delais'] ?? 0);

                return [
                    'direction' => trim((string) ($row['direction_code'] ?? ($row['direction'] ?? '-'))) ?: '-',
                    'direction_libelle' => trim((string) ($row['direction'] ?? '-')) ?: '-',
                    'total_cloturees' => (int) ($row['total_cloturees'] ?? 0),
                    'total_cloturees_delai' => (int) ($row['total_cloturees_delai'] ?? 0),
                    'taux' => round($taux, 1),
                    'color' => $taux >= 80 ? '#34d399' : ($taux >= 60 ? '#fbbf24' : '#fb7185'),
                ];
            })
            ->filter(fn (array $row): bool => $row['direction'] !== '-' && $row['total_cloturees'] > 0)
            ->sortByDesc('taux')
            ->values()
            ->all();
    }

    private function buildServiceSlaRows(array $overviewData): array
    {
        return collect($overviewData['kpi_services'] ?? [])
            ->map(function (array $row): array {
                $taux = (float) ($row['taux_reponse_dans_delais'] ?? 0);

                return [
                    'service' => trim((string) ($row['service_code'] ?? ($row['service'] ?? '-'))) ?: '-',
                    'service_libelle' => trim((string) ($row['service'] ?? '-')) ?: '-',
                    'direction' => trim((string) ($row['direction'] ?? '-')) ?: '-',
                    'total_cloturees' => (int) ($row['total_cloturees'] ?? 0),
                    'total_cloturees_delai' => (int) ($row['total_cloturees_delai'] ?? 0),
                    'taux' => round($taux, 1),
                    'color' => $taux >= 80 ? '#34d399' : ($taux >= 60 ? '#fbbf24' : '#fb7185'),
                ];
            })
            ->filter(fn (array $row): bool => $row['service'] !== '-' && $row['total_cloturees'] > 0)
            ->sortByDesc('taux')
            ->values()
            ->all();
    }

    private function buildReclamationEvolutionData(array $overviewData): array
    {
        $evolution = $overviewData['evolution_temporelle'] ?? [];

        $recues = collect($evolution['series']['reclamations_recues'] ?? [])->map(fn ($value) => (int) $value)->values();
        $cloturees = collect($evolution['series']['demandes_cloturees'] ?? [])->map(fn ($value) => (int) $value)->values();
        $nonCloturees = collect($evolution['series']['reclamations_non_cloturees'] ?? [])->map(fn ($value) => (int) $value)->values();

        if ($nonCloturees->isEmpty() && $recues->isNotEmpty()) {
            $nonCloturees = $recues
                ->map(fn (int $value, int $index) => max(0, $value - (int) ($cloturees[$index] ?? 0)))
                ->values();
        }

        return [
            'labels' => collect($evolution['labels'] ?? [])->map(fn ($value) => (string) $value)->values()->all(),
            'recues' => $recues->all(),
            'cloturees' => $cloturees->all(),
            'non_cloturees' => $nonCloturees->all(),
        ];
    }

    private function buildDemandRankingRows(array $overviewData, string $datasetKey, string $labelKey): array
    {
        $palette = ['#3996d3', '#8fc043', '#f59e0b', '#a78bfa', '#fb7185', '#2dd4bf', '#818cf8', '#f472b6', '#34d399', '#38bdf8', '#fbbf24', '#60a5fa'];

        return collect($overviewData[$datasetKey] ?? [])
            ->map(function (array $row, int $index) use ($palette, $labelKey): array {
                return [
                    'label' => trim((string) ($row[$labelKey] ?? '-')) ?: '-',
                    'total_demandes' => (int) ($row['total_demandes'] ?? 0),
                    'color' => $palette[$index % count($palette)],
                ];
            })
            ->filter(fn (array $row): bool => $row['label'] !== '-' && $row['total_demandes'] > 0)
            ->values()
            ->all();
    }

    private function buildDirectionSlaBarImage(array $rows): ?string
    {
        return $this->buildSlaBarImage($rows, 'direction');
    }

    private function buildServiceSlaBarImage(array $rows): ?string
    {
        return $this->buildSlaBarImage($rows, 'service');
    }

    private function buildSlaBarImage(array $rows, string $labelKey): ?string
    {
        if (empty($rows) || !extension_loaded('gd')) {
            return null;
        }

        $width = 1120;
        $rowHeight = 58;
        $height = max(460, 150 + (count($rows) * $rowHeight));
        $left = 360;
        $right = 90;
        $top = 72;
        $chartWidth = $width - $left - $right;
        $image = imagecreatetruecolor($width, $height);

        if (function_exists('imageantialias')) {
            imageantialias($image, true);
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $navy = $this->gdColor($image, '#1c203d');
        $muted = $this->gdColor($image, '#667085');
        $grid = $this->gdColor($image, '#e5edf5');
        $barBackground = $this->gdColor($image, '#eef4fb');
        imagefill($image, 0, 0, $white);

        for ($step = 0; $step <= 100; $step += 25) {
            $x = $left + (int) round(($step / 100) * $chartWidth);
            imageline($image, $x, $top - 24, $x, $height - 70, $grid);
            $this->drawGdText($image, $step.' %', 11, $x - 16, $height - 42, $muted);
        }

        foreach ($rows as $index => $row) {
            $y = $top + ($index * $rowHeight);
            $barY = $y + 12;
            $barHeight = 22;
            $filledWidth = (int) round($chartWidth * min(100, max(0, (float) $row['taux'])) / 100);
            $color = $this->gdColor($image, (string) $row['color']);

            $this->drawGdText($image, $this->truncateChartText((string) ($row[$labelKey] ?? '-'), 40), 13, 34, $barY + 17, $navy);
            imagefilledrectangle($image, $left, $barY, $left + $chartWidth, $barY + $barHeight, $barBackground);

            if ($filledWidth > 0) {
                imagefilledrectangle($image, $left, $barY, $left + $filledWidth, $barY + $barHeight, $color);
            }

            $this->drawGdText($image, number_format((float) $row['taux'], 1, ',', ' ').' %', 12, $left + $chartWidth + 18, $barY + 17, $navy, true);
        }

        return $this->pngDataUri($image);
    }

    private function buildDemandRankingBarImage(array $rows): ?string
    {
        if (empty($rows) || !extension_loaded('gd')) {
            return null;
        }

        $width = 1120;
        $rowHeight = 58;
        $height = max(460, 150 + (count($rows) * $rowHeight));
        $left = 430;
        $right = 100;
        $top = 72;
        $chartWidth = $width - $left - $right;
        $image = imagecreatetruecolor($width, $height);

        if (function_exists('imageantialias')) {
            imageantialias($image, true);
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $navy = $this->gdColor($image, '#1c203d');
        $muted = $this->gdColor($image, '#667085');
        $grid = $this->gdColor($image, '#e5edf5');
        $barBackground = $this->gdColor($image, '#eef4fb');
        imagefill($image, 0, 0, $white);

        $maxValue = max(1, (int) collect($rows)->max('total_demandes'));
        $maxAxis = max(1, (int) ceil($maxValue / 5) * 5);

        for ($step = 0; $step <= 5; $step++) {
            $value = (int) round(($maxAxis / 5) * $step);
            $x = $left + (int) round(($value / $maxAxis) * $chartWidth);
            imageline($image, $x, $top - 24, $x, $height - 70, $grid);
            $this->drawGdText($image, number_format($value, 0, ',', ' '), 11, $x - 16, $height - 42, $muted);
        }

        foreach ($rows as $index => $row) {
            $y = $top + ($index * $rowHeight);
            $barY = $y + 12;
            $barHeight = 22;
            $total = max(0, (int) ($row['total_demandes'] ?? 0));
            $filledWidth = (int) round($chartWidth * min($maxAxis, $total) / $maxAxis);
            $color = $this->gdColor($image, (string) ($row['color'] ?? '#3996d3'));

            $this->drawGdText($image, $this->truncateChartText((string) ($row['label'] ?? '-'), 48), 13, 34, $barY + 17, $navy);
            imagefilledrectangle($image, $left, $barY, $left + $chartWidth, $barY + $barHeight, $barBackground);

            if ($filledWidth > 0) {
                imagefilledrectangle($image, $left, $barY, $left + $filledWidth, $barY + $barHeight, $color);
            }

            $this->drawGdText($image, number_format($total, 0, ',', ' '), 12, $left + $chartWidth + 18, $barY + 17, $navy, true);
        }

        return $this->pngDataUri($image);
    }

    private function buildReclamationEvolutionImage(array $data): ?string
    {
        $labels = $data['labels'] ?? [];
        $recues = $data['recues'] ?? [];
        $cloturees = $data['cloturees'] ?? [];
        $nonCloturees = $data['non_cloturees'] ?? [];

        if (empty($labels) || !extension_loaded('gd')) {
            return null;
        }

        $width = 1120;
        $height = 440;
        $left = 86;
        $right = 52;
        $top = 42;
        $bottom = 72;
        $chartWidth = $width - $left - $right;
        $chartHeight = $height - $top - $bottom;
        $image = imagecreatetruecolor($width, $height);

        if (function_exists('imageantialias')) {
            imageantialias($image, true);
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $navy = $this->gdColor($image, '#1c203d');
        $muted = $this->gdColor($image, '#667085');
        $grid = $this->gdColor($image, '#e5edf5');
        $sky = $this->gdColor($image, '#3996d3');
        $leaf = $this->gdColor($image, '#8fc043');
        $amber = $this->gdColor($image, '#f59e0b');
        imagefill($image, 0, 0, $white);

        $maxValue = max(1, (int) max(array_merge($recues, $cloturees, $nonCloturees, [1])));
        $maxAxis = (int) (ceil($maxValue / 5) * 5);
        $pointCount = count($labels);
        $xStep = $pointCount > 1 ? $chartWidth / ($pointCount - 1) : 0;

        for ($step = 0; $step <= 5; $step++) {
            $value = (int) round(($maxAxis / 5) * $step);
            $y = $top + $chartHeight - (int) round(($value / $maxAxis) * $chartHeight);
            imageline($image, $left, $y, $width - $right, $y, $grid);
            $this->drawGdText($image, (string) $value, 11, 28, $y + 4, $muted);
        }

        $labelSkip = max(1, (int) ceil($pointCount / 7));
        foreach ($labels as $index => $label) {
            if ($index % $labelSkip !== 0 && $index !== $pointCount - 1) {
                continue;
            }

            $x = $pointCount > 1 ? $left + (int) round($index * $xStep) : $left + (int) round($chartWidth / 2);
            $this->drawGdText($image, $this->truncateChartText((string) $label, 14), 10, $x - 26, $height - 48, $muted);
        }

        $this->drawLineSeries($image, $recues, $pointCount, $left, $top, $chartWidth, $chartHeight, $maxAxis, $sky);
        $this->drawLineSeries($image, $cloturees, $pointCount, $left, $top, $chartWidth, $chartHeight, $maxAxis, $leaf);
        $this->drawLineSeries($image, $nonCloturees, $pointCount, $left, $top, $chartWidth, $chartHeight, $maxAxis, $amber);

        imagesetthickness($image, 1);
        imageline($image, $left, $top, $left, $top + $chartHeight, $grid);
        imageline($image, $left, $top + $chartHeight, $width - $right, $top + $chartHeight, $grid);

        return $this->pngDataUri($image);
    }

    private function drawLineSeries($image, array $values, int $pointCount, int $left, int $top, int $chartWidth, int $chartHeight, int $maxAxis, int $color): void
    {
        if ($pointCount <= 0) {
            return;
        }

        $points = [];
        $xStep = $pointCount > 1 ? $chartWidth / ($pointCount - 1) : 0;

        for ($index = 0; $index < $pointCount; $index++) {
            $value = (int) ($values[$index] ?? 0);
            $x = $pointCount > 1 ? $left + (int) round($index * $xStep) : $left + (int) round($chartWidth / 2);
            $y = $top + $chartHeight - (int) round((min($value, $maxAxis) / $maxAxis) * $chartHeight);
            $points[] = [$x, $y];
        }

        imagesetthickness($image, 4);
        for ($index = 1; $index < count($points); $index++) {
            imageline($image, $points[$index - 1][0], $points[$index - 1][1], $points[$index][0], $points[$index][1], $color);
        }

        foreach ($points as [$x, $y]) {
            imagefilledellipse($image, $x, $y, 12, 12, $color);
        }

        imagesetthickness($image, 1);
    }

    private function gdColor($image, string $hex): int
    {
        [$red, $green, $blue] = $this->hexToRgb($hex);

        return imagecolorallocate($image, $red, $green, $blue);
    }

    private function drawGdText($image, string $text, int $size, int $x, int $y, int $color, bool $bold = false): void
    {
        $font = $this->chartFontPath($bold);

        if ($font) {
            imagettftext($image, $size, 0, $x, $y, $color, $font, $text);
            return;
        }

        $fallbackText = function_exists('iconv')
            ? (iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) ?: $text)
            : $text;
        imagestring($image, min(5, max(1, (int) round($size / 3))), $x, max(0, $y - $size), $fallbackText, $color);
    }

    private function chartFontPath(bool $bold = false): ?string
    {
        $candidates = $bold ? [
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf'),
            'C:\\Windows\\Fonts\\arialbd.ttf',
        ] : [
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf'),
            'C:\\Windows\\Fonts\\arial.ttf',
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function truncateChartText(string $text, int $length): string
    {
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($text) > $length ? mb_substr($text, 0, max(1, $length - 1)).'…' : $text;
        }

        return strlen($text) > $length ? substr($text, 0, max(1, $length - 1)).'...' : $text;
    }

    private function pngDataUri($image): ?string
    {
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return $png ? 'data:image/png;base64,'.base64_encode($png) : null;
    }

    private function buildDirectionDonutImage(array $rows, int $total): ?string
    {
        if ($total <= 0 || empty($rows) || !extension_loaded('gd')) {
            return null;
        }

        $size = 640;
        $center = (int) ($size / 2);
        $outerDiameter = 500;
        $innerDiameter = 245;
        $image = imagecreatetruecolor($size, $size);

        if (function_exists('imageantialias')) {
            imageantialias($image, true);
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $softBackground = imagecolorallocate($image, 245, 247, 251);
        imagefill($image, 0, 0, $white);
        imagefilledellipse($image, $center, $center, $outerDiameter + 34, $outerDiameter + 34, $softBackground);

        $startAngle = 270.0;
        $rowCount = count($rows);

        foreach ($rows as $row) {
            $rgb = $this->hexToRgb((string) ($row['color'] ?? '#3996d3'));
            $color = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);

            if ($rowCount === 1) {
                imagefilledellipse($image, $center, $center, $outerDiameter, $outerDiameter, $color);
                break;
            }

            $angle = (((int) ($row['total'] ?? 0)) / $total) * 360;
            $endAngle = $startAngle + $angle;
            $normalizedStart = fmod($startAngle, 360.0);
            $normalizedEnd = $normalizedStart + $angle;

            if ($normalizedEnd <= 360.0) {
                imagefilledarc($image, $center, $center, $outerDiameter, $outerDiameter, (int) round($normalizedStart), (int) round($normalizedEnd), $color, IMG_ARC_PIE);
            } else {
                imagefilledarc($image, $center, $center, $outerDiameter, $outerDiameter, (int) round($normalizedStart), 360, $color, IMG_ARC_PIE);
                imagefilledarc($image, $center, $center, $outerDiameter, $outerDiameter, 0, (int) round($normalizedEnd - 360.0), $color, IMG_ARC_PIE);
            }

            $startAngle = $endAngle;
        }

        imagefilledellipse($image, $center, $center, $innerDiameter, $innerDiameter, $white);

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return $png ? 'data:image/png;base64,'.base64_encode($png) : null;
    }

    private function hexToRgb(string $hex): array
    {
        $clean = ltrim($hex, '#');
        if (strlen($clean) === 3) {
            $clean = $clean[0].$clean[0].$clean[1].$clean[1].$clean[2].$clean[2];
        }

        if (strlen($clean) !== 6 || !ctype_xdigit($clean)) {
            return [57, 150, 211];
        }

        return [
            hexdec(substr($clean, 0, 2)),
            hexdec(substr($clean, 2, 2)),
            hexdec(substr($clean, 4, 2)),
        ];
    }

    private function buildCiqTrackingPayload(array $overviewData): array
    {
        $rows = collect($overviewData['tableau_suivi_annexe'] ?? [])
            ->map(fn (array $row) => [
                $row['rang'] ?? '-',
                $this->formatDate($row['date_reception'] ?? null),
                $row['expediteur'] ?? '-',
                $row['objet'] ?? '-',
                $this->formatDate($row['date_dispatching'] ?? null),
                $row['delai_transmission_oh'] ?? '-',
                $row['service_direction'] ?? '-',
                $this->formatDate($row['realisation'] ?? null),
                $row['statut_traitement'] ?? '-',
                $row['respect_delais'] ?? '-',
                $row['jours_attente'] ?? '-',
                $row['qcs'] ?? '-',
            ])
            ->all();

        return [
            'title' => "Tableau actuel du suivi des réclamations",
            'sheet_title' => "Suivi réclamations",
            'filename' => 'tableau-suivi-ciq',
            'orientation' => 'landscape',
            'sections' => [[
                'title' => "Suivi détaillé",
                'headers' => [
                    "N°",
                    "Date de réception",
                    "Expéditeur",
                    'Objet',
                    'Date de dispatch',
                    "Délais de transmission",
                    'Service',
                    "Réalisation",
                    'Statut',
                    "Respect délais",
                    "Nombre de jours d'attente",
                    'RZ / CS',
                ],
                'rows' => $rows,
            ]],
        ];
    }

    private function buildAnnexe2Payload(array $overviewData): array
    {
        $repartition = $overviewData['annexe_repartition'] ?? [];
        $reclamationRows = collect($repartition['reclamations'] ?? [])
            ->map(fn (array $row) => [
                $row['categorie'] ?? '-',
                $this->intValue($row['nombre_mails'] ?? 0),
                $this->percent($row['pourcentage'] ?? 0),
            ])->all();

        return [
            'title' => "Tableau de la répartition des réclamations les plus récurrentes dans la cellule.",
            'sheet_title' => "Réclamations récurrentes",
            'filename' => 'tableau-repartition-reclamations-recurrentes',
            'orientation' => 'landscape',
            'sections' => [
                [
                    'title' => 'RECLAMATIONS',
                    'headers' => ["Catégorie", 'Nombre de mails', '%'],
                    'rows' => $reclamationRows,
                    'footer' => [
                        'TOTAL RECLAMATIONS',
                        $this->intValue($repartition['total_reclamations'] ?? 0),
                        '',
                    ],
                ],
            ],
        ];
    }

    private function buildFunctionDistributionPayload(array $dataset, string $title, string $filename): array
    {
        $rows = collect($dataset['rows'] ?? [])
            ->map(fn (array $row) => [
                $row['fonction_code'] ?? '-',
                $this->intValue($row['total_demandes'] ?? 0),
                $this->intValue($row['total_traitees'] ?? 0),
                $this->percent($row['taux_execution'] ?? 0),
                $this->intValue($row['total_traitees_delai'] ?? 0),
                $this->percent($row['taux_conformite'] ?? 0),
                $this->percent($row['score_moyen'] ?? 0),
            ])
            ->all();

        $totals = $dataset['totaux'] ?? [];

        return [
            'title' => $title,
            'sheet_title' => "Réclamations direction",
            'filename' => $filename,
            'orientation' => 'landscape',
            'sections' => [[
                'title' => "Répartition par fonction",
                'headers' => [
                    'Fonctions',
                    'Nombre total',
                    "Nombre traité",
                    "Taux d'exécution (%)",
                    "Nombre traité dans les délais",
                    "Taux de conformité (24h) (%)",
                    '(A + B) / 2',
                ],
                'rows' => $rows,
                'footer' => [
                    'TOTAL',
                    $this->intValue($totals['total_demandes'] ?? 0),
                    $this->intValue($totals['total_traitees'] ?? 0),
                    $this->percent($totals['taux_execution'] ?? 0),
                    $this->intValue($totals['total_traitees_delai'] ?? 0),
                    $this->percent($totals['taux_conformite'] ?? 0),
                    $this->percent($totals['score_moyen'] ?? 0),
                ],
            ]],
        ];
    }

    private function buildServiceDistributionPayload(array $dataset, string $title, string $filename): array
    {
        $rows = collect($dataset['rows'] ?? [])
            ->map(fn (array $row) => [
                $row['service_code'] ?? '-',
                $row['responsable'] ?? '-',
                $this->intValue($row['total_demandes'] ?? 0),
                $this->intValue($row['total_traitees'] ?? 0),
                $this->percent($row['taux_execution'] ?? 0),
                $this->intValue($row['total_traitees_delai'] ?? 0),
                $this->percent($row['taux_conformite'] ?? 0),
            ])
            ->all();

        $totals = $dataset['totaux'] ?? [];

        return [
            'title' => $title,
            'sheet_title' => "Réclamations service",
            'filename' => $filename,
            'orientation' => 'landscape',
            'sections' => [[
                'title' => "Répartition par service",
                'headers' => [
                    "Services / Unité",
                    'Agents',
                    "Nbre de mails reçus",
                    "Nbre de mails traités",
                    "Taux d'exécution (%) (A)",
                    "Nbre de mails traités dans les délais",
                    "Taux de conformité (24h) (%) (B)",
                ],
                'rows' => $rows,
                'footer' => [
                    'TOTAL',
                    '',
                    $this->intValue($totals['total_demandes'] ?? 0),
                    $this->intValue($totals['total_traitees'] ?? 0),
                    $this->percent($totals['taux_execution'] ?? 0),
                    $this->intValue($totals['total_traitees_delai'] ?? 0),
                    $this->percent($totals['taux_conformite'] ?? 0),
                ],
            ]],
        ];
    }

    private function formatDate(null|string $value): string
    {
        if (!$value) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function percent(mixed $value): string
    {
        return number_format((float) ($value ?? 0), 1, ',', ' ').' %';
    }

    private function intValue(mixed $value): string
    {
        return number_format((int) ($value ?? 0), 0, ',', ' ');
    }
}
