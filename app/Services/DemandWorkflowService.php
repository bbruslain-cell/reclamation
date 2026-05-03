<?php

namespace App\Services;

use App\Mail\DemandResponseMail;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class DemandWorkflowService
{
    private const ALLOWED_ATTACHMENT_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
    private const ALLOWED_ATTACHMENT_MIMES = ['application/pdf', 'image/jpeg', 'image/png'];

    public function __construct(
        private readonly WorkingHoursSlaService $slaService,
        private readonly StepAlertService $stepAlerts
    ) {
    }

    public function assignDemand(int $actorId, int $demandId, int $serviceId, ?string $comment): array
    {
        return DB::transaction(function () use ($actorId, $demandId, $serviceId, $comment) {
            $demand = DB::table('demandes')->where('id_demande', $demandId)->lockForUpdate()->first();
            if (!$demand) {
                throw new RuntimeException('Demande introuvable.');
            }

            $statusAffectee = $this->statusId('affectee_service');
            $now = now();

            DB::table('affectations')->insert([
                'id_demande' => $demandId,
                'id_service' => $serviceId,
                'id_utilisateur' => $actorId,
                'date_affectation' => $now,
                'commentaire' => $comment,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('demandes')
                ->where('id_demande', $demandId)
                ->update([
                    'id_service_courant' => $serviceId,
                    'id_statut' => $statusAffectee,
                    'id_agent_accueil' => $actorId,
                    'date_affectation' => $now,
                    'date_affectation_accueil' => $now,
                    'updated_at' => $now,
                ]);

            $this->logAction(
                demandId: $demandId,
                userId: $actorId,
                type: 'affectation_service',
                oldStatusId: $demand->id_statut,
                newStatusId: $statusAffectee,
                serviceId: $serviceId,
                comment: $comment
            );

            return $this->refreshDemandSla($demandId);
        });
    }

    public function assignAgent(int $actorId, int $demandId, int $agentId, ?string $comment): array
    {
        return DB::transaction(function () use ($actorId, $demandId, $agentId, $comment) {
            $demand = DB::table('demandes')->where('id_demande', $demandId)->lockForUpdate()->first();
            if (!$demand) {
                throw new RuntimeException('Demande introuvable.');
            }

            $statusAffecteeAgent = $this->statusId('affectee_agent');
            $now = now();

            DB::table('demandes')
                ->where('id_demande', $demandId)
                ->update([
                    'id_statut' => $statusAffecteeAgent,
                    'id_agent_traitant' => $agentId,
                    'date_affectation_agent' => $now,
                    'updated_at' => $now,
                ]);

            $this->logAction(
                demandId: $demandId,
                userId: $actorId,
                type: 'affectation_agent',
                oldStatusId: $demand->id_statut,
                newStatusId: $statusAffecteeAgent,
                serviceId: $demand->id_service_courant,
                comment: $comment,
                agentId: $agentId
            );

            return $this->refreshDemandSla($demandId);
        });
    }

    public function cancelAgentAssignment(int $actorId, int $demandId, ?string $comment): array
    {
        return DB::transaction(function () use ($actorId, $demandId, $comment) {
            $demand = DB::table('demandes')->where('id_demande', $demandId)->lockForUpdate()->first();
            if (!$demand) {
                throw new RuntimeException('Demande introuvable.');
            }

            if ($this->statusCode((int) $demand->id_statut) !== 'affectee_agent') {
                throw new RuntimeException('Annulation impossible pour ce statut.');
            }

            if (!$demand->id_agent_traitant) {
                throw new RuntimeException('Aucun agent n est affecte a cette demande.');
            }

            $statusAffecteeService = $this->statusId('affectee_service');
            $now = now();

            DB::table('demandes')
                ->where('id_demande', $demandId)
                ->update([
                    'id_statut' => $statusAffecteeService,
                    'id_agent_traitant' => null,
                    'date_affectation_agent' => null,
                    'updated_at' => $now,
                ]);

            $this->logAction(
                demandId: $demandId,
                userId: $actorId,
                type: 'annulation_affectation_agent',
                oldStatusId: $demand->id_statut,
                newStatusId: $statusAffecteeService,
                serviceId: $demand->id_service_courant,
                comment: $comment,
                agentId: (int) $demand->id_agent_traitant
            );

            return $this->refreshDemandSla($demandId);
        });
    }

    public function cancelServiceAssignment(int $actorId, int $demandId, ?string $comment): array
    {
        return DB::transaction(function () use ($actorId, $demandId, $comment) {
            $demand = DB::table('demandes')->where('id_demande', $demandId)->lockForUpdate()->first();
            if (!$demand) {
                throw new RuntimeException('Demande introuvable.');
            }

            if ($this->statusCode((int) $demand->id_statut) !== 'affectee_service') {
                throw new RuntimeException('Annulation impossible pour ce statut.');
            }

            if (!$demand->id_service_courant) {
                throw new RuntimeException('Aucun service n est actuellement affecte a cette demande.');
            }

            $statusNouvelle = $this->statusId('nouvelle');
            $now = now();

            DB::table('demandes')
                ->where('id_demande', $demandId)
                ->update([
                    'id_service_courant' => null,
                    'id_statut' => $statusNouvelle,
                    'id_agent_accueil' => null,
                    'id_agent_direction' => null,
                    'id_agent_traitant' => null,
                    'date_affectation' => null,
                    'date_affectation_accueil' => null,
                    'date_affectation_agent' => null,
                    'date_reponse_direction' => null,
                    'updated_at' => $now,
                ]);

            $this->logAction(
                demandId: $demandId,
                userId: $actorId,
                type: 'annulation_affectation_service',
                oldStatusId: $demand->id_statut,
                newStatusId: $statusNouvelle,
                serviceId: (int) $demand->id_service_courant,
                comment: $comment
            );

            return $this->refreshDemandSla($demandId);
        });
    }

    public function draftResponse(int $actorId, int $demandId, string $content, ?string $typeCode = null): array
    {
        return DB::transaction(function () use ($actorId, $demandId, $content, $typeCode) {
            $demand = DB::table('demandes')->where('id_demande', $demandId)->lockForUpdate()->first();
            if (!$demand) {
                throw new RuntimeException('Demande introuvable.');
            }

            // Réponse unique : on remplace si elle existe déjà
            $typeId = $this->parameterId('type_reponse', $typeCode ?: 'via_direction');
            $now = now();

            $existingResponseId = DB::table('reponses')
                ->where('id_demande', $demandId)
                ->value('id_reponse');

            if ($existingResponseId) {
                DB::table('reponses')
                    ->where('id_reponse', $existingResponseId)
                    ->update([
                        'id_type_reponse'  => $typeId,
                        'contenu_reponse'  => $content,
                        'id_redacteur'     => $actorId,
                        'date_redaction'   => $now,
                        'updated_at'       => $now,
                    ]);
                $responseId = $existingResponseId;
            } else {
                $responseId = DB::table('reponses')->insertGetId([
                    'id_demande'      => $demandId,
                    'id_type_reponse' => $typeId,
                    'contenu_reponse' => $content,
                    'id_redacteur'    => $actorId,
                    'date_redaction'  => $now,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ], 'id_reponse');
            }

            $statusResponseReady = $this->statusId('reponse_prete');

            DB::table('demandes')
                ->where('id_demande', $demandId)
                ->update([
                    'id_statut'              => $statusResponseReady,
                    'id_agent_direction'     => $actorId,
                    'date_reponse_direction' => $now,
                    'updated_at'             => $now,
                ]);

            $this->logAction(
                demandId:    $demandId,
                userId:      $actorId,
                type:        'reponse_redigee',
                oldStatusId: $demand->id_statut,
                newStatusId: $statusResponseReady,
                serviceId:   $demand->id_service_courant,
                comment:     'Réponse rédigée'
            );

            $result = $this->refreshDemandSla($demandId);
            $result['id_reponse'] = (int) $responseId;

            return $result;
        });
    }

    public function sendFinalResponse(int $actorId, int $demandId): array
    {
        return DB::transaction(function () use ($actorId, $demandId) {
            $demand = DB::table('demandes')->where('id_demande', $demandId)->lockForUpdate()->first();
            if (!$demand) {
                throw new RuntimeException('Demande introuvable.');
            }

            $latestResponse = DB::table('reponses')
                ->where('id_demande', $demandId)
                ->orderByDesc('numero_version')
                ->first();

            if (!$latestResponse) {
                throw new RuntimeException('Aucune reponse redigee pour cette demande.');
            }

            $mailPayload = $this->buildFinalResponseMailPayload(
                demandId: $demandId,
                responseId: (int) $latestResponse->id_reponse,
                responseContent: (string) $latestResponse->contenu_reponse
            );

            $now = now();
            DB::table('reponses')
                ->where('id_reponse', $latestResponse->id_reponse)
                ->update([
                    'id_envoyeur' => $actorId,
                    'date_envoi_usager' => $now,
                    'updated_at' => $now,
                ]);

            $statusCloturee = $this->statusId('cloturee');
            $elapsed = $this->slaService->calculateElapsedHours(
                Carbon::parse($demand->date_soumission),
                $now,
                (int) $demand->id_config_sla
            );

            DB::table('demandes')
                ->where('id_demande', $demandId)
                ->update([
                    'id_statut' => $statusCloturee,
                    'date_envoi_usager' => $now,
                    'date_cloture' => $now,
                    'heures_ouvrees_cloture' => $elapsed,
                    'updated_at' => $now,
                ]);

            $this->logAction(
                demandId: $demandId,
                userId: $actorId,
                type: 'envoi_reponse',
                oldStatusId: $demand->id_statut,
                newStatusId: $statusCloturee,
                serviceId: $demand->id_service_courant,
                comment: "Envoi final à l'usager"
            );

            if ($mailPayload !== null) {
                DB::afterCommit(function () use ($mailPayload): void {
                    try {
                        Mail::to($mailPayload['email'])->queue(new DemandResponseMail(
                            trackingNumber: $mailPayload['tracking_number'],
                            subjectLabel: $mailPayload['subject_label'],
                            responseContent: $mailPayload['response_content'],
                            recipientName: $mailPayload['recipient_name'],
                            responseAttachments: $mailPayload['attachments']
                        ));
                    } catch (Throwable $e) {
                        Log::error('Echec envoi mail usager', [
                            'numero_suivi' => $mailPayload['tracking_number'],
                            'email' => $mailPayload['email'],
                            'message' => $e->getMessage(),
                        ]);
                    }
                });
            }

            return $this->refreshDemandSla($demandId);
        });
    }

    /**
     * @param UploadedFile[] $files
     */
    public function attachFilesToResponse(int $actorId, int $responseId, array $files): void
    {
        if (empty($files)) {
            return;
        }

        DB::transaction(function () use ($actorId, $responseId, $files): void {
            $now = now();

            foreach ($files as $file) {
                if (!$file instanceof UploadedFile) {
                    continue;
                }

                $this->ensureAllowedAttachment($file);

                $path = $file->store('pieces_jointes', 'local');
                $pieceId = DB::table('pieces_jointes')->insertGetId([
                    'nom_fichier' => $file->getClientOriginalName(),
                    'chemin_fichier' => $path,
                    'taille_octets' => $file->getSize(),
                    'type_mime' => $file->getMimeType() ?: 'application/octet-stream',
                    'id_uploadeur' => $actorId,
                    'source' => 'agent',
                    'date_upload' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], 'id_piece_jointe');

                DB::table('reponse_piece_jointe')->insert([
                    'id_reponse' => $responseId,
                    'id_piece_jointe' => $pieceId,
                ]);
            }
        });
    }

    public function refreshDemandSla(int $demandId): array
    {
        $demand = DB::table('demandes')->where('id_demande', $demandId)->first();
        if (!$demand) {
            throw new RuntimeException('Demande introuvable.');
        }

        $statusCode = $this->statusCode((int) $demand->id_statut);
        $alerts = $this->stepAlerts->refreshDemandAlerts($demandId);

        return [
            'id_demande' => $demandId,
            'alerte_accueil' => $alerts['alerte_accueil'],
            'alerte_chef' => $alerts['alerte_chef'],
            'alerte_agent' => $alerts['alerte_agent'],
            'alerte_globale' => $alerts['delai_alerte'],
            'statut_code' => $statusCode,
        ];
    }

    private function logAction(
        int $demandId,
        int $userId,
        string $type,
        ?int $oldStatusId,
        ?int $newStatusId,
        ?int $serviceId,
        ?string $comment,
        ?int $agentId = null
    ): void {
        DB::table('historique_actions')->insert([
            'id_demande' => $demandId,
            'id_utilisateur' => $userId,
            'type_action' => $type,
            'ancien_statut_id' => $oldStatusId,
            'nouveau_statut_id' => $newStatusId,
            'id_service_associe' => $serviceId,
            'id_agent_associe' => $agentId,
            'date_action' => now(),
            'commentaire' => $comment,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function statusId(string $code): int
    {
        return (int) $this->parameterId('statut_demande', $code);
    }

    private function ensureAllowedAttachment(UploadedFile $file): void
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $mime = strtolower((string) ($file->getMimeType() ?: ''));

        if (!in_array($extension, self::ALLOWED_ATTACHMENT_EXTENSIONS, true)
            || !in_array($mime, self::ALLOWED_ATTACHMENT_MIMES, true)) {
            throw new RuntimeException('Format de pièce jointe non autorisé. Utilisez PDF, JPG ou PNG.');
        }
    }

    private function statusCode(int $statusId): string
    {
        return (string) DB::table('parametres')->where('id_parametre', $statusId)->value('code');
    }

    private function parameterId(string $family, string $code): int
    {
        $id = DB::table('parametres')
            ->where('famille', $family)
            ->where('code', $code)
            ->value('id_parametre');

        if (!$id) {
            throw new RuntimeException("Parametre manquant: {$family}/{$code}");
        }

        return (int) $id;
    }

    private function slaMaxHours(int $configId): int
    {
        $value = DB::table('config_sla')
            ->where('id_config_sla', $configId)
            ->value('delai_max_heures');

        return max(1, (int) ($value ?: 72));
    }

    /**
     * @return array{
     *     email: string,
     *     recipient_name: string|null,
     *     tracking_number: string,
     *     subject_label: string,
     *     response_content: string,
     *     attachments: array<int, array{name: string, path: string, mime: string|null}>
     * }|null
     */
    private function buildFinalResponseMailPayload(int $demandId, int $responseId, string $responseContent): ?array
    {
        $recipient = DB::table('demandes as d')
            ->join('usagers as u', 'u.id_usager', '=', 'd.id_usager')
            ->where('d.id_demande', $demandId)
            ->select(
                'd.numero_suivi',
                'd.objet',
                'u.email',
                'u.nom',
                'u.prenom'
            )
            ->first();

        $email = trim((string) ($recipient->email ?? ''));
        if ($email === '') {
            return null;
        }

        $attachments = DB::table('reponse_piece_jointe as rpj')
            ->join('pieces_jointes as pj', 'pj.id_piece_jointe', '=', 'rpj.id_piece_jointe')
            ->where('rpj.id_reponse', $responseId)
            ->orderBy('pj.id_piece_jointe')
            ->get([
                'pj.nom_fichier',
                'pj.chemin_fichier',
                'pj.type_mime',
            ])
            ->map(static fn (object $piece): array => [
                'name' => (string) $piece->nom_fichier,
                'path' => (string) $piece->chemin_fichier,
                'mime' => $piece->type_mime ? (string) $piece->type_mime : null,
            ])
            ->values()
            ->all();

        $recipientName = trim((string) (($recipient->prenom ?? '').' '.($recipient->nom ?? '')));

        return [
            'email' => $email,
            'recipient_name' => $recipientName !== '' ? $recipientName : null,
            'tracking_number' => (string) ($recipient->numero_suivi ?? 'Demande'),
            'subject_label' => (string) ($recipient->objet ?? 'Votre demande'),
            'response_content' => $responseContent,
            'attachments' => $attachments,
        ];
    }
}
