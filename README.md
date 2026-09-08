# reclamation

Application Laravel de gestion des réclamations et demandes d'information.

## Démarrage rapide

1. Configurer la base de données dans `.env`
2. Installer les dépendances
3. Exécuter les migrations et les seeds

```bash
composer install
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

## Accès utiles

- Formulaire usager : `/reclamations/nouvelle`
- Connexion : `/login`
- Pilotage CIQ : `/pilotage`
- Administration : `/admin`

## Notes

- Le fichier `.env` n'est pas versionné
- Les pièces jointes, avatars, logs et caches locaux sont exclus du dépôt Git

## Premier déploiement en production

Ne jamais exécuter `migrate:fresh` en production: cette commande supprime toutes les tables et leurs données.

1. Configurer la base de données et les paramètres suivants dans le `.env` du serveur:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://reclamations.exemple.ga

INITIAL_ADMIN_EMAIL=admin@anbg.ga
INITIAL_ADMIN_PASSWORD=un-mot-de-passe-initial-unique-de-12-caracteres-minimum
```

Le mot de passe doit être unique et ne doit jamais reprendre `Admin@123456` ou `ChangeMe@123`.

2. Vider un éventuel cache de configuration, puis initialiser l'application:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --force
```

En production, le seeding crée les référentiels et le premier administrateur. Il ne crée aucun compte, aucune demande et aucune trace de démonstration.

3. Se connecter sur `/login` avec l'adresse définie dans `INITIAL_ADMIN_EMAIL`. L'application impose le changement du mot de passe initial lors de la première connexion.

4. Après le premier seeding réussi, supprimer `INITIAL_ADMIN_PASSWORD` du `.env`, puis reconstruire le cache sans conserver ce secret:

```bash
php artisan config:cache
php artisan view:cache
```

Les seedings suivants conservent le mot de passe, l'état et les rôles existants de l'administrateur. Ils peuvent donc être relancés sans réinitialiser son accès.
