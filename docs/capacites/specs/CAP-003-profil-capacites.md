# CAP-003 — Profil de capacités

## Statut

**EN DÉVELOPPEMENT — GATE ACTIF.**

CAP-001 et CAP-002 sont validés en préproduction. CAP-004 à CAP-084 restent
bloqués.

## Finalité

Décrire une personne par sa situation, ses savoir-faire, ce qu'elle souhaite
apprendre et ce qu'elle cherche à accomplir, sans la réduire à une fiche
biographique ou à un score.

## Autorité et propriété

- GAMAD Core fournit la référence et le libellé canoniques ;
- DG Afrique possède les données métier du profil ;
- PostgreSQL rattache le profil à `core_identity_reference` ;
- aucune FK, adhésion ou dépendance ZUMRA ;
- le nom canonique n'est pas dupliqué dans la table métier.

## Données du lot

- pays et ville/localité ;
- activité actuelle, téléphone facultatif et parcours de formation ;
- savoir-faire existants ou déclaration explicite de démarrage sans compétence ;
- objectifs d'apprentissage ;
- domaines d'intérêt ;
- intentions libres ;
- mode de participation préféré ;
- consentement révocable aux orientations.

Les listes sont simples et bornées à douze éléments par dimension. CAP-004,
CAP-023, CAP-024 et CAP-026 porteront les modèles sémantiques plus riches.

## Invariants

1. La référence de mutation vient exclusivement de la session Core.
2. Une valeur envoyée par le navigateur ne peut pas choisir l'identité cible.
3. Le profil peut exister sans ZUMRA.
4. Le profil peut être incomplet et enrichi progressivement.
5. Déclarer ne pas avoir encore de compétence est valide.
6. Aucun score global ou jugement de valeur n'est stocké ou affiché.
7. Le consentement d'orientation est daté lorsqu'il est accordé et effacé
   lorsqu'il est retiré.
8. Les champs avancés restent des listes libres sans prétendre valider CAP-004.

## Critères de validation

- création et relecture du profil sous session Core réelle ;
- mise à jour sans altération de l'identité canonique ;
- isolation entre deux références Core ;
- profil visible depuis Mon espace ;
- retrait effectif du consentement ;
- migration PostgreSQL et tests automatisés verts ;
- interface desktop et mobile éprouvée en préproduction.

## Administration

Les libellés, textes d'aide, sections actives, ordre, pays suggérés, modes de
participation et texte de consentement sont enregistrés dans `portal_settings`
et modifiables depuis `/administration`. Les administrateurs sont des références
canoniques Core provisionnées dans `portal_administrators`; aucune adresse email
ni référence privilégiée n'est inscrite dans le code source.
