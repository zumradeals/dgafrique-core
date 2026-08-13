# Refonte DG AFRIQUE

## Positionnement validé

**DG AFRIQUE — Développement Global Afrique** devient la marque principale du site.

Slogan validé :

> **DG AFRIQUE — Des solutions pour faire avancer l’Afrique.**

La communication publique ne doit pas utiliser la formule « bras professionnel de l’écosystème GAMAD ».

## Architecture de marque

- **DG AFRIQUE** : structure principale et marque institutionnelle/commerciale.
- **GamaDigit** : pôle numérique de DG AFRIQUE.
- D’autres pôles pourront être ajoutés progressivement lorsqu’ils seront réellement structurés.

## Architecture web cible

```text
/
├── a-propos
├── poles
├── solutions
├── entreprises
├── actualites
├── contact
└── gamadigit
    ├── logiciels
    ├── formations
    ├── services
    ├── entreprises
    └── contenus
```

La migration vers cette arborescence sera progressive afin de préserver les URLs, les données, le SEO et le back-office existants.

## Première tranche

1. Transformer l’accueil en vitrine DG AFRIQUE.
2. Introduire une navigation DG AFRIQUE.
3. Présenter GamaDigit comme pôle numérique.
4. Créer `/gamadigit` comme porte d’entrée du pôle.
5. Conserver provisoirement les routes `/logiciels`, `/formations`, `/services`, `/blog` et `/contact` afin de ne pas casser la production.
6. Garder un wordmark textuel provisoire DG AFRIQUE tant que le nouveau logo définitif n’est pas validé.

## Tranches suivantes

- créer une page institutionnelle DG AFRIQUE ;
- créer un vrai catalogue de pôles administrable ;
- déplacer progressivement les routes GamaDigit sous `/gamadigit/*` avec redirections ;
- adapter le back-office pour distinguer contenus DG AFRIQUE et contenus GamaDigit ;
- faire évoluer le Copilote vers un moteur multi-pôle ;
- basculer `dgafrique.com` vers le VPS uniquement après validation de la préproduction et du plan de retour arrière ;
- mettre à jour sitemap, métadonnées, OpenGraph et données structurées ;
- prévoir les futurs pôles sans publier de promesses ni d’activités non structurées.
