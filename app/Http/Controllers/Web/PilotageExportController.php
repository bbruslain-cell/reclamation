<?php

namespace App\Http\Controllers\Web;

use App\Exports\PilotageTableExport;
use App\Http\Controllers\Api\OverviewController as ApiOverviewController;
use App\Http\Controllers\Controller;
use App\Services\AccessControlService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        $access->assertPermission((int) $actor->id_utilisateur, 'dashboard.view');

        $overviewData = app(ApiOverviewController::class)->index($request, $access)->getData(true);
        $payload = $this->buildPayload($section, $overviewData);

        if ($format === 'xlsx') {
            return Excel::download(
                new PilotageTableExport($payload['sections'], $payload['title']),
                $payload['filename'].'.xlsx'
            );
        }

        $pdf = Pdf::loadView('exports.pilotage.tables', [
            'title' => $payload['title'],
            'sections' => $payload['sections'],
            'forPdf' => true,
        ])->setPaper('a4', $payload['orientation'] ?? 'landscape');

        return $pdf->download($payload['filename'].'.pdf');
    }

    private function buildPayload(string $section, array $overviewData): array
    {
        return match ($section) {
            'ciq-tracking' => $this->buildCiqTrackingPayload($overviewData),
            'annexe2' => $this->buildAnnexe2Payload($overviewData),
            'function-distribution' => $this->buildFunctionDistributionPayload(
                $overviewData['annexes_fonctions']['global'] ?? [],
                "Tableau de repartition de l'ensemble des reclamations de la cellule par direction.",
                'tableau-repartition-reclamations-par-direction'
            ),
            'information-function-distribution' => $this->buildFunctionDistributionPayload(
                $overviewData['annexes_fonctions']['informations'] ?? [],
                "Tableau de repartition des demandes d'information par direction et par service.",
                'tableau-repartition-demandes-information-par-direction-service'
            ),
            'information-service-distribution' => $this->buildServiceDistributionPayload(
                $overviewData['annexes_services']['informations'] ?? [],
                "Tableau de repartition des demandes d'information par service.",
                'tableau-repartition-demandes-information-par-service'
            ),
            'reclamation-function-distribution' => $this->buildFunctionDistributionPayload(
                $overviewData['annexes_fonctions']['reclamations'] ?? [],
                'Tableau de repartition des reclamations par direction et par service.',
                'tableau-repartition-reclamations-par-direction-service'
            ),
            'reclamation-service-distribution' => $this->buildServiceDistributionPayload(
                $overviewData['annexes_services']['reclamations'] ?? [],
                'Tableau de repartition des reclamations par service.',
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
            'title' => 'Tableau actuel du suivi des reclamations',
            'filename' => 'tableau-suivi-ciq',
            'orientation' => 'landscape',
            'sections' => [[
                'title' => 'Suivi detaille',
                'headers' => [
                    'N°',
                    'Date de reception',
                    'Expediteur',
                    'Objet',
                    'Date de dispatch',
                    'Delais de transmission',
                    'Service',
                    'Realisation',
                    'Statut',
                    'Respect delais',
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
        $informationRows = collect($repartition['informations'] ?? [])
            ->map(fn (array $row) => [
                $row['categorie'] ?? '-',
                $this->intValue($row['nombre_mails'] ?? 0),
                $this->percent($row['pourcentage'] ?? 0),
            ])->all();
        $reclamationRows = collect($repartition['reclamations'] ?? [])
            ->map(fn (array $row) => [
                $row['categorie'] ?? '-',
                $this->intValue($row['nombre_mails'] ?? 0),
                $this->percent($row['pourcentage'] ?? 0),
            ])->all();

        return [
            'title' => "Tableau de la repartition des demandes d'informations et reclamations les plus recurrentes dans la cellule.",
            'filename' => "Tableau de la repartition des demandes d'informations et reclamations les plus recurrentes dans la cellule",
            'orientation' => 'landscape',
            'sections' => [
                [
                    'title' => "Demande d'informations sur eBourse, les bourses & accessoires de bourse",
                    'headers' => ['Categorie', 'Nombre de mails', '%'],
                    'rows' => $informationRows,
                    'footer' => [
                        'TOTAL DEMANDES D INFORMATIONS',
                        $this->intValue($repartition['total_informations'] ?? 0),
                        '',
                    ],
                ],
                [
                    'title' => 'RECLAMATIONS',
                    'headers' => ['Categorie', 'Nombre de mails', '%'],
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
            'filename' => $filename,
            'orientation' => 'landscape',
            'sections' => [[
                'title' => 'Repartition par fonction',
                'headers' => [
                    'Fonctions',
                    'Nombre total',
                    'Nombre traite',
                    "Taux d'execution (%)",
                    'Nombre traite dans les delais',
                    'Taux de conformite (72h) (%)',
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
            'filename' => $filename,
            'orientation' => 'landscape',
            'sections' => [[
                'title' => 'Repartition par service',
                'headers' => [
                    'Services / Unite',
                    'Agents',
                    'Nbre de mails recus',
                    'Nbre de mails traites',
                    "Taux d'execution (%) (A)",
                    'Nbre de mails traites dans les delais',
                    'Taux de conformite (72h) (%) (B)',
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
