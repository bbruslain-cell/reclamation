<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('usagers', function (Blueprint $table): void {
            $table->id('id_usager');
            $table->string('nom');
            $table->string('prenom')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('telephone', 30)->nullable();
            $table->string('qualite')->nullable();
            $table->string('matricule', 60)->nullable();
            $table->boolean('consentement_rgpd')->default(false);
            $table->timestamps();
        });

        Schema::create('demandes', function (Blueprint $table): void {
            $table->id('id_demande');
            $table->string('numero_suivi', 50)->unique();
            $table->foreignId('id_usager')
                ->constrained('usagers', 'id_usager')
                ->restrictOnDelete();
            $table->foreignId('id_type_demande')
                ->constrained('parametres', 'id_parametre')
                ->restrictOnDelete();
            $table->foreignId('id_statut')
                ->constrained('parametres', 'id_parametre')
                ->restrictOnDelete();
            $table->foreignId('id_config_sla')
                ->constrained('config_sla', 'id_config_sla')
                ->restrictOnDelete();
            $table->foreignId('id_service_courant')
                ->nullable()
                ->constrained('services', 'id_service')
                ->nullOnDelete();
            $table->foreignId('id_agent_accueil')
                ->nullable()
                ->constrained('utilisateurs', 'id_utilisateur')
                ->nullOnDelete();
            $table->foreignId('id_agent_direction')
                ->nullable()
                ->constrained('utilisateurs', 'id_utilisateur')
                ->nullOnDelete();
            $table->string('objet');
            $table->text('message');
            $table->timestampTz('date_soumission');
            $table->timestampTz('date_affectation')->nullable();
            $table->timestampTz('date_reponse_direction')->nullable();
            $table->timestampTz('date_envoi_usager')->nullable();
            $table->timestampTz('date_cloture')->nullable();
            $table->decimal('heures_ouvrees_cloture', 8, 2)->nullable();
            $table->string('delai_alerte', 30)->nullable(); // dans_les_delais | a_risque | en_retard
            $table->boolean('importee')->default(false);
            $table->timestamps();

            $table->index('date_soumission');
            $table->index('id_service_courant');
            $table->index('id_statut');
            $table->index('id_type_demande');
        });

        Schema::create('affectations', function (Blueprint $table): void {
            $table->id('id_affectation');
            $table->foreignId('id_demande')
                ->constrained('demandes', 'id_demande')
                ->cascadeOnDelete();
            $table->foreignId('id_service')
                ->constrained('services', 'id_service')
                ->restrictOnDelete();
            $table->foreignId('id_utilisateur')
                ->constrained('utilisateurs', 'id_utilisateur')
                ->restrictOnDelete();
            $table->timestampTz('date_affectation');
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });

        Schema::create('reponses', function (Blueprint $table): void {
            $table->id('id_reponse');
            $table->foreignId('id_demande')
                ->constrained('demandes', 'id_demande')
                ->cascadeOnDelete();
            $table->unsignedInteger('numero_version')->default(1);
            $table->foreignId('id_type_reponse')
                ->constrained('parametres', 'id_parametre')
                ->restrictOnDelete();
            $table->text('contenu_reponse');
            $table->foreignId('id_redacteur')
                ->constrained('utilisateurs', 'id_utilisateur')
                ->restrictOnDelete();
            $table->foreignId('id_envoyeur')
                ->nullable()
                ->constrained('utilisateurs', 'id_utilisateur')
                ->nullOnDelete();
            $table->timestampTz('date_redaction');
            $table->timestampTz('date_envoi_usager')->nullable();
            $table->timestamps();
            $table->unique(['id_demande', 'numero_version'], 'uq_reponse_demande_version');
        });

        Schema::create('pieces_jointes', function (Blueprint $table): void {
            $table->id('id_piece_jointe');
            $table->string('nom_fichier');
            $table->string('chemin_fichier');
            $table->unsignedBigInteger('taille_octets');
            $table->string('type_mime', 100);
            $table->foreignId('id_uploadeur')
                ->nullable()
                ->constrained('utilisateurs', 'id_utilisateur')
                ->nullOnDelete();
            $table->string('source', 30)->default('usager'); // usager | agent
            $table->timestampTz('date_upload');
            $table->timestamps();
        });

        Schema::create('demande_piece_jointe', function (Blueprint $table): void {
            $table->foreignId('id_demande')
                ->constrained('demandes', 'id_demande')
                ->cascadeOnDelete();
            $table->foreignId('id_piece_jointe')
                ->constrained('pieces_jointes', 'id_piece_jointe')
                ->cascadeOnDelete();
            $table->primary(['id_demande', 'id_piece_jointe']);
        });

        Schema::create('reponse_piece_jointe', function (Blueprint $table): void {
            $table->foreignId('id_reponse')
                ->constrained('reponses', 'id_reponse')
                ->cascadeOnDelete();
            $table->foreignId('id_piece_jointe')
                ->constrained('pieces_jointes', 'id_piece_jointe')
                ->cascadeOnDelete();
            $table->primary(['id_reponse', 'id_piece_jointe']);
        });

        Schema::create('historique_actions', function (Blueprint $table): void {
            $table->id('id_action');
            $table->foreignId('id_demande')
                ->constrained('demandes', 'id_demande')
                ->cascadeOnDelete();
            $table->foreignId('id_utilisateur')
                ->nullable()
                ->constrained('utilisateurs', 'id_utilisateur')
                ->nullOnDelete();
            $table->string('type_action', 80);
            $table->foreignId('ancien_statut_id')
                ->nullable()
                ->constrained('parametres', 'id_parametre')
                ->nullOnDelete();
            $table->foreignId('nouveau_statut_id')
                ->nullable()
                ->constrained('parametres', 'id_parametre')
                ->nullOnDelete();
            $table->foreignId('id_service_associe')
                ->nullable()
                ->constrained('services', 'id_service')
                ->nullOnDelete();
            $table->timestampTz('date_action');
            $table->text('commentaire')->nullable();
            $table->timestamps();
            $table->index(['id_demande', 'date_action']);
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->id('id_notification');
            $table->foreignId('id_demande')
                ->constrained('demandes', 'id_demande')
                ->cascadeOnDelete();
            $table->foreignId('id_type_notif')
                ->constrained('parametres', 'id_parametre')
                ->restrictOnDelete();
            $table->foreignId('id_statut_notif')
                ->nullable()
                ->constrained('parametres', 'id_parametre')
                ->nullOnDelete();
            $table->foreignId('id_emetteur')
                ->nullable()
                ->constrained('utilisateurs', 'id_utilisateur')
                ->nullOnDelete();
            $table->string('destinataire_email');
            $table->string('sujet');
            $table->text('contenu');
            $table->timestampTz('date_envoi');
            $table->text('message_erreur')->nullable();
            $table->timestamps();
        });

        Schema::create('exports', function (Blueprint $table): void {
            $table->id('id_export');
            $table->foreignId('id_utilisateur')
                ->constrained('utilisateurs', 'id_utilisateur')
                ->cascadeOnDelete();
            $table->foreignId('id_format_export')
                ->constrained('parametres', 'id_parametre')
                ->restrictOnDelete();
            $table->string('fichier_export');
            $table->json('filtres_appliques')->nullable();
            $table->timestampTz('date_generation');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exports');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('historique_actions');
        Schema::dropIfExists('reponse_piece_jointe');
        Schema::dropIfExists('demande_piece_jointe');
        Schema::dropIfExists('pieces_jointes');
        Schema::dropIfExists('reponses');
        Schema::dropIfExists('affectations');
        Schema::dropIfExists('demandes');
        Schema::dropIfExists('usagers');
    }
};

