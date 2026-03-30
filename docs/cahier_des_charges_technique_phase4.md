# Cahier Des Charges Technique - Application Suivi des Reclamations ANBG

## 1. Informations Generales

- Version document: `v1.0 (draft technique phase 4)`
- Date: `09/03/2026`
- Projet: `Digitalisation du suivi des reclamations ANBG`
- Base de donnees cible: `PostgreSQL`
- Stack actuelle: `PHP 8.2`, `Laravel 12`, `Blade`, `Chart.js`

Ce document decrit le **niveau reel atteint** dans l'application et le cadre technique pour poursuivre le developpement jusqu'a la mise en production.

## 2. Objectifs Techniques

- Assurer la tracabilite complete d'une demande de la soumission a la reponse finale.
- Appliquer un SLA metier en heures ouvrees configurable.
- Gerer les habilitations par role et perimetre (direction/service).
- Permettre un pilotage CIQ global avec tableaux et exports PDF.
- Garantir la modularite (directions/services/parametres modifiables sans recodage).

## 3. Perimetre Actuel (Phase 4 - Deja Implante)

### 3.1 Parcours metier couvert

- Soumission publique d'une demande (`information` ou `reclamation`) avec pieces jointes.
- Reception et tri par Accueil.
- Affectation de la demande a un service appartenant a une direction.
- Traitement par Direction/Service, redaction de reponse, pieces jointes.
- Retour vers Accueil pour envoi final a l'usager.
- Reouverture d'une demande.
- Historisation des actions et etats.

### 3.2 Interfaces web actives

- `/reclamations/nouvelle` (soumission usager)
- `/login` (authentification agent)
- `/accueil/inbox` (traitement Accueil)
- `/direction/inbox` (traitement Direction/Service)
- `/pilotage` (tableaux de bord CIQ / profils autorises)
- `/admin` (parametrage et administration)

### 3.3 API active

- `GET /api/overview`
- `GET /api/demandes`
- `GET /api/demandes/{id}`
- `PUT /api/demandes/{id}/affecter`
- `PUT /api/demandes/{id}/rediger-reponse`
- `PUT /api/demandes/{id}/envoyer-reponse`
- `PUT /api/demandes/{id}/reouvrir`

## 4. Architecture Technique

### 4.1 Couches

- Presentation: `Blade` + JS vanilla + `Chart.js`.
- Application: `Controllers` + `Services`.
- Metier:
  - `DemandWorkflowService` (workflow demandes/reponses)
  - `WorkingHoursSlaService` (calcul heures ouvrees + alertes)
  - `AccessControlService` (roles, permissions, perimetres, acces demande)
- Persistance: `PostgreSQL` via query builder Laravel.

### 4.2 Services critiques

- `AccessControlService`:
  - resolution acteur (session/header),
  - verification permissions,
  - controle perimetre service/direction.
- `WorkingHoursSlaService`:
  - calcule les heures ouvrees entre deux dates,
  - ignore jours non ouvres et jours feries,
  - classifie l'alerte (`dans_les_delais`, `a_risque`, `en_retard`).
- `DemandWorkflowService`:
  - affectation,
  - redaction reponse versionnee,
  - envoi final,
  - reouverture,
  - journalisation `historique_actions`.

## 5. Donnees et Modele Technique

### 5.1 Referentiel organisation et acces

- `directions`
- `services`
- `utilisateurs`
- `roles`
- `permissions`
- `utilisateur_role`
- `permission_role`
- `perimetre_direction`
- `perimetre_service`
- `parametres` (types, statuts, formats, etc.)

### 5.2 SLA et calendrier

- `config_sla`
- `sla_jours_ouvres`
- `sla_jours_feries`

### 5.3 Reclamations et traitement

- `usagers`
- `demandes`
- `affectations`
- `reponses` (versionnement)
- `pieces_jointes`
- `demande_piece_jointe`
- `reponse_piece_jointe`
- `historique_actions`
- `notifications`
- `exports`

## 6. Regles Metier Techniques (Etat Courant)

### 6.1 SLA 72h ouvrees

- SLA seed actif: `72h`.
- Fuseau: `Africa/Libreville`.
- Jours ouvres seed: lundi a vendredi.
- Plage horaire: `07:30` a `15:30`.
- Jours feries geres par table parametrable.

### 6.2 Alertes SLA

- `dans_les_delais`: avant seuil risque.
- `a_risque`: seuil intermediaire.
- `en_retard`: au dela du delai maximal.

### 6.3 Workflow statut (parametrable)

Statuts actuellement utilises:
- `nouvelle`
- `affectee`
- `en_cours`
- `reponse_prete`
- `transmise`
- `cloturee`
- `reouverte`

## 7. Securite et Habilitations

### 7.1 Roles existants

- `accueil`
- `direction`
- `chef_direction`
- `chef_service`
- `ciq`
- `dg`
- `admin`
- `lecture_seule`

### 7.2 Principes de controle

- Authentification agent via session.
- Permissions role-based (`permission_role`).
- Cloisonnement par perimetre direction/service.
- Controle d'acces applique dans les ecrans et endpoints metiers.

## 8. Pilotage CIQ (Etat Courant)

Le pilotage affiche notamment:
- KPIs globaux.
- Repartition par direction.
- Repartition par statut (histogramme).
- Repartition par type (camembert avec pourcentages).
- Annexe 1 (tableau detail suivi).
- Annexe 2 (repartition informations/reclamations).
- Tracabilite actions recentes.
- Tracabilite globale par demande (timeline complete + acteur).
- Historique usagers.
- Exports PDF par section et global.

## 9. Exigences Non Fonctionnelles

### 9.1 Performance

- Index metier presents sur `demandes` (date, statut, type, service).
- Limitation du volume renvoye sur certaines vues de pilotage.

### 9.2 Qualite et tests

- Suite de tests automatises Laravel existante.
- Etat constate au 09/03/2026: `14 tests passes`.

### 9.3 Journalisation et audit

- Toute action metier critique inscrit une entree dans `historique_actions`.
- L'identite de l'acteur est conservee lorsque l'action provient d'un agent.

## 10. Prerequis Environnement

- PHP `>= 8.2`
- PostgreSQL
- Composer / Node
- Commandes de demarrage:

```bash
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

## 11. Limites Connues / Points a Verrouiller Avant Prod

- Politique securite renforcee a formaliser (verrouillage compte, rate limiting public, logs securite).
- Strategie notifications (mail) a industrialiser.
- Politique de conservation des pieces jointes et sauvegardes.
- Strategie d'exports PDF serveur (si besoin hors navigateur).
- Validation metier finale des seuils SLA/couleurs.
- Clarification de certaines vues DG/lecture_seule (niveau d'anonymisation attendu).

## 12. Backlog Technique Prioritaire (Phase Suivante)

1. Durcir la securite applicative (rate-limit, audit securite, politiques mot de passe).
2. Completer les tests d'autorisation par role/perimetre.
3. Industrialiser l'observabilite (logs structures, suivi erreurs).
4. Ajouter pipeline CI/CD (lint, tests, migration check).
5. Formaliser procedures de sauvegarde/restauration PostgreSQL.

## 13. Definition of Done Technique (Prochain Jalons)

Un lot est considere termine si:
- migrations/seed passent sans erreur,
- tests unitaires/feature verts,
- controle d'acces valide (pas de fuite hors perimetre),
- trace historique complete pour chaque action metier,
- ecrans de pilotage coherents avec les donnees API,
- documentation mise a jour (routes, droits, regles SLA).

