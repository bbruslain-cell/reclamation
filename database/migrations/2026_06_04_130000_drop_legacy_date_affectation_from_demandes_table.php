<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('demandes') || !Schema::hasColumn('demandes', 'date_affectation')) {
            return;
        }

        Schema::table('demandes', function (Blueprint $table): void {
            $table->dropColumn('date_affectation');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('demandes') || Schema::hasColumn('demandes', 'date_affectation')) {
            return;
        }

        Schema::table('demandes', function (Blueprint $table): void {
            $table->timestampTz('date_affectation')->nullable()->after('date_soumission');
        });

        if (!Schema::hasColumn('demandes', 'date_affectation_accueil')) {
            return;
        }

        DB::table('demandes')
            ->whereNull('date_affectation')
            ->whereNotNull('date_affectation_accueil')
            ->update([
                'date_affectation' => DB::raw('date_affectation_accueil'),
            ]);
    }
};
