> **Archive du portail précédent.** À réauditer avant toute implémentation Laravel. Ce fichier ne valide aucun CAP dans `dgafrique-core`.

# CAP-049 — RELATION SATELLITE ↔ CORE

- **Domaine :** Satellites / Federation
- **Propriétaire d'exécution :** CORE + SATELLITE ; DG déclenche l'ouverture utilisateur
- **Statut :** CAP-CORE-022 implémentée ; exploitation GamaDrive à établir

## Contrat

1. DG Afrique possède une session Core de l'utilisateur final.
2. DG appelle `/produits/{produit}/ouverture`.
3. Core émet un jeton fédéré court, audience unique, usage unique.
4. DG remet ce jeton au callback serveur du satellite sans exposer le mot de passe GAMAD.
5. Le satellite ouvre sa propre session de service auprès du Core et appelle `/verification`.
6. Le satellite crée/résout son compte local puis sa session locale.

## Garde-fous

- Aucun secret de raccordement dans le navigateur ou GitHub.
- Préférer POST/échange serveur au token dans une query string.
- Le satellite conserve rôles, quotas, préférences, contenus, transactions et historique locaux.
