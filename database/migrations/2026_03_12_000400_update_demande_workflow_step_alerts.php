<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('demandes', function (Blueprint $table): void {
            $table->timestampTz('date_affectation_accueil')->nullable()->after('date_affectation');
            $table->timestampTz('date_affectation_agent')->nullable()->after('date_affectation_accueil');
            $table->string('alerte_accueil', 10)->nullable()->after('delai_alerte');
            $table->string('alerte_chef', 10)->nullable()->after('alerte_accueil');
            $table->string('alerte_agent', 10)->nullable()->after('alerte_chef');
            $table->foreignId('id_agent_traitant')
                ->nullable()
                ->after('id_agent_direction')
                ->constrained('utilisateurs', 'id_utilisateur')
                ->nullOnDelete();
        });

        Schema::table('historique_actions', function (Blueprint $table): void {
            $table->foreignId('id_agent_associe')
                ->nullable()
                ->after('id_service_associe')
                ->constrained('utilisateurs', 'id_utilisateur')
                ->nullOnDelete();
        });

        DB::table('demandes')
            ->whereNull('date_affectation_accueil')
            ->whereNotNull('date_affectation')
            ->update([
                'date_affectation_accueil' => DB::raw('date_affectation'),
            ]);
    }

    public function down(): void
    {
        Schema::table('historique_actions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('id_agent_associe');
        });

        Schema::table('demandes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('id_agent_traitant');
            $table->dropColumn([
                'date_affectation_accueil',
                'date_affectation_agent',
                'alerte_accueil',
                'alerte_chef',
                'alerte_agent',
            ]);
        });
    }
};
