# ADR-001 — Stack canonique de DG Afrique Core

## Statut

Accepté le 2026-08-13.

## Décision

DG Afrique est reconstruit sur la même famille technique que GAMAD Core et GamaDrive : PHP, PostgreSQL et Tailwind, avec déploiement maîtrisé sur le VPS GAMAD.

La stack applicative retenue est :

- Laravel sur PHP 8.4 ;
- PostgreSQL comme base métier ;
- Blade et Livewire pour préserver une architecture serveur simple avec interactions riches ;
- Alpine.js pour les micro-interactions locales ;
- Tailwind CSS pour le design system ;
- Redis pour queues, cache, rate limiting applicatif et verrous ;
- Nginx + PHP-FPM ;
- Supervisor pour les workers ;
- scheduler Laravel via systemd timer ou cron ;
- stockage local privé/public avec possibilité S3 compatible ultérieure.

## Raisons

- cohérence opérationnelle avec l'infrastructure GAMAD existante ;
- maîtrise des coûts et de l'hébergement ;
- proximité technique avec Core et les satellites ;
- modèle serveur adapté aux règles métier, paiements, files et tâches planifiées ;
- réduction de la dispersion Vercel/Supabase/VPS.

## Frontières

```text
GAMAD Core
  identité · session · fédération
          │
          ▼
DG Afrique Core
  portail · profil · capacités · ZUMRA · projets · orchestration
          │
          ├── GeniusPay
          └── satellites fédérés
                └── GamaDrive, puis produits autorisés
```

DG Afrique ne duplique pas les secrets, mots de passe ou règles internes du Core. Les intégrations passent par des clients HTTP versionnés, des DTO explicites, des timeouts et une journalisation sans secrets.

## Structure Laravel cible

Le monolithe reste modulaire :

```text
app/
├── Domain/
│   ├── Identity/
│   ├── Profiles/
│   ├── Capabilities/
│   ├── Zumra/
│   ├── Projects/
│   ├── Opportunities/
│   └── Satellites/
├── Application/
├── Infrastructure/
│   ├── GamadCore/
│   ├── GeniusPay/
│   └── Persistence/
└── Http/
```

Cette structure est une direction, pas une invitation à créer 84 modules. Les CAP sont des contrats de capacité, pas des éléments de menu ni nécessairement des dossiers.

## Conséquences

- le code Next.js précédent n'est pas migré ;
- les données utiles feront l'objet d'un inventaire et d'une migration séparée ;
- le design Claude est réimplémenté dans Blade/Livewire ;
- la bascule de `dgafrique.com` n'a lieu qu'après préproduction, sauvegarde, test de retour arrière et parité des parcours prioritaires.
