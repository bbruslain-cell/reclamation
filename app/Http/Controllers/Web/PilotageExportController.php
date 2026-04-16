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

        $overviewData = app(ApiOverviewController::class)->index($request, $access)->getData(true);
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
                "Tableau de rÃƒÂ©partition de l'ensemble des rÃƒÂ©clamations de la cellule par direction.",
                'tableau-repartition-reclamations-par-direction'
            ),
            'reclamation-service-distribution' => $this->buildServiceDistributionPayload(
                $overviewData['annexes_services']['reclamations'] ?? [],
                'Tableau de rÃƒÂ©partition des rÃƒÂ©clamations par service.',
                'tableau-repartition-reclamations-par-service'
            ),
            default => abort(404),
        };
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
            'title' => "Tableau actuel du suivi des r\u{00E9}clamations",
            'sheet_title' => "Suivi r\u{00E9}clamations",
            'filename' => 'tableau-suivi-ciq',
            'orientation' => 'landscape',
            'sections' => [[
                'title' => "Suivi d\u{00E9}taill\u{00E9}",
                'headers' => [
                    "N\u{00B0}",
                    "Date de r\u{00E9}ception",
                    "Exp\u{00E9}diteur",
                    'Objet',
                    'Date de dispatch',
                    "D\u{00E9}lais de transmission",
                    'Service',
                    "R\u{00E9}alisation",
                    'Statut',
                    "Respect d\u{00E9}lais",
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
            'title' => "Tableau de la r\u{00E9}partition des r\u{00E9}clamations les plus r\u{00E9}currentes dans la cellule.",
            'sheet_title' => "R\u{00E9}clamations r\u{00E9}currentes",
            'filename' => 'Tableau de la repartition des reclamations les plus recurrentes dans la cellule',
            'orientation' => 'landscape',
            'sections' => [
                [
                    'title' => 'RECLAMATIONS',
                    'headers' => ["Cat\u{00E9}gorie", 'Nombre de mails', '%'],
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
            'sheet_title' => "R\u{00E9}clamations direction",
            'filename' => $filename,
            'orientation' => 'landscape',
            'sections' => [[
                'title' => "R\u{00E9}partition par fonction",
                'headers' => [
                    'Fonctions',
                    'Nombre total',
                    "Nombre trait\u{00E9}",
                    "Taux d'ex\u{00E9}cution (%)",
                    "Nombre trait\u{00E9} dans les d\u{00E9}lais",
                    "Taux de conformit\u{00E9} (72h) (%)",
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
            'sheet_title' => "R\u{00E9}clamations service",
            'filename' => $filename,
            'orientation' => 'landscape',
            'sections' => [[
                'title' => "R\u{00E9}partition par service",
                'headers' => [
                    "Services / Unit\u{00E9}",
                    'Agents',
                    "Nbre de mails re\u{00E7}us",
                    "Nbre de mails trait\u{00E9}s",
                    "Taux d'ex\u{00E9}cution (%) (A)",
                    "Nbre de mails trait\u{00E9}s dans les d\u{00E9}lais",
                    "Taux de conformit\u{00E9} (72h) (%) (B)",
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


