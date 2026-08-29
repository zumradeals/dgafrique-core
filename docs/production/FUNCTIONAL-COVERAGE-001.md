# FUNCTIONAL-COVERAGE-001 — inventaire exhaustif des interactions et sections

> **État postérieur — FRONTEND-EXCISION-001 / UJ-00.** Les vues et contrôles comptés dans ce
> document ont été supprimés volontairement avec l'ancien frontend. Ce fichier reste la mémoire
> des promesses et écarts détectés ; il ne décrit plus une interface présente dans `main`.
> L'autorité actuelle pour construire les futurs écrans est
> `docs/roadmap/USER-JOURNEY-001-UJ-00-CONTRACT-MATRIX.md`. Une ancienne promesse sans moteur y est
> conservée comme gap, jamais restaurée comme bouton mort.

Statut : **INVENTAIRE TERMINÉ — IMPLÉMENTATION NON COMMENCÉE**

Baseline auditée : `main @ 2361195` (`PRODUCTION-TRUTH-001` fusionné localement).

Branche documentaire : `feat/functional-coverage-001`.

Ce document remplace, pour l’état actuel des interfaces, les inventaires historiques qui
qualifiaient encore plusieurs capacités récentes de « JSON uniquement » ou « inexistantes ».
La vérité exécutable du code et des migrations reste prioritaire.

## 1. Verdict

Non, tous les boutons, moteurs et sections ne sont pas encore opérationnels.

Le dépôt possède cependant beaucoup plus de métier réel que ne le laisse voir l’interface :

- la fondation HTTP est cohérente ;
- toutes les références statiques de routes utilisées par Blade existent ;
- toutes les actions de contrôleurs déclarées par les routes existent ;
- toutes les ancres locales littérales pointent vers une section présente ;
- les moteurs principaux Besoin, Projet, ZUMRA, Mission, Transmission, Preuve, Événement,
  Messagerie, Recommandation, Opportunité, Finance et Administration ont du code réel ;
- le principal risque est désormais une **couverture UI incomplète** : moteurs invisibles,
  sections seulement informatives et affordances visibles sans action.

DG Afrique n’est donc pas face à une reconstruction générale. Il reste un chantier de fermeture
fonctionnelle précis, priorisable et testable.

## 2. Méthode et périmètre

Audit statique de :

- `routes/*.php` et leur chargement par les Service Providers ;
- `app/Http/Controllers/**/*.php` ;
- `resources/views/**/*.blade.php` ;
- `resources/js/app.js` et les scripts Blade locaux ;
- services et modèles derrière les actions litigieuses ;
- liens, formulaires, boutons, ancres, contrôles désactivés et sections visibles.

Mesures brutes, hors composants de structure et pagination lorsqu’ils auraient artificiellement
dupliqué les mêmes contrôles sur plusieurs pages :

| Élément audité | Volume |
|---|---:|
| Fichiers routes/contrôleurs/vues/JS du périmètre | 266 |
| Vues et fragments d’écran interactifs | 110 |
| Routes nommées | 345 |
| Actions de contrôleurs vérifiées | 344 |
| Liens/CTA relevés dans les écrans | 497 |
| Boutons relevés, composants `<x-dg.btn>` inclus | 230 |
| Formulaires | 197 |
| Sections HTML | 233 |
| Ancres locales littérales contrôlées | 62 |
| Références statiques vers une route absente | **0** |
| Actions de route dont le contrôleur ou la méthode manque | **0** |
| Ancres locales sans cible | **0** |

Ces chiffres prouvent la cohérence structurelle statique. Ils ne remplacent pas les tests HTTP,
JavaScript et navigateur dans un environnement PHP/PostgreSQL complet.

## 3. Légende de couverture

| Statut | Sens |
|---|---|
| `OPÉRATIONNEL` | Action réelle, route et autorité présentes, retour utilisateur cohérent. |
| `CONDITIONNEL` | Code réel, mais dépend d’un solde, d’un consentement, d’un rôle ou d’un prestataire externe. |
| `PARTIEL` | Une partie de la section fonctionne ; au moins une promesse visible reste inactive ou inaccessible. |
| `BACKEND_INVISIBLE` | Moteur/routes présents, mais aucun accès humain clair depuis l’écran concerné. |
| `MOTEUR_ABSENT` | Le contrôle visible n’a ni modèle, ni service, ni cycle métier complet. |
| `UX_LEGACY` | Fonctionnel mais visuellement ou ergonomiquement en retrait de la doctrine DG Afrique actuelle. |

## 4. Matrice exhaustive par famille d’écrans

Les volumes couvrent tous les fichiers de la famille, y compris les sous-parcours et fragments
fonctionnels propres à cette famille.

| Famille | Vues | Liens | Boutons | Formulaires | Sections | Verdict fonctionnel | Reste à fermer |
|---|---:|---:|---:|---:|---:|---|---|
| Gateway + landing | 2 | 44 | 9 | 1 | 8 | `PARTIEL` | Recherche publique visible mais inactive. |
| Authentification | 3 | 3 | 5 | 4 | 6 | `PARTIEL` | Mot de passe oublié et connexion WhatsApp sans moteur. |
| Fil global | 1 | 20 | 0 | 0 | 9 | `PARTIEL` | Composer, personnalisation, suivi et rails de suggestions incomplets. |
| Mon espace + profil | 2 | 29 | 8 | 4 | 13 | `OPÉRATIONNEL` | Le wizard profil fonctionne ; la recherche globale appartient au shell. |
| Personnes | 2 | 17 | 6 | 2 | 12 | `PARTIEL` | Recherche/recommandations réelles ; réseau, connexion et invitation absents. |
| Recommandations | 1 | 4 | 2 | 2 | 0 | `OPÉRATIONNEL` | Masquer/restaurer et raisons explicables présents. |
| Opportunités | 1 | 2 | 0 | 0 | 0 | `OPÉRATIONNEL` | Moteur réel ; surface encore pauvre visuellement. |
| Besoins | 3 | 34 | 9 | 6 | 10 | `PARTIEL` | Création, lecture et états réels ; suivi, favoris, brouillons, contributions et financement absents. |
| Projets, Cerveau et brouillons | 29 | 104 | 33 | 30 | 38 | `PARTIEL` | Création et financement réels ; équipe, cycle, matching et accompagnement insuffisamment exposés ; documents/médias absents. |
| Missions | 6 | 45 | 37 | 36 | 23 | `PARTIEL` | Machine d’états très complète ; priorité, favoris et impact par Mission absents. |
| Transmissions | 4 | 13 | 22 | 22 | 4 | `OPÉRATIONNEL` | Cycle, participation, jalons, matching et contribution présents. |
| Preuves | 4 | 11 | 8 | 8 | 4 | `CONDITIONNEL` | Cycle réel ; fichiers GamaDrive bloqués par l’absence d’API documentaire. |
| Monde et espaces ZUMRA | 8 | 77 | 25 | 18 | 30 | `PARTIEL` | Groupe, rôles, adhésion, événements et gouvernance réels ; proximité et canaux multiples absents. |
| Organisations | 3 | 8 | 0 | 3 | 0 | `OPÉRATIONNEL / UX_LEGACY` | Parcours réel mais visuellement pauvre et peu discoverable. |
| Événements | 2 | 4 | 5 | 6 | 0 | `OPÉRATIONNEL` | Moteur réel ; entrée depuis le Fil manquante. |
| Messagerie | 2 | 7 | 3 | 3 | 5 | `OPÉRATIONNEL / UX_LEGACY` | Texte et contextes réels ; design ancien, aucun média promis dans l’UI. |
| Commentaires | 1 | 1 | 1 | 1 | 2 | `OPÉRATIONNEL / UX_LEGACY` | Fonctionnel, présentation minimale. |
| Partages contextuels | 2 | 6 | 2 | 2 | 4 | `OPÉRATIONNEL / UX_LEGACY` | Personne/ZUMRA réels ; entrée globale « avec contexte » inactive. |
| Notifications | 1 | 2 | 0 | 0 | 2 | `OPÉRATIONNEL / UX_LEGACY` | Lecture réelle, sans pression ni compteur conformément à la doctrine. |
| Contributions | 1 | 1 | 7 | 7 | 0 | `PARTIEL` | Paiement ZAHAB réel ; arrêt non exposé et plusieurs retours redirigent vers du JSON. |
| Wallet ZAHAB | 1 | 1 | 1 | 1 | 0 | `BACKEND_INVISIBLE` | Dashboard réel sans entrée claire ; wallets ZUMRA/Organisation restent JSON. |
| Fédération | 2 | 1 | 2 | 2 | 0 | `CONDITIONNEL` | Réel si le satellite et son callback sont configurés. |
| Administration | 33 | 62 | 39 | 39 | 63 | `OPÉRATIONNEL` | Routes, droits admin et formulaires cohérents ; aucune écriture Ledger manuelle exposée. |
| Modération membre | 0 | 0 | 0 | 0 | 0 | `BACKEND_INVISIBLE` | Signalement, suivi et recours existent côté backend, mais aucune surface membre complète. |
| Mesure collective | 0 | 0 | 0 | 0 | 0 | `BACKEND_INVISIBLE` | Trois projections réelles, uniquement JSON. |

## 5. Affordances visibles sans moteur ou sans action complète

Chaque ligne représente une promesse visible à rendre réellement opérante, et non un élément à
masquer. Les variantes desktop/mobile d’un même moteur sont regroupées.

| ID | Surface | Promesse visible | Vérité du code | Fermeture attendue | Priorité |
|---|---|---|---|---|---|
| FC-001 | Landing + topbar membre | Rechercher dans le réseau | Contrôles désactivés ; recherches locales séparées seulement | Recherche fédérée respectant visibilité/consentement, page de résultats et historique minimal | P0 |
| FC-002 | Connexion | Mot de passe oublié | Simple texte sans route | Demande de réinitialisation, jeton expirant, confirmation et rate limit | P0 |
| FC-003 | Connexion | Continuer avec WhatsApp | Bouton désactivé, aucun contrat d’identité WhatsApp | Construire le fournisseur réel ou requalifier explicitement la promesse produit avant production | P2 |
| FC-004 | Fil, composer | Partager une ressource | Élément passif | Sélecteur de contexte puis réutilisation de `ContextShareService` | P0 |
| FC-005 | Fil, composer | Annoncer un événement | Élément passif alors que `CommunityEvent` existe | Choisir ZUMRA/Organisation autorisée puis ouvrir le formulaire existant | P0 |
| FC-006 | Fil, composer | Sonder une question | Aucun modèle ni service de sondage | Modèle Poll/Choice/Vote, consentement, clôture et résultats non manipulables | P1 |
| FC-007 | Fil, navigation gauche | Actions importantes | Libellé passif | Projection des décisions/échéances réellement assignées à la personne | P0 |
| FC-008 | Fil, navigation gauche | Fil de mon réseau | Aucun graphe de connexion | Dépend de FC-014 ; filtre issu des relations acceptées | P1 |
| FC-009 | Fil, navigation gauche | ZUMRA/Projets suivis | Aucun modèle de suivi | Suivi explicite polymorphe et filtres du Fil | P1 |
| FC-010 | Fil, navigation gauche | Événements/Ressources partagés | Libellés passifs ; filtres centraux existent partiellement | Relier aux filtres du Fil et garantir les types de feed correspondants | P0 |
| FC-011 | Fil, tri | Trier par « Récents » | Texte statique | Paramètre de tri autorisé ; ne jamais créer un classement opaque | P2 |
| FC-012 | Fil, rail droit | À faire maintenant | État vide permanent | Synthèse déterministe des décisions et échéances réelles | P0 |
| FC-013 | Fil, rail droit | Projets qui ont besoin de vous | Aucun matching inverse affiché | Réutiliser les capacités consenties pour proposer des Projets/Besoins avec raisons explicables | P1 |
| FC-014 | Personnes | Se connecter / Mon réseau / contacts / invitations | Aucun modèle de relation humaine | Demande, acceptation, refus, retrait, blocage, visibilité et notifications | P0 |
| FC-015 | Personnes | Inviter une personne externe | Bouton désactivé | Invitation signée, expiration, anti-abus et rattachement après inscription | P1 |
| FC-016 | Besoins | Mes suivis + favori carte | Aucun modèle | Suivi explicite d’un Besoin, liste personnelle et retrait | P0 |
| FC-017 | Besoins | Brouillons | Création actuelle directe, pas de brouillon | Brouillon sauvegardable, reprise et abandon, sans publication implicite | P1 |
| FC-018 | Besoins | Mes contributions | Aucun moteur de réponse/contribution au Besoin | Proposition de contribution, décision du porteur, suivi et preuve | P0 |
| FC-019 | Besoins | Besoins financés / finançables | Aucun domaine financier Need ; financement Projet distinct | Décision produit explicite : financement Need autonome ou conversion vers Projet, puis moteur unique | P1 / décision |
| FC-020 | Missions | Par priorité | Aucun champ de priorité | Priorité gouvernée par le contexte, historique et filtre ; pas de score humain | P1 |
| FC-021 | Missions | Proposer une mission depuis l’annuaire | Désactivé par invariant contextuel | Écran de choix du contexte autorisé, puis formulaire existant | P0 |
| FC-022 | Missions | Mes favoris + favori carte | Aucun modèle | Favori personnel réversible | P1 |
| FC-023 | Missions | Taux d’impact | Aucun indicateur par Mission | Définition produit puis mesure dérivée de preuves/résultats ; aucun score fabriqué | P2 / décision |
| FC-024 | Projets, annuaire | Soutenir financièrement | Élément passif malgré financement Projet réel | Vue filtrée des financements ouverts et contribution ZAHAB | P0 |
| FC-025 | Projets, annuaire | Fournir des ressources / devenir bénévole | Éléments passifs | Contribution de ressource et demande de participation à l’équipe | P0 |
| FC-026 | Projet, fiche + feed | Participer / gérer l’équipe | Routes et service `ProjectTeamService` présents, UI absente | Demander, inviter, accepter, approuver, quitter, retirer avec états visibles | P0 |
| FC-027 | Projet, fiche | Contact porteur | Bouton feed désactivé ; messagerie Projet existe | Ouvrir la conversation Projet selon l’autorité existante | P0 |
| FC-028 | Projet, fiche | État, maturité et signaux | Routes/services/données présents, aucun formulaire dans la fiche | Exposer les transitions autorisées, historique et signaux consultatifs | P0 |
| FC-029 | Projet, fiche | Matching, accompagnement et autonomie | Pages/moteurs présents mais sans porte claire | Ajouter les entrées contextuelles selon droits et état | P0 |
| FC-030 | Projet, fiche | Mise à jour/clôture/annulation du financement | Routes présentes ; création/contribution seulement visibles | Formulaires gestionnaire avec confirmation et historique | P0 |
| FC-031 | Projet, fiche | Actions & tâches | Section informative ; Mission utilisée comme substitut | Soit construire Task, soit renommer honnêtement en Missions/Actions sans double moteur | P1 / décision |
| FC-032 | Projet, fiche | Documents et paramètres | Onglets pointent vers collaborations/garanties, pas vers documents/paramètres réels | Stockage documentaire via contrat GamaDrive ; paramètres Projet gouvernés | P1 / externe |
| FC-033 | Cerveau Projet | Pièce jointe, image, document | Quatre boutons désactivés | Dépend du contrat documentaire FC-032 | P1 / externe |
| FC-034 | Cerveau Projet | Saisie vocale | Bouton désactivé | Capture consentie, transcription et relecture avant envoi | P2 |
| FC-035 | Cerveau Projet | Filtrer / projets archivés | Contrôles statiques désactivés | Recherche réelle dans les projets accessibles et vue archives autorisée | P1 |
| FC-036 | ZUMRA | Proximité géographique | Ancienne simulation supprimée ; aucun moteur réel | Consentement géographique, précision limitée, distance protégée et désactivation individuelle | P1 |
| FC-037 | ZUMRA, espace | Canaux général/projets/annonces | Libellés préparatoires ; une conversation unique réelle | Construire les canaux ou présenter clairement la conversation unique | P2 |
| FC-038 | ZUMRA, espace | Créer une Mission | Route contextuelle présente mais aucune entrée forte | Ajouter à « Que voulez-vous faire aujourd’hui ? » selon autorité | P0 |
| FC-039 | Barre AGIR | Partager avec contexte | Contrôle désactivé | Choix Besoin/Projet/ZUMRA autorisé puis moteur de partage existant | P0 |
| FC-040 | Contributions | Arrêter une contribution | Route/service présents, aucun bouton | Confirmation, conséquence claire et retour dashboard | P0 |
| FC-041 | Contributions | Retour paiement et reçu | Endpoints réels mais réponses JSON dans le parcours humain | Pages de statut et reçu harmonisées, sans confiance dans le retour navigateur | P0 |
| FC-042 | Wallet | Consulter/acquérir ses ZAHAB | Dashboard réel sans lien de navigation clair | Entrée depuis Mon espace/Finance ; préserver l’absence de crédit manuel | P0 |
| FC-043 | Modération membre | Signaler, suivre, faire recours | Backend réel, aucune UI membre complète | Boutons contextuels sur commentaire/message/adhésion + pages « mes signalements/décisions » | P0 critique |
| FC-044 | Mesure collective | Voir les effets réels | Services/JSON réels, aucune surface humaine | Sections lisibles dans Mon espace, ZUMRA et Organisation, sans classement | P1 |

## 6. Moteurs présents mais insuffisamment exposés

Ces éléments ne doivent surtout pas être recodés. Le prochain lot doit connecter les interfaces aux
autorités existantes.

| Moteur présent | Routes/services réels | Manque UI constaté |
|---|---|---|
| Équipe Projet | `ProjectTeamService`, `projects.team.*` | Aucun contrôle sur la fiche malgré les demandes et invitations déjà chargées par le contrôleur. |
| Cycle Projet | `ProjectService::transition`, `projects.transition` | Statut affiché mais non pilotable. |
| Maturité Projet | `ProjectMaturityService`, `projects.maturity.update` | Historique et signaux chargés mais non rendus. |
| Matching Projet | `ProjectMatchingEngine`, `projects.matching` | Page existante sans entrée depuis la fiche. |
| Accompagnement | `ProjectAccompanimentController`, page dédiée | Données chargées par la fiche mais aucun CTA contextuel. |
| Autonomie | `ProjectAutonomyController`, page dédiée | Parcours accessible seulement par URL ou page profonde. |
| Financement Projet | `ProjectFundingService`, routes update/close/cancel | Création et contribution visibles, gouvernance de la déclaration invisible. |
| Arrêt contribution | `ContributionService::stop` | Route absente du dashboard humain. |
| Wallet personne | `WalletController::dashboard` | Route non promue dans la navigation membre. |
| Wallet ZUMRA/Organisation | `ZahabWalletService` | Lecture JSON seulement. |
| Modération membre | `ModerationReportService`, `ModerationDecisionService` | Aucun parcours humain complet. |
| Mesure collective | `ImpactMetricsService` | Trois endpoints JSON seulement. |
| Mission ZUMRA | `MissionContextRegistry`, routes contextuelles | Pas de CTA dans les actions rapides de l’espace ZUMRA. |
| Événement | `CommunityEventService` | Formulaires/fiches réels, mais le composer du Fil ne les ouvre pas. |

## 7. Désactivations légitimes — ne pas transformer en faux problèmes

Ces états sont fonctionnels et doivent rester conditionnels :

- précédent/suivant indisponible aux bornes de pagination ;
- étape précédente désactivée à la première étape du profil ;
- paiement ZAHAB désactivé si le solde est insuffisant ;
- demande ZUMRA déjà en cours ;
- partage ZUMRA réservé à un membre actif ;
- action réservée à un responsable ou à l’autorité du contexte ;
- état vide lorsqu’aucune donnée réelle n’existe ;
- fallback « paiement temporairement indisponible » lorsque le prestataire n’est pas configuré.

La production devra toutefois prouver que les variables et secrets du prestataire sont configurés ;
un fallback honnête ne remplace pas une intégration disponible.

## 8. Dette d’harmonisation UX restante

L’harmonisation est réelle sur les grands centres, mais pas à 100 %.

| Niveau | Surfaces |
|---|---|
| Références actuelles | Landing, Gateway, Fil, Personnes, Besoins, Projets, fiche Projet, Missions, fiche Mission, Monde ZUMRA, espace ZUMRA, administration principale. |
| Cohérentes mais encore secondaires | Transmissions, Preuves, création Projet/ZUMRA, événements, profil. |
| Visuellement en retrait | Messagerie, Organisations, Opportunités, Commentaires, Partages, Notifications, Contributions, Wallet, matching/accompagnement/autonomie Projet et plusieurs retours financiers. |
| Sans surface humaine | Modération membre complète, Mesure collective, wallets collectifs détaillés. |

Le prochain travail UX doit réutiliser les composants et gabarits DG Afrique existants. Il ne doit
pas redessiner les moteurs ni créer un deuxième service pour obtenir une page plus jolie.

## 9. Ordre d’implémentation recommandé

### Vague A — fermer les moteurs déjà présents

FC-005, FC-026 à FC-030, FC-038, FC-040 à FC-044.

Objectif : rendre utilisables les capacités déjà codées avant de créer de nouveaux domaines.

### Vague B — fondations sociales transverses

FC-001, FC-002, FC-007 à FC-018.

Objectif : recherche, relations, suivis, contribution Besoin et personnalisation du Fil. Ces
fondations alimentent plusieurs écrans à la fois.

### Vague C — nouvelles capacités métier

FC-006, FC-019, FC-020, FC-022, FC-023, FC-031, FC-036.

Objectif : sondage, financement Besoin, priorité/impact Mission, tâches et proximité. Chaque item
exige un contrat produit avant migration.

### Vague D — intégrations et confort

FC-003, FC-011, FC-032 à FC-035, FC-037.

Objectif : WhatsApp, documents/médias/voix, tri et canaux avancés.

## 10. Définition de « terminé » pour chaque ligne FC

Une action n’est pas considérée opérationnelle parce qu’un bouton mène quelque part. Elle doit
avoir :

1. un modèle ou une autorité métier unique ;
2. une route protégée et limitée ;
3. une validation serveur ;
4. un contrôle d’accès explicite ;
5. une mutation traçable si elle change un état ;
6. un retour utilisateur et un état d’erreur honnêtes ;
7. un état vide réel ;
8. des tests autorisé/interdit/idempotence/concurrence lorsque pertinent ;
9. une vérification navigateur desktop et mobile ;
10. aucune donnée de démonstration ni compteur inventé.

## 11. Bloquants de validation de cet inventaire

- PHP et Composer ne sont pas installés dans l’environnement d’audit : `route:list`, PHPUnit et
  le rendu Blade runtime n’ont pas pu être exécutés.
- Le contrôle actuel est statique, complété par le build Vite déjà réussi sur la baseline.
- Avant toute fusion des futures vagues fonctionnelles : exécuter la suite complète sous PHP 8.4,
  PostgreSQL, Redis et un navigateur réel.

## 12. Conclusion de production

`FUNCTIONAL-COVERAGE-001` démontre que la production n’est pas bloquée par 345 routes cassées ou
par une absence générale de backend. Elle est bloquée par **44 fermetures fonctionnelles
identifiées**, dont une majorité consiste à exposer correctement du code déjà existant.

La première implémentation doit commencer par la Vague A, en particulier la Modération membre et
l’équipe/cycle Projet. Ce sont les écarts présentant le meilleur rapport valeur/risque : forte
valeur utilisateur, autorités déjà présentes, faible invention métier.
