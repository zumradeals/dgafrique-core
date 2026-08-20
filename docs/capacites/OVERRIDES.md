# Addenda au référentiel CAP

Ces décisions ont priorité sur une lecture littérale ancienne du référentiel.

## OVR-001 — ZUMRA est un réseau social d'action

ZUMRA peut avoir fil d'activité, relations entre membres, commentaires, partage et messagerie lorsqu'ils servent l'apprentissage, les besoins, les groupes et les projets. Aucun classement de valeur humaine ni mécanique d'attention artificielle.

## OVR-002 — Adhésion et contribution sont distinctes

Le paiement initial active l'adhésion ZUMRA selon ses règles. La contribution mensuelle est un flux périodique différent. Une contribution ne doit jamais activer une adhésion en attente.

## OVR-003 — La contribution construit une capacité d'action

La contribution mensuelle alimente une capacité financière communautaire destinée à amorcer des projets avant l'arrivée éventuelle de partenaires. Elle n'achète ni rang, ni visibilité, ni pouvoir social et ne promet aucun rendement individuel.

## OVR-004 — Aucun comportement de retard inventé

Les états `not_started`, `up_to_date`, `grace` et `late` peuvent exister. Aucune suspension automatique ou pénalité nouvelle n'est codée sans décision métier explicite.

## OVR-005 — Outils spécialisés, extractibilité et fédération

Un outil spécialisé commence comme **module extractible**. Il ne devient satellite autonome que lorsqu'un besoin réel d'autonomie technique le justifie et qu'un ADR d'extraction est accepté.

Lorsqu'un outil est effectivement autonome, GAMAD Core fournit la primitive de fédération. DG Afrique peut déclencher son ouverture sous session Core ; l'outil autonome vérifie le jeton avec ses propres identifiants. Le mot de passe GAMAD ne transite jamais.

Cette mécanique n'a aucun lien avec la maturité d'un Projet : **un Projet ne devient pas satellite logiciel**.

## OVR-006 — Stack canonique reconstruite

Le dépôt canonique devient `zumradeals/dgafrique-core`. Stack : Laravel/PHP 8.4, PostgreSQL, Blade/Livewire, Tailwind, Alpine.js et Redis sur VPS. L'ancien code Next.js/Supabase n'est pas une dépendance du nouveau produit.
