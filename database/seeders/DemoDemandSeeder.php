<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DemoDemandSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now(config('app.timezone'));
        $configId = DB::table('config_sla')->where('actif', true)->value('id_config_sla');
        if (!$configId) {
            return;
        }

        $typeReclamation = DB::table('parametres')
            ->where('famille', 'type_demande')
            ->where('code', 'reclamation')
            ->value('id_parametre');

        $statutNouvelle = DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->where('code', 'nouvelle')
            ->value('id_parametre');
        $statutAffecteeService = DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->where('code', 'affectee_service')
            ->value('id_parametre');
        $statutAffecteeAgent = DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->where('code', 'affectee_agent')
            ->value('id_parametre');
        $statutReponsePrete = DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->where('code', 'reponse_prete')
            ->value('id_parametre');
        $statutCloturee = DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->where('code', 'cloturee')
            ->value('id_parametre');

        $serviceDs = DB::table('services')->where('code', 'CS_SENB')->value('id_service');
        $serviceDaf = DB::table('services')->where('code', 'CS_FC')->value('id_service');
        $serviceDsic = DB::table('services')->where('code', 'CS_SIRS')->value('id_service');
        $agentDs = DB::table('utilisateurs')->where('email', 'agent.ds@anbg.ga')->value('id_utilisateur');
        $agentDaf = DB::table('utilisateurs')->where('email', 'agent.daf@anbg.ga')->value('id_utilisateur');
        $agentDsic = DB::table('utilisateurs')->where('email', 'agent.dsic@anbg.ga')->value('id_utilisateur');

        $usagerRows = [
            ['nom' => 'MBOUMBA', 'prenom' => 'Aline', 'email' => 'aline@example.com', 'statut_usager' => 'Étudiant', 'pays' => 'Gabon', 'etablissement' => 'Université Omar Bongo'],
            ['nom' => 'OBIANG', 'prenom' => 'Kevin', 'email' => 'kevin@example.com', 'statut_usager' => 'Étudiant', 'pays' => 'Gabon', 'etablissement' => 'Université des Sciences et Techniques de Masuku'],
            ['nom' => 'MVEMBA', 'prenom' => 'Sarah', 'email' => 'sarah@example.com', 'statut_usager' => 'Parent / Tuteur', 'pays' => 'Gabon', 'etablissement' => null],
            ['nom' => 'NZE', 'prenom' => 'Mickael', 'email' => 'mickael@example.com', 'statut_usager' => 'Élève', 'pays' => 'Gabon', 'etablissement' => 'Lycée national Léon Mba'],
        ];

        $usagerIds = [];
        foreach ($usagerRows as $row) {
            DB::table('usagers')->updateOrInsert(
                ['email' => $row['email']],
                array_merge($row, [
                    'consentement_rgpd' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
            $usagerIds[] = DB::table('usagers')->where('email', $row['email'])->value('id_usager');
        }

        $accueilId = DB::table('utilisateurs')->where('email', 'accueil@anbg.ga')->value('id_utilisateur');
        $chefDaf = DB::table('utilisateurs')->where('email', 'chef.daf@anbg.ga')->value('id_utilisateur');

        $demandes = [
            [
                'numero_suivi' => 'ANBG-2026-0001',
                'id_usager' => $usagerIds[0],
                'id_type_demande' => $typeReclamation,
                'id_statut' => $statutNouvelle,
                'id_service_courant' => null,
                'objet' => 'Retard de paiement de bourse',
                'categorie' => 'Reclamation diverses',
                'message' => 'Je signale un retard de paiement sur ma bourse et souhaite une verification.',
                'date_soumission' => $now->copy()->subHours(8),
                'delai_alerte' => 'dans_les_delais',
            ],
            [
                'numero_suivi' => 'ANBG-2026-0002',
                'id_usager' => $usagerIds[1],
                'id_type_demande' => $typeReclamation,
                'id_statut' => $statutAffecteeService,
                'id_service_courant' => $serviceDs,
                'objet' => 'Recours apres deliberation',
                'categorie' => 'Recours apres deliberation de la CT',
                'message' => 'Je depose un recours suite a la deliberation de la commission.',
                'id_agent_accueil' => $accueilId,
                'date_soumission' => $now->copy()->subHours(30),
                'date_affectation' => $now->copy()->subHours(26),
                'date_affectation_accueil' => $now->copy()->subHours(26),
                'alerte_accueil' => 'orange',
                'delai_alerte' => 'a_risque',
            ],
            [
                'numero_suivi' => 'ANBG-2026-0003',
                'id_usager' => $usagerIds[2],
                'id_type_demande' => $typeReclamation,
                'id_statut' => $statutAffecteeAgent,
                'id_service_courant' => $serviceDaf,
                'id_agent_traitant' => $agentDaf,
                'objet' => 'Reclamation frais de scolarite',
                'categorie' => 'Reclamation du paiement des frais de scolarite',
                'message' => 'Mes frais de scolarite ne sont pas encore regles.',
                'id_agent_accueil' => $accueilId,
                'date_soumission' => $now->copy()->subHours(53),
                'date_affectation' => $now->copy()->subHours(48),
                'date_affectation_accueil' => $now->copy()->subHours(48),
                'date_affectation_agent' => $now->copy()->subHours(30),
                'alerte_accueil' => 'rouge',
                'alerte_chef' => 'orange',
                'alerte_agent' => 'orange',
                'delai_alerte' => 'a_risque',
            ],
            [
                'numero_suivi' => 'ANBG-2026-0004',
                'id_usager' => $usagerIds[3],
                'id_type_demande' => $typeReclamation,
                'id_statut' => $statutCloturee,
                'id_service_courant' => $serviceDsic,
                'id_agent_traitant' => $agentDsic,
                'objet' => 'Reclamation acces compte eBourse',
                'categorie' => 'Reclamation diverses',
                'message' => 'Je ne parviens plus a acceder a mon compte eBourse malgre plusieurs tentatives.',
                'id_agent_accueil' => $accueilId,
                'date_soumission' => $now->copy()->subDays(4),
                'date_affectation' => $now->copy()->subDays(4)->addHours(2),
                'date_affectation_accueil' => $now->copy()->subDays(4)->addHours(2),
                'date_affectation_agent' => $now->copy()->subDays(3)->addHours(1),
                'date_reponse_direction' => $now->copy()->subDays(3)->addHours(3),
                'id_agent_direction' => $agentDsic,
                'date_envoi_usager' => $now->copy()->subDays(3)->addHours(4),
                'date_cloture' => $now->copy()->subDays(3)->addHours(4),
                'heures_ouvrees_cloture' => 11.5,
                'alerte_accueil' => 'vert',
                'alerte_chef' => 'vert',
                'alerte_agent' => 'vert',
                'delai_alerte' => 'dans_les_delais',
            ],
            [
                'numero_suivi' => 'ANBG-2026-0005',
                'id_usager' => $usagerIds[1],
                'id_type_demande' => $typeReclamation,
                'id_statut' => $statutCloturee,
                'id_service_courant' => $serviceDaf,
                'id_agent_accueil' => $accueilId,
                'id_agent_direction' => $chefDaf,
                'objet' => 'Reclamation dossier administratif incomplet',
                'categorie' => 'Demande de modification d attestation d attribution de bourse ou maintien',
                'message' => 'Je souhaite comprendre pourquoi mon dossier a ete marque incomplet et comment regulariser.',
                'date_soumission' => $now->copy()->subDays(2)->subHours(5),
                'date_affectation' => $now->copy()->subDays(2)->subHours(3),
                'date_affectation_accueil' => $now->copy()->subDays(2)->subHours(3),
                'date_reponse_direction' => $now->copy()->subDays(2)->addHour(),
                'date_envoi_usager' => $now->copy()->subDays(2)->addHours(2),
                'date_cloture' => $now->copy()->subDays(2)->addHours(2),
                'heures_ouvrees_cloture' => 6.75,
                'alerte_accueil' => 'vert',
                'alerte_chef' => 'vert',
                'alerte_agent' => null,
                'delai_alerte' => 'dans_les_delais',
            ],
        ];

        foreach ($demandes as $demande) {
            DB::table('demandes')->updateOrInsert(
                ['numero_suivi' => $demande['numero_suivi']],
                array_merge($demande, [
                    'id_config_sla' => $configId,
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }
    }
}
