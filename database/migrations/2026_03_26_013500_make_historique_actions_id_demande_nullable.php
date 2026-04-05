<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('historique_actions', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_demande')->nullable()->change();
        });
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

        Schema::table('historique_actions', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_demande')->nullable(false)->change();
        });
    }
};
