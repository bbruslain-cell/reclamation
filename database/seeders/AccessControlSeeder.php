<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['code' => 'accueil', 'libelle' => 'Accueil - Agent de première ligne'],
            ['code' => 'chef_service', 'libelle' => 'Chef de service'],
            ['code' => 'agent', 'libelle' => 'Agent'],
            ['code' => 'chef_direction', 'libelle' => 'Chef de direction'],
            ['code' => 'ciq', 'libelle' => 'Contrôle interne et qualité'],
            ['code' => 'dg', 'libelle' => 'Direction générale'],
            ['code' => 'admin', 'libelle' => 'Administration complète'],
            ['code' => 'lecture_seule', 'libelle' => 'Lecture seule'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['code' => $role['code']],
                [
                    'name' => $role['code'],
                    'libelle' => $role['libelle'],
                    'guard_name' => 'web',
                    'actif' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $permissions = [
            ['code' => 'demande.view.own', 'libelle' => 'Voir les demandes de son périmètre'],
            ['code' => 'demande.view.all', 'libelle' => 'Voir toutes les demandes'],
            ['code' => 'demande.create', 'libelle' => 'Créer une demande'],
            ['code' => 'demande.assign', 'libelle' => 'Affecter une demande'],
            ['code' => 'demande.assign.agent', 'libelle' => 'Affecter une demande à un agent'],
            ['code' => 'demande.reply.draft', 'libelle' => 'Rédiger une réponse'],
            ['code' => 'demande.reply.send', 'libelle' => 'Envoyer la réponse finale'],

            ['code' => 'dashboard.view', 'libelle' => 'Voir les tableaux de bord'],
            ['code' => 'dashboard.export', 'libelle' => 'Exporter les données'],
            ['code' => 'audit.view', 'libelle' => 'Voir l\'historique'],
            ['code' => 'admin.users.manage', 'libelle' => 'Gérer les utilisateurs'],
            ['code' => 'admin.parameters.manage', 'libelle' => 'Gérer les paramètres'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['code' => $permission['code']],
                [
                    'name' => $permission['code'],
                    'libelle' => $permission['libelle'],
                    'guard_name' => 'web',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $rolePermissions = [
            'accueil' => [
                'demande.view.all',
                'demande.assign',
                'demande.reply.send',

            ],
            'chef_service' => [
                'demande.view.own',
                'demande.assign.agent',
                'demande.reply.draft',
                'demande.reply.send',
                'dashboard.view',
            ],
            'agent' => [
                'demande.view.own',
                'demande.reply.send',
            ],
            'chef_direction' => [
                'demande.view.own',
                'dashboard.view',
            ],
            'ciq' => [
                'demande.view.all',
                'dashboard.view',
                'dashboard.export',
                'audit.view',
            ],
            'dg' => [
                'demande.view.all',
                'dashboard.view',
            ],
            'admin' => array_column($permissions, 'code'),
            'lecture_seule' => [
                'demande.view.own',
                'dashboard.view',
            ],
        ];

        foreach ($rolePermissions as $roleCode => $permissionCodes) {
            $roleId = DB::table('roles')->where('code', $roleCode)->value('id_role');
            if (!$roleId) {
                continue;
            }

            DB::table('permission_role')
                ->where('id_role', $roleId)
                ->delete();

            foreach ($permissionCodes as $permissionCode) {
                $permissionId = DB::table('permissions')->where('code', $permissionCode)->value('id_permission');
                if (!$permissionId) {
                    continue;
                }

                DB::table('permission_role')->updateOrInsert(
                    ['id_role' => $roleId, 'id_permission' => $permissionId],
                    ['id_role' => $roleId, 'id_permission' => $permissionId]
                );
            }
        }

        DB::table('roles')
            ->where('code', 'direction')
            ->update([
                'libelle' => 'Ancien rôle direction',
                'actif' => false,
                'updated_at' => now(),
            ]);
    }
}
