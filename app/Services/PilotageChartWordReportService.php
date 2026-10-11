<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpWord\Element\Row;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use RuntimeException;

class PilotageChartWordReportService
{
    private const REPORTS = [
        'direction-demand-donut' => [
            'title' => 'Répartition des demandes par Direction',
            'filename' => 'repartition-demandes-par-direction.docx',
            'type' => 'direction_donut',
        ],
        'direction-sla-performance' => [
            'title' => 'Taux dans les délais par Direction',
            'filename' => 'taux-delais-par-direction.docx',
            'type' => 'direction_sla',
        ],
        'service-sla-performance' => [
            'title' => 'Taux dans les délais par Service',
            'filename' => 'taux-delais-par-service.docx',
            'type' => 'service_sla',
        ],
        'country-demand-ranking' => [
            'title' => 'Pays les plus demandeurs',
            'filename' => 'pays-plus-demandeurs.docx',
            'type' => 'country_ranking',
        ],
        'establishment-demand-ranking' => [
            'title' => 'Établissements les plus demandeurs',
            'filename' => 'etablissements-plus-demandeurs.docx',
            'type' => 'establishment_ranking',
        ],
        'reclamation-evolution' => [
            'title' => 'Réclamations reçues, clôturées et non clôturées',
            'filename' => 'evolution-reclamations.docx',
            'type' => 'reclamation_evolution',
        ],
    ];

    public function __construct(private readonly PilotageChartImageRenderer $charts) {}

    public function supports(string $section): bool
    {
        return isset(self::REPORTS[$section]);
    }

    /**
     * @return array{path: string, filename: string}
     */
    public function generate(string $section, array $overviewData, object $actor): array
    {
        $definition = self::REPORTS[$section] ?? null;
        if (! $definition) {
            throw new RuntimeException("Le graphique demandé n'est pas pris en charge.");
        }

        $report = $this->buildReportData($definition['type'], $overviewData);
        $documentPath = $this->temporaryPath('pilotage-word-', '.docx');
        $chartPath = $this->chartFile($report['chart_image']);

        try {
            $this->writeDocument(
                $documentPath,
                $definition['title'],
                $overviewData,
                $actor,
                $report,
                $chartPath,
            );
        } catch (\Throwable $exception) {
            File::delete($documentPath);
            throw $exception;
        } finally {
            if ($chartPath) {
                File::delete($chartPath);
            }
        }

        return [
            'path' => $documentPath,
            'filename' => $definition['filename'],
        ];
    }

    private function buildReportData(string $type, array $overviewData): array
    {
        return match ($type) {
            'direction_donut' => $this->directionDonutReport($overviewData),
            'direction_sla' => $this->directionSlaReport($overviewData),
            'service_sla' => $this->serviceSlaReport($overviewData),
            'country_ranking' => $this->rankingReport($overviewData, 'demandes_par_pays', 'pays', 'Pays', 'Pays'),
            'establishment_ranking' => $this->rankingReport($overviewData, 'demandes_par_etablissement', 'etablissement', 'Établissement', 'Établissements'),
            'reclamation_evolution' => $this->reclamationEvolutionReport($overviewData),
            default => throw new RuntimeException("Le type de graphique demandé n'est pas pris en charge."),
        };
    }

    private function directionDonutReport(array $overviewData): array
    {
        $rows = $this->buildDirectionDemandDonutRows($overviewData);
        $total = (int) collect($rows)->sum('total');
        $leader = collect($rows)->sortByDesc('total')->first();

        return [
            'chart_image' => $this->charts->directionDonut($rows, $total),
            'chart_shape' => 'square',
            'caption' => 'Répartition proportionnelle des demandes affectées aux directions.',
            'summary' => [
                ['label' => 'Total des demandes', 'value' => number_format($total, 0, ',', ' ')],
                ['label' => 'Directions représentées', 'value' => number_format(count($rows), 0, ',', ' ')],
                ['label' => 'Direction principale', 'value' => $leader ? (string) $leader['direction'] : '-'],
            ],
            'headers' => ['Code', 'Direction', 'Demandes', 'Part'],
            'rows' => collect($rows)->map(fn (array $row) => [
                'color' => $row['color'],
                'cells' => [
                    $row['direction'],
                    $row['direction_libelle'],
                    number_format((int) $row['total'], 0, ',', ' '),
                    number_format((float) $row['percent'], 1, ',', ' ').' %',
                ],
            ])->all(),
        ];
    }

    private function directionSlaReport(array $overviewData): array
    {
        $rows = $this->buildDirectionSlaRows($overviewData);

        return [
            'chart_image' => $this->charts->slaBars($rows, 'direction'),
            'chart_shape' => 'wide',
            'caption' => 'Taux de traitement dans les délais pour chaque direction disposant de demandes clôturées.',
            'summary' => $this->slaSummary($rows),
            'headers' => ['Code', 'Direction', 'Clôturées', 'Dans les délais', 'Taux'],
            'rows' => collect($rows)->map(fn (array $row) => [
                'color' => $row['color'],
                'cells' => [
                    $row['direction'],
                    $row['direction_libelle'],
                    number_format((int) $row['total_cloturees'], 0, ',', ' '),
                    number_format((int) $row['total_cloturees_delai'], 0, ',', ' '),
                    number_format((float) $row['taux'], 1, ',', ' ').' %',
                ],
            ])->all(),
        ];
    }

    private function serviceSlaReport(array $overviewData): array
    {
        $rows = $this->buildServiceSlaRows($overviewData);

        return [
            'chart_image' => $this->charts->slaBars($rows, 'service'),
            'chart_shape' => 'wide',
            'caption' => 'Taux de traitement dans les délais pour chaque service disposant de demandes clôturées.',
            'summary' => $this->slaSummary($rows),
            'headers' => ['Code', 'Service', 'Direction', 'Clôturées', 'Dans les délais', 'Taux'],
            'rows' => collect($rows)->map(fn (array $row) => [
                'color' => $row['color'],
                'cells' => [
                    $row['service'],
                    $row['service_libelle'],
                    $row['direction'],
                    number_format((int) $row['total_cloturees'], 0, ',', ' '),
                    number_format((int) $row['total_cloturees_delai'], 0, ',', ' '),
                    number_format((float) $row['taux'], 1, ',', ' ').' %',
                ],
            ])->all(),
        ];
    }

    private function slaSummary(array $rows): array
    {
        $closed = (int) collect($rows)->sum('total_cloturees');
        $withinDeadline = (int) collect($rows)->sum('total_cloturees_delai');
        $rate = $closed > 0 ? ($withinDeadline / $closed) * 100 : 0;

        return [
            ['label' => 'Entités analysées', 'value' => number_format(count($rows), 0, ',', ' ')],
            ['label' => 'Demandes clôturées', 'value' => number_format($closed, 0, ',', ' ')],
            ['label' => 'Taux global', 'value' => number_format($rate, 1, ',', ' ').' %'],
        ];
    }

    private function rankingReport(
        array $overviewData,
        string $datasetKey,
        string $labelKey,
        string $heading,
        string $pluralHeading,
    ): array {
        $rows = $this->buildDemandRankingRows($overviewData, $datasetKey, $labelKey);
        $total = (int) collect($rows)->sum('total_demandes');
        $leader = $rows[0] ?? null;

        return [
            'chart_image' => $this->charts->demandRankingBars($rows),
            'chart_shape' => 'wide',
            'caption' => 'Classement des '.mb_strtolower($pluralHeading).' selon le nombre de demandes enregistrées.',
            'summary' => [
                ['label' => 'Total des demandes', 'value' => number_format($total, 0, ',', ' ')],
                ['label' => $pluralHeading.' représentés', 'value' => number_format(count($rows), 0, ',', ' ')],
                ['label' => 'Première position', 'value' => $leader ? (string) $leader['label'] : '-'],
            ],
            'headers' => ['Rang', $heading, 'Demandes'],
            'rows' => collect($rows)->map(fn (array $row, int $index) => [
                'color' => $row['color'],
                'cells' => [
                    (string) ($index + 1),
                    $row['label'],
                    number_format((int) $row['total_demandes'], 0, ',', ' '),
                ],
            ])->all(),
        ];
    }

    private function reclamationEvolutionReport(array $overviewData): array
    {
        $data = $this->buildReclamationEvolutionData($overviewData);

        return [
            'chart_image' => $this->charts->reclamationEvolution($data),
            'chart_shape' => 'wide',
            'caption' => 'Évolution comparée des réclamations reçues, clôturées et restant non clôturées.',
            'summary' => [
                ['label' => 'Réclamations reçues', 'value' => number_format((int) array_sum($data['recues']), 0, ',', ' ')],
                ['label' => 'Réclamations clôturées', 'value' => number_format((int) array_sum($data['cloturees']), 0, ',', ' ')],
                ['label' => 'Non clôturées', 'value' => number_format((int) array_sum($data['non_cloturees']), 0, ',', ' ')],
            ],
            'headers' => ['Période', 'Reçues', 'Clôturées', 'Non clôturées'],
            'rows' => collect($data['labels'])->map(fn (string $label, int $index) => [
                'color' => '#3996d3',
                'cells' => [
                    $label,
                    number_format((int) ($data['recues'][$index] ?? 0), 0, ',', ' '),
                    number_format((int) ($data['cloturees'][$index] ?? 0), 0, ',', ' '),
                    number_format((int) ($data['non_cloturees'][$index] ?? 0), 0, ',', ' '),
                ],
            ])->all(),
        ];
    }

    private function writeDocument(
        string $documentPath,
        string $title,
        array $overviewData,
        object $actor,
        array $report,
        ?string $chartPath,
    ): void {
        $author = trim((string) ($actor->prenom ?? '').' '.(string) ($actor->nom ?? '')) ?: 'ANBG';
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);
        $phpWord->getDocInfo()
            ->setCreator($author)
            ->setCompany('Agence Nationale des Bourses du Gabon')
            ->setTitle($title)
            ->setSubject('Rapport graphique de pilotage des réclamations')
            ->setDescription('Rapport généré depuis la plateforme de suivi des réclamations de l’ANBG.');

        $phpWord->addParagraphStyle('Title', [
            'alignment' => 'left',
            'spaceBefore' => 0,
            'spaceAfter' => 140,
            'keepNext' => true,
        ]);
        $phpWord->addParagraphStyle('ReportHeading', [
            'alignment' => 'left',
            'spaceBefore' => 150,
            'spaceAfter' => 90,
            'keepNext' => true,
        ]);
        $phpWord->addTableStyle('ReportData', [
            'borderSize' => 5,
            'borderColor' => 'D6DEE8',
            'cellMargin' => 80,
        ]);

        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'pageSizeW' => Converter::inchToTwip(11),
            'pageSizeH' => Converter::inchToTwip(8.5),
            'marginTop' => Converter::inchToTwip(0.85),
            'marginBottom' => Converter::inchToTwip(0.58),
            'marginLeft' => Converter::inchToTwip(0.68),
            'marginRight' => Converter::inchToTwip(0.68),
            'headerHeight' => Converter::inchToTwip(0.18),
            'footerHeight' => Converter::inchToTwip(0.3),
        ]);

        $this->addHeaderAndFooter($section);

        $section->addText('RAPPORT DE PILOTAGE', [
            'name' => 'Arial',
            'size' => 9,
            'bold' => true,
            'color' => '42752C',
            'allCaps' => true,
        ], ['spaceAfter' => 70]);
        $section->addText($title, [
            'name' => 'Arial',
            'size' => 22,
            'bold' => true,
            'color' => '111827',
        ], 'Title');
        $section->addText(
            'Synthèse graphique issue des données de la plateforme de suivi des réclamations.',
            ['name' => 'Arial', 'size' => 10, 'color' => '596273'],
            ['spaceAfter' => 130],
        );

        $this->addMetadataTable($section, $overviewData, $author);
        $this->addSummary($section, $report['summary']);

        $section->addText('Visualisation', [
            'name' => 'Arial',
            'size' => 13,
            'bold' => true,
            'color' => '111827',
        ], 'ReportHeading');

        if ($chartPath) {
            $section->addImage($chartPath, $this->chartImageStyle($chartPath, $report['chart_shape']));
            $section->addText(
                $report['caption'],
                ['name' => 'Arial', 'size' => 8.5, 'italic' => true, 'color' => '667085'],
                ['alignment' => 'center', 'spaceBefore' => 70, 'spaceAfter' => 0],
            );
        } else {
            $section->addText(
                'Aucune donnée disponible pour les filtres sélectionnés.',
                ['name' => 'Arial', 'size' => 11, 'italic' => true, 'color' => '667085'],
                ['alignment' => 'center', 'spaceBefore' => 180, 'spaceAfter' => 180],
            );
        }

        $section->addText('Données détaillées', [
            'name' => 'Arial',
            'size' => 15,
            'bold' => true,
            'color' => '111827',
        ], 'ReportHeading');
        $section->addText(
            'Les valeurs ci-dessous correspondent exactement aux données utilisées pour produire le graphique.',
            ['name' => 'Arial', 'size' => 9.5, 'color' => '596273'],
            ['spaceAfter' => 120],
        );

        $this->addDataTable($section, $report['headers'], $report['rows']);

        IOFactory::createWriter($phpWord, 'Word2007')->save($documentPath);
    }

    private function addHeaderAndFooter(Section $section): void
    {
        $header = $section->addHeader();
        $logoPath = public_path('Logo_anbg.png');
        if (File::isFile($logoPath)) {
            $header->addImage($logoPath, [
                'width' => 70,
                'alignment' => 'left',
                'marginTop' => 0,
                'marginLeft' => 0,
                'wrappingStyle' => 'inline',
            ]);
        }

        $footer = $section->addFooter();
        $footer->addPreserveText(
            'Agence Nationale des Bourses du Gabon  |  Contrôle interne et qualité  |  Page {PAGE} sur {NUMPAGES}',
            ['name' => 'Arial', 'size' => 8, 'color' => '667085'],
            ['alignment' => 'center'],
        );
    }

    private function addMetadataTable(Section $section, array $overviewData, string $author): void
    {
        $filters = $overviewData['filters_appliques'] ?? [];
        $period = $this->periodLabel($filters);
        $direction = $this->catalogueLabel(
            $overviewData['catalogues']['directions'] ?? [],
            'id_direction',
            $filters['direction_id'] ?? null,
        );
        $service = $this->catalogueLabel(
            $overviewData['catalogues']['services'] ?? [],
            'id_service',
            $filters['service_id'] ?? null,
        );

        $table = $section->addTable([
            'borderSize' => 4,
            'borderColor' => 'D6DEE8',
            'cellMargin' => 80,
        ]);
        $row = $table->addRow(null, ['cantSplit' => true]);
        $this->addMetadataCell($row, 'Période', $period);
        $this->addMetadataCell($row, 'Direction', $direction);
        $this->addMetadataCell($row, 'Service', $service);
        $this->addMetadataCell($row, 'Généré par', $author);
    }

    private function addMetadataCell(Row $row, string $label, string $value): void
    {
        $cell = $row->addCell(2400, ['bgColor' => 'F5F8FB', 'valign' => 'center']);
        $cell->addText($label, [
            'name' => 'Arial',
            'size' => 7.5,
            'bold' => true,
            'color' => '667085',
            'allCaps' => true,
        ], ['spaceAfter' => 35]);
        $cell->addText($value, [
            'name' => 'Arial',
            'size' => 9.5,
            'bold' => true,
            'color' => '1C203D',
        ]);
    }

    private function addSummary(Section $section, array $summary): void
    {
        $table = $section->addTable(['cellMargin' => 70]);
        $row = $table->addRow(null, ['cantSplit' => true]);

        foreach ($summary as $index => $item) {
            $colors = ['EAF4FB', 'EFF7E8', 'FFF7E3'];
            $cell = $row->addCell(3200, [
                'bgColor' => $colors[$index % count($colors)],
                'valign' => 'center',
            ]);
            $cell->addText((string) $item['label'], [
                'name' => 'Arial',
                'size' => 8,
                'bold' => true,
                'color' => '596273',
            ], ['alignment' => 'center', 'spaceAfter' => 25]);
            $cell->addText((string) $item['value'], [
                'name' => 'Arial',
                'size' => 12,
                'bold' => true,
                'color' => '1C203D',
            ], ['alignment' => 'center']);
        }
    }

    private function addDataTable(Section $section, array $headers, array $rows): void
    {
        $table = $section->addTable('ReportData');
        $headerRow = $table->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);

        foreach ($headers as $header) {
            $cell = $headerRow->addCell(null, ['bgColor' => '1C203D', 'valign' => 'center']);
            $cell->addText((string) $header, [
                'name' => 'Arial',
                'size' => 8.5,
                'bold' => true,
                'color' => 'FFFFFF',
            ], ['alignment' => 'center']);
        }

        if (empty($rows)) {
            $cell = $table->addRow()->addCell(null, [
                'gridSpan' => count($headers),
                'bgColor' => 'F8FAFC',
                'valign' => 'center',
            ]);
            $cell->addText(
                'Aucune donnée disponible pour les filtres sélectionnés.',
                ['name' => 'Arial', 'size' => 9.5, 'italic' => true, 'color' => '667085'],
                ['alignment' => 'center'],
            );

            return;
        }

        foreach ($rows as $index => $item) {
            $row = $table->addRow(null, ['cantSplit' => true]);
            $background = $index % 2 === 0 ? 'FFFFFF' : 'F5F8FB';

            foreach ($item['cells'] as $cellIndex => $value) {
                $cell = $row->addCell(null, ['bgColor' => $background, 'valign' => 'center']);
                $run = $cell->addTextRun(['alignment' => $cellIndex === 0 ? 'left' : 'center']);

                if ($cellIndex === 0) {
                    $run->addText('■ ', [
                        'name' => 'Arial',
                        'size' => 8,
                        'color' => ltrim((string) ($item['color'] ?? '#3996d3'), '#'),
                    ]);
                }

                $run->addText((string) $value, [
                    'name' => 'Arial',
                    'size' => 8.5,
                    'color' => '273142',
                ]);
            }
        }
    }

    private function chartImageStyle(string $path, string $shape): array
    {
        $dimensions = getimagesize($path);
        $width = (int) ($dimensions[0] ?? 1);
        $height = (int) ($dimensions[1] ?? 1);
        $maxWidth = $shape === 'square' ? 330 : 700;
        $maxHeight = $shape === 'square' ? 330 : 345;
        $scale = min($maxWidth / max(1, $width), $maxHeight / max(1, $height), 1);

        return [
            'width' => max(1, (int) round($width * $scale)),
            'height' => max(1, (int) round($height * $scale)),
            'alignment' => 'center',
            'wrappingStyle' => 'inline',
        ];
    }

    private function periodLabel(array $filters): string
    {
        $periodLabels = [
            'all' => 'Toutes les périodes',
            'today' => "Aujourd'hui",
            'week' => 'Semaine en cours',
            'month' => 'Mois en cours',
            'quarter' => 'Trimestre en cours',
            'year' => 'Année en cours',
            'custom' => 'Période personnalisée',
        ];
        $label = $periodLabels[(string) ($filters['periode'] ?? 'all')] ?? 'Période sélectionnée';
        $start = $this->formatDate($filters['date_from'] ?? null);
        $end = $this->formatDate($filters['date_to'] ?? null);

        return $start && $end ? "{$label} · {$start} au {$end}" : $label;
    }

    private function formatDate(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return null;
        }
    }

    private function catalogueLabel(array $items, string $idKey, mixed $selectedId): string
    {
        if (! $selectedId) {
            return 'Toutes';
        }

        $item = collect($items)->first(function ($candidate) use ($idKey, $selectedId): bool {
            $candidate = (array) $candidate;

            return (int) ($candidate[$idKey] ?? 0) === (int) $selectedId;
        });

        if (! $item) {
            return 'Sélection appliquée';
        }

        $item = (array) $item;
        $code = trim((string) ($item['code'] ?? ''));
        $label = trim((string) ($item['libelle'] ?? ''));

        return trim($code.($code && $label ? ' - ' : '').$label) ?: 'Sélection appliquée';
    }

    private function temporaryPath(string $prefix, string $extension): string
    {
        $directory = storage_path('app/private/exports');
        File::ensureDirectoryExists($directory);
        $temporary = tempnam($directory, $prefix);

        if ($temporary === false) {
            throw new RuntimeException("Impossible de préparer le fichier d'export Word.");
        }

        $path = $temporary.$extension;
        if (! rename($temporary, $path)) {
            File::delete($temporary);
            throw new RuntimeException("Impossible de préparer le fichier d'export Word.");
        }

        return $path;
    }

    private function chartFile(?string $dataUri): ?string
    {
        if (! $dataUri || ! str_starts_with($dataUri, 'data:image/png;base64,')) {
            return null;
        }

        $contents = base64_decode(substr($dataUri, strlen('data:image/png;base64,')), true);
        if ($contents === false) {
            return null;
        }

        $path = $this->temporaryPath('pilotage-chart-', '.png');
        if (File::put($path, $contents) === false) {
            File::delete($path);

            return null;
        }

        return $path;
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
                    && ! str_contains($label, 'non affect');
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
                $rate = (float) ($row['taux_reponse_dans_delais'] ?? 0);

                return [
                    'direction' => trim((string) ($row['direction_code'] ?? ($row['direction'] ?? '-'))) ?: '-',
                    'direction_libelle' => trim((string) ($row['direction'] ?? '-')) ?: '-',
                    'total_cloturees' => (int) ($row['total_cloturees'] ?? 0),
                    'total_cloturees_delai' => (int) ($row['total_cloturees_delai'] ?? 0),
                    'taux' => round($rate, 1),
                    'color' => $rate >= 80 ? '#34d399' : ($rate >= 60 ? '#fbbf24' : '#fb7185'),
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
                $rate = (float) ($row['taux_reponse_dans_delais'] ?? 0);

                return [
                    'service' => trim((string) ($row['service_code'] ?? ($row['service'] ?? '-'))) ?: '-',
                    'service_libelle' => trim((string) ($row['service'] ?? '-')) ?: '-',
                    'direction' => trim((string) ($row['direction'] ?? '-')) ?: '-',
                    'total_cloturees' => (int) ($row['total_cloturees'] ?? 0),
                    'total_cloturees_delai' => (int) ($row['total_cloturees_delai'] ?? 0),
                    'taux' => round($rate, 1),
                    'color' => $rate >= 80 ? '#34d399' : ($rate >= 60 ? '#fbbf24' : '#fb7185'),
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
        $received = collect($evolution['series']['reclamations_recues'] ?? [])->map(fn ($value) => (int) $value)->values();
        $closed = collect($evolution['series']['demandes_cloturees'] ?? [])->map(fn ($value) => (int) $value)->values();
        $open = collect($evolution['series']['reclamations_non_cloturees'] ?? [])->map(fn ($value) => (int) $value)->values();

        if ($open->isEmpty() && $received->isNotEmpty()) {
            $open = $received
                ->map(fn (int $value, int $index) => max(0, $value - (int) ($closed[$index] ?? 0)))
                ->values();
        }

        return [
            'labels' => collect($evolution['labels'] ?? [])->map(fn ($value) => (string) $value)->values()->all(),
            'recues' => $received->all(),
            'cloturees' => $closed->all(),
            'non_cloturees' => $open->all(),
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
}
