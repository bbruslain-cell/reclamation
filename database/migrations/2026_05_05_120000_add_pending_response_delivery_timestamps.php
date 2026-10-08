<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('demandes', function (Blueprint $table): void {
            $table->timestampTz('date_demande_envoi_usager')->nullable()->after('date_reponse_direction');
        });

        Schema::table('reponses', function (Blueprint $table): void {
            $table->timestampTz('date_demande_envoi_usager')->nullable()->after('date_redaction');
        });
    }

    public function down(): void
    {
        Schema::table('reponses', function (Blueprint $table): void {
            $table->dropColumn('date_demande_envoi_usager');
        });

        Schema::table('demandes', function (Blueprint $table): void {
            $table->dropColumn('date_demande_envoi_usager');
        });
    }
};
