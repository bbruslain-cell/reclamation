# Manuel d'utilisation de l'application de suivi des reclamations ANBG

Version: 1.0  
Application: Suivi des reclamations et demandes ANBG  
Public concerne: usagers, agents d'accueil, chefs de service, agents traitants, chefs de direction, CIQ, DG, administrateurs.

## 1. Objet du manuel

Ce manuel explique l'utilisation complete de l'application de suivi des reclamations ANBG:

- depot d'une reclamation par un usager;
- connexion des utilisateurs internes;
- orientation des demandes par l'accueil;
- affectation et suivi par les chefs de service;
- traitement et envoi des reponses par les agents;
- supervision par les chefs de direction;
- pilotage par le CIQ, la DG et les profils habilites;
- administration des utilisateurs, roles, directions, services et parametres;
- regles de delais, alertes, pieces jointes, exports et bonnes pratiques.

L'application est organisee autour d'un principe simple: chaque reclamation est deposee par un usager, recue par l'accueil, affectee au service competent, prise en charge par un chef de service ou un agent, puis cloturee apres envoi confirme de la reponse finale a l'usager.

## Table des matieres

1. Objet du manuel
2. Acces a l'application
3. Roles et espaces de travail
4. Cycle de vie d'une reclamation
5. Depot d'une reclamation par un usager
6. Connexion et choix d'espace
7. Espace Accueil
8. Espace Chef de service
9. Espace Agent
10. Espace Chef de direction
11. Pilotage et tableaux de bord
12. Administration
13. Delais, SLA et alertes
14. Pieces jointes
15. Historique et tracabilite
16. Messages d'erreur courants
17. Bonnes pratiques par profil
18. Questions frequentes
19. Glossaire
20. Resume des droits par role
21. Procedures rapides

## 2. Acces a l'application

### 2.1. Adresses principales

- Formulaire public de reclamation: `/reclamations/nouvelle`
- Connexion interne: `/login`
- Espace interne apres connexion: `/espace`
- Espace accueil: `/accueil/inbox`
- Espace chef de service: `/chef/inbox`
- Espace agent: `/agent/inbox`
- Espace chef de direction: `/chef-direction/inbox`
- Pilotage: `/pilotage`
- Tableau de bord pilotage avance: `/pilotage/dashboard`
- Administration: `/admin`

La page racine `/` redirige automatiquement vers le formulaire public de reclamation.

### 2.2. Navigateurs et affichage

L'application est accessible depuis un navigateur web moderne. Pour une utilisation confortable, il est recommande d'utiliser Chrome, Edge, Firefox ou Safari dans une version recente. Les ecrans sont prevus pour ordinateur et mobile, mais les espaces internes de traitement sont plus efficaces sur ordinateur car ils contiennent des tableaux, filtres, historiques et formulaires d'action.

### 2.3. Sessions et securite

Les utilisateurs internes doivent se connecter avec une adresse email et un mot de passe. Apres connexion, l'application ouvre l'espace correspondant aux roles de l'utilisateur.

Regles importantes:

- apres 5 tentatives de connexion echouees, le compte est temporairement bloque pendant 15 minutes;
- les comptes inactifs ne peuvent pas se connecter;
- a la premiere connexion ou apres reinitialisation, l'utilisateur peut etre oblige de changer son mot de passe;
- la deconnexion se fait depuis le bouton de sortie dans l'interface interne;
- chaque action sensible est journalisee dans l'historique.

## 3. Roles et espaces de travail

L'application fonctionne avec des roles. Un utilisateur peut avoir un ou plusieurs roles. Si plusieurs espaces sont disponibles, la page `/espace` affiche un selecteur d'espace.

### 3.1. Role Accueil

Mission principale: recevoir les nouvelles reclamations, les qualifier, les affecter au bon service ou repondre directement aux cas simples.

Actions principales:

- consulter les demandes recues;
- rechercher et filtrer les demandes;
- affecter une demande a une direction et a un service;
- annuler une affectation service lorsque c'est autorise;
- envoyer une reponse directe a l'usager pour une reclamation encore nouvelle;
- consulter les pieces jointes et l'historique.

### 3.2. Role Chef de service

Mission principale: piloter le traitement dans son service.

Actions principales:

- consulter les demandes affectees a son service;
- affecter une demande a un agent du meme service;
- annuler une affectation agent;
- repondre directement si la demande n'a pas encore ete affectee a un agent;
- suivre les alertes de delai du service;
- consulter les statistiques de son perimetre;
- acceder au pilotage si la permission est accordee.

### 3.3. Role Agent

Mission principale: traiter les demandes qui lui sont affectees et envoyer la reponse finale.

Actions principales:

- consulter uniquement les demandes qui lui sont affectees;
- rechercher dans sa liste de demandes;
- consulter le detail de la demande et ses pieces jointes;
- rediger une reponse finale;
- ajouter des pieces jointes a la reponse;
- envoyer la reponse a l'usager;
- relancer l'envoi en cas d'echec technique.

### 3.4. Role Chef de direction

Mission principale: superviser les demandes des services rattaches a sa direction.

Actions principales:

- consulter les demandes de son perimetre;
- filtrer par statut;
- rechercher par numero, objet ou usager;
- consulter les pieces jointes;
- suivre la performance des services;
- acceder au pilotage de direction.

Important: le chef de direction dispose d'un acces en consultation. La redaction ou modification directe de la reponse n'est pas autorisee dans cet espace.

### 3.5. Role CIQ

Mission principale: controler la qualite, suivre les indicateurs et exporter les donnees.

Actions principales:

- consulter les tableaux de bord globaux;
- filtrer les indicateurs;
- consulter les repartitions par direction, service, pays, etablissement et statut;
- suivre les delais et retards;
- exporter les donnees si la permission `dashboard.export` est attribuee.

### 3.6. Role DG

Mission principale: consulter les indicateurs de volume et d'activite.

Actions principales:

- acceder aux tableaux de bord;
- suivre les indicateurs globaux;
- consulter les tendances, volumes et repartitions.

Selon la configuration des permissions, l'export peut etre reserve a d'autres profils.

### 3.7. Role Administrateur

Mission principale: administrer la plateforme.

Actions principales:

- gerer les utilisateurs;
- gerer les roles et permissions;
- gerer les directions et services;
- gerer les parametres autorises;
- gerer les jours feries;
- consulter le journal des actions administratives;
- mettre a jour ou supprimer sa photo de profil.

### 3.8. Role Lecture seule

Mission principale: consulter les informations autorisees sans action de traitement.

Actions principales:

- acceder au pilotage selon le perimetre accorde;
- consulter les donnees sans les modifier.

## 4. Cycle de vie d'une reclamation

### 4.1. Statuts principaux

Une demande passe par les statuts suivants:

1. `Recu`: la demande vient d'etre deposee par l'usager.
2. `Affectee au service`: l'accueil a oriente la demande vers un service.
3. `Affectee a un agent`: le chef de service a affecte la demande a un agent traitant.
4. `Reponse redigee`: une reponse a ete preparee et l'envoi est en cours ou pret.
5. `Cloturee`: la reponse finale a ete envoyee avec succes a l'usager.

### 4.2. Parcours standard

1. L'usager depose une reclamation depuis le formulaire public.
2. L'application attribue un numero de suivi, par exemple `ANBG-2026-001`.
3. L'accueil voit la reclamation dans les nouvelles demandes.
4. L'accueil l'affecte a une direction et a un service.
5. Le chef de service consulte la demande dans son espace.
6. Le chef de service l'affecte a un agent ou la traite directement si elle n'a pas encore ete confiee a un agent.
7. L'agent redige la reponse finale et ajoute, si necessaire, des pieces jointes.
8. L'application met l'envoi de la reponse en file.
9. Quand l'email est envoye avec succes, la demande est cloturee.
10. L'historique conserve les actions realisees.

### 4.3. Parcours avec reponse directe par l'accueil

L'accueil peut cloturer directement une reclamation si:

- la demande est encore au statut `Recu`;
- il s'agit d'une reclamation;
- l'utilisateur possede la permission d'envoyer une reponse;
- la reponse contient le minimum requis.

Dans ce cas, l'accueil redige la reponse, ajoute eventuellement des pieces jointes, puis l'application met l'envoi en file. La demande n'est cloturee qu'apres confirmation technique de l'envoi de l'email.

### 4.4. Parcours avec reponse directe par le chef de service

Le chef de service peut repondre directement si:

- la demande appartient a son perimetre;
- la demande est affectee au service ou possede deja une reponse preparee;
- aucun agent traitant n'est encore affecte.

Une fois la demande affectee a un agent, le chef de service ne peut plus utiliser la reponse directe sur cette demande.

### 4.5. Envoi et cloture

L'envoi de la reponse finale se fait par email. L'application:

- verifie qu'une reponse existe;
- verifie que l'adresse email de l'usager est valide;
- empeche les doublons si un envoi est deja en cours;
- place l'envoi en file;
- cloture la demande seulement apres envoi confirme;
- journalise l'envoi dans l'historique.

Si l'envoi echoue, la demande reste non cloturee et l'interface affiche un statut d'echec. Un utilisateur habilite peut relancer l'envoi apres verification du probleme.

## 5. Depot d'une reclamation par un usager

### 5.1. Ouvrir le formulaire

L'usager ouvre `/reclamations/nouvelle`. Aucun compte n'est necessaire pour deposer une reclamation.

### 5.2. Informations personnelles

L'usager doit renseigner:

- nom;
- prenom;
- adresse email;
- statut;
- pays;
- etablissement si le statut le rend obligatoire;
- consentement au traitement des donnees.

Les statuts proposes sont:

- Eleve;
- Etudiant;
- Parent / Tuteur;
- Enseignant;
- Professionnel;
- Autre.

Pour les statuts Eleve et Etudiant, l'etablissement est requis. L'usager doit choisir un etablissement dans la liste ou choisir `Autre` si son etablissement n'est pas present.

### 5.3. Pays

Le pays est obligatoire. La liste propose notamment:

- France;
- Gabon;
- Maroc;
- Etats-Unis;
- Senegal;
- Chine;
- Federation de Russie;
- Tunisie;
- Bresil;
- Canada;
- Ghana;
- Afrique du Sud;
- Allemagne;
- Royaume-Uni;
- Turquie;
- Cameroun;
- Belgique;
- Togo;
- Cote d'Ivoire;
- Italie;
- Autre.

### 5.4. Type et categorie de demande

Le type de demande public actif est `Reclamation`.

L'usager doit choisir une categorie. Les categories principales sont:

- Demande de modification d'attestation d'attribution de bourse ou maintien;
- Reclamation du paiement des frais de scolarite;
- Reclamation sur les RIB non valides sur eBourse;
- Recours apres deliberation de la CT;
- Reclamation diverses.

L'objet de la demande est automatiquement aligne sur la categorie choisie dans le formulaire.

### 5.5. Message

Le message est obligatoire. Il doit:

- contenir au moins 10 caracteres;
- ne pas depasser 2000 caracteres;
- decrire clairement la situation;
- eviter les contenus dangereux ou non autorises.

Bonnes pratiques pour l'usager:

- indiquer le contexte exact;
- preciser les dates importantes;
- mentionner les references utiles;
- expliquer l'attente ou la correction demandee;
- joindre un justificatif lorsque c'est pertinent.

### 5.6. Piece jointe usager

L'usager peut joindre un fichier. Formats acceptes:

- PDF;
- JPG;
- JPEG;
- PNG.

La taille maximale cote formulaire public est d'environ 3,5 Mo. Les autres formats sont refuses.

### 5.7. Consentement

Le consentement est obligatoire. Sans validation de la case de consentement, la reclamation ne peut pas etre transmise.

### 5.8. Confirmation

Apres soumission valide, l'application affiche un message de confirmation avec le numero de suivi. Ce numero doit etre conserve par l'usager pour toute reference ulterieure.

## 6. Connexion et choix d'espace

### 6.1. Se connecter

1. Ouvrir `/login`.
2. Saisir l'adresse email.
3. Saisir le mot de passe.
4. Valider.

Si les identifiants sont corrects, l'utilisateur est redirige vers `/espace`.

### 6.2. Changement de mot de passe

Lorsque le changement de mot de passe est requis:

1. l'application affiche la page de changement;
2. l'utilisateur saisit l'ancien mot de passe;
3. il saisit le nouveau mot de passe;
4. il confirme le nouveau mot de passe;
5. il valide.

Le nouveau mot de passe doit contenir au moins 8 caracteres. Apres changement, l'utilisateur est redirige vers son espace de travail.

### 6.3. Selection d'espace

Si l'utilisateur possede plusieurs roles et donc plusieurs espaces, l'application affiche une page de selection. L'utilisateur choisit alors l'espace dans lequel il souhaite travailler:

- Administration;
- Accueil;
- Chef de service;
- Chef de direction;
- Agent;
- Pilotage.

## 7. Espace Accueil

### 7.1. Objectif

L'espace accueil sert a piloter l'entree des reclamations. Il permet de voir les nouvelles demandes, de suivre les demandes deja affectees et de traiter rapidement certaines reclamations simples.

### 7.2. Informations visibles

Pour chaque demande, l'accueil peut consulter:

- numero de suivi;
- objet;
- message;
- date de soumission;
- type de demande;
- statut;
- direction et service affectes si disponibles;
- nom, prenom et email de l'usager;
- statut de l'usager;
- pays;
- etablissement;
- pieces jointes;
- historique des actions;
- commentaire d'affectation service;
- statut d'envoi de la reponse si applicable.

### 7.3. Indicateurs de synthese

L'espace accueil affiche des indicateurs comme:

- total des demandes;
- total des nouvelles demandes;
- total des reclamations;
- demandes a risque;
- demandes en retard.

Ces indicateurs respectent les filtres actifs.

### 7.4. Recherche et filtres

L'accueil peut rechercher par:

- numero de suivi;
- objet;
- nom d'usager;
- prenom d'usager.

Filtres disponibles:

- type de demande;
- direction;
- date de debut;
- date de fin.

Tri disponible:

- date de soumission;
- numero de suivi;
- type de demande;
- direction;
- niveau d'alerte accueil.

Le tri peut etre ascendant ou descendant.

### 7.5. Affecter une demande a un service

1. Ouvrir la demande dans la liste des nouvelles demandes.
2. Choisir la direction.
3. Choisir le service rattache a cette direction.
4. Ajouter un commentaire si necessaire.
5. Valider l'affectation.

L'application verifie que le service choisi appartient bien a la direction selectionnee. Apres validation:

- la demande passe au statut `Affectee au service`;
- le service courant est renseigne;
- l'agent d'accueil et la date d'affectation accueil sont enregistres;
- l'action est ajoutee a l'historique.

### 7.6. Annuler une affectation service

L'accueil peut annuler une affectation service si la demande est encore au statut `Affectee au service`.

Effets de l'annulation:

- la demande revient au statut `Recu`;
- le service courant est retire;
- les affectations internes associees sont nettoyees;
- les dates d'affectation sont remises a zero;
- l'action est historisee.

Un commentaire peut etre ajoute pour expliquer l'annulation.

### 7.7. Reponse directe accueil

La reponse directe est reservee aux reclamations encore au statut `Recu`.

Procedure:

1. Ouvrir la demande.
2. Saisir une reponse d'au moins 20 caracteres.
3. Ajouter jusqu'a 5 pieces jointes si necessaire.
4. Valider l'envoi.

Contraintes:

- seuls les formats PDF, JPG, JPEG et PNG sont acceptes;
- chaque piece jointe de reponse ne doit pas depasser 4 Mo;
- l'envoi est mis en file;
- la demande est cloturee uniquement apres envoi email confirme.

## 8. Espace Chef de service

### 8.1. Objectif

L'espace chef de service sert a organiser le traitement dans le perimetre du chef. Le perimetre est determine par le service principal de l'utilisateur, ses services autorises et eventuellement ses directions autorisees.

### 8.2. Informations visibles

Le chef de service voit les demandes de son perimetre avec:

- numero de suivi;
- objet et message;
- date de soumission;
- date d'affectation accueil;
- date d'affectation agent;
- service;
- usager;
- statut;
- alertes chef et agent;
- agent affecte;
- commentaires d'affectation;
- pieces jointes;
- historique.

### 8.3. Tableaux de demandes

L'espace distingue notamment:

- demandes en attente d'agent: demandes affectees au service mais pas encore confiees a un agent;
- demandes suivies: demandes affectees a un agent ou avec reponse preparee.

### 8.4. Indicateurs de service

Le chef de service dispose d'une synthese:

- nombre total de dossiers;
- demandes sans agent;
- demandes affectees a des agents;
- reponses pretes;
- demandes ouvertes;
- demandes cloturees;
- demandes a risque;
- demandes en retard;
- total d'agents du perimetre;
- agents mobilises.

### 8.5. Affecter une demande a un agent

1. Selectionner une demande en attente.
2. Choisir un agent dans la liste des agents du service.
3. Ajouter un commentaire si necessaire.
4. Valider.

L'application verifie que l'agent appartient bien au service courant de la demande. Apres validation:

- la demande passe au statut `Affectee a un agent`;
- l'agent traitant est enregistre;
- la date d'affectation agent est enregistree;
- l'action est historisee.

### 8.6. Annuler une affectation agent

L'annulation est possible lorsque la demande est au statut `Affectee a un agent`.

Effets:

- la demande repasse au statut `Affectee au service`;
- l'agent traitant est retire;
- la date d'affectation agent est effacee;
- l'action est historisee avec le commentaire.

### 8.7. Reponse directe chef de service

Le chef de service peut repondre directement si aucun agent n'a encore ete affecte.

Procedure:

1. Ouvrir la demande.
2. Rediger une reponse d'au moins 20 caracteres.
3. Ajouter jusqu'a 5 pieces jointes si necessaire.
4. Valider l'envoi.

Apres validation:

- la reponse est creee ou remplacee si une reponse existait deja;
- l'envoi est mis en file;
- la cloture intervient apres confirmation d'envoi;
- une action `reponse_directe_chef` est historisee.

## 9. Espace Agent

### 9.1. Objectif

L'espace agent est l'espace de traitement operationnel. L'agent n'y voit que les demandes qui lui sont affectees.

### 9.2. Informations visibles

Pour chaque demande, l'agent peut consulter:

- numero de suivi;
- objet;
- message;
- date de soumission;
- date d'affectation;
- type et statut;
- service;
- identite de l'usager;
- email;
- pays;
- etablissement;
- commentaire d'affectation agent;
- pieces jointes usager;
- alerte agent;
- statut d'envoi de la reponse.

### 9.3. Recherche

L'agent peut rechercher par:

- numero de suivi;
- objet;
- nom de l'usager;
- prenom de l'usager.

### 9.4. Rediger et envoyer une reponse

1. Ouvrir la demande affectee.
2. Lire le message et les pieces jointes.
3. Rediger une reponse claire, complete et polie.
4. Verifier que la reponse contient au moins 20 caracteres.
5. Ajouter des pieces jointes si necessaire.
6. Valider l'envoi.

Regles sur les pieces jointes de reponse:

- maximum 5 fichiers;
- formats acceptes: PDF, JPG, JPEG, PNG;
- taille maximale: 4 Mo par fichier.

Apres validation:

- la reponse est enregistree;
- la demande passe en `Reponse redigee`;
- l'envoi email est mis en file;
- la demande sera cloturee apres envoi confirme.

### 9.5. Modifier une reponse avant envoi

La logique applicative remplace la reponse existante lorsque l'utilisateur redige une nouvelle reponse pour la meme demande avant cloture. Il faut toutefois eviter les validations multiples et toujours verifier le statut d'envoi affiche.

### 9.6. Relancer un envoi echoue

Si l'envoi de la reponse a echoue:

1. verifier que le probleme technique ou l'adresse email a ete corrige si necessaire;
2. utiliser l'action de relance d'envoi;
3. attendre la confirmation d'envoi.

La relance n'est disponible que si un echec d'envoi est enregistre pour la demande.

## 10. Espace Chef de direction

### 10.1. Objectif

L'espace chef de direction donne une vue de supervision sur les services de la direction.

### 10.2. Consultation des demandes

Le chef de direction peut consulter:

- demandes affectees aux services de sa direction;
- demandes affectees aux agents;
- reponses redigees;
- demandes cloturees.

Il peut filtrer par statut parmi:

- affectee au service;
- affectee a un agent;
- reponse redigee;
- cloturee.

Il peut rechercher par numero, objet, nom ou prenom de l'usager.

### 10.3. Performance des services

L'espace affiche une performance par service:

- total des demandes;
- total cloture;
- total en cours;
- total dans les delais;
- total a risque;
- total en retard;
- taux de conformite;
- delai moyen en heures.

La synthese met en avant:

- nombre de services actifs;
- service le plus charge;
- service avec le plus de retards;
- meilleur service selon le taux de conformite.

### 10.4. Limite d'action

Le chef de direction ne redige pas de reponse dans cet espace. Toute tentative de redaction est refusee avec un message indiquant que l'acces est en consultation uniquement.

## 11. Pilotage et tableaux de bord

### 11.1. Acces

Le pilotage est accessible aux utilisateurs disposant de la permission `dashboard.view`. Selon les roles:

- CIQ: vision qualite et controle interne;
- DG: vision globale;
- chef de direction: vision limitee aux services de sa direction;
- chef de service: vision limitee a son service ou perimetre;
- lecture seule: consultation selon perimetre;
- admin: acces complet si les permissions sont presentes.

### 11.2. Filtres disponibles

Les tableaux de bord peuvent etre filtres par:

- periode: tout, aujourd'hui, semaine, mois, trimestre, annee, periode personnalisee;
- date de debut;
- date de fin;
- direction;
- service;
- statut;
- etat d'application lorsque le filtre est disponible.

Par defaut, la periode est souvent le mois courant. Pour un chef de direction, la vue peut etre initialisee sur l'ensemble du perimetre.

### 11.3. Indicateurs globaux

Les tableaux de bord calculent notamment:

- total des demandes;
- demandes ouvertes;
- demandes cloturees;
- demandes dans les delais;
- demandes a risque;
- demandes en retard;
- taux de traitement dans les delais;
- delai moyen de traitement;
- repartition par statut;
- repartition par type;
- repartition par direction;
- performance par direction;
- performance par service;
- charge par agent;
- demandes par pays;
- demandes par etablissement;
- evolution temporelle des reclamations recues et cloturees.

### 11.4. Lecture des alertes

Les couleurs d'alerte indiquent le niveau de risque:

- vert: dans les delais;
- orange: a risque;
- rouge: en retard.

Les alertes sont recalculees a l'ouverture des espaces de travail et du pilotage.

### 11.5. Exports

Les exports sont reserves aux utilisateurs disposant de la permission `dashboard.export`.

Formats possibles:

- PDF;
- XLSX pour certains tableaux.

Sections exportables selon l'interface et la configuration:

- suivi CIQ des demandes;
- annexe 2;
- repartition des reclamations par direction;
- repartition des reclamations par service;
- repartition des demandes par direction;
- taux dans les delais par direction;
- taux dans les delais par service;
- pays les plus demandeurs;
- etablissements les plus demandeurs;
- evolution des reclamations recues et cloturees.

Certains exports graphiques sont uniquement disponibles en PDF. Les exports sont traces dans la table des exports avec l'utilisateur, le format, le nom du fichier, la section et les filtres appliques.

## 12. Administration

### 12.1. Tableau de bord administrateur

L'espace administration affiche:

- nombre d'utilisateurs;
- nombre de directions;
- nombre de services;
- nombre de demandes archivees ou cloturees;
- dernieres actions administratives;
- photo de profil administrateur si elle existe.

L'administrateur peut:

- ajouter une photo de profil;
- supprimer sa photo de profil.

Formats de photo acceptes:

- JPG;
- JPEG;
- PNG;
- WEBP.

Taille maximale: 2 Mo.

### 12.2. Gestion des utilisateurs

Depuis `/admin/utilisateurs`, l'administrateur habilite peut:

- consulter les utilisateurs;
- creer un utilisateur;
- modifier un utilisateur;
- activer ou desactiver un utilisateur;
- reinitialiser un mot de passe;
- supprimer un utilisateur lorsque c'est autorise;
- attribuer un ou plusieurs roles;
- definir un service principal;
- definir une direction concernee;
- definir des perimetres de directions;
- definir des perimetres de services.

Champs principaux:

- nom;
- prenom;
- email;
- mot de passe;
- service;
- direction;
- roles;
- perimetres.

Regles importantes:

- l'email doit etre unique;
- un utilisateur doit avoir au moins un role actif;
- le role accueil force le service principal sur le service `UCAS`;
- un chef de direction doit avoir une direction concernee;
- un agent, chef de service ou agent d'accueil doit avoir un service principal;
- un nouveau compte sans mot de passe saisi recoit un mot de passe genere;
- apres creation ou reinitialisation, le changement de mot de passe est requis;
- un utilisateur avec des demandes ouvertes rattachees ne peut pas etre supprime;
- les roles et perimetres sont synchronises a chaque modification.

### 12.3. Activation et desactivation

Desactiver un utilisateur empeche sa connexion sans supprimer son historique. C'est l'action recommandee lorsqu'un compte ne doit plus acceder a l'application mais que ses anciennes actions doivent rester tracables.

### 12.4. Reinitialisation de mot de passe

La reinitialisation genere un nouveau mot de passe lisible et oblige l'utilisateur a le changer a la prochaine connexion.

### 12.5. Suppression d'utilisateur

La suppression est definitive et nettoie:

- roles;
- permissions directes;
- perimetres;
- avatar.

Elle est refusee si l'utilisateur est encore rattache a des demandes ouvertes comme agent traitant, agent direction ou agent accueil.

### 12.6. Gestion des roles

Depuis `/admin/roles`, l'administrateur habilite peut:

- consulter les roles actifs;
- modifier le libelle d'un role;
- activer ou desactiver un role;
- synchroniser les permissions d'un role.

Roles installes:

- accueil;
- chef_service;
- agent;
- chef_direction;
- ciq;
- dg;
- admin;
- lecture_seule.

Permissions principales:

- `demande.view.own`: voir les demandes de son perimetre;
- `demande.view.all`: voir toutes les demandes;
- `demande.create`: creer une demande;
- `demande.assign`: affecter une demande a un service;
- `demande.assign.agent`: affecter une demande a un agent;
- `demande.reply.draft`: rediger une reponse;
- `demande.reply.send`: envoyer la reponse finale;
- `dashboard.view`: voir les tableaux de bord;
- `dashboard.export`: exporter les donnees;
- `audit.view`: voir l'historique;
- `admin.users.manage`: gerer les utilisateurs;
- `admin.parameters.manage`: gerer les parametres.

Le role admin possede toutes les permissions.

### 12.7. Gestion des directions

Depuis `/admin/directions`, l'administrateur habilite peut:

- consulter les directions;
- creer une direction;
- modifier le libelle;
- activer ou desactiver une direction.

Champs:

- code;
- libelle;
- statut actif/inactif.

Le code est normalise en majuscules lors de la creation ou mise a jour.

### 12.8. Gestion des services

Depuis `/admin/services`, l'administrateur habilite peut:

- consulter les services;
- creer un service;
- rattacher un service a une direction;
- modifier le libelle;
- modifier l'email du service;
- activer ou desactiver un service.

Champs:

- direction;
- code;
- libelle;
- email service;
- statut actif/inactif.

Le code est normalise en majuscules.

### 12.9. Gestion des parametres

Depuis `/admin/parametres`, l'administrateur habilite peut gerer les parametres autorises.

Un parametre contient:

- famille;
- code;
- libelle;
- ordre d'affichage;
- statut actif/inactif.

Certaines familles sont protegees et ne sont pas parametrables depuis l'interface:

- format_export;
- seuil_alerte;
- statut_demande;
- statut_notif;
- type_demande;
- type_notif;
- type_reponse.

Ces familles structurent le fonctionnement de l'application et ne doivent pas etre modifiees depuis l'administration standard.

### 12.10. Jours feries et SLA

L'administration des parametres affiche la configuration SLA active, les jours ouvres et les jours feries associes.

L'administrateur habilite peut:

- ajouter un jour ferie;
- modifier un jour ferie;
- supprimer un jour ferie.

Les jours feries sont pris en compte dans le calcul des heures ouvrables.

## 13. Delais, SLA et alertes

### 13.1. Configuration active par defaut

La configuration SLA active par defaut est:

- nom: `SUIVI ANBG 24h Ouvrees`;
- delai global maximum: 24 heures ouvrees;
- fuseau horaire: `Africa/Libreville`;
- jours ouvres: lundi a vendredi;
- horaires ouvres: 07:30 a 15:30.

### 13.2. Calcul en heures ouvrables

Les delais sont calcules uniquement sur les fenetres de travail actives. Les heures hors plage ouvrable, week-ends et jours feries configures ne sont pas comptees.

Exemple:

- une demande deposee apres l'heure de fermeture ne consommera pas d'heures ouvrables avant la prochaine plage ouverte;
- un jour ferie configure est ignore dans le calcul;
- la cloture utilise la date d'envoi confirme ou de cloture.

### 13.3. Seuils par etape

Seuils par defaut:

- accueil: orange a partir de 4 h, rouge apres 8 h;
- chef de service: orange a partir de 8 h, rouge apres 16 h;
- agent: orange a partir de 8 h, rouge apres 16 h;
- global: orange a partir de 12 h, rouge apres 24 h.

### 13.4. Interpretation

- Vert: la demande est dans les delais.
- Orange: la demande approche du seuil critique.
- Rouge: la demande est en retard.

Les alertes peuvent differer selon l'etape. Par exemple, une demande peut etre correcte au niveau accueil mais en retard au niveau agent si elle reste trop longtemps dans le service apres affectation.

## 14. Pieces jointes

### 14.1. Pieces jointes usager

L'usager peut transmettre une piece jointe lors du depot public.

Regles:

- formats: PDF, JPG, JPEG, PNG;
- taille maximale: environ 3,5 Mo;
- stockage prive dans l'application;
- association directe a la demande;
- source marquee comme `usager`.

### 14.2. Pieces jointes de reponse

Les utilisateurs internes qui redigent une reponse peuvent ajouter des pieces jointes.

Regles:

- maximum 5 fichiers;
- formats: PDF, JPG, JPEG, PNG;
- taille maximale: 4 Mo par fichier;
- source marquee comme `agent`;
- association directe a la reponse.

### 14.3. Consultation

Les utilisateurs internes habilites peuvent ouvrir les pieces jointes des demandes de leur perimetre. L'acces aux pieces jointes est protege par authentification et controle d'acces.

## 15. Historique et tracabilite

L'application journalise les actions importantes:

- soumission publique;
- categorie choisie par l'usager;
- affectation service;
- annulation affectation service;
- affectation agent;
- annulation affectation agent;
- reponse redigee;
- reponse directe accueil;
- reponse directe chef;
- envoi de reponse;
- echec d'envoi;
- connexions reussies ou echouees;
- verrouillage de compte;
- deconnexion;
- actions administratives;
- exports.

L'historique permet de reconstituer le parcours d'une reclamation et d'identifier les acteurs, dates, statuts et commentaires associes.

## 16. Messages d'erreur courants

### 16.1. Formulaire public

- `La categorie est requise`: choisir une categorie ou renseigner un objet valide.
- `Le message doit contenir au moins 10 caracteres`: completer la description.
- `Le message ne doit pas depasser 2000 caracteres`: raccourcir le texte.
- `Format de fichier non autorise`: utiliser PDF, JPG, JPEG ou PNG.
- `Etablissement invalide`: choisir un etablissement propose ou `Autre`.
- `Pays invalide`: choisir un pays dans la liste ou `Autre`.
- `Veuillez saisir une adresse email valide`: corriger l'adresse email.
- `Consentement requis`: cocher la case de consentement.

### 16.2. Connexion

- `Identifiants invalides`: verifier email et mot de passe.
- `Il reste X tentative(s)`: attention, le compte sera bloque apres 5 echecs.
- `Compte bloque pendant 15 minutes`: attendre la fin du blocage ou contacter un administrateur.
- `Utilisateur introuvable ou inactif`: le compte n'existe pas ou a ete desactive.

### 16.3. Affectations

- `Service invalide ou inactif`: choisir un service actif.
- `Le service selectionne n'appartient pas a la direction choisie`: corriger la direction ou le service.
- `Agent invalide pour ce service`: choisir un agent du service de la demande.
- `Annulation impossible pour ce statut`: l'action n'est plus compatible avec l'etat de la demande.

### 16.4. Reponses

- `La reponse est requise`: saisir un contenu.
- `La reponse doit contenir au moins 20 caracteres`: completer la reponse.
- `Vous ne pouvez pas joindre plus de 5 fichiers`: retirer des fichiers.
- `Chaque piece jointe ne doit pas depasser 4 Mo`: compresser ou remplacer le fichier.
- `Un envoi de reponse est deja en cours`: attendre le resultat de l'envoi.
- `La reponse finale a deja ete envoyee`: la demande est deja traitee.
- `Aucune reponse redigee pour cette demande`: rediger une reponse avant l'envoi.
- `L'adresse email de l'usager est invalide ou manquante`: corriger les donnees avant envoi.

## 17. Bonnes pratiques par profil

### 17.1. Usager

- decrire le probleme de facon precise;
- utiliser une adresse email active;
- conserver le numero de suivi;
- joindre un justificatif lisible;
- ne pas envoyer plusieurs fois la meme reclamation sauf necessite.

### 17.2. Accueil

- verifier l'objet, la categorie et le message avant affectation;
- affecter au service le plus competent;
- ajouter un commentaire d'affectation utile;
- traiter directement uniquement les cas simples et certains;
- surveiller les alertes orange et rouges.

### 17.3. Chef de service

- affecter rapidement les demandes sans agent;
- choisir l'agent selon competence et charge;
- ajouter un commentaire clair lors de l'affectation;
- annuler l'affectation agent uniquement si necessaire;
- utiliser la reponse directe avant affectation agent lorsque le cas est simple.

### 17.4. Agent

- lire toutes les informations de l'usager;
- ouvrir les pieces jointes avant de repondre;
- rediger une reponse claire, complete et exploitable;
- eviter les reponses trop courtes ou ambiguës;
- verifier les pieces jointes avant envoi;
- suivre les echecs d'envoi.

### 17.5. Chef de direction

- consulter regulierement les demandes en retard;
- identifier les services les plus charges;
- utiliser les indicateurs pour arbitrer ou relancer;
- ne pas chercher a modifier les demandes depuis l'espace de consultation.

### 17.6. CIQ et DG

- appliquer les filtres avant interpretation des chiffres;
- comparer les tendances sur des periodes homogenes;
- exporter les donnees avec les filtres appropries;
- verifier les volumes faibles avant de conclure sur un taux.

### 17.7. Administrateur

- attribuer seulement les roles necessaires;
- verifier les perimetres direction/service;
- preferer la desactivation a la suppression en cas de doute;
- garder les referentiels propres;
- maintenir les jours feries a jour;
- surveiller les journaux d'administration.

## 18. Questions frequentes

### 18.1. Pourquoi une demande n'est-elle pas cloturee juste apres validation de la reponse?

Parce que l'application attend la confirmation technique de l'envoi de l'email. Tant que l'envoi est en cours, la demande reste dans un etat intermediaire.

### 18.2. Que faire si l'envoi email echoue?

Verifier la configuration mail ou l'adresse de l'usager, puis utiliser la relance d'envoi disponible sur la demande.

### 18.3. Pourquoi un chef de service ne peut-il pas repondre directement?

La reponse directe chef est bloquee si la demande a deja ete affectee a un agent. Dans ce cas, l'agent traitant doit finaliser la reponse.

### 18.4. Pourquoi un chef de direction ne peut-il pas rediger une reponse?

Son espace est reserve a la supervision et a la consultation. La redaction reste du ressort de l'accueil, du chef de service ou de l'agent selon le stade de traitement.

### 18.5. Pourquoi un utilisateur ne voit-il aucune demande?

Causes possibles:

- il n'a pas le bon role;
- son compte n'a pas de service principal;
- son perimetre de service ou direction est vide;
- les filtres actifs excluent les demandes;
- les demandes ne sont pas dans les statuts visibles pour son espace.

### 18.6. Pourquoi une piece jointe est refusee?

Le fichier peut etre trop volumineux, avoir un format non autorise ou un type MIME non reconnu. Utiliser PDF, JPG, JPEG ou PNG.

### 18.7. Pourquoi un utilisateur doit-il changer son mot de passe?

Le changement est requis apres creation de compte ou reinitialisation par un administrateur.

## 19. Glossaire

- Accueil: equipe chargee de recevoir et orienter les reclamations.
- Agent traitant: utilisateur charge de traiter une reclamation affectee.
- Alerte: indication de respect ou risque de depassement des delais.
- CIQ: controle interne et qualite.
- Cloture: etat final apres envoi confirme de la reponse a l'usager.
- Demande: enregistrement correspondant a une reclamation deposee.
- Direction: entite organisationnelle regroupant des services.
- Perimetre: ensemble des services ou directions consultables par un utilisateur.
- Piece jointe: fichier associe a une demande ou a une reponse.
- Reponse directe: reponse envoyee sans affectation a un agent.
- SLA: delai de traitement calcule en heures ouvrables.
- Service courant: service actuellement responsable de la demande.
- Usager: personne qui depose une reclamation.

## 20. Resume des droits par role

| Fonction | Accueil | Chef service | Agent | Chef direction | CIQ | DG | Admin | Lecture seule |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Deposer une reclamation publique | Oui, sans compte | Oui, sans compte | Oui, sans compte | Oui, sans compte | Oui, sans compte | Oui, sans compte | Oui, sans compte | Oui, sans compte |
| Voir toutes les demandes | Oui | Non, perimetre | Non, affectees | Non, direction | Oui | Oui | Oui | Non, perimetre |
| Affecter a un service | Oui | Non | Non | Non | Non | Non | Oui si permission | Non |
| Affecter a un agent | Non | Oui | Non | Non | Non | Non | Oui si permission | Non |
| Rediger/envoyer une reponse | Oui, directe | Oui, directe ou selon perimetre | Oui, demandes affectees | Non | Non | Non | Oui si permission | Non |
| Consulter pilotage | Non par defaut | Oui si permission | Non par defaut | Oui | Oui | Oui | Oui | Oui |
| Exporter pilotage | Non par defaut | Selon permission | Non par defaut | Selon permission | Oui | Selon permission | Oui | Selon permission |
| Gerer utilisateurs | Non | Non | Non | Non | Non | Non | Oui | Non |
| Gerer parametres | Non | Non | Non | Non | Non | Non | Oui | Non |

Les droits exacts dependent toujours des permissions effectivement attribuees au role ou a l'utilisateur.

## 21. Procedures rapides

### 21.1. Traiter une reclamation standard de bout en bout

1. L'usager depose la reclamation sur `/reclamations/nouvelle`.
2. L'accueil verifie la demande dans `/accueil/inbox`.
3. L'accueil choisit la direction et le service, puis valide l'affectation.
4. Le chef de service ouvre `/chef/inbox`.
5. Le chef de service affecte la demande a un agent du service.
6. L'agent ouvre `/agent/inbox`.
7. L'agent lit le dossier, consulte les pieces jointes, redige la reponse et valide l'envoi.
8. L'application envoie l'email a l'usager.
9. La demande passe a `Cloturee` apres confirmation d'envoi.
10. Les responsables suivent les delais et statistiques dans `/pilotage`.

### 21.2. Traiter une reclamation simple a l'accueil

1. L'accueil ouvre une demande encore au statut `Recu`.
2. Il verifie qu'il s'agit bien d'une reclamation.
3. Il redige une reponse directe d'au moins 20 caracteres.
4. Il ajoute les pieces jointes utiles, si besoin.
5. Il valide l'envoi.
6. L'application cloture la demande apres confirmation d'envoi email.

### 21.3. Corriger une mauvaise affectation service

1. Ouvrir la demande affectee au service.
2. Utiliser l'action d'annulation d'affectation.
3. Renseigner un commentaire expliquant la correction.
4. Valider.
5. La demande revient a l'accueil au statut `Recu`.
6. Refaire l'affectation vers la bonne direction et le bon service.

### 21.4. Corriger une mauvaise affectation agent

1. Le chef de service ouvre la demande affectee a un agent.
2. Il utilise l'action d'annulation d'affectation agent.
3. Il ajoute un commentaire.
4. La demande revient au statut `Affectee au service`.
5. Il choisit le bon agent et valide une nouvelle affectation.

### 21.5. Ajouter un jour ferie

1. Ouvrir `/admin/parametres`.
2. Aller dans la section des jours feries exclus du delai.
3. Renseigner la date de debut.
4. Renseigner une date de fin si le jour ferie couvre plusieurs jours.
5. Saisir une description.
6. Valider.

La date de fin doit etre posterieure ou egale a la date de debut. Une date deja enregistree pour la configuration SLA active ne peut pas etre creee en double.

### 21.6. Creer un utilisateur interne

1. Ouvrir `/admin/utilisateurs`.
2. Renseigner nom, prenom et email.
3. Choisir un ou plusieurs roles.
4. Renseigner la direction si le role l'exige.
5. Renseigner le service principal si le role l'exige.
6. Completer les perimetres direction ou service si necessaire.
7. Saisir un mot de passe ou laisser l'application en generer un.
8. Valider.
9. Communiquer le mot de passe initial a l'utilisateur par un canal securise.
10. L'utilisateur devra changer son mot de passe a la premiere connexion.

### 21.7. Exporter un tableau de pilotage

1. Ouvrir `/pilotage`.
2. Appliquer la periode et les filtres souhaites.
3. Verifier que les chiffres affiches correspondent au perimetre attendu.
4. Choisir la section a exporter.
5. Choisir le format disponible, PDF ou XLSX selon la section.
6. Telecharger le fichier.

L'export conserve les filtres appliques dans sa trace de generation.
