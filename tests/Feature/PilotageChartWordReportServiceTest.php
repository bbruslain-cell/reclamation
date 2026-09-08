<?php

namespace Tests\Feature;

use App\Services\PilotageChartWordReportService;
use Tests\TestCase;
use ZipArchive;

class PilotageChartWordReportServiceTest extends TestCase
{
    public function test_it_generates_a_valid_word_document_for_each_dashboard_chart(): void
    {
        $service = app(PilotageChartWordReportService::class);
        $overviewData = $this->overviewData();
        $actor = (object) ['prenom' => 'Contrôle', 'nom' => 'Interne'];
        $sections = [
            'direction-demand-donut',
            'direction-sla-performance',
            'service-sla-performance',
            'country-demand-ranking',
            'establishment-demand-ranking',
            'reclamation-evolution',
        ];

        foreach ($sections as $section) {
            $report = $service->generate($section, $overviewData, $actor);
            $zip = new ZipArchive;

            try {
                $this->assertFileExists($report['path']);
                $this->assertStringEndsWith('.docx', $report['filename']);
                $this->assertSame(true, $zip->open($report['path']));
                $documentXml = $zip->getFromName('word/document.xml');
                $this->assertIsString($documentXml);
                $this->assertStringNotContainsString('w:type="page"', $documentXml);
                $this->assertNotFalse($zip->locateName('[Content_Types].xml'));
                $footerXml = $zip->getFromName('word/footer1.xml');
                $this->assertIsString($footerXml);
                $this->assertStringContainsString('Contrôle interne et qualité', $footerXml);

                $mediaCount = 0;
                for ($index = 0; $index < $zip->numFiles; $index++) {
                    $entry = $zip->getNameIndex($index);
                    if (is_string($entry) && str_starts_with($entry, 'word/media/')) {
                        $mediaCount++;
                    }
                }
                $this->assertGreaterThanOrEqual(2, $mediaCount, "Le graphique {$section} doit être intégré au document.");
            } finally {
                $zip->close();
                @unlink($report['path']);
            }
        }
    }

    private function overviewData(): array
    {
        return [
            'filters_appliques' => [
                'periode' => 'custom',
                'date_from' => '2026-01-01',
                'date_to' => '2026-09-04',
                'direction_id' => null,
                'service_id' => null,
            ],
            'catalogues' => [
                'directions' => [],
                'services' => [],
            ],
            'par_direction' => [
                ['direction_code' => 'DAF', 'direction' => 'Direction Administrative et Financière', 'total' => 45],
                ['direction_code' => 'DSI', 'direction' => "Direction des Systèmes d'Information", 'total' => 30],
            ],
            'performance_directions' => [
                ['direction_code' => 'DAF', 'direction' => 'Direction Administrative et Financière', 'total_cloturees' => 40, 'total_cloturees_delai' => 34, 'taux_reponse_dans_delais' => 85],
                ['direction_code' => 'DSI', 'direction' => "Direction des Systèmes d'Information", 'total_cloturees' => 25, 'total_cloturees_delai' => 17, 'taux_reponse_dans_delais' => 68],
            ],
            'kpi_services' => [
                ['service_code' => 'COMPTA', 'service' => 'Comptabilité', 'direction' => 'DAF', 'total_cloturees' => 22, 'total_cloturees_delai' => 19, 'taux_reponse_dans_delais' => 86.4],
                ['service_code' => 'SUPPORT', 'service' => 'Support informatique', 'direction' => 'DSI', 'total_cloturees' => 18, 'total_cloturees_delai' => 12, 'taux_reponse_dans_delais' => 66.7],
            ],
            'demandes_par_pays' => [
                ['pays' => 'Gabon', 'total_demandes' => 58],
                ['pays' => 'France', 'total_demandes' => 17],
            ],
            'demandes_par_etablissement' => [
                ['etablissement' => 'Université Omar Bongo', 'total_demandes' => 39],
                ['etablissement' => 'Université des Sciences et Techniques de Masuku', 'total_demandes' => 21],
            ],
            'evolution_temporelle' => [
                'labels' => ['Janvier', 'Février', 'Mars'],
                'series' => [
                    'reclamations_recues' => [21, 28, 24],
                    'demandes_cloturees' => [17, 23, 20],
                    'reclamations_non_cloturees' => [4, 5, 4],
                ],
            ],
        ];
    }
}
