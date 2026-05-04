# Audit de code — `reclamation` · branche `reclamation-unique`

> **Périmètre** : Laravel 12, PHP 8.2+, spatie/laravel-permission, maatwebsite/excel, barryvdh/laravel-dompdf  
> **Date** : Mai 2026  
> **Criticité** : 🔴 Critique · 🟠 Majeure · 🟡 Mineure · 🔵 Suggestion

---

## Résumé exécutif

L'application est globalement bien architecturée : séparation claire Controllers / Services / Policies, audit-trail systématique, rate-limiting en place, CSRF activé globalement, pièces jointes validées par extension ET MIME. Plusieurs problèmes méritent néanmoins une correction rapide, dont un bug **bloquant en production** (méthode manquante) et deux risques de sécurité notables.

---

## 🔴 Critique

### C-1 — `syncRolePermissions` enregistrée mais non implémentée

**Fichier** : `routes/web.php:138` → `AdminRolesController`

```php
Route::post('/roles/{id}/permissions', [AdminRolesController::class, 'syncRolePermissions']);
```

La méthode `syncRolePermissions` n'existe pas dans `AdminRolesController`. Tout appel à cette route déclenche une **erreur fatale 500** en production. C'est la seule route de modification des permissions de rôle exposée.

**Correction** : implémenter la méthode ou supprimer la route si elle est obsolète.

```php
public function syncRolePermissions(Request $request, int $id): RedirectResponse
{
    return $this->executeAdminAction($request, function () use ($request, $id): void {
        $payload = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);
        $role = Role::findOrFail($id);
        $role->syncPermissions($payload['permissions']);
    }, 'users', 'SYNC_PERMISSIONS', "Sync des permissions du rôle ID-{$id}");
}
```

---

### C-2 — Injection SQL latente dans `OverviewController::alertCounts`

**Fichier** : `app/Http/Controllers/Api/OverviewController.php:1475`

```php
private function alertCounts(Builder $baseQuery, string $column): array
{
    ->selectRaw("SUM(CASE WHEN {$column} = 'vert' THEN 1 ELSE 0 END) as vert")
    ->selectRaw("SUM(CASE WHEN {$column} = 'orange' THEN 1 ELSE 0 END) as orange")
    ->selectRaw("SUM(CASE WHEN {$column} = 'rouge' THEN 1 ELSE 0 END) as rouge")
```

Le paramètre `$column` est interpolé directement dans du SQL brut. Actuellement, la méthode n'est appelée qu'avec des littéraux internes (`'d.alerte_accueil'`, etc.) donc le risque actuel est nul. Cependant, si un développeur future appelle cette méthode avec une entrée externe, c'est une **injection SQL directe**.

**Correction** : utiliser une allowlist ou passer le nom de colonne via les bindings.

```php
private function alertCounts(Builder $baseQuery, string $column): array
{
    $allowed = ['d.alerte_accueil', 'd.alerte_chef', 'd.alerte_agent'];
    if (!in_array($column, $allowed, true)) {
        throw new \InvalidArgumentException("Colonne non autorisée: {$column}");
    }
    // ...
```

---

## 🟠 Majeure

### M-1 — Double chemin d'authentification dans `AccessControlService::resolveActor`

**Fichier** : `app/Services/AccessControlService.php:20`

```php
$authUser = Auth::guard('web')->user();
if ($authUser instanceof Utilisateur) {
    return $authUser;
}
// Fallback sur la session brute
$sessionId = (int) $request->session()->get('agent_id', 0);
if ($sessionId > 0) {
    return Utilisateur::query()->find($sessionId);
}
```

Il existe deux chemins d'authentification : le guard Laravel officiel ET un `agent_id` stocké manuellement en session. Si jamais `Auth::guard('web')->user()` renvoie `null` alors que la session contient encore un `agent_id` valide (ex. après une régénération de session partielle ou un logout raté), un utilisateur déconnecté peut continuer à s'authentifier via le fallback session. Le logout efface bien `agent_id` mais la cohérence est fragile.

**Correction** : supprimer entièrement le fallback `agent_id` et ne se fier qu'au guard officiel, ou au minimum aligner les deux lors du logout.

---

### M-2 — `Utilisateur::$guarded = []` — Protection masse-assignment désactivée

**Fichier** : `app/Models/Utilisateur.php:18`

```php
protected $guarded = [];
```

Tous les champs du modèle sont mass-assignables sans restriction. Des champs sensibles comme `password_hash`, `actif`, `bloque_jusqua`, `changement_mdp_requis`, `admin` peuvent être assignés par erreur si un formulaire ou une API retourne une donnée non filtrée. Plusieurs appels `forceFill()` dans `AuthController` sont corrects mais le risque subsiste à l'échelle du projet.

**Correction** : définir explicitement `$fillable` avec la liste des champs légitimes, ou au minimum ajouter `$hidden` pour les champs sensibles supplémentaires.

---

### M-3 — Avatar uploadé sans restriction d'extension

**Fichier** : `app/Http/Controllers/Web/Admin/AdminDashboardController.php:71`

```php
$request->validate([
    'avatar' => ['required', 'image', 'max:2048'],
]);
```

La règle `image` de Laravel autorise JPG, PNG, GIF, BMP, SVG et WebP selon la configuration PHP. Un SVG uploadé peut contenir du JavaScript inline (XSS stocké) s'il est servi directement. L'avatar est stocké sur le disque `public` donc accessible via URL directe.

**Correction** :

```php
'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
```

---

### M-4 — Politique de mot de passe insuffisante

**Fichier** : `app/Http/Controllers/Web/PasswordController.php:34`

```php
'password' => ['required', 'string', 'min:8', 'confirmed'],
```

Seule la longueur minimale de 8 caractères est vérifiée pour les mots de passe choisis par l'utilisateur. Un mot de passe comme `aaaaaaaa` est accepté. Paradoxalement, la génération automatique (`generateReadablePassword`) produit des mots de passe avec majuscules, minuscules et chiffres.

**Correction** : appliquer la règle `Password` de Laravel 12.

```php
use Illuminate\Validation\Rules\Password;

'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
```

---

### M-5 — `test-brevo.php` commité avec une adresse email personnelle

**Fichier** : `test-brevo.php`

```php
$m->to('bbruslain@gmail.com')
```

Un fichier de test de debug est commité à la racine du projet avec une adresse email personnelle codée en dur. S'il est accessible via le web (pas de protection `.htaccess`), il peut être exécuté par n'importe qui et envoyer des emails via le serveur SMTP configuré.

**Correction** : supprimer le fichier, l'ajouter à `.gitignore`.

---

### M-6 — Pas de route de réinitialisation de mot de passe par email

**Fichier** : `routes/web.php:39` (commentaire orphelin)

```php
// Mot de passe (oubli / réinitialisation)
// ---------------------------------------------------------------
// Routes PROTÉGÉES ...
```

Le commentaire suggère qu'une route de récupération de mot de passe était prévue mais n'est pas implémentée. Un agent qui oublie son mot de passe ne peut être débloqué que par un admin. C'est une faille d'utilisabilité et un vecteur d'ingénierie sociale potentiel.

---

## 🟡 Mineure

### m-1 — Messages flash avec encodage UTF-8 corrompu

**Fichiers** :
- `AccueilInboxController.php` : `'Affectation annulÃ©e et demande retournÃ©e Ã  l accueil.'`
- `AccueilInboxController.php` : `'Le service sÃ©lectionnÃ© n appartient pas Ã  la direction choisie.'`

Des chaînes UTF-8 mal encodées se retrouvent dans des messages d'erreur (probablement copié-collé depuis un environnement Windows). Ces messages s'afficheront illisibles pour les utilisateurs.

**Correction** : remplacer par les chaînes correctement encodées en UTF-8.

---

### m-2 — Bloc de code commenté legacy dans `AccueilInboxController`

**Fichier** : `app/Http/Controllers/Web/AccueilInboxController.php` (~80 lignes commentées)

Un bloc de code mort est conservé dans un commentaire `/* Legacy direct-response block... */`. Ce code contient des références à `legacy_disabled` et des messages encodés de manière incorrecte. Il pollue la lisibilité et peut induire de la confusion.

**Correction** : supprimer, l'historique git conserve la trace.

---

### m-3 — `email` non re-validé avant envoi mail dans `buildFinalResponseMailPayload`

**Fichier** : `app/Services/DemandWorkflowService.php`

```php
$email = trim((string) ($recipient->email ?? ''));
if ($email === '') {
    return null;
}
```

L'email est vérifié non-vide mais pas revalidé comme adresse email valide. Si un email malformé a été stocké en base (import, bug de migration), le mail sera tenté avec une adresse invalide, produisant une exception non gérée dans le job.

**Correction** :

```php
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Log::warning('Email usager invalide', ['id_demande' => $demandId]);
    return null;
}
```

---

### m-4 — Conflit potentiel entre rate-limit HTTP et lockout applicatif

**Fichiers** : `routes/web.php:36`, `AuthController.php`

La route `/login` est protégée par `throttle:10,1` (10 req/min par IP) **et** par un lockout applicatif après 5 tentatives échouées (15 min). Ces deux mécanismes opèrent indépendamment : le lockout applicatif est par compte, le throttle par IP. Un attaquant avec plusieurs IPs peut bypass le throttle, et un seul IP peut lock plusieurs comptes. Les deux mécanismes sont utiles mais leur interaction n'est pas documentée.

---

### m-5 — Absence d'en-tête `Content-Security-Policy` global

Seul le téléchargement de pièce jointe définit un CSP (`"default-src 'none'; sandbox"`). Le reste de l'application n'a pas de CSP global. En cas de XSS résiduel dans une vue Blade, le navigateur n'a aucune protection supplémentaire.

**Correction** : ajouter un middleware CSP ou configurer les headers dans la réponse globale.

---

### m-6 — `annulerAffectation` dans `AccueilInboxController` utilise la clé flash `'erreur'` au lieu de `'error'`

**Fichier** : `app/Http/Controllers/Web/AccueilInboxController.php`

Certains blocs `catch` retournent `->with('erreur', ...)` alors que d'autres retournent `->with('error', ...)`. Si la vue écoute uniquement `'error'`, les messages d'erreur de l'annulation d'affectation ne s'afficheront jamais.

---

### m-7 — Aucune validation de la suppression d'un utilisateur avec des demandes en cours

**Fichier** : `AdminUsersController::deleteUser`

Un utilisateur peut être supprimé même s'il est `id_agent_traitant` sur des demandes ouvertes. Les demandes se retrouveront avec un `id_agent_traitant` pointant vers un utilisateur supprimé, cassant potentiellement les vues et les requêtes de jointure.

**Correction** : vérifier avant suppression :

```php
$hasOpenDemands = DB::table('demandes')
    ->where('id_agent_traitant', $id)
    ->whereNull('date_cloture')
    ->exists();
if ($hasOpenDemands) {
    throw ValidationException::withMessages([
        'user' => 'Impossible de supprimer : cet agent a des demandes en cours.',
    ]);
}
```

---

## 🔵 Suggestions / Qualité

### S-1 — Dupplication de logique entre routes web et API

Les routes `/accueil/demandes/{id}/affecter`, `/chef/demandes/{id}/affecter-agent` et leurs homologues API (`/api/demandes/{id}/affecter`) partagent exactement la même logique de workflow mais à travers deux controllers différents. Les deux appellent `DemandWorkflowService` correctement, mais les validations d'entrée diffèrent (ex. le contrôleur Web vérifie `id_direction` en plus, l'API ne le fait pas). Une divergence de règles entre les deux surfaces est possible à mesure que le code évolue.

---

### S-2 — Closures dans les routes pilotage devraient être des Controllers

**Fichier** : `routes/web.php:79-103`

Les routes `/pilotage`, `/pilotage/dashboard` et `/pilotage/data` contiennent des closures volumineuses directement dans le fichier de routes. Cela empêche le caching des routes (`php artisan route:cache`), ce qui dégrade légèrement les performances.

**Correction** : extraire dans un `PilotageController`.

---

### S-3 — `nextTrackingNumber` avec race condition potentielle sur SQLite

**Fichier** : `PublicDemandController::nextTrackingNumber`

Le `lockForUpdate()` est correctement désactivé pour SQLite (tests), mais la logique de lecture-puis-écriture sur `demandes_numero_compteurs` est exécutée en dehors d'une transaction imbriquée explicite avec isolation suffisante. Sur une base PostgreSQL avec charge concurrente élevée, la séquence native est correctement utilisée. Sur MySQL (production probable), le `lockForUpdate` est correct mais le double `first()` avant et après l'insert initial du compteur crée une légère redondance.

---

### S-4 — `email` dans `createUsagerSnapshot` non normalisé en minuscules

**Fichier** : `PublicDemandController.php`

```php
'email' => $email ? trim($email) : null,
```

Contrairement à `AdminUsersController` qui fait `strtolower(trim($payload['email']))`, l'email de l'usager public n'est pas passé en minuscules. Des doublons de facto peuvent se créer (`Test@example.com` vs `test@example.com`).

---

### S-5 — Tests : couverture des cas d'erreur à renforcer

Les tests Feature couvrent les flux nominaux (auth, workflow, SLA) mais aucun test visible ne couvre :
- La tentative d'appel à `syncRolePermissions` (qui crasherait)
- La suppression d'un utilisateur avec des demandes en cours
- L'upload d'un SVG en tant qu'avatar
- La concurrence sur `nextTrackingNumber`

---

## Tableau récapitulatif

| ID  | Criticité | Fichier principal                          | Résumé                                      |
|-----|-----------|--------------------------------------------|---------------------------------------------|
| C-1 | 🔴        | `AdminRolesController`                     | Méthode `syncRolePermissions` manquante     |
| C-2 | 🔴        | `OverviewController`                       | Injection SQL latente dans `alertCounts`    |
| M-1 | 🟠        | `AccessControlService`                     | Double chemin d'auth (guard + session brute)|
| M-2 | 🟠        | `Utilisateur`                              | `$guarded = []` — masse-assignment ouvert   |
| M-3 | 🟠        | `AdminDashboardController`                 | Avatar : SVG accepté, XSS potentiel        |
| M-4 | 🟠        | `PasswordController`                       | Politique de mot de passe trop faible       |
| M-5 | 🟠        | `test-brevo.php`                           | Fichier de debug commité + email personnel  |
| M-6 | 🟠        | `routes/web.php`                           | Pas de reset-password self-service          |
| m-1 | 🟡        | `AccueilInboxController`                   | UTF-8 corrompu dans messages flash          |
| m-2 | 🟡        | `AccueilInboxController`                   | Bloc de code mort commenté                  |
| m-3 | 🟡        | `DemandWorkflowService`                    | Email non revalidé avant envoi              |
| m-4 | 🟡        | `routes/web.php` + `AuthController`        | Throttle HTTP vs lockout applicatif         |
| m-5 | 🟡        | Global                                     | Pas de CSP global                           |
| m-6 | 🟡        | `AccueilInboxController`                   | Clé flash `'erreur'` vs `'error'`           |
| m-7 | 🟡        | `AdminUsersController`                     | Suppression utilisateur sans vérif demandes |
| S-1 | 🔵        | Web + API controllers                      | Duplication logique validation              |
| S-2 | 🔵        | `routes/web.php`                           | Closures bloquant `route:cache`             |
| S-3 | 🔵        | `PublicDemandController`                   | Race condition potentielle tracking number  |
| S-4 | 🔵        | `PublicDemandController`                   | Email usager non normalisé en minuscules    |
| S-5 | 🔵        | `tests/`                                   | Couverture de tests insuffisante            |

---

## Points positifs à conserver

- **CSRF** : activé globalement par Laravel, pas de bypass détecté.
- **Rate-limiting** : appliqué sur les routes sensibles (login, formulaire public).
- **Pièces jointes** : double validation extension + MIME dans `ensureAllowedAttachment`.
- **Téléchargement sécurisé** : `AttachmentController` force le MIME via allowlist, ajoute `X-Content-Type-Options` et `CSP sandbox`.
- **Lockout brute-force** : 5 tentatives max, verrouillage 15 min, audit trail complet.
- **Transactions DB** : toutes les opérations multi-tables utilisent `DB::transaction` avec `lockForUpdate`.
- **Séparation des responsabilités** : `DemandWorkflowService` centralise le workflow, les controllers restent légers.
- **Audit trail** : chaque action significative est enregistrée dans `historique_actions`.
- **Mail asynchrone** : envoi via `Queue` après `DB::afterCommit`, correctement isolé des transactions.
