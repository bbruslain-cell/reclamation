<?php

namespace Database\Seeders;

use App\Models\Utilisateur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $directions = [
            'DG' => 'Direction Generale',
            'DS' => 'Direction de la Scolarite',
            'DAF' => 'Direction Administrative et Financiere',
            'DSIC' => 'Direction des systemes d informations et de la Communication',
        ];

        foreach ($directions as $code => $libelle) {
            DB::table('directions')->updateOrInsert(
                ['code' => $code],
                [
                    'libelle' => $libelle,
                    'actif' => true,
                    'date_debut_validite' => now()->toDateString(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $legacyServiceMap = [
            ['direction' => 'DG', 'old' => 'ACC', 'new' => 'UCAS', 'libelle' => 'Unite Courrier, Accueil et Securite'],
            ['direction' => 'DG', 'old' => 'CAB_DG', 'new' => 'CABINET_DG', 'libelle' => 'Cabinet - DG'],
            ['direction' => 'DSIC', 'old' => 'SSIRS', 'new' => 'CS_SIRS', 'libelle' => 'Systemes d Informations, Reseaux et Securite'],
            ['direction' => 'DSIC', 'old' => 'SCRP', 'new' => 'CS_JSP', 'libelle' => 'Communication et Relation Publique'],
            ['direction' => 'DSIC', 'old' => 'SGDS', 'new' => 'CS_GDS', 'libelle' => 'Gestion Documentaire et Statistiques'],
            ['direction' => 'DAF', 'old' => 'SAJARH', 'new' => 'CS_AJARH', 'libelle' => 'Affaires Juridiques et RH'],
            ['direction' => 'DAF', 'old' => 'SFC', 'new' => 'CS_FC', 'libelle' => 'Financier et Comptable'],
            ['direction' => 'DAF', 'old' => 'SAMG', 'new' => 'CS_AMG', 'libelle' => 'Approvisionnement et Moyens Generaux'],
            ['direction' => 'DS', 'old' => 'ETUD_NON_BOURS', 'new' => 'CS_SNB', 'libelle' => 'Etudiants non Boursiers'],
            ['direction' => 'DS', 'old' => 'ETUD_BOURS', 'new' => 'CS_SENB', 'libelle' => 'Etudiants Boursiers'],
            ['direction' => 'DS', 'old' => 'PLANIF', 'new' => 'CS_P', 'libelle' => 'Planification'],
        ];

        foreach ($legacyServiceMap as $mapping) {
            $directionId = DB::table('directions')->where('code', $mapping['direction'])->value('id_direction');
            if (!$directionId || DB::table('services')->where('code', $mapping['new'])->exists()) {
                continue;
            }

            DB::table('services')
                ->where('code', $mapping['old'])
                ->update([
                    'code' => $mapping['new'],
                    'id_direction' => $directionId,
                    'libelle' => $mapping['libelle'],
                    'actif' => true,
                    'updated_at' => now(),
                ]);
        }

        $services = [
            ['direction' => 'DG', 'code' => 'UCAS', 'libelle' => 'Unite Courrier, Accueil et Securite'],
            ['direction' => 'DG', 'code' => 'SCIQ', 'libelle' => 'Service Controle Interne et Qualite'],
            ['direction' => 'DG', 'code' => 'CABINET_DG', 'libelle' => 'Cabinet - DG'],

            ['direction' => 'DSIC', 'code' => 'CS_SIRS', 'libelle' => 'Systemes d Informations, Reseaux et Securite'],
            ['direction' => 'DSIC', 'code' => 'CS_JSP', 'libelle' => 'Communication et Relation Publique'],
            ['direction' => 'DSIC', 'code' => 'CS_GDS', 'libelle' => 'Gestion Documentaire et Statistiques'],

            ['direction' => 'DAF', 'code' => 'CS_AJARH', 'libelle' => 'Affaires Juridiques et RH'],
            ['direction' => 'DAF', 'code' => 'CS_FC', 'libelle' => 'Financier et Comptable'],
            ['direction' => 'DAF', 'code' => 'CS_AMG', 'libelle' => 'Approvisionnement et Moyens Generaux'],

            ['direction' => 'DS', 'code' => 'CS_SNB', 'libelle' => 'Etudiants non Boursiers'],
            ['direction' => 'DS', 'code' => 'CS_SENB', 'libelle' => 'Etudiants Boursiers'],
            ['direction' => 'DS', 'code' => 'CS_P', 'libelle' => 'Planification'],
        ];

        foreach ($services as $service) {
            $directionId = DB::table('directions')->where('code', $service['direction'])->value('id_direction');
            if (!$directionId) {
                continue;
            }

            DB::table('services')->updateOrInsert(
                ['code' => $service['code']],
                [
                    'id_direction' => $directionId,
                    'libelle' => $service['libelle'],
                    'actif' => true,
                    'date_debut_validite' => now()->toDateString(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        DB::table('utilisateurs')->updateOrInsert(
            ['email' => 'admin@anbg.ga'],
            [
                'nom' => 'Super',
                'prenom' => 'Admin',
                'password_hash' => Hash::make('Admin@123456'),
                'actif' => true,
                'changement_mdp_requis' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $adminRoleId = DB::table('roles')->where('code', 'admin')->value('id_role');
        $adminUserId = DB::table('utilisateurs')->where('email', 'admin@anbg.ga')->value('id_utilisateur');
        if ($adminRoleId && $adminUserId) {
            $this->syncUserRoles((int) $adminUserId, [(int) $adminRoleId]);
        }

        $accounts = [
            [
                'email' => 'accueil@anbg.ga',
                'nom' => 'Service',
                'prenom' => 'Accueil',
                'service_code' => 'UCAS',
                'role_code' => 'accueil',
                'direction_scope' => ['DG', 'DS', 'DAF', 'DSIC'],
                'service_scope' => [],
            ],
            [
                'email' => 'agent.ds@anbg.ga',
                'nom' => 'Agent',
                'prenom' => 'Scolarite',
                'service_code' => 'CS_SENB',
                'role_code' => 'agent',
                'direction_scope' => [],
                'service_scope' => [],
            ],
            [
                'email' => 'agent.daf@anbg.ga',
                'nom' => 'Agent',
                'prenom' => 'DAF',
                'service_code' => 'CS_FC',
                'role_code' => 'agent',
                'direction_scope' => [],
                'service_scope' => [],
            ],
            [
                'email' => 'agent.dsic@anbg.ga',
                'nom' => 'Agent',
                'prenom' => 'DSIC',
                'service_code' => 'CS_SIRS',
                'role_code' => 'agent',
                'direction_scope' => [],
                'service_scope' => [],
            ],
            [
                'email' => 'chef.ds@anbg.ga',
                'nom' => 'Chef',
                'prenom' => 'Scolarite',
                'service_code' => 'CS_SENB',
                'role_code' => 'chef_service',
                'direction_scope' => [],
                'service_scope' => ['CS_SENB'],
            ],
            [
                'email' => 'chef.daf@anbg.ga',
                'nom' => 'Chef',
                'prenom' => 'DAF',
                'service_code' => 'CS_FC',
                'role_code' => 'chef_service',
                'direction_scope' => [],
                'service_scope' => ['CS_FC'],
            ],
            [
                'email' => 'chef.dsic@anbg.ga',
                'nom' => 'Chef',
                'prenom' => 'DSIC',
                'service_code' => 'CS_SIRS',
                'role_code' => 'chef_service',
                'direction_scope' => [],
                'service_scope' => ['CS_SIRS'],
            ],
            [
                'email' => 'chef.direction.ds@anbg.ga',
                'nom' => 'Chef',
                'prenom' => 'Direction DS',
                'service_code' => null,
                'role_code' => 'chef_direction',
                'direction_scope' => ['DS'],
                'service_scope' => [],
            ],
            [
                'email' => 'chef.direction.daf@anbg.ga',
                'nom' => 'Chef',
                'prenom' => 'Direction DAF',
                'service_code' => null,
                'role_code' => 'chef_direction',
                'direction_scope' => ['DAF'],
                'service_scope' => [],
            ],
            [
                'email' => 'chef.direction.dsic@anbg.ga',
                'nom' => 'Chef',
                'prenom' => 'Direction DSIC',
                'service_code' => null,
                'role_code' => 'chef_direction',
                'direction_scope' => ['DSIC'],
                'service_scope' => [],
            ],
            [
                'email' => 'chef.sciq@anbg.ga',
                'nom' => 'Chef',
                'prenom' => 'SCIQ',
                'service_code' => 'SCIQ',
                'role_codes' => ['chef_service', 'ciq'],
                'direction_scope' => ['DG', 'DS', 'DAF', 'DSIC'],
                'service_scope' => ['SCIQ'],
            ],
            [
                'email' => 'ciq@anbg.ga',
                'nom' => 'Controle',
                'prenom' => 'Interne',
                'service_code' => 'SCIQ',
                'role_codes' => ['agent', 'ciq'],
                'direction_scope' => ['DG', 'DS', 'DAF', 'DSIC'],
                'service_scope' => [],
            ],
            [
                'email' => 'dg@anbg.ga',
                'nom' => 'Direction',
                'prenom' => 'Generale',
                'service_code' => null,
                'role_code' => 'dg',
                'direction_scope' => ['DG', 'DS', 'DAF', 'DSIC'],
                'service_scope' => [],
            ],
            [
                'email' => 'lecture@anbg.ga',
                'nom' => 'Compte',
                'prenom' => 'Lecture',
                'service_code' => null,
                'role_code' => 'lecture_seule',
                'direction_scope' => ['DG', 'DS', 'DAF', 'DSIC'],
                'service_scope' => [],
            ],
        ];

        foreach ($accounts as $account) {
            $serviceId = $account['service_code']
                ? DB::table('services')->where('code', $account['service_code'])->value('id_service')
                : null;

            DB::table('utilisateurs')->updateOrInsert(
                ['email' => $account['email']],
                [
                    'nom' => $account['nom'],
                    'prenom' => $account['prenom'],
                    'password_hash' => Hash::make('ChangeMe@123'),
                    'id_service' => $serviceId,
                    'actif' => true,
                    'changement_mdp_requis' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $userId = DB::table('utilisateurs')->where('email', $account['email'])->value('id_utilisateur');
            $roleCodes = $account['role_codes'] ?? [$account['role_code']];
            $roleIds = DB::table('roles')
                ->whereIn('code', $roleCodes)
                ->pluck('id_role')
                ->map(static fn ($value) => (int) $value)
                ->all();

            if ($userId && $roleIds !== []) {
                $this->syncUserRoles((int) $userId, $roleIds);
            }

            DB::table('perimetre_direction')->where('id_utilisateur', $userId)->delete();
            DB::table('perimetre_service')->where('id_utilisateur', $userId)->delete();

            foreach ($account['direction_scope'] as $directionCode) {
                $directionId = DB::table('directions')->where('code', $directionCode)->value('id_direction');
                if ($directionId && $userId) {
                    DB::table('perimetre_direction')->updateOrInsert(
                        ['id_utilisateur' => $userId, 'id_direction' => $directionId],
                        ['id_utilisateur' => $userId, 'id_direction' => $directionId]
                    );
                }
            }

            foreach ($account['service_scope'] as $serviceCode) {
                $scopeServiceId = DB::table('services')->where('code', $serviceCode)->value('id_service');
                if ($scopeServiceId && $userId) {
                    DB::table('perimetre_service')->updateOrInsert(
                        ['id_utilisateur' => $userId, 'id_service' => $scopeServiceId],
                        ['id_utilisateur' => $userId, 'id_service' => $scopeServiceId]
                    );
                }
            }
        }
    }

    private function syncUserRoles(int $userId, array $roleIds): void
    {
        $roleIds = array_values(array_unique(array_map(static fn ($value) => (int) $value, $roleIds)));

        DB::table('utilisateur_role')->where('id_utilisateur', $userId)->delete();
        DB::table('model_has_roles')
            ->where('model_type', Utilisateur::class)
            ->where('model_id', $userId)
            ->delete();

        foreach ($roleIds as $roleId) {
            DB::table('utilisateur_role')->insert([
                'id_utilisateur' => $userId,
                'id_role' => $roleId,
            ]);

            DB::table('model_has_roles')->insert([
                'id_role' => $roleId,
                'model_type' => Utilisateur::class,
                'model_id' => $userId,
            ]);
        }
    }
}
