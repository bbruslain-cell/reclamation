<?php

namespace App\Http\Controllers\Web;

use App\Exports\PilotageTableExport;
use App\Http\Controllers\Api\OverviewController as ApiOverviewController;
use App\Http\Controllers\Controller;
use App\Services\AccessControlService;
use App\Services\PilotageChartWordReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class PilotageExportController extends Controller
{
    public function download(
        Request $request,
        AccessControlService $access,
        PilotageChartWordReportService $wordReports,
        string $section,
        string $format,
    ): Response|BinaryFileResponse {
        abort_unless(in_array($format, ['pdf', 'xlsx', 'docx'], true), 404);

        $actor = $access->resolveActor($request);
        if (! $actor) {
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

        if ($wordReports->supports($section)) {
            abort_unless($format === 'docx', 404);

            $report = $wordReports->generate($section, $overviewData, $actor);
            try {
                $this->traceExport($actor, $format, $report['filename'], $section, $request);

                return response()->download($report['path'], $report['filename'], [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'X-Content-Type-Options' => 'nosniff',
                ])->deleteFileAfterSend(true);
            } catch (\Throwable $exception) {
                File::delete($report['path']);

                throw $exception;
            }
        }

        abort_unless(in_array($format, ['pdf', 'xlsx'], true), 404);

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
        $formatCode = match ($format) {
            'xlsx' => 'excel',
            'docx' => 'word',
            default => $format,
        };
        $formatId = DB::table('parametres')
            ->where('famille', 'format_export')
            ->where('code', $formatCode)
            ->value('id_parametre');

        if (! $formatId) {
            $formatId = DB::table('parametres')->insertGetId([
                'famille' => 'format_export',
                'code' => $formatCode,
                'libelle' => strtoupper($formatCode),
                'ordre_affichage' => match ($formatCode) {
                    'excel' => 1,
                    'pdf' => 2,
                    'word' => 3,
                    default => 99,
                },
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
