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

SESSION_DRIVER=database
SESSION_TABLE=sessions
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_DOMAIN=null

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

### Erreur 419 à la connexion

Le formulaire `/login` utilise un jeton CSRF stocké dans la session. Une erreur `419 Page Expired` indique généralement que le navigateur n'a pas renvoyé le même cookie de session lors du `POST /login`.

La table `sessions` est créée dans la migration `0001_01_01_000000_create_users_table.php`. Elle n'apparaît donc pas sous la forme d'une migration séparée dans `php artisan migrate:status`.

Vérifier la configuration réellement chargée et l'existence de la table:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan config:show session
php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); var_export(Illuminate\Support\Facades\Schema::hasTable(config("session.table"))); echo PHP_EOL;'
```

Le résultat attendu en HTTPS est `driver => database`, `table_exists => true` et `secure => true`. `SESSION_DOMAIN` doit être `null` ou contenir uniquement le domaine, jamais `http://`, `https://`, un chemin ou un port.

Si HTTPS est terminé par un reverse proxy, celui-ci doit transmettre `X-Forwarded-Proto: https`. Définir également `TRUSTED_PROXIES=REMOTE_ADDR` lorsque le serveur Laravel n'est accessible qu'à travers ce proxy, ou indiquer explicitement l'adresse IP ou le réseau du proxy. Ne pas faire confiance à tous les proxies si le serveur PHP reste directement accessible depuis Internet.

Si le site est provisoirement consulté en `http://`, un cookie `Secure` ne sera pas renvoyé par le navigateur. La correction recommandée est d'activer HTTPS. Pour un diagnostic temporaire uniquement, définir `SESSION_SECURE_COOKIE=false`, exécuter `php artisan optimize:clear`, puis supprimer les anciens cookies du navigateur. Réactiver impérativement `SESSION_SECURE_COOKIE=true` dès que HTTPS fonctionne.

Si `table_exists` vaut `false` alors que la migration initiale est marquée comme exécutée, ne pas lancer `migrate:fresh` ni annuler la migration initiale en production. Créer une migration corrective avec `php artisan make:session-table`, puis exécuter `php artisan migrate --force`.

### Erreur 500 lors d'une soumission publique

Consulter d'abord l'exception réelle sur le serveur:

```bash
tail -n 150 storage/logs/laravel.log
```

Le serveur PHP doit pouvoir écrire les journaux, le cache et les pièces jointes privées. Sur un serveur Debian ou Ubuntu utilisant `www-data`:

```bash
sudo mkdir -p storage/app/private/pieces_jointes storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
php artisan optimize:clear
```

Adapter `www-data` si PHP-FPM utilise un autre utilisateur. Ne jamais appliquer `chmod -R 777`. Pour isoler le problème, envoyer une demande sans pièce jointe: si elle fonctionne, vérifier en priorité les droits de `storage/app/private` et les limites `upload_max_filesize` et `post_max_size` de PHP.

Vérifier également l'état de la base et la configuration effective:

```bash
php artisan migrate:status
php artisan config:show filesystems
php artisan config:show mail
```

Une panne SMTP est journalisée mais n'annule pas la demande. En revanche, une erreur de base de données ou l'impossibilité de stocker une pièce jointe empêche volontairement la création afin d'éviter une demande incomplète.
