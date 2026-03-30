<?php

use App\Models\Utilisateur;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('utilisateurs')->updateOrInsert(
            ['email' => 'admin@anbg.ga'],
            [
                'nom' => 'Super',
                'prenom' => 'Admin',
                'password_hash' => Hash::make('Admin@123456'),
                'id_service' => null,
                'actif' => true,
                'changement_mdp_requis' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $adminRoleId = DB::table('roles')->where('code', 'admin')->value('id_role');
        $adminUserId = DB::table('utilisateurs')->where('email', 'admin@anbg.ga')->value('id_utilisateur');
        if ($adminRoleId && $adminUserId) {
            DB::table('utilisateur_role')->updateOrInsert(
                ['id_utilisateur' => $adminUserId, 'id_role' => $adminRoleId],
                ['id_utilisateur' => $adminUserId, 'id_role' => $adminRoleId]
            );

            if (DB::getSchemaBuilder()->hasTable('model_has_roles')) {
                DB::table('model_has_roles')->updateOrInsert(
                    [
                        'id_role' => $adminRoleId,
                        'model_type' => Utilisateur::class,
                        'model_id' => $adminUserId,
                    ],
                    [
                        'id_role' => $adminRoleId,
                        'model_type' => Utilisateur::class,
                        'model_id' => $adminUserId,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        // Ne pas supprimer le compte admin lors du rollback.
    }
};
