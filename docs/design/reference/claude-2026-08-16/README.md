# DG Afrique — Handoff design (direction validée)

Paquet autonome décrivant l'identité visuelle validée de **DG Afrique — le réseau où les capacités deviennent des actions**, et les trois interfaces fondatrices : Landing page, Mon espace, Fil ZUMRA.

Ce paquet est **une matière de design**. Il ne contient aucun code applicatif, aucune règle métier, aucun secret, aucun `.env`, aucun token d'accès. Le dépôt `zumradeals/dgafrique-core` n'a pas été modifié.

## Structure

```
handoff-dg-afrique/
├── README.md                      ← ce fichier
├── DECISIONS.md                   ← les choix de design et leur raison
├── maquettes/
│   ├── DG Afrique - Direction.dc.html   ← la maquette validée, source de vérité visuelle
│   └── support.js                        ← runtime nécessaire pour ouvrir la maquette hors ligne
├── design-system/
│   ├── tokens.css                 ← variables CSS + classes de base (implémentation de référence)
│   ├── tokens.json                ← mêmes valeurs, exploitables par un build
│   ├── tailwind.theme.js          ← extrait de configuration Tailwind
│   ├── PALETTE.md                 ← palette complète, valeurs exactes et règles d'emploi
│   ├── TYPOGRAPHIE.md             ← polices, échelle, règles
│   └── SURFACES-ESPACEMENTS.md    ← surfaces, profondeur, espacements, rayons, ombres
├── composants/
│   ├── navigation-desktop.html
│   ├── navigation-mobile.html
│   ├── fil-cartes.html            ← les quatre types de carte du Fil ZUMRA
│   ├── mon-espace.html            ← priorité du jour, blocs secondaires, colonne d'ancrage
│   ├── personnes.html             ← traitement des personnes, avatars, anonymat
│   ├── etats-vides.html
│   └── outils-specialises.html    ← GamaDrive en contexte
├── assets/
│   ├── motif-trame.svg            ← trame diagonale (grain des surfaces vert profond)
│   └── ASSETS.md
├── donnees-de-demonstration/
│   ├── LISEZ-MOI.md               ← ⚠ contenu fictif, jamais une donnée métier
│   └── demo-content.json
└── integration/
    └── BLADE-TAILWIND.md          ← comment transposer vers Blade / Tailwind / Laravel
```

## Ouvrir la maquette

Ouvrir `maquettes/DG Afrique - Direction.dc.html` dans un navigateur, en gardant `support.js` dans le même dossier. Le document est un canevas : Landing, Mon espace, Fil ZUMRA, navigation globale et mini design system, chacun en desktop (1440 px) et mobile (390 px), avec pour chaque interface l'état rempli **et** l'état vide.

La maquette est la référence visuelle exacte. `design-system/` et `composants/` en sont la transcription réutilisable : mêmes valeurs, mêmes noms, aucune simplification.

## Séparation stricte structure / démonstration

- `composants/` et `design-system/` ne contiennent **aucun** contenu métier : uniquement structure, classes, tokens et libellés d'interface.
- Tout texte fictif (noms, besoins, projets, ZUMRA) est isolé dans `donnees-de-demonstration/demo-content.json` et marqué `"__demonstration": true`.
- Dans la maquette, les blocs illustratifs sont annoncés par le mot « exemple ».
- Aucun compteur social, aucun montant, aucun membre ni partenaire fictif n'est présenté comme réel.

## Ce que le design ne change pas

Aucune règle métier n'a été inventée ni modifiée. États de besoin et de projet, repères de maturité, cinq responsabilités ZUMRA, intentions de contribution, anonymisation « Membre DG Afrique », partage avec contexte obligatoire, absence totale de score : tout provient des capacités existantes (CAP-003 → CAP-022) et de la doctrine ZUMRA invariante.
