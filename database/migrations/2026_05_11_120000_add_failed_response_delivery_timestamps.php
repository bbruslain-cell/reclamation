<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demandes', function (Blueprint $table): void {
            if (!Schema::hasColumn('demandes', 'date_echec_envoi_usager')) {
                $table->dateTime('date_echec_envoi_usager')->nullable()->after('date_demande_envoi_usager');
            }
        });

        Schema::table('reponses', function (Blueprint $table): void {
            if (!Schema::hasColumn('reponses', 'date_echec_envoi_usager')) {
                $table->dateTime('date_echec_envoi_usager')->nullable()->after('date_demande_envoi_usager');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reponses', function (Blueprint $table): void {
            if (Schema::hasColumn('reponses', 'date_echec_envoi_usager')) {
                $table->dropColumn('date_echec_envoi_usager');
            }
        });

        Schema::table('demandes', function (Blueprint $table): void {
            if (Schema::hasColumn('demandes', 'date_echec_envoi_usager')) {
                $table->dropColumn('date_echec_envoi_usager');
            }
        });
    }
};
