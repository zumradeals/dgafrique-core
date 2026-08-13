> **Archive du portail précédent.** À réauditer avant toute implémentation Laravel. Ce fichier ne valide aucun CAP dans `dgafrique-core`.

# CAP-002 — COMPTE DG AFRIQUE

- **Famille :** Fondations humaines et ZUMRA
- **Domaine :** Identity
- **Propriétaire d'exécution :** HYBRID — DG Afrique orchestre l'expérience compte ; GAMAD Core reste l'autorité d'identité, de vérification et d'authentification
- **Statut gate :** **VALIDÉ PROD**
- **Validation production :** OUI — 2026-08-11
- **CAP précédent :** CAP-001 — **VALIDÉ PROD**
- **CAP suivant autorisé :** CAP-003 — **EN SPEC**
- **Production DG validée :** branche `cursor`, commit `da39eb0dc7a2916c464e3e652591426fd7182535`
- **Production Core utilisée :** `gamad-core/main` commit `67b2ca74c7b4e9e510d1bb4a0fa5f094e56d952b`

## Source fonctionnelle V0.1

Le référentiel `DG_Afrique_Referentiel_Final_84_Capacites_V0.1` définit CAP-002 ainsi :

- **Finalité :** donner à une personne un accès personnel à l'application DG Afrique.
- **Capacité :** une personne peut créer gratuitement un compte DG Afrique, se connecter, vérifier son accès et entrer dans Mon espace.
- **Points clés :** le compte DG Afrique reste distinct de l'adhésion au Programme ZUMRA ; l'utilisateur peut entrer dans l'écosystème avant d'avoir choisi un parcours précis.
- **Garde-fou / résultat :** le compte est une porte d'accès, pas une adhésion automatique à tous les programmes.

## Limite de CAP-002

CAP-002 couvre uniquement la porte d'accès personnelle DG Afrique : création, vérification, connexion, session portail, retour vers la destination utile et déconnexion.

Il ne valide pas CAP-003 profil, CAP-004 compétences, CAP-007 ZUMRA, CAP-018 lanceur satellite, CAP-051 portabilité d'identité ni les autres CAP futurs. Leur code préexistant peut être traversé pendant un test sans être validé par ricochet.

## Invariants validés

1. Le compte DG Afrique est gratuit.
2. Compte DG Afrique ≠ adhésion ZUMRA.
3. GAMAD Core reste l'autorité d'identité, de vérification et d'authentification.
4. DG Afrique ne persiste jamais le mot de passe ni le code de vérification.
5. EMAIL doit être vérifié avant authentification normale.
6. Une session réellement invalide revient à la connexion ; une panne transitoire ne détruit pas une session encore valide.
7. `next` n'accepte qu'un chemin local sûr.
8. Une vérification interrompue possède un dossier local borné sans mot de passe/code.
9. La déconnexion ferme Core et efface le cookie portail.
10. DG ne calcule jamais lui-même une prolongation de session : le cookie n'est renouvelé que jusqu'à une échéance attestée par Core.
11. Une ancienne intention satellite ne peut pas contaminer une nouvelle identité ou une nouvelle session.
12. GamaDrive n'est ouvert qu'après intention explicite de l'utilisateur ; son lien n'est pas préchargé automatiquement depuis Mon espace.

## Contrat Core session courante

Le contrat minimal nécessaire est désormais fusionné et déployé côté Core :

```http
GET /api/v1/sessions/current
Authorization: Bearer <jeton déjà ouvert>
```

Réponse : `entite`, `assurance`, `expire_le`, avec `Cache-Control: no-store`. Un 401 signale une session absente, invalide, expirée ou révoquée.

DG utilise cette attestation pour renouveler son enveloppe de session sans inventer de durée locale.

## Corrections livrées

- renvoi aligné sur `{identifiant_reference,destination}` ;
- retour `?next=` local sûr ;
- reprise de vérification bornée ;
- conservation des références après `VERIFICATION_NON_LIVREE` ;
- mapping explicite de la limitation 429 ;
- lecture de `/sessions/current` et renouvellement du cookie à l'échéance Core ;
- effacement du vieux cookie de retour satellite après création de compte et déconnexion ;
- désactivation du prefetch du lien GamaDrive depuis Mon espace.

## Critères d'acceptation

- **AC-002-01** création gratuite sans adhésion ZUMRA automatique — **VALIDÉ**.
- **AC-002-02** création + email de vérification + vérification réelle — **VALIDÉ PROD**.
- **AC-002-03** contrat de renvoi conforme au Core — **VALIDÉ AUTOMATISÉ** ; re-test manuel volontairement non bloquant.
- **AC-002-04** dossier de reprise sans mot de passe/code — **VALIDÉ AUTOMATISÉ** ; re-test manuel volontairement non bloquant.
- **AC-002-05** `VERIFICATION_NON_LIVREE` reprend le même compte — **VALIDÉ AUTOMATISÉ**.
- **AC-002-06** connexion Core + cookie sécurisé + Mon espace — **VALIDÉ PROD**.
- **AC-002-07** retour `next` local sûr — **VALIDÉ PROD** avec `/formations`.
- **AC-002-08** utilisateur connecté ne repasse pas par le formulaire — **VALIDÉ PROD**.
- **AC-002-09** distinction 401 / panne transitoire — **VALIDÉ AUTOMATISÉ**.
- **AC-002-10** déconnexion centrale — **VALIDÉ PROD**.
- **AC-002-11** renouvellement limité à l'`expire_le` attesté par Core — **VALIDÉ PROD + AUTOMATISÉ**.

## Preuves finales

- Core PR #83 fusionnée ; main `67b2ca74c7b4e9e510d1bb4a0fa5f094e56d952b` ; route live `/api/v1/sessions/current` vérifiée.
- DG production finale : commit `da39eb0dc7a2916c464e3e652591426fd7182535`.
- Déploiement Vercel : `dpl_FwwqzsHCSyccApqPS5DhfBPHGuAH` — **READY**, aliases `dgafrique.com` et `www.dgafrique.com`.
- Build final : `npm test && next build` — **18 tests / 18 pass / 0 fail**, compilation, lint/types et génération statique réussis.
- Tests navigateur réels : connexion, Mon espace, déconnexion, retour `next=/formations`, redirection d'un utilisateur déjà connecté, création d'un nouveau compte + email reçu + vérification + première connexion.
- Incident découvert en test réel : nouveau compte redirigé vers GamaDrive à cause d'un cookie de retour satellite ancien ; corrigé et revalidé par l'utilisateur avec **« Mon espace DG OK »**.

## Décision produit sur la profondeur de validation

À partir de CAP-002, le chantier ne cherche plus à épuiser tous les cas imaginables avant de progresser. Un CAP peut être `VALIDÉ PROD` lorsque :

- son parcours principal réel est fonctionnel ;
- les invariants de sécurité et de données critiques sont couverts ;
- les tests automatisés pertinents sont verts ;
- la production est READY et le comportement essentiel est validé.

Les raffinements UX et cas rares non bloquants vont au backlog d'amélioration continue.

## Gate

**CAP-002 — COMPTE DG AFRIQUE : VALIDÉ PROD.**

**CAP-003 — PROFIL DE CAPACITÉS devient le seul gate actif.**
