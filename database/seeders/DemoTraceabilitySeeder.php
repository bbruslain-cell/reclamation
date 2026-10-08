<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoTraceabilitySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now(config('app.timezone'));

        $statusIds = DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->pluck('id_parametre', 'code');

        $responseTypeIds = DB::table('parametres')
            ->where('famille', 'type_reponse')
            ->pluck('id_parametre', 'code');

        $userIds = DB::table('utilisateurs')
            ->whereIn('email', [
                'accueil@anbg.ga',
                'chef.daf@anbg.ga',
                'chef.ds@anbg.ga',
                'chef.dsic@anbg.ga',
                'agent.daf@anbg.ga',
                'agent.dsic@anbg.ga',
            ])
            ->pluck('id_utilisateur', 'email');

        $serviceIds = DB::table('services')
            ->whereIn('code', ['CS_SENB', 'CS_FC', 'CS_SIRS'])
            ->pluck('id_service', 'code');

        $demandRows = DB::table('demandes')
            ->whereIn('numero_suivi', [
                'ANBG-2026-0001',
                'ANBG-2026-0002',
                'ANBG-2026-0003',
                'ANBG-2026-0004',
                'ANBG-2026-0005',
            ])
            ->get()
            ->keyBy('numero_suivi');

        if ($demandRows->count() === 0) {
            return;
        }

        $demandIds = $demandRows->pluck('id_demande')->map(fn ($value) => (int) $value)->values()->all();

        $responseIds = DB::table('reponses')
            ->whereIn('id_demande', $demandIds)
            ->pluck('id_reponse')
            ->map(fn ($value) => (int) $value)
            ->values()
            ->all();

        if ($responseIds !== []) {
            DB::table('reponse_piece_jointe')->whereIn('id_reponse', $responseIds)->delete();
        }

        DB::table('historique_actions')->whereIn('id_demande', $demandIds)->delete();
        DB::table('affectations')->whereIn('id_demande', $demandIds)->delete();
        DB::table('reponses')->whereIn('id_demande', $demandIds)->delete();

        $this->seedSoumission($demandRows['ANBG-2026-0001'] ?? null, (int) ($statusIds['nouvelle'] ?? 0));

        $this->seedServiceAssignment(
            demand: $demandRows['ANBG-2026-0002'] ?? null,
            accueilId: (int) ($userIds['accueil@anbg.ga'] ?? 0),
            serviceId: (int) ($serviceIds['CS_SENB'] ?? 0),
            oldStatusId: (int) ($statusIds['nouvelle'] ?? 0),
            newStatusId: (int) ($statusIds['affectee_service'] ?? 0),
            comment: 'Affectation initiale au service de la scolarite'
        );

        $this->seedServiceAssignment(
            demand: $demandRows['ANBG-2026-0003'] ?? null,
            accueilId: (int) ($userIds['accueil@anbg.ga'] ?? 0),
            serviceId: (int) ($serviceIds['CS_FC'] ?? 0),
            oldStatusId: (int) ($statusIds['nouvelle'] ?? 0),
            newStatusId: (int) ($statusIds['affectee_service'] ?? 0),
            comment: 'Orientation vers le service financier et comptable'
        );
        $this->seedAgentAssignment(
            demand: $demandRows['ANBG-2026-0003'] ?? null,
            chefId: (int) ($userIds['chef.daf@anbg.ga'] ?? 0),
            serviceId: (int) ($serviceIds['CS_FC'] ?? 0),
            oldStatusId: (int) ($statusIds['affectee_service'] ?? 0),
            newStatusId: (int) ($statusIds['affectee_agent'] ?? 0),
            agentId: (int) ($userIds['agent.daf@anbg.ga'] ?? 0),
            comment: 'Affectation a un agent pour instruction'
        );

        $this->seedServiceAssignment(
            demand: $demandRows['ANBG-2026-0004'] ?? null,
            accueilId: (int) ($userIds['accueil@anbg.ga'] ?? 0),
            serviceId: (int) ($serviceIds['CS_SIRS'] ?? 0),
            oldStatusId: (int) ($statusIds['nouvelle'] ?? 0),
            newStatusId: (int) ($statusIds['affectee_service'] ?? 0),
            comment: 'Transmission au service systemes d informations'
        );
        $this->seedAgentAssignment(
            demand: $demandRows['ANBG-2026-0004'] ?? null,
            chefId: (int) ($userIds['chef.dsic@anbg.ga'] ?? 0),
            serviceId: (int) ($serviceIds['CS_SIRS'] ?? 0),
            oldStatusId: (int) ($statusIds['affectee_service'] ?? 0),
            newStatusId: (int) ($statusIds['affectee_agent'] ?? 0),
            agentId: (int) ($userIds['agent.dsic@anbg.ga'] ?? 0),
            comment: 'Affectation a l agent reseaux et securite'
        );
        $this->seedFinalResponse(
            demand: $demandRows['ANBG-2026-0004'] ?? null,
            responseTypeId: (int) ($responseTypeIds['finale'] ?? 0),
            actorId: (int) ($userIds['agent.dsic@anbg.ga'] ?? 0),
            oldStatusResponseReady: (int) ($statusIds['affectee_agent'] ?? 0),
            responseReadyStatusId: (int) ($statusIds['reponse_prete'] ?? 0),
            closedStatusId: (int) ($statusIds['cloturee'] ?? 0),
            serviceId: (int) ($serviceIds['CS_SIRS'] ?? 0),
            content: 'Votre compte eBourse a ete reinitialise. Vous pouvez vous reconnecter avec le mot de passe provisoire communique.',
            finalAction: null
        );

        $this->seedServiceAssignment(
            demand: $demandRows['ANBG-2026-0005'] ?? null,
            accueilId: (int) ($userIds['accueil@anbg.ga'] ?? 0),
            serviceId: (int) ($serviceIds['CS_FC'] ?? 0),
            oldStatusId: (int) ($statusIds['nouvelle'] ?? 0),
            newStatusId: (int) ($statusIds['affectee_service'] ?? 0),
            comment: 'Transmission au chef du service financier et comptable'
        );
        $this->seedFinalResponse(
            demand: $demandRows['ANBG-2026-0005'] ?? null,
            responseTypeId: (int) ($responseTypeIds['finale'] ?? 0),
            actorId: (int) ($userIds['chef.daf@anbg.ga'] ?? 0),
            oldStatusResponseReady: (int) ($statusIds['affectee_service'] ?? 0),
            responseReadyStatusId: (int) ($statusIds['reponse_prete'] ?? 0),
            closedStatusId: (int) ($statusIds['cloturee'] ?? 0),
            serviceId: (int) ($serviceIds['CS_FC'] ?? 0),
            content: 'Votre dossier a ete regularise apres verification. Les pieces complementaires attendues ont ete prises en compte.',
            finalAction: 'reponse_directe_chef'
        );
    }

    private function seedSoumission(?object $demand, int $statusId): void
    {
        if (!$demand || !$statusId) {
            return;
        }

        $this->insertHistory([
            'id_demande' => (int) $demand->id_demande,
            'id_utilisateur' => null,
            'type_action' => 'soumission_usager',
            'ancien_statut_id' => null,
            'nouveau_statut_id' => $statusId,
            'id_service_associe' => null,
            'id_agent_associe' => null,
            'date_action' => $demand->date_soumission,
            'commentaire' => 'Soumission publique',
        ]);
    }

    private function seedServiceAssignment(
        ?object $demand,
        int $accueilId,
        int $serviceId,
        int $oldStatusId,
        int $newStatusId,
        string $comment
    ): void {
        if (!$demand || !$accueilId || !$serviceId || !$newStatusId) {
            return;
        }

        $this->seedSoumission($demand, $oldStatusId);

        $assignmentDate = $demand->date_affectation_accueil;
        if (!$assignmentDate) {
            return;
        }

        DB::table('affectations')->insert([
            'id_demande' => (int) $demand->id_demande,
            'id_service' => $serviceId,
            'id_utilisateur' => $accueilId,
            'date_affectation' => $assignmentDate,
            'commentaire' => $comment,
            'created_at' => $assignmentDate,
            'updated_at' => $assignmentDate,
        ]);

        $this->insertHistory([
            'id_demande' => (int) $demand->id_demande,
            'id_utilisateur' => $accueilId,
            'type_action' => 'affectation_service',
            'ancien_statut_id' => $oldStatusId ?: null,
            'nouveau_statut_id' => $newStatusId,
            'id_service_associe' => $serviceId,
            'id_agent_associe' => null,
            'date_action' => $assignmentDate,
            'commentaire' => $comment,
        ]);
    }

    private function seedAgentAssignment(
        ?object $demand,
        int $chefId,
        int $serviceId,
        int $oldStatusId,
        int $newStatusId,
        int $agentId,
        string $comment
    ): void {
        if (!$demand || !$chefId || !$agentId || !$serviceId || !$newStatusId || !$demand->date_affectation_agent) {
            return;
        }

        $this->insertHistory([
            'id_demande' => (int) $demand->id_demande,
            'id_utilisateur' => $chefId,
            'type_action' => 'affectation_agent',
            'ancien_statut_id' => $oldStatusId ?: null,
            'nouveau_statut_id' => $newStatusId,
            'id_service_associe' => $serviceId,
            'id_agent_associe' => $agentId,
            'date_action' => $demand->date_affectation_agent,
            'commentaire' => $comment,
        ]);
    }

    private function seedFinalResponse(
        ?object $demand,
        int $responseTypeId,
        int $actorId,
        int $oldStatusResponseReady,
        int $responseReadyStatusId,
        int $closedStatusId,
        int $serviceId,
        string $content,
        ?string $finalAction
    ): void {
        if (!$demand || !$responseTypeId || !$actorId || !$responseReadyStatusId || !$closedStatusId) {
            return;
        }

        $draftDate = $demand->date_reponse_direction ?: $demand->date_envoi_usager;
        $sendDate = $demand->date_envoi_usager ?: $demand->date_cloture;

        if (!$draftDate || !$sendDate) {
            return;
        }

        DB::table('reponses')->insert([
            'id_demande' => (int) $demand->id_demande,
            'numero_version' => 1,
            'id_type_reponse' => $responseTypeId,
            'contenu_reponse' => $content,
            'id_redacteur' => $actorId,
            'id_envoyeur' => $actorId,
            'date_redaction' => $draftDate,
            'date_envoi_usager' => $sendDate,
            'created_at' => $draftDate,
            'updated_at' => $sendDate,
        ]);

        $this->insertHistory([
            'id_demande' => (int) $demand->id_demande,
            'id_utilisateur' => $actorId,
            'type_action' => 'reponse_redigee',
            'ancien_statut_id' => $oldStatusResponseReady ?: null,
            'nouveau_statut_id' => $responseReadyStatusId,
            'id_service_associe' => $serviceId,
            'id_agent_associe' => null,
            'date_action' => $draftDate,
            'commentaire' => 'Version 1',
        ]);

        $this->insertHistory([
            'id_demande' => (int) $demand->id_demande,
            'id_utilisateur' => $actorId,
            'type_action' => 'envoi_reponse',
            'ancien_statut_id' => $responseReadyStatusId,
            'nouveau_statut_id' => $closedStatusId,
            'id_service_associe' => $serviceId,
            'id_agent_associe' => null,
            'date_action' => $sendDate,
            'commentaire' => 'Envoi final a l usager',
        ]);

        if ($finalAction !== null) {
            $finalDate = Carbon::parse($sendDate)->addMinute();
            $this->insertHistory([
                'id_demande' => (int) $demand->id_demande,
                'id_utilisateur' => $actorId,
                'type_action' => $finalAction,
                'ancien_statut_id' => null,
                'nouveau_statut_id' => null,
                'id_service_associe' => $serviceId,
                'id_agent_associe' => null,
                'date_action' => $finalDate,
                'commentaire' => 'Reponse directe chef de service',
            ]);
        }
    }

    private function insertHistory(array $payload): void
    {
        $dateAction = $payload['date_action'] instanceof Carbon
            ? $payload['date_action']
            : Carbon::parse((string) $payload['date_action']);

        DB::table('historique_actions')->insert([
            'id_demande' => $payload['id_demande'],
            'id_utilisateur' => $payload['id_utilisateur'],
            'type_action' => $payload['type_action'],
            'ancien_statut_id' => $payload['ancien_statut_id'],
            'nouveau_statut_id' => $payload['nouveau_statut_id'],
            'id_service_associe' => $payload['id_service_associe'],
            'id_agent_associe' => $payload['id_agent_associe'],
            'date_action' => $dateAction,
            'commentaire' => $payload['commentaire'],
            'created_at' => $dateAction,
            'updated_at' => $dateAction,
        ]);
    }
}
