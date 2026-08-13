# Handoff : Portail DG Afrique (accueil public + espace connecté + ZUMRA)

## Overview
Refonte de la page d'accueil de DG Afrique (portail humain du dépôt `zumradeals/gamadigit`, branche `cursor`) en portail façon "moteur de recherche / hub" à la Go Africa Online, plus le parcours connecté associé : connexion, tableau de bord "Mon espace", et l'expérience réseau social ZUMRA. Objectif produit : que DG Afrique reste le point d'entrée fort (activité principale) tout en accueillant d'autres satellites GAMAD (ZUMRA, GamaDrive, Wasplex, G-Market, G-POS) sans perdre son identité.

## About the Design Files
Les fichiers `.dc.html` de ce dossier sont des **références de design** créées en HTML — des prototypes qui montrent l'apparence et le comportement voulus, pas du code à copier tel quel en production. La mission du développeur est de **recréer ces designs dans l'environnement réel du dépôt `gamadigit`** : Next.js 15 (App Router), React 19, TypeScript, Tailwind CSS, Supabase (Auth/Postgres/RLS), en respectant les conventions déjà en place dans le dépôt (voir `github.md` pour le pointeur repo/branche).

## Fidelity
**Haute fidélité (hifi)** : couleurs, typographie, espacements et hiérarchie sont définitifs et tirés de la charte graphique GamaDigit (`docs/03-charte-graphique.md` du dépôt). Le développeur doit recréer l'UI fidèlement avec Tailwind, en mappant les valeurs ci-dessous sur la config Tailwind existante (`tailwind.config.ts`).

## Fichiers
- `Portail DG Afrique.dc.html` — page d'accueil publique (visiteur non connecté ou connecté, adaptatif)
- `Espace DG Afrique.dc.html` — 3 écrans internes dans un seul fichier de prototype : Connexion, Mon espace (tableau de bord), ZUMRA connecté (fil, profils, projets). Un petit sélecteur flottant en bas à droite ("Aperçu") permet de naviguer entre les 3 écrans dans le prototype — ce sélecteur est un outil de démo et NE FAIT PAS partie du produit final.
- `github.md` — pointeur vers le dépôt source (`zumradeals/gamadigit`, branche `cursor`)

---

## Écrans / Vues

### 1. Accueil public (`Portail DG Afrique.dc.html`)

**Objectif** : point d'entrée principal. Recherche unifiée façon moteur de recherche, accès rapide aux besoins numériques les plus fréquents, mise en avant du réseau ZUMRA (seul satellite avec du contenu vivant), et accès aux autres satellites GAMAD sans quitter DG Afrique.

**Layout** :
- Header sticky sombre (`#061F35`) : logo/wordmark à gauche, nav (`Entreprises`, `Talents`, `Emploi`, `Services ▾`) qui wrap sur petit écran, CTA amber "Booster ma visibilité", lanceur de satellites (icône grille, ouvre un dropdown listant les 6 satellites avec badge Actif/Bientôt + lien "Être notifié"), cloche de notifications (dropdown 3 items), bouton Connexion/avatar.
- Hero plein cadre (fond sombre à motif, à remplacer par une vraie photo de couverture — actuellement un placeholder rayé avec légende monospace "photo de couverture") : titre avec mot-clé en amber, sous-titre, 4 onglets (Services/Professionnels/Satellites/Emploi — actuellement décoratifs, à câbler à un vrai filtre de recherche), barre de recherche double (Quoi/Qui + Où) + bouton Rechercher.
- Section "Quel est votre besoin numérique ?" : grille responsive (`auto-fit`, min 240px) de 6 cartes ; une carte est mise en avant (fond `#061F35`, badge amber "Mise en avant").
- Bandeau ZUMRA (fond `#061F35`) : titre + paragraphe + 4 statistiques + carte "Ils viennent de nous rejoindre" (avatars empilés) + CTA "Rejoindre ZUMRA". Collage de 2 photos placeholder à droite (chevauchement volontaire).
- Section "Ils nous font confiance" : 5 logos partenaires placeholder + CTA amber.
- Section "Tendances" (fil ZUMRA en direct) : 3 colonnes (satellites suivis / fil de publications avec composeur visuel / professionnels suivis), responsive en `auto-fit`.
- Section "Nos derniers articles" : grille responsive de 4 cartes.
- Bandeau "Découvrez aussi nos plateformes" : pills des 6 satellites (accent réduit + suffixe "· bientôt" pour les satellites non actifs).
- Footer sombre : colonnes liens, réseaux sociaux, pays de présence, mention GAMAD.

**Important — bug de layout résolu** : les sections directement enfants du `<main style="display:flex;flex-direction:column">` utilisent `max-width:1180px;margin:0 auto`. En flexbox, des marges auto sur l'axe transversal empêchent l'étirement (`stretch`) et font que la section se réduit à son contenu. La correction ajoute `width:100%` sur ces sections. **Le développeur doit reproduire ce point d'attention** si l'implémentation réelle réutilise un layout flex-column pour la page.

### 2. Connexion (`Espace DG Afrique.dc.html`, écran "login")

**Layout** : split écran en 2 colonnes qui wrap en mobile (`flex-wrap`) — colonne gauche sombre (logo, citation de marque, tagline), colonne droite blanche (formulaire : e-mail/téléphone, mot de passe, case "Se souvenir de moi", lien mot de passe oublié, bouton "Se connecter" (marine), bouton "Continuer avec WhatsApp" (menthe clair), lien création de compte).

### 3. Mon espace (écran "espace")

**Layout** : header sombre avec recherche centrée + notifications (badge de compteur) + menu compte (dropdown : Mon profil / Paramètres / Se déconnecter). Sidebar gauche (nav : Tableau de bord, Mon profil, ZUMRA, Mes demandes, Satellites, Paramètres) + contenu principal qui change selon l'item actif :
- **Tableau de bord** : accueil personnalisé, 3 cartes stats (profil complété avec barre de progression, demandes en cours, statut contribution ZUMRA), 3 cartes actions rapides, liste d'activité récente, grille compacte des satellites.
- **Mon profil** : avatar, bio éditable, compétences déclarées (chips), bouton Enregistrer.
- **Mes demandes** : liste de devis avec badges de statut colorés (En cours/En attente/Terminé).
- **Satellites** : grille complète des 6 satellites avec badge Actif/Bientôt et bouton Ouvrir / Être notifié.
- **Paramètres** : toggles notifications e-mail/push, langue, lien changement de mot de passe.

Layout en flexbox avec wrap (sidebar / contenu) pour rester utilisable sur petit écran.

### 4. ZUMRA connecté (écran "zumra")

**Layout** : header sombre (retour Mon espace, wordmark ZUMRA vert, recherche centrée, badge "Contribution à jour", avatar). 3 colonnes en flex-wrap : sidebar nav ZUMRA (Fil/Ma contribution/Projets soutenus/Messages/Mon profil), colonne centrale (composeur fonctionnel qui publie réellement dans le fil, squelette de chargement à l'ouverture, fil de publications avec bouton "Voir plus"), colonne droite (carte profil ZUMRA avec stats, profils à suivre avec bouton Suivre/Suivi fonctionnel, projets tendances avec barre de progression de financement).

---

## Interactions & Behavior (déjà simulées dans le prototype)
- Navigation Connexion → Mon espace → ZUMRA via les CTA internes ("Se connecter", "Ouvrir ZUMRA", "← Mon espace").
- Composeur ZUMRA : publier ajoute réellement un post en tête du fil (état local, pas de persistance).
- Bouton "Voir plus de publications" charge 2 posts mockés supplémentaires.
- Boutons "Suivre"/"Suivi" basculent d'état par profil.
- Skeleton de chargement (3 blocs gris) affiché ~800ms à l'ouverture du fil ZUMRA.
- Dropdowns notifications (portail + espace) et menu compte (espace) s'ouvrent/ferment au clic.
- Lanceur de satellites (portail) : "Être notifié" passe en "Inscrit" au clic (état local par satellite).
- Recherche accueil : au clic sur "Rechercher", affiche un message "recherche à venir" sous la barre (aucune requête réelle envoyée).

## À implémenter côté réel (non simulé dans le prototype)
- Recherche unifiée réelle (services, satellites, articles) — actuellement décorative.
- Authentification réelle (le prototype navigue sans vérifier d'identifiants) — brancher sur Supabase Auth / GAMAD Core selon `docs/AI-HANDOFF.md` du dépôt.
- Persistance des publications ZUMRA, des demandes de devis, du profil.
- Pagination réelle / infinite scroll du fil ZUMRA (le "Voir plus" est un mock).
- États de chargement réseau (skeletons) sur toutes les données dynamiques, pas seulement le fil ZUMRA.
- Notifications réelles avec compteur serveur.
- Responsive mobile plus poussé : le prototype utilise flexbox + `auto-fit`/`minmax` sans media queries pour rester dans les contraintes de l'outil de prototypage ; en production, ajouter des points de rupture CSS dédiés (drawer mobile pour les sidebars, nav condensée en menu burger) plutôt que le simple wrap flexbox utilisé ici.

## Design Tokens

**Couleurs (charte GamaDigit)**
- Bleu nuit (fond, structure, header/footer) : `#061F35`
- Bleu océan (liens, actions) : `#0877C9`
- Cyan (accent innovation) : `#19C2D0`
- Vert menthe (validation, ZUMRA, WhatsApp) : `#21C87A`
- Ambre (CTA principaux, promotions) : `#F5B942`
- Nuage (fonds secondaires) : `#F4F8FB`
- Neutres UI : bordures `#E4EBF1`, texte secondaire `#7C8A9A` / `#9AA6B2`, texte body `#334452`

**Typographie** : pile système (`-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif`). Titres en 800 (extra-bold), corps en 400-700. Tailles : H1 hero ~34-38px, H2 section ~19-24px, body 13-15px, labels/petits textes 11-12px.

**Rayons** : cartes 14-20px, pills/boutons 999px (full), petites puces 7-10px.

**Ombres** : cartes flottantes/dropdowns `0 12-16px 32-40px rgba(6,31,53,0.18-0.22)` ; barre de recherche hero `0 16px 40px rgba(6,31,53,0.3)`.

## Assets
Aucune image réelle fournie — tous les visuels (photo de couverture hero, portraits du collage, images d'articles/publications, logos partenaires) sont des placeholders rayés avec légende monospace indiquant ce qui doit être déposé (ex. "photo de couverture", "image article"). Le logo GamaDigit officiel existe dans le dépôt (`public/brand/gamadigit-mark.svg`, `gamadigit-logo.svg`, `favicon.svg`) — à utiliser à la place du monogramme "G" simplifié du prototype.
