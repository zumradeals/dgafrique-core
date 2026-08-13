# Architecture de l’information — GamaDigit V1

## Navigation publique

```text
Accueil
├── Services
│   ├── Création web et applications
│   ├── Design et communication
│   ├── Hébergement et infrastructure
│   ├── Logiciels et abonnements
│   ├── Formation et accompagnement
│   └── Solutions numériques pour entreprises
├── Blog
└── Contact / Devis
```

## Back-office cible

```text
Tableau de bord
├── Contenus et pages
├── Menus
├── Familles de services
├── Services et packs
├── Logiciels et abonnements
├── Formations
├── Solutions entreprises
├── Articles et catégories du blog
├── Témoignages et FAQ
├── Demandes de devis et prospects
├── Médias
└── Paramètres et SEO
```

## Règle de configurabilité

Les contenus commerciaux sont pilotés depuis la base : titres, textes, offres, prix, images, ordre, visibilité, SEO et messages WhatsApp.

Les éléments sensibles restent dans le code : sécurité, authentification, validation, règles d’accès, structure responsive et contrats de données.

## Modèle de publication

Les contenus utilisent trois états :

- `draft` : visible uniquement dans l’administration ;
- `published` : visible publiquement ;
- `archived` : conservé mais retiré du site.
