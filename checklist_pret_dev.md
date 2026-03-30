# Checklist Prête Dev - Suivi des Réclamations (ANBG)

## 1. SLA totalement paramétrable
- [ ] Remplacer la logique fixe lundi-vendredi par une table de calendrier ouvré paramétrable.
- [ ] Conserver `sla_jour_ferie` et ajouter une table `sla_jour_ouvre` (jour + plage horaire) ou `sla_calendrier`.
- [ ] L'algorithme SLA doit lire uniquement la configuration active.
- [ ] Critère: un jour déclaré non ouvré est automatiquement ignoré dans le calcul.

## 2. Réouverture compatible avec le modèle de réponse
- [ ] Décider: une réponse finale unique stricte OU plusieurs réponses versionnées.
- [ ] Si réouverture autorisée: supprimer `UNIQUE(id_demande)` sur `reponses` et ajouter `is_finale` + version.
- [ ] Critère: une demande réouverte peut recevoir une nouvelle réponse sans casser l'historique.

## 3. Modularité directions/services
- [ ] Ajouter `actif`, `date_debut_validite`, `date_fin_validite` sur `directions` et `services`.
- [ ] Interdire suppression physique si référencée; préférer désactivation.
- [ ] Critère: ajout/suppression logique d'une direction/service sans perte d'historique.

## 4. Seuils d'alerte figés
- [ ] Fixer une règle unique: vert <24h, orange 24h-<48h, rouge >=48h, hors délai >72h (ou autre règle validée).
- [ ] Enregistrer la règle dans les paramètres SLA.
- [ ] Critère: une demande donnée produit la même couleur en DB et UI.

## 5. Confidentialité et périmètres fermes
- [ ] Valider noir sur blanc la vue DG (synthétique/anonymisée), CIQ (globale), lecture seule (niveau exact).
- [ ] Implémenter filtrage par permissions + périmètres service/direction dans toutes les requêtes API.
- [ ] Critère: tests d'accès refusé (403) sur données hors périmètre.

## 6. Intégrité statuts et notifications
- [ ] Remplacer les statuts texte libre par références `parametres` (ou ENUM technique maîtrisé).
- [ ] `historique_actions.ancien_statut/nouveau_statut` -> FK statut si possible.
- [ ] Critère: aucune valeur de statut invalide ne peut être insérée.

## 7. Ordre des migrations SQL
- [ ] Créer d'abord les tables mères (`utilisateurs`, `roles`, `permissions`, etc.) puis les tables de liaison.
- [ ] Ajouter tous les index métier (statut, service courant, date soumission, type).
- [ ] Critère: `migrate:fresh` passe sans erreur FK.

## 8. Sécurité applicative durcie
- [ ] Renommer `password` en `password_hash`.
- [ ] Formaliser politique mot de passe, verrouillage, durée session, rotation token.
- [ ] Ajouter rate-limit endpoints publics + journalisation des actions sensibles.
- [ ] Critère: tests sécurité de base validés (auth, brute force, autorisation).

## 9. Décisions fonctionnelles à valider immédiatement (Go/No-Go)
- [ ] Réouverture activée ?
- [ ] Couleur rouge à 48h ou 72h ?
- [ ] DG accède aux détails nominatifs ou seulement agrégats ?
- [ ] Une réponse finale unique ou réponses versionnées ?

## 10. Definition of Done avant dev sprint 1
- [ ] MLD final signé.
- [ ] Workflow statuts signé.
- [ ] Matrice habilitation signée.
- [ ] Spécification SLA testable signée.
- [ ] Jeux de tests minimaux définis.
