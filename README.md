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
