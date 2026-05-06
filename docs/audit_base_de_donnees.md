# Audit de la base de données — Application Reclamation

> **Source** : MCD Simplifié — mise à jour 31/03/2026  
> **Date audit** : Mai 2026  
> **Criticité** : 🔴 Critique · 🟠 Majeure · 🟡 Mineure · 🔵 Suggestion

---

## Résumé exécutif

La base de données est globalement bien conçue pour une application de gestion
de demandes/réclamations. La traçabilité est complète, le modèle SLA est solide
et la séparation en 4 blocs fonctionnels est cohérente. Plusieurs points méritent
néanmoins une attention particulière avant la mise en production.

### Mise à jour du 06/05/2026

Les premiers ajustements de cohérence ont été appliqués :

- `reponses.id_demande` est désormais unique en base pour aligner le schéma avec la logique applicative de réponse unique.
- `notifications.id_reponse` a été ajouté en nullable pour relier une notification à la réponse envoyée.
- `config_sla.id_direction` et `config_sla.id_service` ont été ajoutés en nullable pour permettre des SLA par direction ou service.
- Un test automatisé vérifie la cohérence entre `utilisateur_role` et `model_has_roles`.

---

## 🟠 Majeur

### M-1 — Table `Parametre` trop centrale

**Entités concernées** : `Demande`, `Reponse`, `Notification`, `Export`, `Historique action`

La table `Parametre` est utilisée comme référentiel unique pour des familles
très différentes : types de demande, statuts, types de réponse, types de
notification, formats d'export.

**Risque** : la suppression ou la modification accidentelle d'un paramètre
utilisé en production casse plusieurs entités en cascade. Sans contrainte
`ON DELETE RESTRICT`, rien n'empêche cette opération.

**Vérification recommandée** : s'assurer que toutes les clés étrangères
pointant vers `Parametre` ont bien `ON DELETE RESTRICT` ou `ON DELETE NO ACTION`.

```sql
-- Exemple de contrainte à vérifier sur chaque FK vers parametres
ALTER TABLE demandes
  ADD CONSTRAINT fk_demandes_id_statut
  FOREIGN KEY (id_statut) REFERENCES parametres(id_parametre)
  ON DELETE RESTRICT;
```

---

### M-2 — Double pivot pour les rôles — risque de désynchronisation

**Tables concernées** : `model_has_roles` (Spatie) et `utilisateur_role` (legacy)

Deux tables stockent la même information : les rôles attribués à un utilisateur.
Toute opération qui passe par l'une sans mettre à jour l'autre crée une
incohérence silencieuse. Actuellement, `RoleSyncService` maintient les deux en
parallèle, mais ce n'est pas garanti dans tous les chemins de code.

**Risque** : un utilisateur peut avoir un rôle dans Spatie mais pas dans la table
legacy, ou inversement. Les permissions applicatives (Spatie) et les requêtes
métier (legacy) peuvent alors donner des résultats contradictoires.

**Recommandation à court terme** : ajouter un test automatisé qui vérifie
la cohérence entre les deux tables.

**Recommandation à long terme** : migrer entièrement vers Spatie et supprimer
`utilisateur_role`.

---

### M-3 — Table `Demande` surchargée

**Table concernée** : `demandes`

La table `demandes` cumule :
- les références aux acteurs (`id_agent_accueil`, `id_agent_direction`, `id_agent_traitant`)
- toutes les dates d'étapes (`date_affectation`, `date_affectation_accueil`, `date_affectation_agent`, `date_reponse_direction`, `date_envoi_usager`, `date_cloture`)
- les indicateurs SLA (`heures_ouvrees_cloture`, alertes)
- le statut courant

Cela représente potentiellement **15+ colonnes nullables** sur une seule table.
Chaque nouvelle étape métier impliquera une migration ALTER TABLE sur cette table
centrale, avec risque de lock en production.

**Recommandation** : extraire les données de suivi dans une table séparée
`demande_suivi` ou `demande_etat`, et ne garder dans `demandes` que les données
immuables (usager, objet, date de soumission, numéro de suivi).

---

### M-4 — Divergence entre le modèle de données et la logique applicative sur `Reponse`

**Tables concernées** : `reponses`, `reponse_piece_jointe`

Le MCD modélise `Demande (1,1) -> Reponse (0,N)` — une demande peut avoir
plusieurs réponses. Mais dans `DemandWorkflowService`, la logique remplace
systématiquement la réponse existante :

```php
// Réponse unique : on remplace si elle existe déjà
if ($existingResponseId) {
    DB::table('reponses')->where('id_reponse', $existingResponseId)->update([...]);
}
```

**Risque** : la base permet N réponses mais le code n'en exploite qu'une.
Si un bug ou une opération externe insère une deuxième réponse, le code
récupérera toujours la dernière par `numero_version` sans que rien ne signale
l'anomalie.

**Décision appliquée** : la réalité applicative actuelle est la réponse unique.
Une migration ajoute donc une contrainte `UNIQUE (id_demande)` sur `reponses`.
Le champ `numero_version` reste présent pour compatibilité, mais il ne doit plus
être interprété comme une gestion de plusieurs réponses actives par demande.

---

## 🟡 Mineur

### m-1 — Redondance entre `Affectation` et les colonnes de `Demande`

**Tables concernées** : `affectations`, `demandes`

La table `affectations` trace l'historique complet des affectations. En parallèle,
`demandes` contient des colonnes `id_service_courant`, `date_affectation`,
`id_agent_accueil` qui dupliquent l'état courant. Les deux sont mis à jour
simultanément dans `assignDemand`.

C'est un choix de dénormalisation acceptable pour les performances de lecture,
mais il doit être documenté et maintenu rigoureusement. Si les deux tables
divergent, la source de vérité n'est pas claire.

**Recommandation** : documenter explicitement que `demandes` contient l'état
courant (lecture rapide) et `affectations` contient l'historique (audit).

---

### m-2 — Contrainte d'unicité sur `numero_suivi` confirmée

**Table concernée** : `demandes`

Le numéro de suivi est l'identifiant visible par l'usager. La contrainte
`UNIQUE` est bien présente en base sur `demandes.numero_suivi`, et la génération
utilise aussi `demandes_numero_compteurs` pour limiter les collisions.

**Statut** : point surveillé, mais pas bloquant.

---

### m-3 — `Notification` liée uniquement à `Demande` et non à `Reponse`

**Table concernée** : `notifications`

D'après le MCD, `Notification` est reliée à `Demande` mais pas directement
à `Reponse`. Si une notification est envoyée suite à l'envoi d'une réponse,
le lien entre la notification et la réponse concernée n'est pas traçable
directement.

**Décision appliquée** : une FK optionnelle `id_reponse` a été ajoutée dans
`notifications` pour les notifications liées à une réponse spécifique.

---

### m-4 — `Export` tracé avec filtres à vérifier

**Table concernée** : `exports`

La table `exports` trace qui a exporté quoi et quand. La colonne JSON
`filtres_appliques` existe déjà pour stocker les critères d'export.

**Recommandation** : vérifier que chaque export renseigne systématiquement
`filtres_appliques` avec la période, le type d'export et les filtres utiles.

---

### m-5 — Pas de soft delete visible sur les entités sensibles

**Tables concernées** : `utilisateurs`, `services`, `directions`

Si ces entités n'ont pas de colonne `deleted_at` (soft delete), la suppression
d'un utilisateur, service ou direction supprime la ligne et casse les jointures
sur les demandes historiques qui y font référence.

Point déjà signalé dans l'audit code (C-1 : suppression d'utilisateur sans
vérification des demandes en cours).

**Recommandation** : vérifier la présence de `deleted_at` sur ces tables et
activer `SoftDeletes` dans les modèles Eloquent correspondants.

---

## 🔵 Suggestions

### S-1 — Nommage des tables pivots à harmoniser

Les tables pivots mélangent deux conventions :
- **Spatie** : `model_has_roles`, `model_has_permissions` (préfixe `model_has_`)
- **Legacy** : `utilisateur_role`, `permission_role` (format `entite_entite`)
- **Métier** : `demande_piece_jointe`, `reponse_piece_jointe`, `perimetre_direction`

Ce n'est pas bloquant mais complexifie la lecture du schéma pour un nouveau
développeur.

---

### S-2 — `Config SLA` sans lien direct vers `Direction` ou `Service`

D'après le MCD, `Config SLA` est liée directement à `Demande`. Cela signifie
que la config SLA est déterminée demande par demande. Si demain on veut appliquer
une config SLA différente par service ou par direction, le modèle actuel
nécessite une migration.

**Décision appliquée** : les FK optionnelles `id_service` et `id_direction`
ont été ajoutées sur `config_sla`.

---

### S-3 — Pas de table `template_notification`

Les notifications semblent générées à la volée sans template stocké en base.
Si les contenus des notifications évoluent, cela nécessite un redéploiement
applicatif plutôt qu'une simple mise à jour en base.

---

## Tableau récapitulatif

| ID  | Criticité | Tables concernées                    | Résumé                                          |
|-----|-----------|--------------------------------------|-------------------------------------------------|
| M-1 | 🟠        | `parametres` + toutes FK             | Table trop centrale, risque de casse en cascade |
| M-2 | 🟠        | `model_has_roles` + `utilisateur_role` | Double pivot rôles, désynchronisation possible |
| M-3 | 🟠        | `demandes`                           | Table surchargée, difficile à faire évoluer     |
| M-4 | 🟠        | `reponses`                           | Divergence modèle N vs logique unique           |
| m-1 | 🟡        | `affectations` + `demandes`          | Redondance assumée mais non documentée          |
| m-2 | 🟡        | `demandes`                           | Contrainte UNIQUE sur `numero_suivi` confirmée  |
| m-3 | 🟡        | `notifications`                      | Lien optionnel vers `reponses` ajouté           |
| m-4 | 🟡        | `exports`                            | Critères d'export à vérifier côté écriture      |
| m-5 | 🟡        | `utilisateurs`, `services`, `directions` | Soft delete à vérifier                      |
| S-1 | 🔵        | Tables pivots                        | Nommage hétérogène                              |
| S-2 | 🔵        | `config_sla`                         | Portée Service/Direction ajoutée en optionnel   |
| S-3 | 🔵        | —                                    | Pas de templates de notification en base        |

---

## Points positifs à conserver

- **Traçabilité complète** : `historique_actions` à chaque étape du workflow.
- **SLA bien modélisé** : `config_sla` + `sla_jour_ouvre` + `sla_jour_ferie`
  permettent un calcul précis en heures ouvrées avec exclusion des jours fériés.
- **Périmètres flexibles** : `perimetre_direction` et `perimetre_service`
  permettent à un utilisateur d'avoir des droits transversaux sans changer
  son rattachement principal.
- **Séparation claire** des 4 blocs fonctionnels : Organisation, Métier,
  Suivi/Audit, Délais.
- **Pièces jointes** modélisées en table pivot sur `Demande` ET `Reponse` —
  flexibilité maximale.
