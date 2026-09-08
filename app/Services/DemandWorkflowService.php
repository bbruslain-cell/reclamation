<?php

namespace App\Services;

use App\Jobs\SendDemandAssignmentNotificationJob;
use App\Jobs\SendDemandResponseJob;
use App\Mail\DemandAssignmentNotificationMail;
use App\Mail\DemandResponseMail;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DemandWorkflowService
{
    private const ALLOWED_ATTACHMENT_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
    private const ALLOWED_ATTACHMENT_MIMES = ['application/pdf', 'image/jpeg', 'image/png'];
    private const ASSIGNMENT_SERVICE_ASSIGNED = 'service_assigned';
    private const ASSIGNMENT_SERVICE_CANCELLED = 'service_assignment_cancelled';
    private const ASSIGNMENT_AGENT_ASSIGNED = 'agent_assigned';
    private const ASSIGNMENT_AGENT_CANCELLED = 'agent_assignment_cancelled';

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

            $this->queueServiceAssignmentNotificationsAfterCommit(
                actorId: $actorId,
                demandId: $demandId,
                serviceId: $serviceId,
                eventCode: self::ASSIGNMENT_SERVICE_ASSIGNED,
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

            $this->queueAgentAssignmentNotificationAfterCommit(
                actorId: $actorId,
                demandId: $demandId,
                agentId: $agentId,
                eventCode: self::ASSIGNMENT_AGENT_ASSIGNED,
                comment: $comment
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
                throw new RuntimeException('Aucun agent n\'est affecté à cette demande.');
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

            $this->queueAgentAssignmentNotificationAfterCommit(
                actorId: $actorId,
                demandId: $demandId,
                agentId: (int) $demand->id_agent_traitant,
                eventCode: self::ASSIGNMENT_AGENT_CANCELLED,
                comment: $comment
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
                throw new RuntimeException('Aucun service n\'est actuellement affecté à cette demande.');
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

            $this->queueServiceAssignmentNotificationsAfterCommit(
                actorId: $actorId,
                demandId: $demandId,
                serviceId: (int) $demand->id_service_courant,
                eventCode: self::ASSIGNMENT_SERVICE_CANCELLED,
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

            if ($this->isResponseDeliveryPending($demand)) {
                throw new RuntimeException('Un envoi de réponse est déjà en cours pour cette demande.');
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

            if ($demand->date_envoi_usager || $demand->date_cloture || $this->statusCode((int) $demand->id_statut) === 'cloturee') {
                throw new RuntimeException('La réponse finale a déjà été envoyée à l\'usager.');
            }

            if ($this->isResponseDeliveryPending($demand)) {
                throw new RuntimeException('Un envoi de réponse est déjà en cours pour cette demande.');
            }

            $latestResponse = DB::table('reponses')
                ->where('id_demande', $demandId)
                ->orderByDesc('numero_version')
                ->first();

            if (!$latestResponse) {
                throw new RuntimeException('Aucune réponse rédigée pour cette demande.');
            }

            if ($latestResponse->date_envoi_usager) {
                throw new RuntimeException('La réponse finale a déjà été envoyée à l\'usager.');
            }

            $mailPayload = $this->buildFinalResponseMailPayload(
                demandId: $demandId,
                responseId: (int) $latestResponse->id_reponse,
                responseContent: (string) $latestResponse->contenu_reponse
            );

            if ($mailPayload === null) {
                throw new RuntimeException('L\'adresse email de l\'usager est invalide ou manquante.');
            }

            $now = now();
            DB::table('reponses')
                ->where('id_reponse', $latestResponse->id_reponse)
                ->update([
                    'id_envoyeur' => $actorId,
                    'date_demande_envoi_usager' => $now,
                    'date_echec_envoi_usager' => null,
                    'updated_at' => $now,
                ]);

            DB::table('demandes')
                ->where('id_demande', $demandId)
                ->update([
                    'date_demande_envoi_usager' => $now,
                    'date_echec_envoi_usager' => null,
                    'updated_at' => $now,
                ]);

            DB::afterCommit(function () use ($actorId, $demandId, $latestResponse, $mailPayload): void {
                SendDemandResponseJob::dispatch(
                    actorId: $actorId,
                    demandId: $demandId,
                    responseId: (int) $latestResponse->id_reponse,
                    mailPayload: $mailPayload
                );
            });

            $result = $this->refreshDemandSla($demandId);
            $result['delivery_pending'] = true;

            return $result;
        });
    }

    /**
     * @param array{
     *     email: string,
     *     recipient_name: string|null,
     *     tracking_number: string,
     *     subject_label: string,
     *     response_content: string,
     *     attachments: array<int, array{name: string, path: string, mime: string|null}>
     * } $mailPayload
     */
    public function deliverQueuedFinalResponse(int $actorId, int $demandId, int $responseId, array $mailPayload): void
    {
        Mail::to($mailPayload['email'])->send(new DemandResponseMail(
            trackingNumber: $mailPayload['tracking_number'],
            subjectLabel: $mailPayload['subject_label'],
            responseContent: $mailPayload['response_content'],
            recipientName: $mailPayload['recipient_name'],
            responseAttachments: $mailPayload['attachments']
        ));

        DB::transaction(function () use ($actorId, $demandId, $responseId): void {
            $demand = DB::table('demandes')->where('id_demande', $demandId)->lockForUpdate()->first();
            if (!$demand) {
                throw new RuntimeException('Demande introuvable.');
            }

            $response = DB::table('reponses')->where('id_reponse', $responseId)->lockForUpdate()->first();
            if (!$response || (int) $response->id_demande !== $demandId) {
                throw new RuntimeException('Réponse introuvable.');
            }

            if ($demand->date_envoi_usager || $demand->date_cloture) {
                return;
            }

            $now = now();
            $statusCloturee = $this->statusId('cloturee');
            $elapsed = $this->slaService->calculateElapsedHours(
                Carbon::parse($demand->date_soumission),
                $now,
                (int) $demand->id_config_sla
            );

            DB::table('reponses')
                ->where('id_reponse', $responseId)
                ->update([
                    'id_envoyeur' => $actorId,
                    'date_demande_envoi_usager' => null,
                    'date_echec_envoi_usager' => null,
                    'date_envoi_usager' => $now,
                    'updated_at' => $now,
                ]);

            DB::table('demandes')
                ->where('id_demande', $demandId)
                ->update([
                    'id_statut' => $statusCloturee,
                    'date_demande_envoi_usager' => null,
                    'date_echec_envoi_usager' => null,
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
        });

        $this->refreshDemandSla($demandId);
    }

    public function markQueuedFinalResponseFailed(int $actorId, int $demandId, int $responseId, Throwable $exception): void
    {
        DB::transaction(function () use ($actorId, $demandId, $responseId, $exception): void {
            $demand = DB::table('demandes')->where('id_demande', $demandId)->lockForUpdate()->first();
            $response = DB::table('reponses')->where('id_reponse', $responseId)->lockForUpdate()->first();
            $failedAt = now();

            if ($response && !$response->date_envoi_usager) {
                DB::table('reponses')
                    ->where('id_reponse', $responseId)
                    ->update([
                        'date_demande_envoi_usager' => null,
                        'date_echec_envoi_usager' => $failedAt,
                        'updated_at' => $failedAt,
                    ]);
            }

            if ($demand && !$demand->date_envoi_usager && !$demand->date_cloture) {
                DB::table('demandes')
                    ->where('id_demande', $demandId)
                    ->update([
                        'date_demande_envoi_usager' => null,
                        'date_echec_envoi_usager' => $failedAt,
                        'updated_at' => $failedAt,
                    ]);

                $this->logAction(
                    demandId: $demandId,
                    userId: $actorId,
                    type: 'echec_envoi_reponse',
                    oldStatusId: $demand->id_statut,
                    newStatusId: $demand->id_statut,
                    serviceId: $demand->id_service_courant,
                    comment: "Echec d'envoi a l'usager. Verifiez le serveur mail puis relancez l'envoi."
                );
            }
        });

        Log::error('Echec envoi mail usager', [
            'id_demande' => $demandId,
            'id_reponse' => $responseId,
            'message' => $exception->getMessage(),
        ]);
    }

    /**
     * @param array{
     *     demand_id: int,
     *     actor_id: int,
     *     email: string,
     *     recipient_name: string,
     *     tracking_number: string,
     *     subject_label: string,
     *     event_code: string,
     *     mail_subject: string,
     *     intro_line: string,
     *     instruction_line: string,
     *     actor_name: string,
     *     service_label: string|null,
     *     comment: string|null,
     *     login_url: string
     * } $mailPayload
     */
    public function deliverQueuedAssignmentNotification(array $mailPayload): void
    {
        Mail::to($mailPayload['email'])->send(new DemandAssignmentNotificationMail(
            trackingNumber: $mailPayload['tracking_number'],
            subjectLabel: $mailPayload['subject_label'],
            eventCode: $mailPayload['event_code'],
            mailSubject: $mailPayload['mail_subject'],
            recipientName: $mailPayload['recipient_name'],
            introLine: $mailPayload['intro_line'],
            instructionLine: $mailPayload['instruction_line'],
            actorName: $mailPayload['actor_name'],
            loginUrl: $mailPayload['login_url'],
            serviceLabel: $mailPayload['service_label'],
            comment: $mailPayload['comment']
        ));

        $this->recordAssignmentNotification($mailPayload, 'succes');
    }

    /**
     * @param array{
     *     demand_id: int,
     *     actor_id: int,
     *     email: string,
     *     recipient_name: string,
     *     tracking_number: string,
     *     subject_label: string,
     *     event_code: string,
     *     mail_subject: string,
     *     intro_line: string,
     *     instruction_line: string,
     *     actor_name: string,
     *     service_label: string|null,
     *     comment: string|null,
     *     login_url: string
     * } $mailPayload
     */
    public function markQueuedAssignmentNotificationFailed(array $mailPayload, Throwable $exception): void
    {
        $this->recordAssignmentNotification($mailPayload, 'echec', $exception->getMessage());

        Log::warning('Echec envoi notification affectation', [
            'id_demande' => $mailPayload['demand_id'],
            'destinataire_email' => $mailPayload['email'],
            'event_code' => $mailPayload['event_code'],
            'message' => $exception->getMessage(),
        ]);
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
                    'nom_fichier' => $this->sanitizeOriginalFilename($file),
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

    private function queueServiceAssignmentNotificationsAfterCommit(
        int $actorId,
        int $demandId,
        int $serviceId,
        string $eventCode,
        ?string $comment
    ): void {
        DB::afterCommit(function () use ($actorId, $demandId, $serviceId, $eventCode, $comment): void {
            $payloads = $this->buildServiceAssignmentMailPayloads(
                actorId: $actorId,
                demandId: $demandId,
                serviceId: $serviceId,
                eventCode: $eventCode,
                comment: $comment
            );

            if ($payloads === []) {
                Log::warning('Aucun destinataire valide pour la notification de service', [
                    'id_demande' => $demandId,
                    'id_service' => $serviceId,
                    'event_code' => $eventCode,
                ]);

                return;
            }

            foreach ($payloads as $payload) {
                $this->dispatchAssignmentNotification($payload);
            }
        });
    }

    private function queueAgentAssignmentNotificationAfterCommit(
        int $actorId,
        int $demandId,
        int $agentId,
        string $eventCode,
        ?string $comment
    ): void {
        DB::afterCommit(function () use ($actorId, $demandId, $agentId, $eventCode, $comment): void {
            $payload = $this->buildAgentAssignmentMailPayload(
                actorId: $actorId,
                demandId: $demandId,
                agentId: $agentId,
                eventCode: $eventCode,
                comment: $comment
            );

            if ($payload === null) {
                Log::warning('Aucun destinataire valide pour la notification agent', [
                    'id_demande' => $demandId,
                    'id_agent' => $agentId,
                    'event_code' => $eventCode,
                ]);

                return;
            }

            $this->dispatchAssignmentNotification($payload);
        });
    }

    /**
     * @param array{
     *     demand_id: int,
     *     actor_id: int,
     *     email: string,
     *     recipient_name: string,
     *     tracking_number: string,
     *     subject_label: string,
     *     event_code: string,
     *     mail_subject: string,
     *     intro_line: string,
     *     instruction_line: string,
     *     actor_name: string,
     *     service_label: string|null,
     *     comment: string|null,
     *     login_url: string
     * } $payload
     */
    private function dispatchAssignmentNotification(array $payload): void
    {
        try {
            SendDemandAssignmentNotificationJob::dispatch($payload);
        } catch (Throwable $exception) {
            $this->markQueuedAssignmentNotificationFailed($payload, $exception);
        }
    }

    /**
     * @return array<int, array{
     *     demand_id: int,
     *     actor_id: int,
     *     email: string,
     *     recipient_name: string,
     *     tracking_number: string,
     *     subject_label: string,
     *     event_code: string,
     *     mail_subject: string,
     *     intro_line: string,
     *     instruction_line: string,
     *     actor_name: string,
     *     service_label: string|null,
     *     comment: string|null,
     *     login_url: string
     * }>
     */
    private function buildServiceAssignmentMailPayloads(
        int $actorId,
        int $demandId,
        int $serviceId,
        string $eventCode,
        ?string $comment
    ): array {
        $context = $this->assignmentContext($actorId, $demandId, $serviceId, $eventCode, $comment);
        if ($context === null) {
            return [];
        }

        return $this->serviceChiefRecipients($serviceId)
            ->map(fn (object $recipient): ?array => $this->assignmentMailPayloadForRecipient($context, $recipient))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     demand_id: int,
     *     actor_id: int,
     *     email: string,
     *     recipient_name: string,
     *     tracking_number: string,
     *     subject_label: string,
     *     event_code: string,
     *     mail_subject: string,
     *     intro_line: string,
     *     instruction_line: string,
     *     actor_name: string,
     *     service_label: string|null,
     *     comment: string|null,
     *     login_url: string
     * }|null
     */
    private function buildAgentAssignmentMailPayload(
        int $actorId,
        int $demandId,
        int $agentId,
        string $eventCode,
        ?string $comment
    ): ?array {
        $agent = DB::table('utilisateurs')
            ->where('id_utilisateur', $agentId)
            ->where('actif', true)
            ->select('id_utilisateur', 'nom', 'prenom', 'email', 'id_service')
            ->first();

        if (!$agent) {
            return null;
        }

        $context = $this->assignmentContext(
            actorId: $actorId,
            demandId: $demandId,
            serviceId: $agent->id_service ? (int) $agent->id_service : null,
            eventCode: $eventCode,
            comment: $comment
        );
        if ($context === null) {
            return null;
        }

        return $this->assignmentMailPayloadForRecipient($context, $agent);
    }

    /**
     * @return array{
     *     demand_id: int,
     *     actor_id: int,
     *     tracking_number: string,
     *     subject_label: string,
     *     event_code: string,
     *     mail_subject: string,
     *     intro_line: string,
     *     instruction_line: string,
     *     actor_name: string,
     *     service_label: string|null,
     *     comment: string|null,
     *     login_url: string
     * }|null
     */
    private function assignmentContext(
        int $actorId,
        int $demandId,
        ?int $serviceId,
        string $eventCode,
        ?string $comment
    ): ?array {
        $demand = DB::table('demandes')
            ->where('id_demande', $demandId)
            ->select('id_demande', 'numero_suivi', 'objet')
            ->first();

        if (!$demand) {
            return null;
        }

        $trackingNumber = (string) ($demand->numero_suivi ?? 'Demande');
        $event = $this->assignmentEventLines($eventCode);

        return [
            'demand_id' => $demandId,
            'actor_id' => $actorId,
            'tracking_number' => $trackingNumber,
            'subject_label' => (string) ($demand->objet ?? 'Demande'),
            'event_code' => $eventCode,
            'mail_subject' => $event['subject_prefix'].' '.$trackingNumber,
            'intro_line' => $event['intro'],
            'instruction_line' => $event['instruction'],
            'actor_name' => $this->userDisplayName($actorId),
            'service_label' => $serviceId ? $this->serviceLabel($serviceId) : null,
            'comment' => $comment !== null && trim($comment) !== '' ? trim($comment) : null,
            'login_url' => url('/login'),
        ];
    }

    /**
     * @param array{
     *     demand_id: int,
     *     actor_id: int,
     *     tracking_number: string,
     *     subject_label: string,
     *     event_code: string,
     *     mail_subject: string,
     *     intro_line: string,
     *     instruction_line: string,
     *     actor_name: string,
     *     service_label: string|null,
     *     comment: string|null,
     *     login_url: string
     * } $context
     * @return array{
     *     demand_id: int,
     *     actor_id: int,
     *     email: string,
     *     recipient_name: string,
     *     tracking_number: string,
     *     subject_label: string,
     *     event_code: string,
     *     mail_subject: string,
     *     intro_line: string,
     *     instruction_line: string,
     *     actor_name: string,
     *     service_label: string|null,
     *     comment: string|null,
     *     login_url: string
     * }|null
     */
    private function assignmentMailPayloadForRecipient(array $context, object $recipient): ?array
    {
        $email = trim((string) ($recipient->email ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $context + [
            'email' => $email,
            'recipient_name' => $this->displayNameFromParts(
                $recipient->prenom ?? null,
                $recipient->nom ?? null,
                $email
            ),
        ];
    }

    private function serviceChiefRecipients(int $serviceId): \Illuminate\Support\Collection
    {
        return DB::table('utilisateurs as u')
            ->join('utilisateur_role as ur', 'ur.id_utilisateur', '=', 'u.id_utilisateur')
            ->join('roles as r', 'r.id_role', '=', 'ur.id_role')
            ->leftJoin('perimetre_service as ps', function ($join) use ($serviceId): void {
                $join->on('ps.id_utilisateur', '=', 'u.id_utilisateur')
                    ->where('ps.id_service', '=', $serviceId);
            })
            ->where('r.code', 'chef_service')
            ->where('u.actif', true)
            ->where(function ($query) use ($serviceId): void {
                $query->where('u.id_service', $serviceId)
                    ->orWhereNotNull('ps.id_service');
            })
            ->distinct()
            ->orderBy('u.nom')
            ->get(['u.id_utilisateur', 'u.nom', 'u.prenom', 'u.email']);
    }

    /**
     * @return array{subject_prefix: string, intro: string, instruction: string}
     */
    private function assignmentEventLines(string $eventCode): array
    {
        return match ($eventCode) {
            self::ASSIGNMENT_SERVICE_CANCELLED => [
                'subject_prefix' => 'ANBG - Affectation service annulée',
                'intro' => "L'affectation de cette demande à votre service a été annulée.",
                'instruction' => "Cette demande n'est plus à traiter dans votre service.",
            ],
            self::ASSIGNMENT_AGENT_ASSIGNED => [
                'subject_prefix' => 'ANBG - Nouvelle demande affectée',
                'intro' => 'Une demande vous a été affectée.',
                'instruction' => 'Merci de vous connecter à la plateforme pour traiter cette demande.',
            ],
            self::ASSIGNMENT_AGENT_CANCELLED => [
                'subject_prefix' => 'ANBG - Affectation agent annulée',
                'intro' => "L'affectation de cette demande à votre nom a été annulée.",
                'instruction' => "Cette demande n'est plus à traiter dans votre espace agent.",
            ],
            default => [
                'subject_prefix' => 'ANBG - Nouvelle demande affectée au service',
                'intro' => 'Une demande a été affectée à votre service.',
                'instruction' => "Merci de vous connecter à la plateforme pour l'analyser, l'affecter à un agent ou la traiter selon votre rôle.",
            ],
        };
    }

    private function userDisplayName(int $userId): string
    {
        $user = DB::table('utilisateurs')
            ->where('id_utilisateur', $userId)
            ->select('nom', 'prenom', 'email')
            ->first();

        if (!$user) {
            return 'Utilisateur ANBG';
        }

        return $this->displayNameFromParts($user->prenom ?? null, $user->nom ?? null, $user->email ?? null);
    }

    private function serviceLabel(int $serviceId): ?string
    {
        $service = DB::table('services')
            ->where('id_service', $serviceId)
            ->select('code', 'libelle')
            ->first();

        if (!$service) {
            return null;
        }

        return trim(trim(((string) ($service->code ?? '')).' - '.((string) ($service->libelle ?? ''))), ' -');
    }

    private function displayNameFromParts(?string $firstName, ?string $lastName, ?string $fallback = null): string
    {
        $name = trim(trim((string) $firstName).' '.trim((string) $lastName));

        return $name !== '' ? $name : (trim((string) $fallback) ?: 'Utilisateur ANBG');
    }

    /**
     * @param array{
     *     demand_id: int,
     *     actor_id: int,
     *     email: string,
     *     recipient_name: string,
     *     tracking_number: string,
     *     subject_label: string,
     *     event_code: string,
     *     mail_subject: string,
     *     intro_line: string,
     *     instruction_line: string,
     *     actor_name: string,
     *     service_label: string|null,
     *     comment: string|null,
     *     login_url: string
     * } $mailPayload
     */
    private function recordAssignmentNotification(array $mailPayload, string $statusCode, ?string $errorMessage = null): void
    {
        try {
            DB::table('notifications')->insert([
                'id_demande' => $mailPayload['demand_id'],
                'id_type_notif' => $this->parameterId('type_notif', 'interne'),
                'id_statut_notif' => $this->parameterId('statut_notif', $statusCode),
                'id_emetteur' => $mailPayload['actor_id'],
                'destinataire_email' => $mailPayload['email'],
                'sujet' => $mailPayload['mail_subject'],
                'contenu' => $this->assignmentNotificationContent($mailPayload),
                'date_envoi' => now(),
                'message_erreur' => $errorMessage,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Journalisation notification affectation impossible', [
                'id_demande' => $mailPayload['demand_id'] ?? null,
                'destinataire_email' => $mailPayload['email'] ?? null,
                'status_code' => $statusCode,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param array{
     *     tracking_number: string,
     *     subject_label: string,
     *     event_code: string,
     *     actor_name: string,
     *     service_label: string|null,
     *     comment: string|null,
     *     login_url: string
     * } $mailPayload
     */
    private function assignmentNotificationContent(array $mailPayload): string
    {
        $lines = [
            $mailPayload['intro_line'],
            'Numero de suivi : '.$mailPayload['tracking_number'],
            'Objet de la demande : '.$mailPayload['subject_label'],
            'Action realisee par : '.$mailPayload['actor_name'],
        ];

        if ($mailPayload['service_label']) {
            $lines[] = 'Service concerne : '.$mailPayload['service_label'];
        }
        if ($mailPayload['comment']) {
            $lines[] = 'Commentaire : '.$mailPayload['comment'];
        }

        $lines[] = $mailPayload['instruction_line'];
        $lines[] = 'Lien de connexion : '.$mailPayload['login_url'];

        return implode("\n", $lines);
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

    private function sanitizeOriginalFilename(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $baseName = Str::of($baseName)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9._-]+/', '_')
            ->trim('._-')
            ->limit(80, '')
            ->value();

        if ($baseName === '') {
            $baseName = 'piece_jointe';
        }

        return $extension !== '' ? "{$baseName}.{$extension}" : $baseName;
    }

    private function isResponseDeliveryPending(object $demand): bool
    {
        return !empty($demand->date_demande_envoi_usager)
            && empty($demand->date_envoi_usager)
            && empty($demand->date_cloture);
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
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Email usager invalide ou manquant', [
                'id_demande' => $demandId,
                'email' => $email !== '' ? $email : null,
            ]);

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
