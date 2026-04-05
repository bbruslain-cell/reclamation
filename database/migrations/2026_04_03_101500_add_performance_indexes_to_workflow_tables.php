<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demandes', function (Blueprint $table): void {
            $table->index(['id_statut', 'date_soumission'], 'idx_demandes_statut_soumission');
            $table->index(['id_service_courant', 'id_statut'], 'idx_demandes_service_statut');
            $table->index('date_affectation_accueil', 'idx_demandes_date_affectation_accueil');
            $table->index('date_cloture', 'idx_demandes_date_cloture');
            $table->index('delai_alerte', 'idx_demandes_delai_alerte');
            $table->index('alerte_accueil', 'idx_demandes_alerte_accueil');
            $table->index('alerte_chef', 'idx_demandes_alerte_chef');
            $table->index('alerte_agent', 'idx_demandes_alerte_agent');
        });

        Schema::table('historique_actions', function (Blueprint $table): void {
            $table->index(['type_action', 'date_action'], 'idx_historique_type_date');
            $table->index('id_service_associe', 'idx_historique_service_associe');
            $table->index('id_utilisateur', 'idx_historique_utilisateur');
        });
    }

    public function down(): void
    {
        Schema::table('historique_actions', function (Blueprint $table): void {
            $table->dropIndex('idx_historique_type_date');
            $table->dropIndex('idx_historique_service_associe');
            $table->dropIndex('idx_historique_utilisateur');
        });

        Schema::table('demandes', function (Blueprint $table): void {
            $table->dropIndex('idx_demandes_statut_soumission');
            $table->dropIndex('idx_demandes_service_statut');
            $table->dropIndex('idx_demandes_date_affectation_accueil');
            $table->dropIndex('idx_demandes_date_cloture');
            $table->dropIndex('idx_demandes_delai_alerte');
            $table->dropIndex('idx_demandes_alerte_accueil');
            $table->dropIndex('idx_demandes_alerte_chef');
            $table->dropIndex('idx_demandes_alerte_agent');
        });
    }
};
