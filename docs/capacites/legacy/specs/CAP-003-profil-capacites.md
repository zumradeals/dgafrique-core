> **Archive du portail précédent.** À réauditer avant toute implémentation Laravel. Ce fichier ne valide aucun CAP dans `dgafrique-core`.

# CAP-003 — PROFIL DE CAPACITÉS

- **Famille :** Fondations humaines et ZUMRA
- **Domaine :** Profil métier DG Afrique
- **Propriétaire d'exécution :** DG Afrique
- **Statut gate :** **VALIDÉ PROD**
- **CAP précédent :** CAP-002 — **VALIDÉ PROD**
- **CAP suivant :** CAP-004 — **EN SPEC**
- **Branche de réalisation :** `cap/003-profil-capacites`

## Source fonctionnelle V0.1

Le référentiel définit CAP-003 ainsi :

- **Finalité :** décrire une personne par ce qu'elle est capable de faire, ce qu'elle souhaite apprendre et ce qu'elle cherche à accomplir.
- **Capacité :** le profil devient une source structurée de capacités plutôt qu'une simple fiche biographique.
- **Points clés :** identité et localisation ; activité actuelle ; compétences existantes ; compétences recherchées ; domaines d'intérêt et intentions.
- **Garde-fou :** le profil doit rester utile à l'orientation, sans réduire la personne à un score.

## Interprétation produit

Le profil appartient au **compte DG Afrique**, pas au Programme ZUMRA.

Une personne peut créer et enrichir son profil immédiatement après avoir créé son compte DG, même si elle ne rejoint jamais ZUMRA. ZUMRA peut ensuite consommer les informations utiles de ce profil mais ne conditionne pas son existence.

L'identité canonique (nom/référence) reste fournie par GAMAD Core. DG Afrique conserve uniquement les données métier du profil, attachées à `core_identity_reference`.

## Limites de CAP-003

CAP-003 structure les grandes dimensions du profil mais ne résout pas encore en profondeur :

- CAP-004 — modèle riche de COMPÉTENCES ;
- CAP-005 — moteur APPRENTISSAGE ;
- CAP-023/024 — graphe complet de capacités et profil comme source généralisée ;
- CAP-026 — modèle riche d'INTENTION ;
- CAP-029/030/031 — découverte, matching et explicabilité.

Pour CAP-003, compétences, apprentissages, domaines et intentions restent des listes structurées simples. Leur sémantique avancée vient à leur CAP.

## Audit de l'existant

Avant CAP-003, les données pertinentes existaient dans `zumra_member_profiles`, avec localisation, téléphone, compétences, apprentissages, activité, formation, secteurs, intentions et préférences.

Problème architectural : `zumra_member_profiles.core_identity_reference` possède une FK vers `zumra_memberships.core_identity_reference`. Le profil ne pouvait donc exister qu'après création d'une adhésion ZUMRA.

De plus, `/api/zumra/me` retournait le profil uniquement après avoir trouvé une adhésion et `/api/zumra/enroll` créait adhésion + profil ensemble.

**GAP-003-A : profil DG dépendant de ZUMRA — CORRIGÉ.**

## Architecture retenue

Nouvelle table métier canonique : `public.dg_person_profiles`.

Principes :

1. clé = `core_identity_reference` fournie par GAMAD Core ;
2. aucune identité personnelle parallèle dans Supabase ;
3. aucune FK vers `zumra_memberships` ;
4. profil disponible pour tout compte DG authentifié ;
5. RLS activée, accès applicatif via API serveur ;
6. profils ZUMRA existants recopiés automatiquement à la migration ;
7. pont de compatibilité temporaire vers `zumra_member_profiles` uniquement pour les membres ZUMRA existants ;
8. existence d'un profil DG ≠ adhésion ou consentement ZUMRA ;
9. les intentions libres DG ne sont jamais remplacées par les choix contrôlés du formulaire d'adhésion ZUMRA ;
10. aucun score ou pourcentage de valeur personnelle n'est affiché.

## Données CAP-003

- nom canonique affiché depuis Core ;
- pays ;
- ville/localité ;
- activité actuelle ;
- compétences existantes (liste simple) ;
- indication « je commence sans compétence particulière » ;
- objectifs d'apprentissage (liste simple) ;
- domaines d'intérêt (liste simple) ;
- intentions / ce que la personne cherche à accomplir (liste simple) ;
- consentement à recevoir des orientations/recommandations ;
- champs historiques compatibles : téléphone, formation, mode de participation.

Aucun score global de personne ou de valeur humaine n'est calculé ni présenté.

## Parcours principal validé

1. personne connectée → Mon espace ;
2. ouvre **Mon profil** ;
3. voit son nom issu de l'identité DG/Core ;
4. renseigne situation, savoir-faire, apprentissages, domaines et intentions ;
5. enregistre ;
6. retrouve les données ;
7. les données sont visibles dans Mon espace ;
8. ZUMRA reste une action séparée.

## Critères d'acceptation

- **AC-003-01** un compte DG peut avoir un profil sans dépendance technique à une adhésion ZUMRA — validé par architecture/tests.
- **AC-003-02** profil rattaché uniquement à la référence Core de la session — validé.
- **AC-003-03** identité affichée issue du Core, pas recréée dans Supabase Auth — validé.
- **AC-003-04** localisation, activité, savoir-faire, apprentissages, domaines et intentions enregistrables et relisibles — validé.
- **AC-003-05** absence de compétence exprimable sans invalider le profil — validé.
- **AC-003-06** aucun score global de personne — validé.
- **AC-003-07** profil historique ZUMRA repris à la migration — validé.
- **AC-003-08** ZUMRA peut réutiliser les champs partagés sans posséder le profil DG ni écraser les intentions libres — validé.
- **AC-003-09** lecture/mutation exigent une session Core valide ; mutation same-origin — validé.
- **AC-003-10** parcours principal validé en production sur `dgafrique.com` — validé par l'utilisateur le 2026-08-12 : **« profil enregistré et visible »**.

## Production finale

- commit production : `59009d5450c7e00ff7cf7583d8e1e530103a6158` ;
- déploiement Vercel : `dpl_jS8o3KuFG7WyvEMHWBiz3KXYDxnu` — **READY** ;
- `npm test && next build` : **27/27 tests pass**, compilation Next.js réussie, lint/types verts, génération statique 102/102 ;
- table Supabase `public.dg_person_profiles` présente en production avec RLS, PK Core et aucune FK ZUMRA.

## Gate

**CAP-003 — PROFIL DE CAPACITÉS → VALIDÉ PROD.**

**CAP-004 — COMPÉTENCES devient le seul nouveau gate actif, EN SPEC.**
