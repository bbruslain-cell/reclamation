# Rapport de Sécurité — Suivi-reclamation

> Analyse des principes de sécurité fondamentaux : Confidentialité, Intégrité, Disponibilité, Authentification, Non-répudiation.

---

## 1. Authentification

**Niveau : Bon**

### Ce qui est en place

| Mécanisme | Détail | Fichier |
|---|---|---|
| Verrouillage de compte | 5 tentatives échouées → blocage 15 minutes (`bloque_jusqua`) | `app/Http/Controllers/Web/AuthController.php` |
| Compteur de tentatives | Persisté en base (`tentatives_echouees`) | `app/Http/Controllers/Web/AuthController.php` |
| Protection session fixation | `$request->session()->regenerate()` après login | `app/Http/Controllers/Web/AuthController.php` |
| Hashage Bcrypt | `Hash::check()` / `Hash::make()` — mot de passe jamais stocké en clair | `app/Http/Controllers/Web/AuthController.php` |
| Changement de mot de passe forcé | Redirection obligatoire au premier login (`changement_mdp_requis`) | `app/Http/Middleware/EnsureAgentAuthenticated.php` |
| Rate limiting login | 10 requêtes/minute (`throttle:10,1`) | `routes/web.php` |
| Déconnexion complète | Session invalidée + token CSRF régénéré | `app/Http/Controllers/Web/AuthController.php` |
| Vérification compte actif | Utilisateur inactif rejeté même avec bon mot de passe | `app/Services/AccessControlService.php` |

### Faiblesses identifiées

- Pas de 2FA (authentification à deux facteurs)
- Aucun mot de passe admin par défaut : le compte initial n'est créé que si `INITIAL_ADMIN_PASSWORD` est renseigné avec un secret unique.

---

## 2. Confidentialité

**Niveau : Bon**

### Données au repos

| Mécanisme | Détail | Fichier |
|---|---|---|
| Fichiers joints privés | Stockés sur le disque `local` (hors dossier public), non accessibles via URL directe | `config/filesystems.php` |
| Téléchargement contrôlé | Accès aux pièces jointes uniquement via contrôleur avec vérification de droits | `app/Http/Controllers/Web/AttachmentController.php` |

### Données en transit

| En-tête HTTP | Valeur | Protection |
|---|---|---|
| `Content-Security-Policy` | `default-src 'self'`, `object-src 'none'`, etc. | Bloque les ressources externes non autorisées |
| `X-Frame-Options` | `DENY` | Protège contre le clickjacking |
| `X-Content-Type-Options` | `nosniff` | Empêche le MIME sniffing |
| `Permissions-Policy` | caméra, micro, géolocalisation désactivés | Restreint les APIs du navigateur |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Limite l'exposition des URLs |
| `X-Powered-By` | Supprimé | Cache la stack technique aux attaquants |

Source : `app/Http/Middleware/SecurityHeaders.php`

### Contrôle d'accès aux données

Le service `AccessControlService` vérifie l'accès à chaque demande à **3 niveaux** :

1. **Rôle** : permission globale `demande.view.all`
2. **Service** : l'utilisateur appartient au service traitant la demande
3. **Direction** : l'utilisateur a un périmètre direction couvrant le service

Source : `app/Services/AccessControlService.php` — méthode `canAccessDemand()`

### Faiblesses identifiées

- `script-src 'unsafe-inline' 'unsafe-eval'` présent dans le CSP — affaiblit la protection XSS (nécessaire pour Vue.js inline, mais à améliorer avec des nonces)

---

## 3. Intégrité

**Niveau : Bon**

### Validation des entrées

| Règle | Détail | Fichier |
|---|---|---|
| Regex sur noms/textes | Bloque caractères spéciaux non autorisés | `app/Http/Controllers/Web/PublicDemandController.php` |
| Anti-XSS | Détecte `<>`, `javascript:`, `onerror=`, etc. | `PublicDemandController::safePublicTextRule()` |
| Anti-SQLi | Détecte `UNION SELECT`, `DROP TABLE`, `--`, etc. | `PublicDemandController::safePublicTextRule()` |
| Validation fichiers | Extension ET type MIME vérifiés (PDF, JPG, PNG — max 3.5 Mo) | `PublicDemandController::ensureAllowedAttachment()` |
| CSRF | Token sur tous les formulaires POST (Laravel natif) | Global |

### Intégrité des données en base

| Mécanisme | Détail |
|---|---|
| Clés étrangères | `cascadeOnDelete` / `restrictOnDelete` selon les entités |
| Unicité `numero_suivi` | Index d'unicité en base pour éviter les doublons |
| Transactions DB | Opérations critiques enveloppées dans `DB::transaction()` |
| Versionnage des réponses | Chaque réponse a un `numero_version` pour l'historique |

### Faiblesses identifiées

- Pas de signature ou hash sur les fichiers stockés — une modification physique d'un fichier après stockage ne serait pas détectée

---

## 4. Disponibilité

**Niveau : Moyen**

### Protections en place

| Mécanisme | Détail | Source |
|---|---|---|
| Rate limiting formulaire public | 20 requêtes/minute | `routes/web.php` |
| Rate limiting API | 60 requêtes/minute | `routes/api.php` |
| Rate limiting login | 10 requêtes/minute | `routes/web.php` |
| Envois email en queue | Pannes SMTP n'impactent pas les réponses HTTP | `app/Jobs/SendDemandResponseJob.php` |
| Suivi des échecs d'envoi | `failed_response_delivery_at` + `message_erreur` enregistrés | Table `notifications` |

### Faiblesses identifiées

- **SQLite en production** : ne supporte pas les connexions concurrentes élevées — migrer vers MySQL/PostgreSQL pour la production
- **Pas de Redis** : queue, cache et session sur la base de données — goulot d'étranglement sous charge
- **Pas de retry automatique** configuré pour les jobs d'envoi email échoués
- **Pas de health check endpoint** documenté pour la supervision

---

## 5. Non-répudiation

**Niveau : Excellent**

C'est le point le plus robuste de l'application. Toutes les actions significatives sont tracées dans la table `historique_actions`.

### Actions auditées

| Type d'action | Déclencheur |
|---|---|
| `AUTH_LOGIN_SUCCESS` | Connexion réussie |
| `AUTH_LOGIN_FAILED` | Mauvais identifiants |
| `AUTH_LOGIN_BLOCKED` | Tentative pendant verrouillage |
| `AUTH_LOGIN_LOCKOUT` | Compte verrouillé après trop de tentatives |
| `AUTH_LOGOUT` | Déconnexion |
| `soumission_usager` | Soumission du formulaire public |
| `categorie_usager` | Catégorie choisie par l'usager |
| `affectation_accueil` | Affectation d'une demande à un service |
| `affectation_agent` | Affectation d'une demande à un agent |
| `changement_statut` | Tout changement de statut de demande |
| `reponse_redigee` | Rédaction d'une réponse |
| `reponse_envoyee` | Envoi de la réponse à l'usager |

### Structure d'un enregistrement d'audit

```
historique_actions
├── id_utilisateur       → qui a agi
├── id_demande           → sur quelle demande
├── type_action          → quelle action
├── ancien_statut_id     → état avant
├── nouveau_statut_id    → état après
├── id_service_associe   → quel service
├── date_action          → quand
└── commentaire          → détail libre
```

### Traçabilité des emails

La table `notifications` trace pour chaque email : expéditeur, destinataire, type, statut d'envoi, horodatage et message d'erreur éventuel.

---

## Résumé synthétique

| Principe | Niveau | Points forts | Points faibles |
|---|---|---|---|
| **Authentification** | ✅ Bon | Lockout, Bcrypt, anti-session fixation | Pas de 2FA |
| **Confidentialité** | ✅ Bon | Headers CSP, fichiers privés, périmètre données | CSP `unsafe-eval` |
| **Intégrité** | ✅ Bon | CSRF, validation stricte, transactions DB | Pas de hash sur fichiers |
| **Disponibilité** | ⚠️ Moyen | Rate limiting, queue async emails | SQLite, pas de Redis |
| **Non-répudiation** | ✅ Excellent | Audit trail complet sur toutes les actions | — |

---

## Recommandations prioritaires

1. **Définir `INITIAL_ADMIN_PASSWORD` avec un secret unique** lors du provisionnement initial, puis le retirer/faire tourner après première connexion
2. **Migrer vers MySQL/PostgreSQL** pour la base de données de production
3. **Activer Redis** pour la queue, le cache et les sessions sous charge
4. **Remplacer `unsafe-inline`/`unsafe-eval`** dans le CSP par des nonces CSP (nécessite adaptation du build Vite)
5. **Ajouter un 2FA** (TOTP via Google Authenticator par exemple) pour les comptes admin
6. **Configurer le retry** des jobs d'envoi email échoués (`$tries`, `$backoff` dans le Job)

---

*Rapport généré le 28 mai 2026 — Application : Suivi-reclamation (Laravel 12 / PHP 8.2)*
