<?php

namespace Tests\Feature;

use App\Jobs\SendDemandResponseJob;
use App\Mail\DemandAssignmentNotificationMail;
use App\Mail\DemandResponseMail;
use App\Services\DemandWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class DemandWorkflowApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_plain_text_response_mail_keeps_apostrophes_unescaped(): void
    {
        $body = view('emails.demand-response-text', [
            'recipientName' => 'Therence Edzome',
            'trackingNumber' => 'ANBG-2026-003',
            'subjectLabel' => "Reclamation sur l'accueil",
            'responseContent' => "Reponse envoyee a travers l'application.",
            'attachmentCount' => 0,
        ])->render();

        $this->assertStringContainsString("Reclamation sur l'accueil", $body);
        $this->assertStringContainsString("Reponse envoyee a travers l'application.", $body);
        $this->assertStringNotContainsString('&#039;', $body);
    }

    public function test_plain_text_assignment_mail_keeps_apostrophes_unescaped(): void
    {
        $body = view('emails.demand-assignment-notification-text', [
            'recipientName' => 'Agent Test',
            'trackingNumber' => 'ANBG-2026-003',
            'subjectLabel' => "Verification d'un dossier",
            'eventCode' => 'agent_assigned',
            'introLine' => "Une demande vous a ete affectee par l'accueil.",
            'instructionLine' => "Merci de vous connecter a l'application pour la traiter.",
            'actorName' => 'Accueil Service',
            'serviceLabel' => 'CS_SENB - Etudiants Boursiers',
            'comment' => "Dossier d'etudiant a verifier.",
            'loginUrl' => 'https://reclamations.anbg.ga/espace',
        ])->render();

        $this->assertStringContainsString("Verification d'un dossier", $body);
        $this->assertStringContainsString("par l'accueil", $body);
        $this->assertStringContainsString("a l'application", $body);
        $this->assertStringContainsString("Dossier d'etudiant", $body);
        $this->assertStringNotContainsString('&#039;', $body);
    }

    public function test_full_workflow_from_assignment_to_final_send(): void
    {
        $this->seed();
        Queue::fake();
        Mail::fake();

        $demandeId = (int) DB::table('demandes')->where('numero_suivi', 'ANBG-2026-0001')->value('id_demande');
        $serviceId = (int) DB::table('services')->where('code', 'CS_SENB')->value('id_service');
        $accueilId = (int) DB::table('utilisateurs')->where('email', 'accueil@anbg.ga')->value('id_utilisateur');
        $chefId = (int) DB::table('utilisateurs')->where('email', 'chef.ds@anbg.ga')->value('id_utilisateur');
        $agentId = (int) DB::table('utilisateurs')->where('email', 'agent.ds@anbg.ga')->value('id_utilisateur');
        DB::table('utilisateurs')
            ->whereIn('id_utilisateur', [$accueilId, $chefId, $agentId])
            ->update(['changement_mdp_requis' => false]);
        $usagerEmail = (string) DB::table('demandes as d')
            ->join('usagers as u', 'u.id_usager', '=', 'd.id_usager')
            ->where('d.id_demande', $demandeId)
            ->value('u.email');

        $this->withHeader('X-User-Id', (string) $accueilId)
            ->putJson("/api/demandes/{$demandeId}/affecter", [
                'id_service' => $serviceId,
                'commentaire' => 'Affectation test',
            ])
            ->assertOk();

        $this->withHeader('X-User-Id', (string) $chefId)
            ->putJson("/api/demandes/{$demandeId}/affecter-agent", [
                'id_agent' => $agentId,
                'commentaire' => 'Affectation agent test',
            ])
            ->assertOk();

        $this->withHeader('X-User-Id', (string) $agentId)
            ->putJson("/api/demandes/{$demandeId}/rediger-reponse", [
                'contenu_reponse' => 'Votre dossier est en cours de traitement.',
                'type_reponse_code' => 'finale',
            ])
            ->assertOk();

        $this->withHeader('X-User-Id', (string) $agentId)
            ->putJson("/api/demandes/{$demandeId}/envoyer-reponse")
            ->assertOk();

        $statusCode = DB::table('demandes as d')
            ->join('parametres as p', 'p.id_parametre', '=', 'd.id_statut')
            ->where('d.id_demande', $demandeId)
            ->value('p.code');

        $this->assertSame('reponse_prete', $statusCode);
        $this->assertNotNull(DB::table('demandes')->where('id_demande', $demandeId)->value('date_demande_envoi_usager'));
        $this->assertNull(DB::table('demandes')->where('id_demande', $demandeId)->value('date_envoi_usager'));

        Queue::assertPushed(SendDemandResponseJob::class, function (SendDemandResponseJob $job) use ($demandeId, $usagerEmail): bool {
            app()->call([$job, 'handle']);

            Mail::assertSent(DemandResponseMail::class, function (DemandResponseMail $mail) use ($usagerEmail): bool {
                return $mail->hasTo($usagerEmail);
            });

            return $job->demandId === $demandeId;
        });

        $statusCode = DB::table('demandes as d')
            ->join('parametres as p', 'p.id_parametre', '=', 'd.id_statut')
            ->where('d.id_demande', $demandeId)
            ->value('p.code');

        $this->assertSame('cloturee', $statusCode);
    }

    public function test_failed_final_response_delivery_can_be_retried_from_web_interface(): void
    {
        $this->seed();
        Queue::fake();
        Mail::fake();

        $demandeId = (int) DB::table('demandes')->where('numero_suivi', 'ANBG-2026-0001')->value('id_demande');
        $serviceId = (int) DB::table('services')->where('code', 'CS_SENB')->value('id_service');
        $accueilId = (int) DB::table('utilisateurs')->where('email', 'accueil@anbg.ga')->value('id_utilisateur');
        $chefId = (int) DB::table('utilisateurs')->where('email', 'chef.ds@anbg.ga')->value('id_utilisateur');
        $agentId = (int) DB::table('utilisateurs')->where('email', 'agent.ds@anbg.ga')->value('id_utilisateur');
        DB::table('utilisateurs')
            ->whereIn('id_utilisateur', [$accueilId, $chefId, $agentId])
            ->update(['changement_mdp_requis' => false]);

        $this->withHeader('X-User-Id', (string) $accueilId)
            ->putJson("/api/demandes/{$demandeId}/affecter", [
                'id_service' => $serviceId,
            ])
            ->assertOk();

        $this->withHeader('X-User-Id', (string) $chefId)
            ->putJson("/api/demandes/{$demandeId}/affecter-agent", [
                'id_agent' => $agentId,
            ])
            ->assertOk();

        $this->withHeader('X-User-Id', (string) $agentId)
            ->putJson("/api/demandes/{$demandeId}/rediger-reponse", [
                'contenu_reponse' => 'Votre dossier a ete verifie par le service concerne.',
                'type_reponse_code' => 'finale',
            ])
            ->assertOk();

        $this->withHeader('X-User-Id', (string) $agentId)
            ->putJson("/api/demandes/{$demandeId}/envoyer-reponse")
            ->assertOk();

        Queue::assertPushed(SendDemandResponseJob::class, 1);

        $responseId = (int) DB::table('reponses')
            ->where('id_demande', $demandeId)
            ->max('id_reponse');
        app(DemandWorkflowService::class)->markQueuedFinalResponseFailed(
            actorId: $agentId,
            demandId: $demandeId,
            responseId: $responseId,
            exception: new RuntimeException('Connection could not be established with host "ssl://mail.anbg.ga:465"')
        );

        $this->assertDatabaseHas('historique_actions', [
            'id_demande' => $demandeId,
            'type_action' => 'echec_envoi_reponse',
            'commentaire' => "Echec d'envoi a l'usager. Verifiez le serveur mail puis relancez l'envoi.",
        ]);
        $this->assertDatabaseMissing('historique_actions', [
            'id_demande' => $demandeId,
            'type_action' => 'echec_envoi_reponse',
            'commentaire' => 'Echec envoi usager : Connection could not be established with host "ssl://mail.anbg.ga:465"',
        ]);

        $this->withHeader('X-User-Id', (string) $agentId)
            ->put("/demandes/{$demandeId}/relancer-envoi")
            ->assertRedirect()
            ->assertSessionHas('success');

        $demand = DB::table('demandes')->where('id_demande', $demandeId)->first();
        $response = DB::table('reponses')->where('id_reponse', $responseId)->first();

        $this->assertNotNull($demand->date_demande_envoi_usager);
        $this->assertNull($demand->date_echec_envoi_usager);
        $this->assertNotNull($response->date_demande_envoi_usager);
        $this->assertNull($response->date_echec_envoi_usager);

        Queue::assertPushed(SendDemandResponseJob::class, 2);
    }

    public function test_assign_agent_updates_status_and_fields(): void
    {
        $this->seed();

        $demandeId = (int) DB::table('demandes')->where('numero_suivi', 'ANBG-2026-0001')->value('id_demande');
        $serviceId = (int) DB::table('services')->where('code', 'CS_SENB')->value('id_service');
        $accueilId = (int) DB::table('utilisateurs')->where('email', 'accueil@anbg.ga')->value('id_utilisateur');
        $chefId = (int) DB::table('utilisateurs')->where('email', 'chef.ds@anbg.ga')->value('id_utilisateur');
        $agentId = (int) DB::table('utilisateurs')->where('email', 'agent.ds@anbg.ga')->value('id_utilisateur');
        DB::table('utilisateurs')
            ->whereIn('id_utilisateur', [$accueilId, $chefId, $agentId])
            ->update(['changement_mdp_requis' => false]);

        $this->withHeader('X-User-Id', (string) $accueilId)
            ->putJson("/api/demandes/{$demandeId}/affecter", [
                'id_service' => $serviceId,
            ])
            ->assertOk();

        $this->withHeader('X-User-Id', (string) $chefId)
            ->putJson("/api/demandes/{$demandeId}/affecter-agent", [
                'id_agent' => $agentId,
            ])
            ->assertOk();

        $row = DB::table('demandes as d')
            ->join('parametres as st', 'st.id_parametre', '=', 'd.id_statut')
            ->where('d.id_demande', $demandeId)
            ->select('st.code as statut_code', 'd.id_agent_traitant', 'd.date_affectation_agent')
            ->first();

        $this->assertSame('affectee_agent', $row->statut_code);
        $this->assertSame($agentId, (int) $row->id_agent_traitant);
        $this->assertNotNull($row->date_affectation_agent);
    }

    public function test_service_assignment_sends_mail_to_service_chief(): void
    {
        $this->seed();
        Mail::fake();

        $demandeId = (int) DB::table('demandes')->where('numero_suivi', 'ANBG-2026-0001')->value('id_demande');
        $serviceId = (int) DB::table('services')->where('code', 'CS_SENB')->value('id_service');
        $accueilId = (int) DB::table('utilisateurs')->where('email', 'accueil@anbg.ga')->value('id_utilisateur');

        DB::table('utilisateurs')
            ->where('id_utilisateur', $accueilId)
            ->update(['changement_mdp_requis' => false]);

        $this->withHeader('X-User-Id', (string) $accueilId)
            ->putJson("/api/demandes/{$demandeId}/affecter", [
                'id_service' => $serviceId,
                'commentaire' => "Merci d'analyser la demande.",
            ])
            ->assertOk();

        Mail::assertSent(DemandAssignmentNotificationMail::class, function (DemandAssignmentNotificationMail $mail): bool {
            return $mail->hasTo('chef.ds@anbg.ga')
                && $mail->eventCode === 'service_assigned'
                && $mail->actorName === 'Accueil Service'
                && $mail->comment === "Merci d'analyser la demande.";
        });

        $this->assertDatabaseHas('notifications', [
            'destinataire_email' => 'chef.ds@anbg.ga',
            'sujet' => 'ANBG - Nouvelle demande affectée au service ANBG-2026-0001',
        ]);
    }

    public function test_agent_assignment_and_cancellation_send_mail_to_agent(): void
    {
        $this->seed();
        Mail::fake();

        $demandeId = (int) DB::table('demandes')->where('numero_suivi', 'ANBG-2026-0001')->value('id_demande');
        $serviceId = (int) DB::table('services')->where('code', 'CS_SENB')->value('id_service');
        $accueilId = (int) DB::table('utilisateurs')->where('email', 'accueil@anbg.ga')->value('id_utilisateur');
        $chefId = (int) DB::table('utilisateurs')->where('email', 'chef.ds@anbg.ga')->value('id_utilisateur');
        $agentId = (int) DB::table('utilisateurs')->where('email', 'agent.ds@anbg.ga')->value('id_utilisateur');

        DB::table('utilisateurs')
            ->whereIn('id_utilisateur', [$accueilId, $chefId, $agentId])
            ->update(['changement_mdp_requis' => false]);

        $this->withHeader('X-User-Id', (string) $accueilId)
            ->putJson("/api/demandes/{$demandeId}/affecter", [
                'id_service' => $serviceId,
            ])
            ->assertOk();

        $this->withHeader('X-User-Id', (string) $chefId)
            ->putJson("/api/demandes/{$demandeId}/affecter-agent", [
                'id_agent' => $agentId,
                'commentaire' => 'Prise en charge prioritaire.',
            ])
            ->assertOk();

        $this->withHeader('X-User-Id', (string) $chefId)
            ->putJson("/api/demandes/{$demandeId}/annuler-affectation-agent", [
                'commentaire' => 'Agent indisponible.',
            ])
            ->assertOk();

        Mail::assertSent(DemandAssignmentNotificationMail::class, function (DemandAssignmentNotificationMail $mail): bool {
            return $mail->hasTo('agent.ds@anbg.ga')
                && $mail->eventCode === 'agent_assigned'
                && str_ends_with($mail->loginUrl, '/login')
                && $mail->actorName === 'Scolarite Chef'
                && $mail->comment === 'Prise en charge prioritaire.';
        });

        Mail::assertSent(DemandAssignmentNotificationMail::class, function (DemandAssignmentNotificationMail $mail): bool {
            return $mail->hasTo('agent.ds@anbg.ga')
                && $mail->eventCode === 'agent_assignment_cancelled'
                && $mail->actorName === 'Scolarite Chef'
                && $mail->comment === 'Agent indisponible.';
        });
    }

    public function test_agent_assignment_mail_failure_does_not_block_assignment(): void
    {
        $this->seed();

        $demandeId = (int) DB::table('demandes')->where('numero_suivi', 'ANBG-2026-0001')->value('id_demande');
        $serviceId = (int) DB::table('services')->where('code', 'CS_SENB')->value('id_service');
        $statusAffecteeService = (int) DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->where('code', 'affectee_service')
            ->value('id_parametre');
        $chefId = (int) DB::table('utilisateurs')->where('email', 'chef.ds@anbg.ga')->value('id_utilisateur');
        $agentId = (int) DB::table('utilisateurs')->where('email', 'agent.ds@anbg.ga')->value('id_utilisateur');

        DB::table('utilisateurs')
            ->whereIn('id_utilisateur', [$chefId, $agentId])
            ->update(['changement_mdp_requis' => false]);
        DB::table('demandes')
            ->where('id_demande', $demandeId)
            ->update([
                'id_service_courant' => $serviceId,
                'id_statut' => $statusAffecteeService,
                'date_affectation_accueil' => now(),
                'updated_at' => now(),
            ]);

        Mail::shouldReceive('to')
            ->once()
            ->with('agent.ds@anbg.ga')
            ->andReturn(new class {
                public function send(object $mailable): void
                {
                    throw new RuntimeException('SMTP indisponible');
                }
            });

        $this->withHeader('X-User-Id', (string) $chefId)
            ->putJson("/api/demandes/{$demandeId}/affecter-agent", [
                'id_agent' => $agentId,
            ])
            ->assertOk();

        $row = DB::table('demandes as d')
            ->join('parametres as st', 'st.id_parametre', '=', 'd.id_statut')
            ->where('d.id_demande', $demandeId)
            ->select('st.code as statut_code', 'd.id_agent_traitant')
            ->first();

        $this->assertSame('affectee_agent', $row->statut_code);
        $this->assertSame($agentId, (int) $row->id_agent_traitant);
        $this->assertDatabaseHas('notifications', [
            'id_demande' => $demandeId,
            'destinataire_email' => 'agent.ds@anbg.ga',
            'message_erreur' => 'SMTP indisponible',
        ]);
    }

    public function test_chef_can_cancel_agent_assignment(): void
    {
        $this->seed();

        $demandeId = (int) DB::table('demandes')->where('numero_suivi', 'ANBG-2026-0001')->value('id_demande');
        $serviceId = (int) DB::table('services')->where('code', 'CS_SENB')->value('id_service');
        $accueilId = (int) DB::table('utilisateurs')->where('email', 'accueil@anbg.ga')->value('id_utilisateur');
        $chefId = (int) DB::table('utilisateurs')->where('email', 'chef.ds@anbg.ga')->value('id_utilisateur');
        $agentId = (int) DB::table('utilisateurs')->where('email', 'agent.ds@anbg.ga')->value('id_utilisateur');
        DB::table('utilisateurs')
            ->whereIn('id_utilisateur', [$accueilId, $chefId, $agentId])
            ->update(['changement_mdp_requis' => false]);

        $this->withHeader('X-User-Id', (string) $accueilId)
            ->putJson("/api/demandes/{$demandeId}/affecter", [
                'id_service' => $serviceId,
            ])
            ->assertOk();

        $this->withHeader('X-User-Id', (string) $chefId)
            ->putJson("/api/demandes/{$demandeId}/affecter-agent", [
                'id_agent' => $agentId,
            ])
            ->assertOk();

        $this->withHeader('X-User-Id', (string) $chefId)
            ->putJson("/api/demandes/{$demandeId}/annuler-affectation-agent", [
                'commentaire' => 'Agent indisponible',
            ])
            ->assertOk();

        $row = DB::table('demandes as d')
            ->join('parametres as st', 'st.id_parametre', '=', 'd.id_statut')
            ->where('d.id_demande', $demandeId)
            ->select('st.code as statut_code', 'd.id_agent_traitant', 'd.date_affectation_agent')
            ->first();

        $this->assertSame('affectee_service', $row->statut_code);
        $this->assertNull($row->id_agent_traitant);
        $this->assertNull($row->date_affectation_agent);
    }

    public function test_chef_api_cannot_reply_directly_after_assigning_an_agent(): void
    {
        $this->seed();

        $demandeId = (int) DB::table('demandes')->where('numero_suivi', 'ANBG-2026-0001')->value('id_demande');
        $serviceId = (int) DB::table('services')->where('code', 'CS_SENB')->value('id_service');
        $accueilId = (int) DB::table('utilisateurs')->where('email', 'accueil@anbg.ga')->value('id_utilisateur');
        $chefId = (int) DB::table('utilisateurs')->where('email', 'chef.ds@anbg.ga')->value('id_utilisateur');
        $agentId = (int) DB::table('utilisateurs')->where('email', 'agent.ds@anbg.ga')->value('id_utilisateur');
        DB::table('utilisateurs')
            ->whereIn('id_utilisateur', [$accueilId, $chefId, $agentId])
            ->update(['changement_mdp_requis' => false]);

        $this->withHeader('X-User-Id', (string) $accueilId)
            ->putJson("/api/demandes/{$demandeId}/affecter", [
                'id_service' => $serviceId,
            ])
            ->assertOk();

        $this->withHeader('X-User-Id', (string) $chefId)
            ->putJson("/api/demandes/{$demandeId}/affecter-agent", [
                'id_agent' => $agentId,
            ])
            ->assertOk();

        $this->withHeader('X-User-Id', (string) $chefId)
            ->putJson("/api/demandes/{$demandeId}/rediger-reponse", [
                'contenu_reponse' => 'Tentative de reponse directe du chef apres affectation.',
                'type_reponse_code' => 'finale',
            ])
            ->assertForbidden();
    }

}
