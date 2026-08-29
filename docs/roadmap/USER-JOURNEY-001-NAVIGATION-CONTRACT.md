# USER-JOURNEY-001 — contrat canonique de navigation

## Statut et portée

`CANONIQUE — VERROUILLÉ — OBLIGATOIRE POUR UJ-01+`

Ce document fixe la navigation du **frontend neuf** de DG Afrique. Il est subordonné à
`USER-JOURNEY-001.md` et ne constitue ni une seconde roadmap, ni une autorisation de restaurer
l'ancienne interface.

Décision produit : **sur mobile, DG Afrique utilise une barre inférieure fixe à cinq contrôles :
Fil · Découvrir · Agir · ZUMRA · Espace.** Le contrôle `Agir`, placé au centre, ouvre les actions
réelles disponibles ; il n'est pas une destination vide.

Cette décision traduit DG Afrique comme réseau social d'action compréhensible sans diplôme,
vocabulaire technique ou expérience numérique préalable.

## 1. Formule humaine

> Fil pour voir — Découvrir pour trouver — Agir pour commencer — ZUMRA pour avancer ensemble —
> Espace pour savoir quoi faire ensuite.

Un écran ou un composant qui ne préserve pas cette lecture doit être corrigé avant merge.

## 2. Navigation mobile obligatoire

La barre est horizontale, fixée au bas de l'écran et limitée à cinq contrôles dans cet ordre :

| Position | Libellé visible | Nature | Destination ou comportement réel |
|---:|---|---|---|
| 1 | **Fil** | destination | ouvre le Fil réel, route `activity.index` |
| 2 | **Découvrir** | regroupement de découverte | donne accès à **Personnes**, **Besoins** et **Projets**, respectivement `people.index`, `needs.index` et `projects.index` |
| 3 | **Agir** | déclencheur d'action | ouvre la feuille d'actions décrite au §4 ; ne pointe jamais vers une page vide |
| 4 | **ZUMRA** | destination | ouvre le Monde ZUMRA réel, route `zumra.index` |
| 5 | **Espace** | destination personnelle | ouvre `member.space` ; son nom accessible complet est « Mon espace » |

Règles structurelles :

- les cinq libellés restent visibles ; une icône seule est interdite ;
- `Découvrir` n'est pas un menu générique « Plus » : il ne contient que les trois centres
  **Personnes**, **Besoins** et **Projets** ;
- aucune sixième entrée, aucun carrousel horizontal et aucun menu « Plus » ne sont ajoutés à la
  barre ;
- `Agir` demeure exactement au centre et visuellement distinct, sans devenir plus difficile à
  comprendre que les autres contrôles ;
- le repère `Agir` emploie par défaut le jaune solaire `#F4D312` avec contenu encre `#181715` ; le
  texte blanc sur jaune est interdit et aucune animation permanente ne cherche à capter
  artificiellement l'attention ;
- la barre appartient aux surfaces membre. Elle n'est pas affichée au visiteur public avant une
  session membre valide ;
- les compteurs éventuellement affichés proviennent exclusivement de données réelles.

## 3. Correspondance avec les six centres fonctionnels

Les six centres définis par `UJ-00` restent tous présents. La navigation mobile les compose sans
en supprimer :

| Centre fonctionnel | Présence mobile | Présence desktop |
|---|---|---|
| Fil | `Fil` | `Fil` |
| Personnes | dans `Découvrir` | `Personnes` |
| Besoins | dans `Découvrir` | `Besoins` |
| Projets | dans `Découvrir` | `Projets` |
| ZUMRA | `ZUMRA` | `ZUMRA` |
| Mon espace | `Espace` | `Mon espace` |

Sur desktop, ces six centres sont exposés directement dans la navigation principale. `Agir` reste
un appel à l'action distinct de ces destinations. Une largeur intermédiaire peut adopter la
composition mobile si les six libellés directs ne tiennent plus sans compression.

L'état actif est honnête :

- les écrans Personnes, Besoins et Projets activent `Découvrir` sur mobile ;
- les écrans d'une ZUMRA activent `ZUMRA` ;
- les surfaces personnelles et outils personnels activent `Espace` ;
- une surface contextuelle conserve le centre depuis lequel elle a été ouverte lorsque ce contexte
  est connu ;
- `Agir` n'est jamais marqué comme destination active.

## 4. Contrat du contrôle central « Agir »

`Agir` ouvre une feuille d'actions depuis le bas de l'écran. Chaque proposition utilise une icône,
un libellé humain et une courte phrase qui explique le résultat attendu.

Les entrées canoniques sont :

| Action humaine | Contrat réel | Condition d'affichage |
|---|---|---|
| **Exprimer un besoin** | `needs.create` | session membre et création réellement disponible |
| **Lancer un projet** | `projects.create` | session membre ; prérequis suivants expliqués par le parcours |
| **Transmettre un savoir** | `transmissions.create` | session et configuration de transmission disponibles |
| **Ajouter une preuve** | `proofs.create` | session et contexte admissible disponibles |
| **Faire naître une ZUMRA** | `zumra.groups.create` | session membre ; prérequis métier expliqués sans fausse promesse |

Une action contextuelle supplémentaire, par exemple **Créer une mission**, ne peut apparaître que
si le contexte courant fournit réellement l'autorité et la route nécessaires. Elle ne devient pas
une promesse globale.

Le frontend peut ordonner ou réduire la liste selon le contexte et les permissions, mais il ne
peut pas inventer une action. Le serveur reste l'autorité finale à chaque requête.

Trois cas doivent être distingués :

1. contrat moteur absent : l'action est absente et reste mémorisée comme gap dans `UJ-00` ;
2. contrat réel mais prérequis humain manquant : l'action peut être visible avec une explication et
   une prochaine étape réelle ;
3. action interdite ou sensible : elle est masquée si sa présence divulgue une information, sinon
   son indisponibilité est expliquée sans permettre de soumission.

Un clic sans effet, un faux succès, un formulaire sans soumission réelle ou un libellé « bientôt »
sans contrat documenté est interdit.

## 5. Placement des surfaces secondaires

Les surfaces secondaires ne deviennent pas des entrées de la barre inférieure :

| Surface | Point d'accès attendu |
|---|---|
| Messages et notifications | raccourci d'en-tête, contexte source ou `Espace` |
| Opportunités et recommandations | `Espace` et objets concernés |
| Missions, transmissions et preuves | Projet, Besoin, ZUMRA ou action concernée ; `Agir` seulement si le contrat global/contextuel est réel |
| Contributions, ZAHAB et reçus | `Espace`, ZUMRA, Projet ou contexte financier légitime |
| Organisations, événements et partenariats | objet ou collectif porteur du contexte |
| Administration et modération | coque autorisée accessible depuis `Espace`, jamais exposée à tous |
| Outils spécialisés | `Espace` ou contexte métier, jamais catalogue principal |

Cette répartition est obligatoire : l'absence d'un menu « Plus » ne justifie ni la disparition de
ces surfaces, ni leur ajout sauvage à la navigation principale.

## 6. Accessibilité et simplicité non négociables

Le composant futur doit respecter simultanément :

- cible tactile d'au moins `48 × 48px` et espacement empêchant les activations accidentelles ;
- icône cohérente **et** libellé visible pour chaque contrôle ;
- texte courant en langage humain, sans identifiant CAP, état interne ou jargon d'architecture ;
- zone sûre du téléphone (`safe-area`) et espace réservé dans la page pour ne masquer aucun
  contenu ;
- fonctionnement dès `360px`, sans défilement horizontal ;
- focus visible, ordre clavier logique, nom accessible, `aria-current` sur la destination active ;
- ouverture et fermeture de la feuille `Agir` au clavier, focus contenu puis rendu au déclencheur ;
- fermeture par retour système/Échap sans perdre silencieusement une saisie ;
- support LTR, RTL et bidi ; `Agir` reste au centre ;
- mode mouvement réduit et absence d'animation indispensable à la compréhension ;
- chargement léger et utilisable sur téléphone modeste ou réseau faible ;
- couleur jamais utilisée seule pour indiquer l'état actif, l'attente, le succès ou l'erreur.

Aucun geste caché, appui long, balayage obligatoire ou connaissance préalable du produit ne doit
être nécessaire pour atteindre les cinq fonctions.

## 7. Exceptions de concentration

La barre reste visible par défaut sur les surfaces membre. Un parcours sensible ou séquentiel
(identité, paiement, confirmation irréversible ou formulaire à étapes) peut la remplacer
temporairement par une navigation de tâche uniquement si :

- un retour ou une fermeture explicite est toujours visible ;
- la saisie ou l'état réel est préservé ou la perte est confirmée ;
- le parcours restitue ensuite la navigation canonique ;
- l'exception est testée et documentée dans le lot utilisateur concerné.

Une page ne peut pas masquer la barre uniquement pour gagner de l'espace ou produire un effet
visuel.

## 8. Preuves obligatoires avant validation du composant

`UJ-01` ne peut passer à `PASS` sans tests prouvant au minimum :

1. l'ordre et les cinq libellés visibles à `360px` ;
2. l'absence de débordement et de contenu masqué par la barre ;
3. la correspondance exacte entre destination et route réelle ;
4. l'état actif de Fil, Découvrir, ZUMRA et Espace ;
5. l'ouverture, le focus, la fermeture et le retour de focus de `Agir` ;
6. l'absence d'action non autorisée et le refus serveur d'une mutation forgée ;
7. les états vide, chargement, erreur, hors ligne ou dépendance indisponible ;
8. la navigation au clavier, lecteur d'écran, contraste, mouvement réduit et RTL ;
9. l'absence d'un bouton « Plus » ou d'une sixième entrée primaire ;
10. le rendu desktop des six centres et de l'appel à l'action distinct `Agir`.

## 9. Gouvernance du verrou

Une IA ou un contributeur ne peut modifier les cinq libellés, leur ordre, le rôle central de
`Agir`, le contenu limité de `Découvrir` ou la règle « aucun Plus » pour une préférence visuelle,
une bibliothèque de composants ou un gain de place local.

Toute évolution exige une **nouvelle décision explicite du dépositaire produit**, puis une mise à
jour atomique de ce contrat, de `USER-JOURNEY-001.md`, de la matrice `UJ-00` et des tests concernés.
L'ajout d'une route ou d'un module au moteur n'élargit jamais automatiquement la navigation.
