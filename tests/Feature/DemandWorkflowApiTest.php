<?php

namespace Tests\Feature;

use App\Jobs\SendDemandResponseJob;
use App\Mail\DemandResponseMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DemandWorkflowApiTest extends TestCase
{
    use RefreshDatabase;

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
