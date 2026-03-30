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
        $newStatusId = DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->where('code', 'nouvelle')
            ->value('id_parametre');

        if ($reopenedStatusId && $newStatusId) {
            DB::table('demandes')
                ->where('id_statut', (int) $reopenedStatusId)
                ->update([
                    'id_statut' => (int) $newStatusId,
                    'updated_at' => now(),
                ]);
        }

        if ($reopenedStatusId) {
            DB::table('parametres')
                ->where('id_parametre', (int) $reopenedStatusId)
                ->update([
                    'actif' => false,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $reopenedStatusId = DB::table('parametres')
            ->where('famille', 'statut_demande')
            ->where('code', 'reouverte')
            ->value('id_parametre');

        if ($reopenedStatusId) {
            DB::table('parametres')
                ->where('id_parametre', (int) $reopenedStatusId)
                ->update([
                    'actif' => true,
                    'updated_at' => now(),
                ]);
        }
    }
};
