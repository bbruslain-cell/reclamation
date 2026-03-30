<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE historique_actions ALTER COLUMN id_demande DROP NOT NULL');
    }

    public function down(): void
    {
        $fallbackDemandId = (int) DB::table('demandes')->min('id_demande');

        if ($fallbackDemandId > 0) {
            DB::table('historique_actions')
                ->whereNull('id_demande')
                ->update(['id_demande' => $fallbackDemandId]);
        } else {
            DB::table('historique_actions')
                ->whereNull('id_demande')
                ->delete();
        }

        DB::statement('ALTER TABLE historique_actions ALTER COLUMN id_demande SET NOT NULL');
    }
};
