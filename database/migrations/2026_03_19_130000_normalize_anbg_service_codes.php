<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $this->normalizeCatalog([
            ['direction' => 'DG', 'old' => 'UCAS', 'new' => 'UCAS', 'libelle' => 'Unité Courrier, Accueil et Sécurité'],
            ['direction' => 'DG', 'old' => 'SCIQ', 'new' => 'SCIQ', 'libelle' => 'Service Contrôle Interne et Qualité'],
            ['direction' => 'DG', 'old' => 'CABINET_DG', 'new' => 'CABINET_DG', 'libelle' => 'Cabinet - DG'],
            ['direction' => 'DSIC', 'old' => 'SSIRS', 'new' => 'CS_SIRS', 'libelle' => 'Systèmes d Informations, Réseaux et Sécurité'],
            ['direction' => 'DSIC', 'old' => 'SCRP', 'new' => 'CS_JSP', 'libelle' => 'Communication et Relation Publique'],
            ['direction' => 'DSIC', 'old' => 'SGDS', 'new' => 'CS_GDS', 'libelle' => 'Gestion Documentaire et Statistiques'],
            ['direction' => 'DAF', 'old' => 'SAJARH', 'new' => 'CS_AJARH', 'libelle' => 'Affaires Juridiques et RH'],
            ['direction' => 'DAF', 'old' => 'SFC', 'new' => 'CS_FC', 'libelle' => 'Financier et Comptable'],
            ['direction' => 'DAF', 'old' => 'SAMG', 'new' => 'CS_AMG', 'libelle' => 'Approvisionnement et Moyens Généraux'],
            ['direction' => 'DS', 'old' => 'ETUD_NON_BOURS', 'new' => 'CS_SNB', 'libelle' => 'Étudiants non Boursiers'],
            ['direction' => 'DS', 'old' => 'ETUD_BOURS', 'new' => 'CS_SENB', 'libelle' => 'Étudiants Boursiers'],
            ['direction' => 'DS', 'old' => 'PLANIF', 'new' => 'CS_P', 'libelle' => 'Planification'],
        ]);

        $this->updateUserService('agent.ds@anbg.ga', 'CS_SENB');
        $this->updateUserService('agent.daf@anbg.ga', 'CS_FC');
        $this->updateUserService('agent.dsic@anbg.ga', 'CS_SIRS');
        $this->updateUserService('chef.ds@anbg.ga', 'CS_SENB');
        $this->updateUserService('chef.daf@anbg.ga', 'CS_FC');
        $this->updateUserService('chef.dsic@anbg.ga', 'CS_SIRS');
        $this->updateUserService('accueil@anbg.ga', 'UCAS');
        $this->updateUserService('ciq@anbg.ga', 'SCIQ');
    }

    public function down(): void
    {
        $this->normalizeCatalog([
            ['direction' => 'DG', 'old' => 'UCAS', 'new' => 'UCAS', 'libelle' => 'Unité Courrier, Accueil et Sécurité'],
            ['direction' => 'DG', 'old' => 'SCIQ', 'new' => 'SCIQ', 'libelle' => 'Système Contrôle Interne et Qualité'],
            ['direction' => 'DG', 'old' => 'CABINET_DG', 'new' => 'CABINET_DG', 'libelle' => 'Cabinet DG'],
            ['direction' => 'DSIC', 'old' => 'CS_SIRS', 'new' => 'SSIRS', 'libelle' => 'Service des Systèmes d Informations Réseaux et Sécurité'],
            ['direction' => 'DSIC', 'old' => 'CS_JSP', 'new' => 'SCRP', 'libelle' => 'Service Communication et Relations Publiques'],
            ['direction' => 'DSIC', 'old' => 'CS_GDS', 'new' => 'SGDS', 'libelle' => 'Service Gestion Documentaires des Statistiques'],
            ['direction' => 'DAF', 'old' => 'CS_AJARH', 'new' => 'SAJARH', 'libelle' => 'Service des Affaires Juridiques Administratives et Ressources Humaines'],
            ['direction' => 'DAF', 'old' => 'CS_FC', 'new' => 'SFC', 'libelle' => 'Service Financier et Comptable'],
            ['direction' => 'DAF', 'old' => 'CS_AMG', 'new' => 'SAMG', 'libelle' => 'Service Approvisionnement et Moyens Généraux'],
            ['direction' => 'DS', 'old' => 'CS_SNB', 'new' => 'ETUD_NON_BOURS', 'libelle' => 'Service Étudiants non Boursiers'],
            ['direction' => 'DS', 'old' => 'CS_SENB', 'new' => 'ETUD_BOURS', 'libelle' => 'Service Étudiants Boursiers'],
            ['direction' => 'DS', 'old' => 'CS_P', 'new' => 'PLANIF', 'libelle' => 'Service Planification'],
        ]);

        $this->updateUserService('agent.ds@anbg.ga', 'ETUD_BOURS');
        $this->updateUserService('agent.daf@anbg.ga', 'SFC');
        $this->updateUserService('agent.dsic@anbg.ga', 'SSIRS');
        $this->updateUserService('chef.ds@anbg.ga', 'ETUD_BOURS');
        $this->updateUserService('chef.daf@anbg.ga', 'SFC');
        $this->updateUserService('chef.dsic@anbg.ga', 'SSIRS');
        $this->updateUserService('accueil@anbg.ga', 'UCAS');
        $this->updateUserService('ciq@anbg.ga', 'SCIQ');
    }

    private function normalizeCatalog(array $mappings): void
    {
        foreach ($mappings as $mapping) {
            $directionId = DB::table('directions')->where('code', $mapping['direction'])->value('id_direction');
            if (!$directionId) {
                continue;
            }

            $this->normalizeService(
                oldCode: $mapping['old'],
                newCode: $mapping['new'],
                newLabel: $mapping['libelle'],
                directionId: (int) $directionId
            );
        }
    }

    private function updateUserService(string $email, string $serviceCode): void
    {
        $serviceId = DB::table('services')->where('code', $serviceCode)->value('id_service');
        if (!$serviceId) {
            return;
        }

        DB::table('utilisateurs')
            ->where('email', $email)
            ->update([
                'id_service' => (int) $serviceId,
                'updated_at' => now(),
            ]);
    }

    private function normalizeService(string $oldCode, string $newCode, string $newLabel, int $directionId): void
    {
        $oldId = DB::table('services')->where('code', $oldCode)->value('id_service');
        $newId = DB::table('services')->where('code', $newCode)->value('id_service');

        if (!$oldId && !$newId) {
            DB::table('services')->insert([
                'id_direction' => $directionId,
                'code' => $newCode,
                'libelle' => $newLabel,
                'actif' => true,
                'date_debut_validite' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        if ($oldCode === $newCode && $newId) {
            DB::table('services')
                ->where('id_service', $newId)
                ->update([
                    'id_direction' => $directionId,
                    'libelle' => $newLabel,
                    'actif' => true,
                    'updated_at' => now(),
                ]);

            return;
        }

        if ($oldId && !$newId) {
            DB::table('services')
                ->where('id_service', $oldId)
                ->update([
                    'code' => $newCode,
                    'id_direction' => $directionId,
                    'libelle' => $newLabel,
                    'actif' => true,
                    'updated_at' => now(),
                ]);

            return;
        }

        if ($newId) {
            DB::table('services')
                ->where('id_service', $newId)
                ->update([
                    'id_direction' => $directionId,
                    'libelle' => $newLabel,
                    'actif' => true,
                    'updated_at' => now(),
                ]);
        }

        if (!$oldId || (int) $oldId === (int) $newId) {
            return;
        }

        DB::table('utilisateurs')->where('id_service', $oldId)->update([
            'id_service' => $newId,
            'updated_at' => now(),
        ]);

        DB::table('demandes')->where('id_service_courant', $oldId)->update([
            'id_service_courant' => $newId,
            'updated_at' => now(),
        ]);

        DB::table('affectations')->where('id_service', $oldId)->update([
            'id_service' => $newId,
            'updated_at' => now(),
        ]);

        DB::table('historique_actions')->where('id_service_associe', $oldId)->update([
            'id_service_associe' => $newId,
            'updated_at' => now(),
        ]);

        $perimeterRows = DB::table('perimetre_service')
            ->where('id_service', $oldId)
            ->get();

        foreach ($perimeterRows as $row) {
            DB::table('perimetre_service')->insertOrIgnore([
                'id_utilisateur' => $row->id_utilisateur,
                'id_service' => $newId,
            ]);
        }

        DB::table('perimetre_service')->where('id_service', $oldId)->delete();
        DB::table('services')->where('id_service', $oldId)->delete();
    }
};
