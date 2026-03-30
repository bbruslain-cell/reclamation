<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('config_sla', function (Blueprint $table): void {
            $table->id('id_config_sla');
            $table->string('nom');
            $table->unsignedInteger('delai_max_heures')->default(72);
            $table->string('fuseau_horaire', 80)->default('Africa/Libreville');
            $table->boolean('actif')->default(true);
            $table->date('date_debut_validite');
            $table->date('date_fin_validite')->nullable();
            $table->timestamps();
            $table->index(['actif', 'date_debut_validite', 'date_fin_validite'], 'idx_config_sla_active_window');
        });

        Schema::create('sla_jours_ouvres', function (Blueprint $table): void {
            $table->id('id_sla_jour_ouvre');
            $table->foreignId('id_config_sla')
                ->constrained('config_sla', 'id_config_sla')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('jour_semaine_iso'); // 1=lundi ... 7=dimanche
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['id_config_sla', 'jour_semaine_iso'], 'uq_sla_jours_ouvres_config_jour');
        });

        Schema::create('sla_jours_feries', function (Blueprint $table): void {
            $table->id('id_sla_jour_ferie');
            $table->foreignId('id_config_sla')
                ->constrained('config_sla', 'id_config_sla')
                ->cascadeOnDelete();
            $table->date('date_ferie');
            $table->string('libelle');
            $table->timestamps();
            $table->unique(['id_config_sla', 'date_ferie'], 'uq_sla_jour_ferie_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_jours_feries');
        Schema::dropIfExists('sla_jours_ouvres');
        Schema::dropIfExists('config_sla');
    }
};

