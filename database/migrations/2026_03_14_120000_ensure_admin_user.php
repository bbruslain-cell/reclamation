<?php

use App\Models\Utilisateur;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $adminEmail = strtolower(trim((string) config('deployment.initial_admin.email', 'admin@anbg.ga')));
        if (! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('INITIAL_ADMIN_EMAIL must be a valid email address.');
        }

        $adminUserId = DB::table('utilisateurs')->where('email', $adminEmail)->value('id_utilisateur');

        if (! $adminUserId) {
            $initialPassword = trim((string) config('deployment.initial_admin.password', ''));

            if ($initialPassword !== '') {
                if (
                    in_array($initialPassword, ['Admin@123456', 'ChangeMe@123'], true)
                    || strlen($initialPassword) < 12
                ) {
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
