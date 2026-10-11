<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use ZipArchive;

class PilotageWordExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_ciq_can_download_a_professional_word_chart_report_and_pdf_is_disabled(): void
    {
        $this->seed();

        $userId = (int) DB::table('utilisateurs')
            ->where('email', 'ciq@anbg.ga')
            ->value('id_utilisateur');
        DB::table('utilisateurs')
            ->where('id_utilisateur', $userId)
            ->update(['changement_mdp_requis' => false]);

        $headers = ['X-User-Id' => (string) $userId];
        $dashboard = $this->withHeaders($headers)->get('/pilotage/dashboard?periode=all');

        $dashboard
            ->assertOk()
            ->assertSee('/pilotage/export/direction-demand-donut/docx', false)
            ->assertDontSee('/pilotage/export/direction-demand-donut/pdf', false);

        $response = $this->withHeaders($headers)
            ->get('/pilotage/export/direction-demand-donut/docx?periode=all');

        $response
            ->assertOk()
            ->assertDownload('repartition-demandes-par-direction.docx')
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            );

        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;

        try {
            $this->assertSame(true, $zip->open($path));
            $documentXml = $zip->getFromName('word/document.xml');
            $this->assertIsString($documentXml);
            $this->assertStringContainsString('RAPPORT DE PILOTAGE', $documentXml);
            $this->assertStringContainsString('Données détaillées', $documentXml);
            $this->assertStringNotContainsString('w:type="page"', $documentXml);

            $mediaEntries = [];
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->getNameIndex($index);
                if (is_string($entry) && str_starts_with($entry, 'word/media/')) {
                    $mediaEntries[] = $entry;
                }
            }
            $this->assertGreaterThanOrEqual(2, count($mediaEntries));
        } finally {
            $zip->close();
            @unlink($path);
        }

        $this->assertDatabaseHas('parametres', [
            'famille' => 'format_export',
            'code' => 'word',
        ]);
        $this->assertDatabaseHas('exports', [
            'id_utilisateur' => $userId,
            'fichier_export' => 'repartition-demandes-par-direction.docx',
        ]);

        $this->withHeaders($headers)
            ->get('/pilotage/export/direction-demand-donut/pdf?periode=all')
            ->assertNotFound();
    }
}
