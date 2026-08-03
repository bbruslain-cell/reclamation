---
name: laravel-structure
description: "Use this skill for any task involving Laravel file placement, folder structure, class naming, or database migrations. Trigger on: creating a new class (controller, model, service, job, etc.), writing or editing a migration, asking where a file should go, naming a table or column, or reviewing project structure. Ensures every file lands in the right folder with the right name and every migration follows schema conventions."
---

# Laravel — Structure des fichiers & Migrations

## Arborescence de référence

```
app/
├── Console/
│   └── Commands/          # php artisan make:command
├── Exceptions/
│   └── Handler.php
├── Http/
│   ├── Controllers/
│   │   ├── Web/           # Controllers pour les routes web (Blade)
│   │   │   └── PublicDemandController.php
│   │   └── Api/           # Controllers pour les routes API
│   │       └── DemandController.php
│   ├── Middleware/        # php artisan make:middleware
│   └── Requests/          # php artisan make:request
│       └── StoreDemandRequest.php
├── Models/                # php artisan make:model
│   └── Demand.php
├── Policies/              # php artisan make:policy
│   └── DemandPolicy.php
├── Providers/             # php artisan make:provider
├── Rules/                 # php artisan make:rule
│   ├── SafePublicText.php
│   └── AcceptedEmailDomain.php
├── Services/              # créer manuellement
│   └── DemandService.php
├── Jobs/                  # php artisan make:job
├── Events/                # php artisan make:event
├── Listeners/             # php artisan make:listener
├── Notifications/         # php artisan make:notification
└── Mail/                  # php artisan make:mail

database/
├── migrations/            # php artisan make:migration
├── seeders/               # php artisan make:seeder
└── factories/             # php artisan make:factory

resources/
├── views/
│   ├── layouts/           # templates Blade partagés
│   ├── public/            # vues accessibles sans auth
│   └── admin/             # vues back-office
├── css/
└── js/

routes/
├── web.php                # routes HTML/Blade
├── api.php                # routes API (préfixe /api)
└── console.php            # commandes Artisan schedulées

tests/
├── Feature/               # tests HTTP end-to-end
│   └── PublicDemandTest.php
└── Unit/                  # tests unitaires de classes isolées
    └── Services/
        └── DemandServiceTest.php
```

---

## Règles de placement — où va chaque fichier ?

| Ce que tu crées | Dossier | Commande Artisan |
|---|---|---|
| Controller web (Blade) | `app/Http/Controllers/Web/` | `make:controller Web/NomController` |
| Controller API | `app/Http/Controllers/Api/` | `make:controller Api/NomController --api` |
| FormRequest | `app/Http/Requests/` | `make:request StoreNomRequest` |
| Model Eloquent | `app/Models/` | `make:model Nom` |
| Migration | `database/migrations/` | `make:migration create_nom_table` |
| Service (logique métier) | `app/Services/` | *(manuel)* |
| Rule de validation | `app/Rules/` | `make:rule NomRule` |
| Policy | `app/Policies/` | `make:policy NomPolicy --model=Nom` |
| Job | `app/Jobs/` | `make:job NomJob` |
| Event | `app/Events/` | `make:event NomEvent` |
| Listener | `app/Listeners/` | `make:listener NomListener` |
| Middleware | `app/Http/Middleware/` | `make:middleware NomMiddleware` |
| Command Artisan | `app/Console/Commands/` | `make:command NomCommand` |
| Notification | `app/Notifications/` | `make:notification NomNotification` |
| Mail | `app/Mail/` | `make:mail NomMail` |
| Vue Blade | `resources/views/<section>/` | *(manuel)* |
| Test Feature | `tests/Feature/` | `make:test NomTest` |
| Test Unit | `tests/Unit/` | `make:test NomTest --unit` |

---

## Nommage des classes et fichiers

```
✅ Controller  : NomEntitéController.php     (PascalCase, singulier)
✅ Model       : Demand.php                  (PascalCase, singulier)
✅ FormRequest : StoreNomRequest.php         (Store/Update/Delete + Nom + Request)
✅ Service     : DemandService.php           (PascalCase, singulier)
✅ Rule        : SafePublicText.php          (PascalCase, ce qu'elle valide)
✅ Policy      : DemandPolicy.php            (PascalCase, singulier)
✅ Job         : ProcessDemandJob.php        (verbe + nom + Job)
✅ Event       : DemandSubmitted.php         (nom + participe passé)
✅ Listener    : SendConfirmationEmail.php   (verbe + ce qu'il fait)
✅ Migration   : 2024_01_01_000000_create_demandes_table.php
```

---

## Migrations — conventions de nommage

### Nom du fichier de migration

```bash
# Création de table
php artisan make:migration create_demandes_table

# Ajout de colonne
php artisan make:migration add_categorie_to_demandes_table

# Modification de colonne
php artisan make:migration change_statut_in_demandes_table

# Suppression de colonne
php artisan make:migration drop_ancien_champ_from_demandes_table

# Table pivot (relation many-to-many)
php artisan make:migration create_demande_piece_jointe_table
# ↑ toujours alphabétique + singulier_singulier
```

### Nommage des tables

```
✅ demandes               — pluriel, snake_case
✅ usagers
✅ historique_actions
✅ config_sla
✅ parametres
✅ demande_piece_jointe   — pivot : singulier_singulier, alphabétique

❌ Demande                — PascalCase interdit
❌ demandesPieceJointe    — camelCase interdit
❌ tbl_demandes           — préfixe tbl_ inutile
```

### Nommage des colonnes

```
✅ id_demande             — PK : id_<table_singulier>
✅ id_usager              — FK : id_<table_référencée_singulier>
✅ date_soumission        — dates : date_<nom>
✅ created_at / updated_at — toujours présents (timestamps())
✅ nom, prenom, email     — snake_case, pas d'abréviation
✅ est_actif              — booléens : est_<adjectif>
✅ alerte_accueil         — snake_case descriptif

❌ idDemande              — camelCase interdit
❌ DemId                  — abréviation + majuscule
❌ date1                  — non descriptif
```

---

## Structure d'une migration complète

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes', function (Blueprint $table) {
            // 1. Clé primaire
            $table->id('id_demande');

            // 2. Clés étrangères
            $table->unsignedBigInteger('id_usager');
            $table->unsignedBigInteger('id_type_demande');
            $table->unsignedBigInteger('id_statut');
            $table->unsignedBigInteger('id_config_sla');

            // 3. Champs métier
            $table->string('numero_suivi', 30)->unique();
            $table->string('objet', 255);
            $table->string('categorie', 255)->nullable();
            $table->text('message');

            // 4. Dates métier
            $table->timestamp('date_soumission');
            $table->timestamp('date_cloture')->nullable();

            // 5. Champs de statut / flags
            $table->enum('alerte_accueil', ['vert', 'orange', 'rouge'])->default('vert');
            $table->enum('delai_alerte', ['dans_les_delais', 'en_retard'])->default('dans_les_delais');

            // 6. Timestamps Laravel (toujours en dernier)
            $table->timestamps();

            // 7. Index et contraintes FK
            $table->foreign('id_usager')->references('id_usager')->on('usagers');
            $table->foreign('id_type_demande')->references('id_parametre')->on('parametres');
            $table->foreign('id_statut')->references('id_parametre')->on('parametres');
            $table->foreign('id_config_sla')->references('id_config_sla')->on('config_sla');

            $table->index('numero_suivi');
            $table->index('id_statut');
            $table->index('date_soumission');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes');
    }
};
```

### Ordre des colonnes dans une migration

1. Clé primaire (`id`)
2. Clés étrangères (`id_*`)
3. Champs métier obligatoires
4. Champs métier nullables
5. Dates métier
6. Flags / enums
7. `$table->timestamps()` — toujours en dernier
8. Déclarations FK et index — après la définition des colonnes

---

## Règles absolues pour les migrations

```php
// ✅ Toujours une méthode down() qui annule proprement le up()
public function down(): void
{
    Schema::dropIfExists('demandes');
}

// ✅ Toujours timestamps()
$table->timestamps();

// ✅ Index sur toute colonne utilisée dans un WHERE fréquent
$table->index('id_statut');
$table->index(['id_usager', 'date_soumission']); // index composite

// ✅ Contraintes FK explicites (pas juste foreignId sans constrained)
$table->unsignedBigInteger('id_usager');
$table->foreign('id_usager')->references('id_usager')->on('usagers')->onDelete('restrict');

// ✅ Nullable explicite sur les champs optionnels
$table->string('categorie')->nullable();

// ❌ Jamais modifier une migration déjà en production
// → Toujours créer une nouvelle migration d'altération
php artisan make:migration add_priorite_to_demandes_table
```

---

## Checklist structure avant PR

- [ ] Chaque fichier est dans le bon dossier (voir tableau ci-dessus)
- [ ] Nom de fichier en PascalCase, cohérent avec le nom de la classe
- [ ] Controllers Web dans `Web/`, API dans `Api/`
- [ ] FormRequest dans `Http/Requests/`, nommé `Store/Update/DeleteNomRequest`
- [ ] Services dans `app/Services/`, un fichier par domaine métier
- [ ] Rules custom dans `app/Rules/`, pas de closures inline dans les controllers
- [ ] Migration nommée avec le bon préfixe (`create_`, `add_`, `change_`, `drop_`)
- [ ] Tables en snake_case pluriel, colonnes en snake_case
- [ ] PKs nommées `id_<table_singulier>`, FKs `id_<référence_singulier>`
- [ ] `timestamps()` présent dans toutes les migrations
- [ ] Index sur toutes les colonnes de filtrage/tri fréquent
- [ ] Contraintes FK explicites avec `onDelete`
- [ ] Méthode `down()` complète et correcte
- [ ] Colonnes nullable uniquement si vraiment optionnelles
