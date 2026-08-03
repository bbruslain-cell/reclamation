# Audit de la base de donnees

Date de l'audit : 2026-06-04

## Perimetre

Audit realise sur le projet Laravel local et la base PostgreSQL locale `laravel_app`.

Elements verifies :

- migrations Laravel ;
- etat d'execution des migrations ;
- tables, volumes, contraintes, cles et index PostgreSQL ;
- coherence des donnees metier principales ;
- seeders et conventions de structure ;
- alignement avec `laravel-structure.md`.

## Synthese

La base est saine pour l'usage actuel :

- toutes les migrations sont executees ;
- 35 tables sont presentes ;
- 49 cles etrangeres sont en place ;
- 109 index existent apres durcissement ;
- aucun doublon critique detecte ;
- aucun orphelin detecte sur les relations metier auditees ;
- une configuration SLA active existe ;
- la sequence PostgreSQL des numeros de suivi est alignee avec les demandes existantes.

Corrections appliquees le 2026-06-04 :

- ajout d'une migration de durcissement des index FK ;
- toutes les FK auditees disposent maintenant d'un index dont la colonne FK est en premiere position ;
- realignement de `demandes_numero_compteurs` sur le plus grand numero de suivi existant ;
- verification que `demandes_numero_seq` n'est pas en retard sur les demandes existantes ;
- suppression du champ legacy `demandes.date_affectation` au profit de `demandes.date_affectation_accueil` ;
- ajout de tests de non-regression dans `DatabaseConsistencyTest`.

Les points restants a surveiller concernent surtout la maintenance historique :

- deux migrations historiques creent la meme sequence PostgreSQL ;
- une migration SLA est vide ;
- la table Laravel standard `users` et le modele `User` existent mais l'application utilise `utilisateurs` et `Utilisateur`.

## Etat des migrations

Toutes les migrations sont au statut `Ran`.

Nombre de migrations executees : 37.

Observation importante : ne pas renommer ni modifier directement les migrations historiques deja deployees. Les corrections doivent passer par de nouvelles migrations.

## Volumetrie locale

Tables principales :

| Table | Lignes |
|---|---:|
| demandes | 32 |
| usagers | 38 |
| utilisateurs | 28 |
| services | 12 |
| directions | 4 |
| roles | 8 |
| permissions | 12 |
| reponses | 22 |
| pieces_jointes | 13 |
| historique_actions | 328 |
| config_sla | 2 |
| sla_jours_ouvres | 10 |
| sla_jours_feries | 0 |

Tables techniques :

| Table | Lignes |
|---|---:|
| migrations | 36 |
| sessions | 13 |
| cache | 5 |
| jobs | 0 |
| failed_jobs | 0 |
| users | 0 |

## Coherence des donnees

Verifications OK :

- doublons `demandes.numero_suivi` : 0 ;
- doublons `utilisateurs.email` : 0 ;
- doublons `services.code` : 0 ;
- doublons `directions.code` : 0 ;
- doublons `parametres(famille, code)` : 0 ;
- plusieurs reponses pour une meme demande : 0 ;
- demandes sans usager : 0 ;
- demandes sans statut : 0 ;
- demandes sans type : 0 ;
- demandes sans SLA : 0 ;
- affectations orphelines : 0 ;
- reponses orphelines : 0 ;
- pieces jointes de demande orphelines : 0 ;
- pieces jointes de reponse orphelines : 0.

Tests Laravel dedies passes :

- `DatabaseConsistencyTest` : 5 tests OK.

## Referentiel metier

Parametres actifs :

| Famille | Total | Actifs |
|---|---:|---:|
| format_export | 2 | 2 |
| seuil_alerte | 4 | 4 |
| statut_demande | 5 | 5 |
| statut_notif | 3 | 3 |
| type_demande | 2 | 1 |
| type_notif | 4 | 4 |
| type_reponse | 3 | 3 |

Distribution des demandes :

| Statut | Total |
|---|---:|
| nouvelle | 6 |
| affectee_service | 2 |
| affectee_agent | 2 |
| cloturee | 22 |

Toutes les demandes locales sont de type `reclamation`.

Services :

| Direction | Services | Actifs |
|---|---:|---:|
| DAF | 3 | 3 |
| DG | 3 | 3 |
| DS | 3 | 3 |
| DSIC | 3 | 3 |

SLA actif :

- `SUIVI ANBG 24h Ouvrees`
- delai : 24 heures ouvrables ;
- fuseau : `Africa/Libreville` ;
- portee globale : pas de direction/service specifique.

## Numeros de suivi

Etat PostgreSQL :

- sequence utilisee : `demandes_numero_seq` ;
- derniere valeur : 32 ;
- prochain numero probable : 33 ;
- plus grand numero en base : `ANBG-2026-032`.

Conclusion : la generation PostgreSQL est coherente.

Compteur table :

- `demandes_numero_compteurs.valeur = 32` pour 2026 ;
- le compteur table est maintenant aligne avec le plus grand numero local ;
- cela protege aussi les environnements non PostgreSQL qui utilisent ce compteur.

## Contraintes et index

Points positifs :

- contraintes FK explicites sur les tables metier ;
- pivots avec cles primaires composees ;
- unicite sur `numero_suivi`, `email`, `code`, `famille + code` ;
- index metier importants sur `demandes` :
  - `id_statut + date_soumission` ;
  - `id_service_courant + id_statut` ;
  - `date_affectation_accueil` ;
  - `date_cloture` ;
  - `delai_alerte` ;
  - alertes accueil/chef/agent.

Corrections appliquees :

- index ajoutes sur les FK de workflow, notifications, pieces jointes, reponses, perimetres et exports ;
- verification post-migration : 0 FK non indexee en premiere colonne.

## Colonnes proches ou redondantes

Le doublon historique entre `demandes.date_affectation` et `demandes.date_affectation_accueil` est resolu.

Etat retenu :

- `demandes.date_affectation_accueil` est le champ canonique pour la date de transmission depuis l'accueil ;
- `demandes.date_affectation_agent` reste le champ canonique pour l'affectation a un agent ;
- `affectations.date_affectation` est conserve, car il appartient a la table d'historique des affectations et ne duplique pas le champ de `demandes`.

Migration appliquee :

- `2026_06_04_130000_drop_legacy_date_affectation_from_demandes_table.php`.

## Migrations a surveiller

### Sequences numeriques

Deux migrations creent la meme sequence :

- `2026_03_18_210612_create_demandes_numero_sequence.php`
- `2026_03_18_212319_create_demandes_numero_sequence.php`

Elles utilisent `CREATE SEQUENCE IF NOT EXISTS`, donc la montee ne casse pas. Le risque est au rollback : chaque `down()` fait `DROP SEQUENCE IF EXISTS demandes_numero_seq`.

Recommandation : ne pas modifier ces migrations historiques. Si une correction est necessaire, creer une nouvelle migration ou documenter que ces migrations ne doivent pas etre rollbackees individuellement en production.

### Migration vide

`2026_03_25_225906_update_sla_jours_feries_table.php` ne fait rien dans `up()` ni `down()`.

Ce n'est pas bloquant, mais c'est du bruit historique.

### Table `users`

La table Laravel standard `users` existe, ainsi que `App\Models\User`, mais l'application utilise `utilisateurs` et `App\Models\Utilisateur`.

La table `users` est vide. Ce n'est pas bloquant, mais cela peut preter a confusion.

Recommandation : la conserver si elle sert aux defaults Laravel/tests, ou planifier une suppression propre seulement si toute reference Laravel par defaut est maitrisee.

## Recommandations

### Court terme

1. Ne pas toucher aux migrations historiques deja executees.
2. Continuer a ajouter les nouveaux index via de nouvelles migrations.
3. Garder le test de compteur actif pour eviter toute collision future.

### Moyen terme

1. Documenter officiellement que la table metier des comptes est `utilisateurs`, pas `users`.
2. Evaluer si la table Laravel standard `users` doit rester pour compatibilite ou etre supprimee proprement plus tard.

### Long terme

1. Eviter les migrations vides.
2. Nommer les futures migrations avec le suffixe `_table` lorsque le guide le recommande.
3. Ajouter les index au moment de creer les FK frequemment requetees.
4. Garder les seeders de production separes des seeders de demonstration.

## Verdict

La base est saine, coherentement migree et renforcee. Aucun probleme critique de corruption ou d'integrite n'a ete detecte.

Les ameliorations principales de performance et de coherence ont ete appliquees. Les sujets restants sont essentiellement historiques et documentaires.
