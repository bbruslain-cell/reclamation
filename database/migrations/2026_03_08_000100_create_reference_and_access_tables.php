<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('directions', function (Blueprint $table): void {
            $table->id('id_direction');
            $table->string('code', 30)->unique();
            $table->string('libelle');
            $table->boolean('actif')->default(true);
            $table->date('date_debut_validite')->nullable();
            $table->date('date_fin_validite')->nullable();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table): void {
            $table->id('id_service');
            $table->foreignId('id_direction')
                ->constrained('directions', 'id_direction')
                ->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->string('libelle');
            $table->string('email_service')->nullable();
            $table->boolean('actif')->default(true);
            $table->date('date_debut_validite')->nullable();
            $table->date('date_fin_validite')->nullable();
            $table->timestamps();
        });

        Schema::create('utilisateurs', function (Blueprint $table): void {
            $table->id('id_utilisateur');
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->foreignId('id_service')
                ->nullable()
                ->constrained('services', 'id_service')
                ->nullOnDelete();
            $table->boolean('actif')->default(true);
            $table->timestamp('derniere_connexion')->nullable();
            $table->unsignedInteger('tentatives_echouees')->default(0);
            $table->timestamp('bloque_jusqua')->nullable();
            $table->boolean('changement_mdp_requis')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id('id_role');
            $table->string('code', 50)->unique();
            $table->string('libelle');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id('id_permission');
            $table->string('code', 100)->unique();
            $table->string('libelle');
            $table->timestamps();
        });

        Schema::create('utilisateur_role', function (Blueprint $table): void {
            $table->foreignId('id_utilisateur')
                ->constrained('utilisateurs', 'id_utilisateur')
                ->cascadeOnDelete();
            $table->foreignId('id_role')
                ->constrained('roles', 'id_role')
                ->cascadeOnDelete();
            $table->primary(['id_utilisateur', 'id_role']);
        });

        Schema::create('permission_role', function (Blueprint $table): void {
            $table->foreignId('id_permission')
                ->constrained('permissions', 'id_permission')
                ->cascadeOnDelete();
            $table->foreignId('id_role')
                ->constrained('roles', 'id_role')
                ->cascadeOnDelete();
            $table->primary(['id_permission', 'id_role']);
        });

        Schema::create('perimetre_direction', function (Blueprint $table): void {
            $table->foreignId('id_utilisateur')
                ->constrained('utilisateurs', 'id_utilisateur')
                ->cascadeOnDelete();
            $table->foreignId('id_direction')
                ->constrained('directions', 'id_direction')
                ->cascadeOnDelete();
            $table->primary(['id_utilisateur', 'id_direction']);
        });

        Schema::create('perimetre_service', function (Blueprint $table): void {
            $table->foreignId('id_utilisateur')
                ->constrained('utilisateurs', 'id_utilisateur')
                ->cascadeOnDelete();
            $table->foreignId('id_service')
                ->constrained('services', 'id_service')
                ->cascadeOnDelete();
            $table->primary(['id_utilisateur', 'id_service']);
        });

        Schema::create('parametres', function (Blueprint $table): void {
            $table->id('id_parametre');
            $table->string('famille', 80);
            $table->string('code', 80);
            $table->string('libelle');
            $table->unsignedSmallInteger('ordre_affichage')->default(1);
            $table->boolean('actif')->default(true);
            $table->date('date_debut_validite')->nullable();
            $table->date('date_fin_validite')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
            $table->unique(['famille', 'code']);
            $table->index(['famille', 'actif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres');
        Schema::dropIfExists('perimetre_service');
        Schema::dropIfExists('perimetre_direction');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('utilisateur_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('utilisateurs');
        Schema::dropIfExists('services');
        Schema::dropIfExists('directions');
    }
};

