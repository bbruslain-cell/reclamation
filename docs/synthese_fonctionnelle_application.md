# Synthese Fonctionnelle De L Application ANBG

Date de mise a jour : 07/05/2026

## Objectif

Ce document resume ce que fait l'application de suivi des reclamations ANBG, depuis la soumission publique par un usager jusqu'au tableau de bord de pilotage.

Il explique aussi les roles, les statuts, les actions possibles, les donnees creees, les delais SLA, les exports et les principaux fichiers techniques associes.

## Vue D Ensemble

L'application sert a recevoir, affecter, traiter, tracer et piloter les reclamations envoyees a l'ANBG.

Le parcours principal est le suivant :

```text
Usager
  -> Formulaire public de reclamation
  -> Creation de la demande et du numero de suivi
  -> Accueil
  -> Affectation a un service
  -> Chef de service
  -> Affectation a un agent ou reponse directe
  -> Agent traitant
  -> Redaction de la reponse
  -> Envoi email a l'usager
  -> Cloture
  -> Tableaux de bord, suivi CIQ et exports
```

Les actions importantes sont tracees dans l'historique. Les delais sont recalcules avec les heures ouvrees, et les dashboards utilisent ces donnees pour afficher les volumes, retards, performances par direction/service/agent et exports CIQ.

## Parcours Complet D Une Reclamation

### 1. Soumission publique

L'usager arrive sur la page publique :

- `GET /reclamations/nouvelle`
- vue : `resources/views/public/create-demand.blade.php`
- controleur : `app/Http/Controllers/Web/PublicDemandController.php`

Le formulaire demande notamment :

- nom
- prenom
- email
- statut de l'usager
- pays
- etablissement si l'usager est eleve ou etudiant
- categorie optionnelle
- objet
- message
- piece jointe optionnelle
- consentement

La piece jointe publique accepte uniquement :

- PDF
- JPG / JPEG
- PNG

La taille maximale actuelle est de 3584 Ko pour la piece jointe de soumission.

Au moment de l'envoi :

- l'application verifie qu'une configuration SLA active existe dans `config_sla`
- elle recupere le type de demande actif `reclamation`
- elle recupere le statut initial `nouvelle`
- elle cree un snapshot usager dans `usagers`
- elle cree une ligne dans `demandes`
- elle genere un numero de suivi de type `ANBG-2026-001`
- elle stocke la piece jointe dans `pieces_jointes` puis lie le fichier a la demande via `demande_piece_jointe`
- elle ecrit une trace `soumission_usager` dans `historique_actions`
- elle redirige l'usager vers le formulaire avec un message de succes et son numero de suivi

Actuellement, le type public utilise par defaut est `reclamation`. Le type `demande_information` est desactive dans le parametrage actuel.

### 2. Connexion des agents internes

Les utilisateurs internes passent par :

- `GET /login`
- `POST /login`
- vue : `resources/views/auth/login.blade.php`
- controleur : `app/Http/Controllers/Web/AuthController.php`

Apres connexion, l'utilisateur arrive sur :

- `GET /espace`
- controleur : `app/Http/Controllers/Web/AgentPortalController.php`

Cette page determine les espaces disponibles selon les roles de l'utilisateur :

- administration
- accueil
- chef de service
- chef de direction
- agent
- pilotage

Si un utilisateur a plusieurs roles, l'application peut afficher une page de choix d'espace. Sinon, elle redirige automatiquement vers l'espace principal.

### 3. Traitement par l'Accueil

L'Accueil est la premiere ligne de traitement interne.

Page :

- `GET /accueil/inbox`
- vue : `resources/views/workflow/accueil-inbox.blade.php`
- controleur : `app/Http/Controllers/Web/AccueilInboxController.php`

Le role Accueil voit les nouvelles reclamations et les demandes deja affectees. Il peut filtrer, rechercher et consulter les pieces jointes.

Actions principales :

- affecter une demande a une direction et un service
- traiter directement une reclamation simple
- annuler une affectation service si la demande peut revenir a l'etat initial

Quand l'Accueil affecte une demande a un service :

- une ligne est creee dans `affectations`
- la demande passe de `nouvelle` a `affectee_service`
- `id_service_courant` est renseigne
- `id_agent_accueil` est renseigne
- `date_affectation_accueil` est renseignee
- une trace `affectation_service` est ajoutee dans `historique_actions`
- les alertes SLA sont recalculees

Quand l'Accueil repond directement :

- une reponse est creee ou mise a jour dans `reponses`
- des pieces jointes de reponse peuvent etre ajoutees
- l'envoi email final est mis en file
- une trace `reponse_directe_accueil` est ajoutee
- la cloture devient effective apres confirmation d'envoi par le job email

Indicateurs cote Accueil :

- total demandes
- nouvelles demandes
- reclamations
- demandes a risque
- demandes en retard

### 4. Traitement par le Chef de service

Le Chef de service pilote les demandes affectees a son service ou a son perimetre.

Page :

- `GET /chef/inbox`
- vue : `resources/views/workflow/chef-inbox.blade.php`
- controleur : `app/Http/Controllers/Web/ChefInboxController.php`

Le Chef de service voit deux grands blocs :

- les demandes affectees au service mais sans agent
- les demandes deja affectees a un agent ou avec reponse prete

Actions principales :

- affecter une demande a un agent de son service
- annuler une affectation agent
- repondre directement si aucun agent n'a encore ete designe
- consulter l'historique recent du service
- suivre les retards et alertes SLA

Quand le Chef affecte une demande a un agent :

- la demande passe a `affectee_agent`
- `id_agent_traitant` est renseigne
- `date_affectation_agent` est renseignee
- une trace `affectation_agent` est ajoutee
- les alertes sont recalculees

Quand il annule l'affectation agent :

- la demande revient a `affectee_service`
- l'agent traitant est retire
- la date d'affectation agent est retiree
- une trace `annulation_affectation_agent` est ajoutee

Quand le Chef repond directement :

- cela est possible seulement si aucun agent n'est deja affecte
- la demande doit etre en statut `affectee_service` ou `reponse_prete`
- une reponse finale est preparee
- les pieces jointes de reponse peuvent etre ajoutees
- l'email final est mis en file
- une trace `reponse_directe_chef` est ajoutee

KPI attendus pour le Chef de service :

- nombre de demandes affectees
- nombre de demandes en retard
- total demandes assignees
- total agents

Le controleur calcule aussi des donnees utiles comme les demandes sans agent, les agents mobilises, les reponses pretes, les demandes ouvertes et les demandes cloturees.

### 5. Traitement par l'Agent

L'Agent traite les demandes qui lui sont personnellement affectees.

Page :

- `GET /agent/inbox`
- vue : `resources/views/workflow/agent-inbox.blade.php`
- controleur : `app/Http/Controllers/Web/AgentInboxController.php`

L'Agent voit uniquement :

- les demandes dont `id_agent_traitant` correspond a son utilisateur
- les demandes en statut `affectee_agent`
- les demandes en statut `reponse_prete`

Actions principales :

- consulter la demande
- consulter les pieces jointes de l'usager
- rediger la reponse finale
- ajouter jusqu'a 5 pieces jointes de reponse
- envoyer la reponse finale a l'usager

Quand l'Agent envoie une reponse :

- la reponse est creee ou mise a jour dans `reponses`
- son type est `finale`
- la demande passe a `reponse_prete`
- les pieces jointes de reponse sont stockees
- l'envoi email est mis en file
- la demande sera cloturee seulement apres confirmation d'envoi

### 6. Envoi Email Et Cloture

Le service central du workflow est :

- `app/Services/DemandWorkflowService.php`

L'envoi final ne cloture pas brutalement la demande au clic. L'application met d'abord l'envoi en file via :

- `App\Jobs\SendDemandResponseJob`
- mail : `App\Mail\DemandResponseMail`
- vue email : `resources/views/emails/demand-response.blade.php`

Pendant la mise en file :

- la reponse recoit un statut d'envoi en attente
- la demande garde la trace de la demande d'envoi
- le job email est execute apres le commit de la transaction

Quand l'email est envoye avec succes :

- `date_envoi_usager` est renseignee
- `date_cloture` est renseignee
- la demande passe a `cloturee`
- `heures_ouvrees_cloture` est calcule
- une trace `envoi_reponse` est ajoutee
- les alertes SLA sont recalculees

Si l'envoi echoue :

- l'etat d'envoi en attente est retire
- l'erreur est tracee
- la demande n'est pas cloturee

Ce fonctionnement evite de cloturer une reclamation alors que l'email n'est pas reellement parti.

## Statuts D Une Demande

Les statuts actifs sont stockes dans `parametres` avec la famille `statut_demande`.

| Statut technique | Libelle metier | Sens |
| --- | --- | --- |
| `nouvelle` | Recu | La reclamation vient d'etre soumise et attend l'Accueil. |
| `affectee_service` | Affectee au service | L'Accueil a transmis la demande a un service. |
| `affectee_agent` | Affectee a un agent | Le Chef de service a designe un agent traitant. |
| `reponse_prete` | Reponse redigee | Une reponse existe et l'envoi final est en cours ou pret. |
| `cloturee` | Cloturee | L'usager a recu la reponse finale avec succes. |

## Roles Et Responsabilites

### Usager

L'usager n'est pas un utilisateur interne. Il utilise seulement le formulaire public.

Il peut :

- soumettre une reclamation
- joindre un document
- recevoir un numero de suivi
- recevoir la reponse finale par email

### Accueil

Role technique : `accueil`

Mission :

- recevoir les nouvelles reclamations
- qualifier le dossier
- choisir la direction et le service destinataire
- traiter directement les cas simples si possible

Permissions principales :

- voir toutes les demandes
- affecter une demande a un service
- envoyer une reponse directe

### Chef De Service

Role technique : `chef_service`

Mission :

- piloter les demandes de son service
- affecter les demandes aux agents
- surveiller les retards
- repondre directement avant affectation a un agent si necessaire

Permissions principales :

- voir les demandes de son perimetre
- affecter une demande a un agent
- rediger ou envoyer une reponse
- consulter le dashboard si autorise

### Agent

Role technique : `agent`

Mission :

- traiter les demandes qui lui sont affectees
- rediger la reponse finale
- envoyer la reponse a l'usager

Permissions principales :

- voir ses demandes
- envoyer une reponse finale

### Chef De Direction

Role technique : `chef_direction`

Mission :

- superviser les services de sa direction
- consulter les demandes de son perimetre
- suivre les performances par service
- identifier les services les plus charges, les plus en retard et les plus conformes

Etat actuel :

- l'acces est principalement en consultation
- la route de redaction existe, mais le controleur refuse l'action avec le message indiquant que le chef de direction est en consultation uniquement

### CIQ

Role technique : `ciq`

Mission :

- controle interne et qualite
- suivi transversal des reclamations
- consultation globale
- exports et tableaux de suivi
- audit fonctionnel des actions

Permissions principales :

- voir toutes les demandes
- consulter le dashboard
- exporter les donnees
- consulter l'audit

Le CIQ est l'acteur naturel pour les tableaux de suivi, les annexes, les exports PDF/Excel et les indicateurs de conformite.

### Direction Generale

Role technique : `dg`

Mission :

- supervision globale
- lecture des indicateurs consolides
- suivi des volumes et performances de l'organisation

Permissions principales :

- voir toutes les demandes
- consulter le dashboard

### Administrateur

Role technique : `admin`

Mission :

- administrer l'application
- gerer les utilisateurs
- gerer les roles et permissions
- gerer les directions et services
- gerer les parametres
- gerer les jours feries et la configuration utile au SLA

Pages principales :

- `/admin/dashboard`
- `/admin/utilisateurs`
- `/admin/roles`
- `/admin/directions`
- `/admin/services`
- `/admin/parametres`
- `/admin/jours-feries`

L'administrateur a toutes les permissions.

### Lecture Seule

Role technique : `lecture_seule`

Mission :

- consulter sans agir
- acceder aux vues autorisees par son perimetre

Permissions principales :

- voir les demandes de son perimetre
- consulter le dashboard

## Perimetres D Acces

Les droits ne dependent pas seulement du role. Ils dependent aussi du perimetre.

Le service `AccessControlService` calcule les services accessibles a partir de :

- `utilisateurs.id_service`
- `perimetre_service`
- `perimetre_direction`

Regle simple :

- un utilisateur avec `demande.view.all` voit tout
- un agent simple voit seulement ses demandes affectees
- un chef voit son service ou son perimetre de service
- un chef de direction voit les services de sa direction
- le CIQ et la DG peuvent avoir un perimetre global selon les comptes graines

## Tableau De Bord Et Pilotage

Pages :

- `GET /pilotage`
- `GET /pilotage/dashboard`
- `GET /pilotage/data`
- `GET /pilotage/demandes/{id}/detail`

Controleur API principal :

- `app/Http/Controllers/Api/OverviewController.php`

Le dashboard consolide les donnees des demandes selon les filtres :

- periode
- date debut / date fin
- direction
- service
- statut
- etat appliquee / non appliquee

KPI globaux :

- total demandes
- total ouvertes
- total traitees
- total appliquees
- total non appliquees
- total dans les delais
- total a risque
- total en retard
- taux de traitement dans les delais
- delai moyen de traitement en heures ouvrees
- alertes Accueil, Chef, Agent et globales

Analyses disponibles :

- repartition par statut
- repartition par direction
- performance des directions
- KPI par service
- KPI par agent
- repartition par type
- performance par type
- evolution temporelle des reclamations recues et cloturees
- usagers les plus actifs
- registre des mails
- registre controle interne
- tableau de suivi CIQ
- annexes de repartition
- actions recentes
- tracabilite globale
- demandes en cours
- organisation

## Exports

Controleur :

- `app/Http/Controllers/Web/PilotageExportController.php`

Formats acceptes :

- PDF
- Excel XLSX

Exports principaux :

- suivi CIQ detaille
- annexe de repartition des reclamations recurrentes
- repartition par fonction
- repartition des reclamations par service
- repartition des demandes par direction en PDF
- taux dans les delais par direction en PDF
- taux dans les delais par service en PDF
- evolution des reclamations en PDF

Chaque export genere une ligne dans `exports` avec :

- utilisateur
- format
- nom du fichier
- filtres appliques
- date de generation

## SLA Et Alertes

Le SLA actif est cree par `database/seeders/SlaSeeder.php`.

Configuration actuelle :

- nom : `SLA ANBG 24h Ouvrees`
- delai global : 24 heures ouvrees
- fuseau : `Africa/Libreville`
- jours ouvres : lundi a vendredi
- plage horaire : 07:30 a 15:30

Les jours feries sont stockes dans `sla_jours_feries`.

Les calculs utilisent :

- `app/Services/WorkingHoursSlaService.php`
- `app/Services/StepAlertService.php`

Seuils d'alerte actuels :

| Etape | Orange a partir de | Rouge apres |
| --- | ---: | ---: |
| Accueil | 4 h ouvrees | 8 h ouvrees |
| Chef de service | 8 h ouvrees | 16 h ouvrees |
| Agent | 8 h ouvrees | 16 h ouvrees |
| Global | 12 h ouvrees | 24 h ouvrees |

Codes d'alerte :

- `vert` : dans les delais
- `orange` : a risque
- `rouge` : en retard

Ancien code global conserve :

- `dans_les_delais`
- `a_risque`
- `en_retard`

## API REST Interne

Routes principales :

- `GET /api/overview`
- `GET /api/demandes`
- `GET /api/demandes/{id}`
- `PUT /api/demandes/{id}/affecter`
- `PUT /api/demandes/{id}/affecter-agent`
- `PUT /api/demandes/{id}/annuler-affectation-agent`
- `PUT /api/demandes/{id}/rediger-reponse`
- `PUT /api/demandes/{id}/envoyer-reponse`

Ces routes utilisent la session agent et les permissions Laravel. Elles permettent d'alimenter les vues dynamiques, le dashboard et les actions workflow depuis une interface ou un client interne.

## Donnees Metier Importantes

Tables principales :

- `usagers` : informations snapshot de l'usager
- `demandes` : coeur du dossier
- `affectations` : affectations service
- `reponses` : reponse finale ou directe
- `pieces_jointes` : fichiers envoyes par l'usager ou par un agent
- `demande_piece_jointe` : lien demande/fichier
- `reponse_piece_jointe` : lien reponse/fichier
- `historique_actions` : trace de tout le workflow
- `notifications` : notifications et etats d'envoi
- `exports` : exports generes
- `config_sla` : configuration des delais
- `sla_jours_ouvres` : plages horaires ouvrables
- `sla_jours_feries` : jours exclus du calcul SLA
- `directions` : directions ANBG
- `services` : services rattaches aux directions
- `utilisateurs` : agents internes
- `roles` : roles applicatifs
- `permissions` : permissions applicatives
- `parametres` : types, statuts, formats, seuils et catalogues

## Tracabilite

La table `historique_actions` garde les evenements metier importants :

- `soumission_usager`
- `categorie_usager`
- `affectation_service`
- `affectation_agent`
- `annulation_affectation_agent`
- `annulation_affectation_service`
- `reponse_redigee`
- `reponse_directe_accueil`
- `reponse_directe_chef`
- `envoi_reponse`

Elle permet de reconstituer :

- qui a agi
- quand l'action a ete faite
- quel statut a change
- quel service etait concerne
- quel commentaire a ete ajoute

## Organisation Graines

Le seeder d'organisation cree les directions et services de base.

Directions principales :

- `DG`
- `DS`
- `DAF`
- `DSIC`

Services exemples :

- `UCAS`
- `SCIQ`
- `CABINET_DG`
- `CS_SIRS`
- `CS_JSP`
- `CS_GDS`
- `CS_AJARH`
- `CS_FC`
- `CS_AMG`
- `CS_SNB`
- `CS_SENB`
- `CS_P`

Comptes de test ou de reference crees par les seeders :

- `admin@anbg.ga`
- `accueil@anbg.ga`
- `agent.ds@anbg.ga`
- `agent.daf@anbg.ga`
- `agent.dsic@anbg.ga`
- `chef.ds@anbg.ga`
- `chef.daf@anbg.ga`
- `chef.dsic@anbg.ga`
- `chef.direction.ds@anbg.ga`
- `chef.direction.daf@anbg.ga`
- `chef.direction.dsic@anbg.ga`
- `chef.sciq@anbg.ga`
- `ciq@anbg.ga`
- `dg@anbg.ga`
- `lecture@anbg.ga`

## Parametrage Minimal En Production

Pour que le formulaire public fonctionne, il faut au minimum :

- les parametres actifs dans `parametres`
- un type `reclamation`
- les statuts actifs
- une configuration SLA active
- des jours ouvres SLA actifs

Pour Render et les environnements de test, le seeder minimal est :

- `database/seeders/ProductionBaselineSeeder.php`

Il execute les seeders minimums sans ajouter les donnees de demonstration. C'est ce qui evite l'erreur fonctionnelle :

```text
Configuration des delais absente.
```

## Fichiers Techniques A Connaitre

Routes :

- `routes/web.php`
- `routes/api.php`

Controleurs metier :

- `app/Http/Controllers/Web/PublicDemandController.php`
- `app/Http/Controllers/Web/AccueilInboxController.php`
- `app/Http/Controllers/Web/ChefInboxController.php`
- `app/Http/Controllers/Web/AgentInboxController.php`
- `app/Http/Controllers/Web/DirectionInboxController.php`
- `app/Http/Controllers/Api/OverviewController.php`
- `app/Http/Controllers/Api/DemandWorkflowController.php`
- `app/Http/Controllers/Web/PilotageExportController.php`

Services :

- `app/Services/DemandWorkflowService.php`
- `app/Services/AccessControlService.php`
- `app/Services/StepAlertService.php`
- `app/Services/WorkingHoursSlaService.php`
- `app/Services/RoleSyncService.php`

Vues importantes :

- `resources/views/public/create-demand.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/workflow/accueil-inbox.blade.php`
- `resources/views/workflow/chef-inbox.blade.php`
- `resources/views/workflow/agent-inbox.blade.php`
- `resources/views/workflow/direction-inbox.blade.php`
- `resources/views/pilotage-dashboard.blade.php`
- `resources/views/pilotage-ciq.blade.php`
- `resources/views/emails/demand-response.blade.php`

Seeders importants :

- `database/seeders/ParameterSeeder.php`
- `database/seeders/AccessControlSeeder.php`
- `database/seeders/OrganizationSeeder.php`
- `database/seeders/SlaSeeder.php`
- `database/seeders/ProductionBaselineSeeder.php`

## Resume Tres Court

L'application transforme une reclamation publique en dossier interne suivi. L'Accueil recoit et affecte, le Chef de service pilote et distribue, l'Agent repond, l'email final cloture la demande, et le CIQ/DG utilisent le dashboard pour controler les delais, les volumes, les performances et la tracabilite.
