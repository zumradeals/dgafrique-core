# CAP-009 — Découverte de personnes

- **Domaine :** Discovery & Matching
- **Propriétaire d’exécution :** DG Afrique
- **Statut :** implémenté — preuve VPS requise
- **Sources :** doctrine ZUMRA v1.1, CAP-003 à CAP-006, CAP-029 à CAP-031

## Finalité

Permettre à un membre connecté de découvrir des personnes à partir de capacités,
transmissions, apprentissages, localisation et mode de participation réellement
déclarés, sans transformer la personne en score ni publier son profil par défaut.

CAP-009 est une découverte explicite. La recommandation personnalisée et son
explication approfondie relèvent de CAP-010 et des capacités ultérieures.

## Consentements

La présence dans la découverte requiert simultanément :

1. le consentement révocable aux orientations ;
2. un consentement distinct à la découverte ;
3. un nom public choisi par le membre ;
4. au moins un profil encore actif.

Le retrait du consentement à la découverte rend immédiatement la fiche
introuvable et replace les déclarations dans l’état `PRIVATE`. La référence
opaque est conservée afin de préserver la continuité et l’historique.

## Données visibles

- nom public choisi ;
- courte présentation choisie ;
- activité, ville, pays et mode lorsqu’ils sont renseignés ;
- capacités `DISCOVERABLE` non archivées et autorisées au matching ;
- domaines d’intérêt, sans coordonnées de contact.

Ne sont jamais exposés par CAP-009 : téléphone, preuves privées, référence
d’identité canonique, secret de connexion, paiement ou donnée financière.

## Invariants

1. aucun profil fictif ou résultat de démonstration ;
2. aucune découverte publique hors connexion membre ;
3. aucun score global, classement humain ou promesse de compatibilité ;
4. les raisons affichées sont des déclarations factuelles compréhensibles ;
5. le profil courant est exclu de ses propres résultats ;
6. les requêtes sont validées, paginées et limitées en débit ;
7. textes, filtres et taille de page sont administrables sans déploiement ;
8. CAP-009 ne crée ni messagerie ni relation implicite entre deux personnes.

## Critères de preuve

- absence des profils privés ou seulement orientables ;
- activation volontaire avec nom public ;
- recherche et filtres sur des données réelles ;
- absence des données privées dans liste et détail ;
- retrait immédiat de la visibilité ;
- configuration administrateur persistée ;
- tests ciblés et suite complète verts sur PostgreSQL/VPS ;
- rendu desktop et mobile compilé par Vite.
