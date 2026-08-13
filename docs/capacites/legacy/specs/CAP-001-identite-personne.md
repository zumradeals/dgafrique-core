> **Archive du portail précédent.** À réauditer avant toute implémentation Laravel. Ce fichier ne valide aucun CAP dans `dgafrique-core`.

# CAP-001 — IDENTITÉ PERSONNE

- **Famille :** Fondations humaines et ZUMRA
- **Domaine :** Identity
- **Propriétaire d'exécution :** HYBRID — GAMAD Core fournit l'identité canonique ; DG Afrique la consomme et la relie à ses expériences métier
- **Statut gate :** **VALIDÉ PROD**
- **Validation production :** **OUI — 2026-08-10**
- **CAP suivant autorisé :** **OUI — CAP-002 EN SPEC**
- **Dossier de preuve final :** `../proofs/CAP-001-2026-08-10.md`

## Source fonctionnelle V0.1

Le référentiel `DG_Afrique_Referentiel_Final_84_Capacites_V0.1` définit CAP-001 ainsi :

- **Finalité :** permettre à une personne d'exister de manière persistante dans l'écosystème numérique.
- **Capacité :** DG Afrique peut créer et utiliser une identité personnelle canonique fournie par GAMAD Core ; cette identité devient le point d'attache stable des autres capacités.
- **Points clés :** relier le compte DG Afrique, le profil, les compétences, les apprentissages, ZUMRA, les projets, les services, les opportunités et les futures applications ; éviter de recréer une identité différente pour chaque service.
- **Garde-fou :** une identité Core peut servir plusieurs expériences sans confondre leurs données métier.

## Limite de CAP-001

CAP-001 porte sur **l'identité canonique persistante de la personne**. Il ne valide pas le parcours complet du compte DG Afrique (CAP-002), le profil (CAP-003), les satellites (CAP-018/CAP-051), ni le consentement ou la séparation détaillée des contextes (CAP-052/CAP-053).

## Invariants validés

1. Une personne possède une identité canonique stable fournie par GAMAD Core.
2. DG Afrique ne crée pas une seconde autorité d'identité membre parallèle dans Supabase.
3. Les données métier DG Afrique peuvent référencer l'identité canonique sans devenir elles-mêmes l'identité.
4. Des expériences métier différentes peuvent utiliser la même identité sans fusionner automatiquement leurs données.
5. Une session DG Afrique résout la même entité canonique auprès du Core pendant sa validité.
6. Une indisponibilité transitoire du Core n'est pas interprétée comme la disparition de l'identité.
7. Un vrai refus Core `401` invalide la session portail.
8. Secrets, mots de passe et jetons ne sont jamais des identifiants métier ni des éléments affichables.
9. Le Supabase Auth historique du CMS `/admin` reste un credential du plan de contrôle et ne devient pas l'identité canonique d'un membre.

## Contrat Core audité

`CAP-CORE-001 / CTR-01` conserve l'identité minimale et canonique, son état, sa source, son assurance et son historique ; les produits conservent leurs données métier. DG Afrique consomme `GET /identites/{reference}` sous session Core.

Références auditées :

- `zumradeals/gamad-core:core/registre-identites/src/Ctr01.php`
- `zumradeals/gamad-core:apps/console-laravel/app/Http/Controllers/Ctr01Controller.php`

## Comportement DG Afrique validé

- session portail signée : `token`, `entity`, `assurance`, `expiresAt` ;
- cookie falsifié, malformé ou expiré rejeté ;
- identité canonique lue via Core `GET /identites/{entity}` ;
- `/api/genesis/account/me` distingue session invalide et panne transitoire ;
- Core `401` → cookie effacé ;
- erreur Core non-401 → session conservée, 503 réessayable ;
- `src/lib/gamad-core/portal-session.ts` et `identity-state.ts` sont testés automatiquement.

## Point d'attache métier

Les tables métier ZUMRA existantes observées utilisent `core_identity_reference` comme point d'attache. `/api/zumra/me` requête avec `session.entity`. Le client Supabase métier est serveur/service-role sans session utilisateur Supabase persistée.

Cette observation valide uniquement l'invariant d'identité CAP-001 ; elle ne valide aucun CAP ZUMRA futur.

## États utiles

- `IDENTITE_RESOLUE`
- `SESSION_ABSENTE`
- `SESSION_INVALIDE`
- `IDENTITE_TEMPORAIREMENT_INDISPONIBLE`

## Permissions et confidentialité

L'identité canonique n'autorise pas à elle seule l'accès à toutes les données métier. Les contextes conservent leurs propres permissions. Les tokens ne sont jamais affichés. La session maître DG reste bornée au portail.

## Critères d'acceptation

- **AC-001 — Stabilité canonique : PASS**
- **AC-002 — Point d'attache métier : PASS**
- **AC-003 — Session invalide : PASS**
- **AC-004 — Panne transitoire : PASS**
- **AC-005 — Intégrité du cookie : PASS**
- **AC-006 — Absence de seconde autorité membre : PASS**

## Preuves de validation production

- [x] Contrat Core audité.
- [x] Points d'attache DG Afrique/Supabase audités.
- [x] AC-001 confirmé après déploiement final par parcours membre réel.
- [x] AC-002 vérifié structurellement.
- [x] AC-003 couvert par test automatisé, build vert et déploiement production.
- [x] AC-004 couvert par test automatisé, build vert et déploiement production.
- [x] AC-005 couvert par tests automatisés, build vert et déploiement production.
- [x] AC-006 confirmé par audit code/configuration.
- [x] Aucun secret/token ajouté aux tests ou preuves.
- [x] **8/8 tests pass, 0 fail** dans le build final.
- [x] Preview final `dpl_5n97En7V4aDEeSTKgBdwzc6YPc56` READY.
- [x] Production `dpl_7pSY1rsS2x8EbhcNym1He1nHCZYx` READY.
- [x] Aucun runtime error trouvé sur `/espace` et `/api/genesis/account/me` dans la fenêtre de contrôle post-déploiement.
- [x] Validation utilisateur finale : **« Mon espace OK »**, 2026-08-10 11:58 UTC.
- [x] Historique, tracker et handoff finalisés.

## Rollback

En cas de régression CAP-001, revertir les commits CAP-001 ou revenir à l'état applicatif antérieur `75c722d735090484e1cdbea5f278cfa5984b21c3`. Aucun rollback de données Core/Supabase n'est requis.

## Décision

**CAP-001 est VALIDÉ PROD.** Toute modification future de ses invariants doit être traitée comme régression ou réouverture explicite du gate.

**CAP-002 — COMPTE DG AFRIQUE est désormais le seul CAP autorisé à progresser.**
