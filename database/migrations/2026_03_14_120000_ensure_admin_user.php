<?php

use App\Models\Utilisateur;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $adminEmail = (string) env('INITIAL_ADMIN_EMAIL', 'admin@anbg.ga');
        $adminUserId = DB::table('utilisateurs')->where('email', $adminEmail)->value('id_utilisateur');

        if (!$adminUserId) {
            $initialPassword = trim((string) env('INITIAL_ADMIN_PASSWORD', ''));

            if ($initialPassword !== '') {
                if ($initialPassword === 'Admin@123456' || strlen($initialPassword) < 12) {
                    throw new RuntimeException('INITIAL_ADMIN_PASSWORD must be unique and at least 12 characters long.');
                }

                $adminUserId = DB::table('utilisateurs')->insertGetId([
                    'email' => $adminEmail,
                    'nom' => 'Super',
                    'prenom' => 'Admin',
                    'password_hash' => \Illuminate\Support\Facades\Hash::make($initialPassword),
                    'id_service' => null,
                    'actif' => true,
                    'changement_mdp_requis' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ], 'id_utilisateur');
            }
        } else {
            DB::table('utilisateurs')->where('id_utilisateur', $adminUserId)->update([
                'nom' => 'Super',
                'prenom' => 'Admin',
                'id_service' => null,
                'actif' => true,
                'changement_mdp_requis' => true,
                'updated_at' => now(),
            ]);
        }

        $adminRoleId = DB::table('roles')->where('code', 'admin')->value('id_role');
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
