# AI HANDOFF — DG Afrique Core

> Lire ce document avant toute modification.

## Projet canonique

- dépôt : `zumradeals/dgafrique-core` ;
- production cible : `dgafrique.com` sur le VPS GAMAD ;
- stack : Laravel, PHP 8.4, PostgreSQL, Blade/Livewire, Tailwind, Alpine.js, Redis ;
- identité et fédération : GAMAD Core ;
- paiement : GeniusPay lorsque le CAP concerné l'autorise ;
- design : dossier `Design/`, référence visuelle haute fidélité à adapter au produit réel.

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

## Design

Le handoff Claude fixe la direction visuelle. L'implémentation doit :

- occuper intelligemment les écrans larges au lieu de rester dans une colonne étroite ;
- utiliser des largeurs de contenu adaptées par section, jusqu'à environ 1600–1760 px lorsque pertinent ;
- conserver une excellente lisibilité ;
- proposer de vrais comportements mobile (navigation condensée, drawers, grilles adaptées) ;
- remplacer tous les placeholders par des états vides honnêtes ou des actifs réels ;
- ne jamais injecter de faux membres, montants, likes, projets ou partenaires.

## Premier gate

`CAP-001 — IDENTITÉ PERSONNE` est le premier gate à réimplémenter et valider sur cette nouvelle stack.
