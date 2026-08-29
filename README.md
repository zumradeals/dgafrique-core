# DG Afrique Core

Portail applicatif de **DG Afrique**, reconstruit sur la stack familiale GAMAD.

> [!IMPORTANT]
> **État actuel : moteur certifié, frontend neuf en construction.** L'ancienne interface a été
> entièrement retirée de `main`. Ne pas restaurer les anciennes vues ou leurs assets depuis
> l'historique. Le produit web reste indisponible tant que les parcours du frontend neuf ne sont
> pas terminés selon
> [`docs/roadmap/FRONTEND-REBUILD-001.md`](docs/roadmap/FRONTEND-REBUILD-001.md).

## Situation du produit

- moteur Laravel/PostgreSQL/Redis : présent et certifié ;
- données de démonstration : supprimées ;
- ancien frontend : supprimé définitivement ;
- nouveau frontend : fondations UJ-01 en cours, aucune page métier encore livrée ;
- mise en production publique : **NO-GO** tant que la roadmap frontend et les portes de
  préproduction ne sont pas terminées.

Lire [`AGENTS.md`](AGENTS.md) avant toute intervention automatisée. Les preuves détaillées sont
[`ENGINE-TRUTH-FINAL-001`](docs/production/ENGINE-TRUTH-FINAL-001.md) et
[`FRONTEND-EXCISION-001`](docs/production/FRONTEND-EXCISION-001.md).
La reprise du chantier frontend se fait exclusivement par
[`USER-JOURNEY-001 — Opération Parcours de l'Utilisateur`](docs/roadmap/USER-JOURNEY-001.md).
Sa navigation mobile est déjà verrouillée par le
[`contrat canonique de navigation`](docs/roadmap/USER-JOURNEY-001-NAVIGATION-CONTRACT.md) :
**Fil · Découvrir · Agir · ZUMRA · Espace**, sans menu « Plus ».

## Stack canonique

- PHP 8.4 et Laravel ;
- PostgreSQL ;
- Blade + Livewire, Tailwind CSS et Alpine.js constituent le pipeline frontend neuf en cours de
  validation ;
- Redis pour les files, le cache et les verrous distribués ;
- Nginx, PHP-FPM, Supervisor et scheduler Laravel sur VPS ;
- GAMAD Core comme autorité d'identité, de session et de fédération ;
- GeniusPay pour les flux de paiement autorisés.

## Sources de vérité

1. code et tests de `main` : vérité technique exécutable ;
2. `docs/production/ENGINE-TRUTH-FINAL-001.md` et
   `docs/production/FRONTEND-EXCISION-001.md` : état certifié du moteur et état du frontend ;
3. `docs/capacites/CAPABILITY-COVERAGE.md` : progression réelle des capacités ;
4. `docs/capacites/CAPABILITY-INDEX.md` et `docs/capacites/OVERRIDES.md` : référentiel CAP-001 à
   CAP-084 et décisions métier prioritaires ;
5. `docs/brand/BRAND-DOCTRINE-001.md`, `docs/product/EXPERIENCE-PRODUIT-CANONIQUE.md` et
   `docs/roadmap/USER-JOURNEY-001.md`, complétés par
   `docs/roadmap/USER-JOURNEY-001-NAVIGATION-CONTRACT.md` : autorité de marque, d'expérience,
   d'exécution et de navigation du frontend neuf ;
6. `docs/architecture/ADR-001-stack-canonique.md` : architecture technique.

Le dossier `Design/` est une mémoire de handoff, pas une autorité permettant de restaurer
l'ancienne interface. `docs/design/DESIGN-INVARIANTS.md` est également une archive historique,
explicitement supersédée par `BRAND-DOCTRINE-001` pour le futur frontend.

Les anciennes réalisations Next.js/Supabase ne sont pas importées comme code. Les spécifications utiles sont conservées dans `docs/capacites/legacy/` uniquement comme matière d'audit.

## Règles fondatrices

- aucune seconde identité membre : GAMAD Core reste canonique ;
- compte DG Afrique gratuit et distinct de l'adhésion ZUMRA ;
- adhésion initiale et contribution mensuelle sont deux flux différents ;
- ZUMRA est un réseau social d'action, sans classement de valeur humaine ;
- les satellites restent autonomes et sont ouverts par fédération ;
- aucun mock ne doit être présenté comme une donnée réelle ;
- aucune capacité n'est déclarée validée sans preuve sur cette nouvelle base.

Lire `AGENTS.md` puis `docs/AI-HANDOFF.md` avant toute modification.
