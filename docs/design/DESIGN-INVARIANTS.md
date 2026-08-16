# DG Afrique — Invariants de design

**Statut : CANONIQUE DESIGN — ADOPTÉ**  
**Version : 1.0**  
**Date d’adoption : 16 août 2026**  
**Référence visuelle :** `docs/design/reference/claude-2026-08-16/`

## 1. Autorité et portée

Ce document fixe les invariants d’identité visuelle et d’expérience de DG Afrique.

Il s’applique à toute nouvelle interface et à toute refonte d’interface existante. Il ne remplace jamais la doctrine métier, les contrats GAMAD Core, les règles de sécurité, de confidentialité, de consentement ou de gouvernance. En cas de conflit, la doctrine métier et les règles de sécurité priment ; le design doit s’y adapter.

Le handoff Claude remis le 16 août 2026 et archivé sous `docs/design/reference/claude-2026-08-16/` est la **référence de design adoptée**. L’archive complète permet de reconstruire à l’octet près la maquette, le design system, les composants, les fixtures d’exemple et le guide d’intégration.

Une maquette ultérieure, une demande ponctuelle, un commit ou un changement de composant ne peut pas remplacer implicitement cette référence. Une évolution de ces invariants doit être explicite, datée, versionnée et justifiée.

## 2. Identité produit à faire ressentir

**DG Afrique est un réseau social d’action.**

Il relie personnes, capacités, apprentissage/transmission, besoins, projets et ZUMRA pour transformer des possibilités humaines en actions réelles.

Les applications spécialisées — GamaDrive et futurs satellites — sont des **outils au service de l’action**. Elles constituent une famille secondaire et ne doivent pas concurrencer l’identité principale de DG Afrique.

Boussole UX :

> Une identité. Des personnes. Des capacités. Des actions. Des outils spécialisés.

Formulation de marque de référence :

> DG Afrique — le réseau où les capacités deviennent des actions.

## 3. Trois interfaces fondatrices

Les trois interfaces suivantes définissent le langage de l’ensemble du produit :

1. **Landing page = la promesse.** Elle donne envie d’entrer et explique le passage de la capacité à l’action.
2. **Mon espace = mon centre d’action.** Il indique ce qui mérite l’attention maintenant ; il ne devient pas un tableau de statistiques ou un catalogue de modules.
3. **Fil ZUMRA / Fil d’action = la vie collective.** Il montre des situations réelles et la possibilité d’agir, plutôt qu’un flux de consommation sociale.

Toute propagation du design vers Besoins, Projets, Personnes, Messages, Partages, Administration ou satellites doit rester reconnaissable comme appartenant au même univers.

## 4. Navigation

La navigation principale nomme la matière de l’action, pas des modules techniques :

**Fil · Mon espace · Personnes · Besoins · Projets · ZUMRA**.

Messages, partages reçus, apprentissage/transmission et autres fonctions restent accessibles de manière secondaire ou contextuelle lorsqu’il n’est pas utile de les mettre au premier niveau.

Les satellites ne prennent pas une entrée principale équivalente au réseau. Ils apparaissent sous **Mes outils** ou à l’endroit où une action réelle les rend utiles.

## 5. Matières, couleurs et typographie

Principes immuables de la version 1.0 :

- fond principal ivoire `#F8F3EA` ;
- sable `#EDE4D4` pour les variations de matière ;
- cartes ivoire clair `#FFFDF7` ;
- vert profond `#103028` pour les surfaces d’ancrage ;
- vert d’action `#1B4A3B` pour ce qui agit ;
- cuivre `#A9552B` pour les besoins / ce qui manque ;
- bleu nuit `#14314D` pour les outils spécialisés ;
- safran `#D9A02B` pour ce qui attend une décision humaine.

Le blanc pur ne doit pas redevenir le fond structurel dominant. La profondeur vient d’abord des matières, des contrastes et du rythme, pas d’un empilement de cartes blanches avec ombres.

Typographies de référence :

- **Instrument Serif** : voix humaine, grands titres, moments éditoriaux ;
- **Instrument Sans** : interface et lecture courante ;
- **IBM Plex Mono** : références, structure, micro-libellés techniques uniquement.

Les polices doivent être auto-hébergées lors de l’intégration ; la maquette autonome ne dicte pas les dépendances runtime de production.

## 6. Couleur = sens, jamais décoration arbitraire

Une même famille d’objet conserve son signal visuel d’un écran à l’autre. La couleur doit permettre de reconnaître la nature d’un contenu même si son texte est masqué.

L’or/safran est rare : il signale une action ou décision humaine attendue, pas chaque bouton.

Le bleu nuit appartient en priorité aux outils spécialisés, afin que GamaDrive et les satellites restent clairement intégrés mais distincts du cœur social d’action.

## 7. Mon espace : une priorité avant le reste

Mon espace doit donner une direction quotidienne.

- une seule priorité dominante à la fois ;
- au maximum deux actions principales dans ce bloc ;
- le reste est organisé dans le temps : **Ensuite**, **Cette semaine**, ou équivalent ;
- pas de grille de compteurs comme langage principal ;
- une progression n’est montrée que lorsqu’elle décrit un objet métier réel.

Le design ne crée pas de moteur de décision fictif : la priorité affichée doit être déduite d’objets et événements réellement disponibles, ou remplacée par un état vide honnête.

## 8. Fil d’action : pertinence → compréhension → action

Le Fil ne cherche pas à maximiser la réaction ou le temps passé.

Chaque élément doit répondre à deux questions :

1. **Pourquoi cela m’est montré ?**
2. **Que puis-je réellement faire ici ?**

Les actions visibles dépendent toujours des permissions métier réelles. Le design ne crée aucun droit.

Sont exclus comme signaux de valeur humaine ou de classement : likes, nombre d’abonnés, score d’engagement, viralité, classement des personnes, défilement infini conçu pour la dépendance.

Le fil peut s’arrêter et le dire.

## 9. Présence humaine digne

DG Afrique doit faire sentir les personnes sans imiter un réseau social de divertissement.

- avatars typographiques et portraits réels lorsqu’ils sont disponibles et autorisés ;
- anonymat assumé avec `Membre DG Afrique` lorsque le profil n’est pas découvrable ;
- `Vous` pour soi ;
- groupes/portraits collectifs uniquement lorsque l’équipe existe réellement ;
- pas de banque d’images générique utilisée pour simuler une communauté réelle.

## 10. États vides

L’état vide est un écran de plein droit.

Il doit dire honnêtement qu’aucune donnée réelle n’est disponible, expliquer pourquoi, proposer l’action qui peut débloquer la situation, rester chaleureux et ne jamais être remplacé automatiquement par des données fictives.

## 11. Données de démonstration — invariant de référence UX

Les fixtures incluses dans le handoff Claude sont conservées **intentionnellement** afin de ne pas perdre le fil des interactions et de fournir un scénario stable de référence visuelle.

Elles ne sont pas des données métier et ne doivent jamais :

- être seedées dans les tables métier ;
- créer de faux membres, besoins, projets, ZUMRA, paiements ou partenaires ;
- apparaître automatiquement lorsqu’un utilisateur réel n’a pas de données ;
- être présentées sans marquage comme une activité réelle de DG Afrique.

Elles peuvent être utilisées dans les maquettes et tests visuels, dans un **mode Exemple / Démonstration** explicitement activé en environnement non productif, ou dans une landing/documentation si chaque scénario est clairement marqué **Exemple**.

Cette règle constitue une décision produit explicite du 16 août 2026 : elle autorise un usage non productif **uniquement sous marquage Exemple/Démonstration**, tout en maintenant l’interdiction de les faire passer pour des données réelles.

## 12. Outils spécialisés

GamaDrive et les futurs satellites apparaissent de préférence au moment où ils servent une action : documents d’un projet, espace documentaire d’une ZUMRA ou besoin métier spécialisé.

Ils peuvent aussi être regroupés dans **Mes outils**, mais ne structurent pas la navigation principale du réseau.

## 13. Hiérarchie avec le métier

Le design doit toujours se brancher sur les routes réellement disponibles, les permissions et services métier existants, les états et transitions canoniques, les consentements et visibilités réels, et GAMAD Core comme autorité d’identité.

Un prototype peut illustrer une interaction future, mais l’implémentation ne doit pas inventer le backend pour satisfaire une maquette.

## 14. Gouvernance de changement

Pour remplacer ou modifier substantiellement cette direction :

1. identifier l’invariant concerné ;
2. expliquer le problème utilisateur qui justifie le changement ;
3. montrer l’impact sur Landing, Mon espace, Fil et navigation ;
4. vérifier la compatibilité doctrine, accessibilité, mobile et sécurité ;
5. adopter explicitement une nouvelle version datée ;
6. conserver l’ancienne référence dans `docs/design/reference/`.

Une retouche locale peut évoluer sans nouvelle version si elle ne contredit aucun invariant.

## 15. Ordre de propagation de la version 1.0

1. tokens / typographies / surfaces ;
2. navigation globale desktop + mobile ;
3. Fil d’action / ZUMRA ;
4. Mon espace ;
5. Landing page ;
6. propagation progressive vers les autres capacités.

Le backend CAP-001 → CAP-022 n’est pas réinitialisé par ce chantier : on refond l’expérience et l’identité visuelle, pas les contrats métier validés.
