# AI HANDOFF — DG Afrique Core

> Lire ce document avant toute modification.

Pour toute capacité liée à ZUMRA, lire également et respecter intégralement
[`docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md`](canon/ZUMRA-DOCTRINE-INVARIANTE.md).

Pour toute modification d’interface, de navigation ou de design, lire **avant de coder** :

- [`docs/design/DESIGN-INVARIANTS.md`](design/DESIGN-INVARIANTS.md) ;
- [`docs/design/reference/claude-2026-08-16/README.md`](design/reference/claude-2026-08-16/README.md) ;
- [`docs/design/reference/claude-2026-08-16/DECISIONS.md`](design/reference/claude-2026-08-16/DECISIONS.md).

## Projet canonique

- dépôt : `zumradeals/dgafrique-core` ;
- production cible : `dgafrique.com` sur le VPS GAMAD ;
- stack : Laravel, PHP 8.4, PostgreSQL, Blade/Livewire, Tailwind, Alpine.js, Redis ;
- identité et fédération : GAMAD Core ;
- paiement : GeniusPay lorsque le CAP concerné l'autorise ;
- design : `docs/design/DESIGN-INVARIANTS.md` + référence haute fidélité versionnée sous `docs/design/reference/`.

## Décision de reconstruction

Le dépôt précédent `zumradeals/gamadigit` n'est pas la base de code de ce produit. Il sert uniquement de source historique pour certaines décisions métier. Aucun composant Next.js, aucune migration Supabase et aucune preuve Vercel ne doivent être copiés aveuglément.

Le produit repart sur une nouvelle implémentation. Par conséquent, aucun CAP n'est considéré validé par héritage. Les anciennes spécifications conservées sous `docs/capacites/legacy/` doivent être réauditées contre Laravel, PostgreSQL, GAMAD Core et les contrats réels.

## Architecture à préserver

- GAMAD Core possède l'identité canonique, les sessions et les primitives de fédération ;
- DG Afrique possède son métier : profil, capacités, apprentissage, ZUMRA, projets, opportunités, contenus et orchestration ;
- les satellites comme GamaDrive gardent leur métier et leurs données ;
- DG Afrique ne reçoit ni ne transmet le mot de passe GAMAD à un satellite ;
- les références Core sont stockées comme identifiants externes, sans recréer une personne locale concurrente.

## Méthode de chantier

1. lire `CAPABILITY-INDEX.md`, `OVERRIDES.md` et le tracker ;
2. travailler uniquement le premier CAP non validé, sauf override explicite ;
3. produire spec, migration, code, tests et preuve ;
4. tester les états mobile, desktop large, vide, chargement, erreur et permissions ;
5. déployer en préproduction VPS ;
6. ne marquer VALIDÉ PROD qu'après preuve sur la nouvelle application.

## Design — invariant adopté le 16 août 2026

La référence actuelle n’est plus une simple inspiration : elle est un **invariant de produit versionné**.

- DG Afrique est un **réseau social d’action**, pas un lanceur de modules ;
- ZUMRA est le moteur humain et collectif ;
- GamaDrive et les futurs satellites sont des **outils spécialisés secondaires**, accessibles sous « Mes outils » ou contextuellement ;
- Landing, Mon espace et Fil ZUMRA sont les trois interfaces fondatrices ;
- la palette ivoire / vert profond / cuivre / nuit / safran, la typographie et les matières sont définies dans la référence adoptée ;
- le blanc pur ne redevient pas le fond structurel dominant ;
- Mon espace affiche une priorité claire avant les éléments secondaires ;
- le Fil explique la pertinence et privilégie les actions réelles plutôt que les métriques sociales ;
- aucune interface ne doit réintroduire likes, followers, score humain ou mécanisme de popularité comme signal de valeur ;
- les données de démonstration sont conservées comme scénarios UX de référence, mais ne sont jamais seedées ni présentées comme réelles ; elles ne peuvent être rendues qu’en mode Exemple/Démonstration explicitement marqué en environnement non productif.

Le handoff Claude complet est archivé de manière reproductible dans :

`docs/design/reference/claude-2026-08-16/archive/`

Son intégrité est documentée dans `SOURCE-MANIFEST.md`.

Toute divergence substantielle doit être une décision explicite et versionnée ; ne jamais remplacer implicitement la direction par une nouvelle maquette, une préférence ponctuelle ou un commit isolé.

L'implémentation doit également :

- occuper intelligemment les écrans larges au lieu de rester dans une colonne étroite ;
- conserver une excellente lisibilité et de vraies hiérarchies ;
- proposer de vrais comportements mobile ;
- afficher des états vides honnêtes lorsque les données réelles sont absentes ;
- ne jamais injecter de faux membres, montants, paiements, projets ou partenaires dans le métier.

## Premier gate

`CAP-001 — IDENTITÉ PERSONNE` est le premier gate à réimplémenter et valider sur cette nouvelle stack.
