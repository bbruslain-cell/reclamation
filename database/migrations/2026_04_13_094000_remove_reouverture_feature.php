<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $reopenedStatusId = DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->where('code', 'reouverte')
            ->value('id_parametre');

        if ($reopenedStatusId) {
            DB::table('historique_actions')
                ->where('ancien_statut_id', (int) $reopenedStatusId)
                ->update(['ancien_statut_id' => null]);

            DB::table('historique_actions')
                ->where('nouveau_statut_id', (int) $reopenedStatusId)
                ->update(['nouveau_statut_id' => null]);

            DB::table('demandes')
                ->where('id_statut', (int) $reopenedStatusId)
                ->update(['id_statut' => $this->statusId('nouvelle')]);

            DB::table('parametres')
                ->where('id_parametre', (int) $reopenedStatusId)
                ->delete();
        }

        DB::table('historique_actions')
            ->where('type_action', 'reouverture')
            ->delete();

        $permissionId = DB::table('permissions')
            ->where('code', 'demande.reopen')
            ->value('id_permission');

        if ($permissionId) {
            DB::table('permission_role')
                ->where('id_permission', (int) $permissionId)
                ->delete();

            DB::table('model_has_permissions')
                ->where('id_permission', (int) $permissionId)
                ->delete();

            DB::table('permissions')
                ->where('id_permission', (int) $permissionId)
                ->delete();
        }
    }

    public function down(): void
    {
        DB::table('parametres')->updateOrInsert(
            ['famille' => 'statut_demande', 'code' => 'reouverte'],
            [
                'libelle' => 'Reouverte',
                'ordre_affichage' => 6,
                'actif' => false,
                'date_debut_validite' => now()->toDateString(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('permissions')->updateOrInsert(
            ['code' => 'demande.reopen'],
            [
                'name' => 'demande.reopen',
                'libelle' => 'Reouvrir une demande',
                'guard_name' => 'web',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function statusId(string $code): int
    {
        return (int) DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->where('code', $code)
            ->value('id_parametre');
    }
};
