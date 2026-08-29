# ENGINE-TRUTH-FINAL-001 — décision finale sur le moteur V1

## Statut

`CERTIFIÉ — PASS` au commit `f9ab5fc404fa231b21758ae7854923c64f70610b`.

Le moteur a déjà obtenu `PASS` aux Stages A, B, C et D d'`ENGINE-RUNTIME-001`. Le présent lot
ferme les deux écarts de vérité restants : le périmètre fonctionnel V1 est explicitement signé sur
84 capacités et les univers de démonstration sont physiquement retirés. Le replay final PHP 8.4.24
et PostgreSQL 16.15 a réussi dans le clone VPS isolé le 29 août 2026 à 08:46:49 UTC.

Production `/var/www/dgafrique-core` ne doit pas être modifiée par ce replay.

## Preuves déjà acquises

| Porte | Résultat acquis | Portée |
|---|---|---|
| Stage A | PASS | dépendances, 61 migrations, suite moteur PostgreSQL, Redis, build et caches |
| Stage B | PASS | concurrence PostgreSQL et invariants transactionnels |
| Stage C | PASS | frontières GAMAD Core, GeniusPay et DeepSeek simulées, retours et réconciliation |
| Stage D | PASS | readiness, scheduler réel sous `www-data`, dump/restore, rollback migration et release atomique |

Le dernier Stage D concluant a exécuté 1 110 tests et 5 148 assertions sans échec sur le commit
`97890d0ef317f7c6e6b5c6465a115367d5a79ffe`. Son dump de 570 717 octets, SHA-256
`8e7300f1f02c5214e51a75603517cf1a36294bb4ed53ff5f7d25aefb9d9d0f52`, a été restauré et contrôlé.

## Vérité du périmètre V1

Le registre unique `docs/capacites/CAPABILITY-COVERAGE.md` classe les 84 capacités :

| Disposition | Nombre | Effet produit |
|---|---:|---|
| `CLOSED`, incluse | 60 | fonctionnalité moteur disponible |
| `DOC_ONLY`, incluse | 15 | doctrine obligatoire, aucun moteur distinct attendu |
| contrat domaine suffisant pour V1 | 4 | CAP-023, CAP-047, CAP-053, CAP-056 incluses sans abstraction artificielle |
| différée par contrat externe | 2 | CAP-051 et CAP-070 hors promesse V1 |
| différée sans consommateur réel | 3 | CAP-077 à CAP-079 hors promesse V1 |

Total : **84 / 84 classées**, dont **79 incluses ou assumées** et **5 explicitement différées**.
Les statuts techniques canoniques restent inchangés ; ce contrat ne transforme pas une capacité
partielle en capacité fermée.

Conséquence d'interface : les cinq capacités différées ne doivent produire ni bouton, ni section,
ni texte promettant une disponibilité. Lorsqu'un contrat externe réel les débloquera, elles feront
l'objet d'un nouveau lot fonctionnel complet, tests et interface compris.

## Vérité des données

`PRODUCTION-TRUTH-002` retire les 12 seeders concrets, leur classe de base et les tests dépendants.
`DatabaseSeeder.php` est le seul seeder restant et demeure vide. Les données synthétiques
transactionnelles créées à l'intérieur des tests moteur restent autorisées ; elles ne sont jamais
livrées comme contenu du produit.

Le replay final doit prouver cette absence avant de relancer migrations et suite PostgreSQL.

## Frontière moteur / carrosserie

La certification porte sur les modèles, migrations, règles d'autorité, services d'application,
contrats de persistance, intégrations, routes métier et mécanismes d'exploitation. L'interface
historique Blade reste couplée à plusieurs contrôleurs de rendu ; sa suppression rendra donc
volontairement le produit web indisponible jusqu'à l'arrivée de sa remplaçante, sans invalider les
services métier certifiés.

L'opération irréversible devra préserver :

- `app/`, `bootstrap/`, `config/`, `database/migrations/`, `routes/` et les tests moteur ;
- la charte `docs/brand/BRAND-DOCTRINE-001.md` et la doctrine produit canonique ;
- l'inventaire des routes et actions à recouvrir par le nouveau frontend ;
- les contrats d'autorité, d'état, de validation et d'erreur déjà testés.

Elle pourra retirer la présentation historique et ses tests exclusivement visuels. Aucune route ne
sera déclarée opérationnelle dans le produit neuf avant que son nouveau rendu et toutes ses actions
aient une preuve automatisée.

## Trois verdicts

### 1. Suppression irréversible du frontend actuel

`GO` — le runner `run-engine-truth-final-vps.sh` a retourné `0` sur le commit certifié et la
production est restée au même commit. DG Afrique doit rester en chantier/maintenance jusqu'au
nouveau frontend ; l'ancien rendu ne doit pas être maintenu en parallèle comme référence
concurrente.

### 2. Construction du nouveau frontend unique

`GO` — la construction peut démarrer après suppression contrôlée de l'ancienne présentation. Elle
utilise le moteur existant et le contrat V1 signé. Chaque bouton exposé doit avoir route, autorité,
mutation ou navigation réelle, retour utilisateur et test ; les cinq capacités différées sont
absentes.

### 3. Mise en production du produit DG Afrique

`NO-GO` — ce verdict ne changera pas avec le seul replay moteur. La production exige encore :

1. le nouveau frontend complet et sa couverture fonctionnelle/visuelle ;
2. des contrats de préproduction réels pour GAMAD Core et les fournisseurs externes activés ;
3. les secrets, domaines, TLS, alertes et sauvegardes hors serveur de l'infrastructure finale ;
4. un déploiement de préproduction, des parcours bout en bout et une répétition du rollback ;
5. une décision de migration n'important que des données légitimes.

## Preuve finale archivée

Rapport VPS :
`storage/logs/engine-runtime/20260829T084649Z-engine-truth-final.log`.

| Contrôle final | Résultat |
|---|---|
| Commit testé | `f9ab5fc404fa231b21758ae7854923c64f70610b` |
| Seeders | `DatabaseSeeder.php` uniquement ; zéro `*DemoSeeder.php` |
| Périmètre | 84/84 CAP ; 60 `CLOSED`, 5 `PARTIAL`, 15 `DOC_ONLY`, 2 `DEPENDENCY_BLOCKED`, 2 `NOT_IMPLEMENTED` |
| Dépendances | Composer valide, aucun avis de sécurité, prérequis plateforme satisfaits |
| Base propre | 61 migrations PostgreSQL appliquées, seeding vide réussi |
| Porte vérité | 5 tests, 33 assertions, zéro échec |
| Suite moteur | 1 068 tests, 4 831 assertions, zéro échec en 2 min 14,125 s |
| Code retour | `0` |
| Production | inchangée à `2a1847cb3c070e48400c81a6c0927cb22d65b6f7` |

La porte ENGINE-TRUTH est fermée. Tout changement ultérieur des services, migrations, autorités,
frontières externes ou mécanismes d'exploitation invalide uniquement la partie concernée et exige
un replay adapté avant production.
