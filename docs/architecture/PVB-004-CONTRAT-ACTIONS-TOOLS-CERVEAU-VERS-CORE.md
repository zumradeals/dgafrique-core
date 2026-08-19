# PVB-004 — Contrat Actions/Tools du Cerveau vers le Core

**Statut :** CONTRAT PRODUIT / INTÉGRATION — V1 de travail  
**Branche :** `feat/project-brain-v2`  
**Références :** `ARCHITECTURE-PRODUIT-V2.md`, PVB-001, PVB-002, PVB-003  
**Objet :** définir comment le Cerveau Projet transforme une intention humaine en lecture, suggestion, brouillon ou action métier sans devenir une seconde autorité du Core.

---

## 1. Thèse

Le Cerveau ne manipule jamais directement la base de données métier.

Il utilise un **catalogue explicite d'actions/outils autorisés** qui constituent une frontière entre l'intelligence conversationnelle et les domaines canoniques.

Chaîne obligatoire :

`INTENTION → COMPRÉHENSION → ACTION CANDIDATE → VALIDATION → APERÇU → CONFIRMATION SI NÉCESSAIRE → TOOL/ADAPTER → SERVICE MÉTIER → RÉSULTAT → ÉVÉNEMENT → RENDU AU CERVEAU`

> **Le Cerveau propose ; le Tool traduit ; le Core décide si l'action est valide ; l'humain conserve l'autorité requise.**

---

## 2. Définition d'un Tool

Dans PVB-004, un **Tool** est un contrat applicatif contrôlé permettant au Cerveau de lire ou demander une opération au Core.

Ce n'est pas nécessairement un outil IA externe. Il peut être un adapter interne vers un service Laravel existant.

Un Tool doit déclarer au minimum :

- identifiant stable ;
- description métier ;
- niveau de risque N0–N4 ;
- schéma d'entrée ;
- schéma de sortie ;
- permissions/préconditions ;
- besoin ou non de confirmation ;
- service métier cible ;
- effets attendus ;
- erreurs métier possibles ;
- règles de traçabilité.

---

## 3. Le Cerveau n'appelle pas « n'importe quelle fonction »

Le modèle d'intelligence n'a jamais accès arbitrairement :

- aux modèles ORM ;
- au SQL ;
- aux commandes système ;
- aux contrôleurs génériques ;
- aux secrets ;
- à une URL libre d'écriture ;
- à un mécanisme permettant de contourner les policies.

Seuls les Tools inscrits au catalogue et explicitement autorisés pour le contexte courant peuvent être proposés/exécutés.

---

## 4. Niveaux de risque

### N0 — Lecture

Lire, expliquer, résumer des informations déjà autorisées.

Exemples : état Projet, besoins ouverts, équipe visible, événements récents.

Pas de confirmation métier supplémentaire.

### N1 — Suggestion

Produire une recommandation sans mutation.

Exemples : prochaine action, personnes compatibles, besoin à clarifier.

### N2 — Brouillon

Préparer une structure modifiable sans mutation canonique.

Exemples : brouillon Need, Mission, invitation, résumé.

### N3 — Mutation confirmée

Créer ou modifier un objet métier après confirmation explicite et autorisation.

Exemples : créer un Need, créer une Mission, inviter un membre, enregistrer une activité/preuve selon le workflow.

### N4 — Action sensible

Finance, décaissement, gouvernance majeure, suppression irréversible, engagement contractuel ou autre action renforcée.

Le Cerveau peut assister et préparer ; l'exécution dépend d'un workflow spécialisé, de permissions fortes et de confirmations adaptées. L'IA ne prend jamais la décision finale.

---

## 5. Pipeline d'une action N3

Exemple :

> « Il nous faut deux développeurs web à Bouaké. »

Le pipeline est :

1. détecter l'intention `need.create` ;
2. extraire les paramètres candidats ;
3. résoudre le Projet actif ;
4. récupérer uniquement le contexte nécessaire ;
5. valider les champs métier connus ;
6. demander une clarification si une donnée obligatoire manque ;
7. construire un brouillon N2 ;
8. afficher l'aperçu ;
9. recevoir une confirmation explicite ;
10. revérifier permissions et état courant ;
11. appeler l'adapter/service canonique ;
12. recevoir le résultat réel ;
13. exposer le succès/échec ;
14. laisser les événements métier/Fil suivre leurs règles normales.

Aucun « succès » ne doit être affiché avant l'étape 12.

---

## 6. Confirmation explicite

Une confirmation doit porter sur une action compréhensible et sur ses données essentielles.

Bon :

> **Créer ce besoin** — 2 développeurs web, Bouaké.

Mauvais :

> OK

ou une confirmation cachée dans un long message.

Une confirmation est liée au brouillon présenté. Si les données essentielles changent après confirmation, une nouvelle confirmation est requise.

---

## 7. Revalidation au moment d'exécuter

La confirmation humaine ne garantit pas que l'action est encore valide.

Entre aperçu et clic, l'état peut changer : permission retirée, Projet clôturé, objet déjà modifié, besoin devenu invalide.

Le Tool doit donc revalider au moment de l'exécution :

- identité ;
- autorité ;
- Projet ;
- préconditions ;
- données ;
- état métier courant.

Le Core peut refuser une action pourtant confirmée.

---

## 8. Idempotence et doubles clics

Les actions créatrices/financières doivent être conçues pour éviter les doubles effets causés par :

- double clic ;
- retry réseau ;
- reprise après timeout ;
- message dupliqué ;
- réexécution du modèle.

Le contrat technique devra prévoir des clés d'idempotence ou mécanismes équivalents pour les actions concernées.

**Invariant : une confirmation utilisateur ne doit pas pouvoir créer accidentellement deux opérations identiques par défaut.**

---

## 9. Catalogue initial — lecture Projet

Tools candidats N0 :

- `project.get_state`
- `project.get_today`
- `project.get_activity`
- `project.get_team`
- `project.get_needs`
- `project.get_missions`
- `project.get_proofs`
- `project.get_context`

Ces noms sont des identifiants conceptuels, pas encore des signatures techniques figées.

Ils doivent lire les services/read models autorisés et non répliquer la logique métier dans le Cerveau.

---

## 10. Catalogue initial — Besoins

Tools candidats :

- `need.prepare_create` — N2 ;
- `need.create` — N3 ;
- `need.prepare_update` — N2 ;
- `need.update` — N3 ;
- `need.get` — N0 ;
- `need.list` — N0 ;
- `need.get_matches` — N1/N0 selon rendu.

Exemple :

`« Nous cherchons deux développeurs » → prepare_create → brouillon → confirmation → create → objet Need réel`

Le matching ne doit jamais être inventé par le Cerveau ; il consomme le moteur autorisé.

---

## 11. Catalogue initial — Missions

Tools candidats :

- `mission.prepare_create` — N2 ;
- `mission.create` — N3 ;
- `mission.prepare_update` — N2 ;
- `mission.update` — N3 ;
- `mission.get` — N0 ;
- `mission.list` — N0 ;
- `mission.prepare_complete` — N2 ;
- `mission.complete` — N3.

Terminer une Mission ne doit pas fabriquer automatiquement une preuve. Le Cerveau peut proposer ensuite l'ajout d'une preuve lorsque pertinent.

---

## 12. Catalogue initial — Équipe

Tools candidats :

- `team.get` — N0 ;
- `team.prepare_invitation` — N2 ;
- `team.invite` — N3 ;
- `team.get_pending_invitations` — N0 ;
- `team.prepare_role_change` — N2/N4 selon autorité du rôle ;
- `team.change_role` — N3/N4 selon gouvernance.

Le Cerveau ne doit jamais ajouter directement une personne comme membre si le domaine exige invitation/acceptation.

> **Invitation envoyée ≠ membre rejoint.**

---

## 13. Catalogue initial — Preuves

Tools candidats :

- `proof.get` — N0 ;
- `proof.list` — N0 ;
- `proof.prepare_create` — N2 ;
- `proof.create` — N3 ;
- `proof.prepare_link` — N2 ;
- `proof.link` — N3.

Le Tool Proof doit respecter la doctrine de preuve existante : source, objet soutenu, visibilité, autorité et autres contraintes du domaine.

Un fichier de conversation n'est jamais promu silencieusement en Proof.

---

## 14. Catalogue initial — Projet

Tools candidats :

- `project.prepare_create` — N2 ;
- `project.create` — N3 ;
- `project.prepare_update_identity` — N2 ;
- `project.update_identity` — N3 ;
- `project.get_state` — N0 ;
- `project.get_today` — N0 ;
- `project.get_activity` — N0.

La création V2 doit accepter une intention minimale et laisser les informations différables pour plus tard, sous réserve des invariants du Core.

---

## 15. Catalogue initial — Accompagnement

Lorsque le domaine actuel le permet, candidats :

- `support.get_active` — N0 ;
- `support.prepare_request` — N2 ;
- `support.request` — N3.

Le vocabulaire technique final doit s'aligner sur les objets réellement présents dans le dépôt lors de la cartographie d'implémentation.

---

## 16. Recommandation

Tools candidats :

- `recommendation.for_need` — N1 ;
- `recommendation.for_project` — N1 ;
- `recommendation.explain` — N0/N1.

Une recommandation doit retourner non seulement des résultats mais des **raisons structurées** exploitables par l'UX.

Exemple :

```text
Personne : Awa
Raisons :
- capacité Développement web déclarée
- visible pour ce rapprochement
- territoire compatible
```

Le Cerveau transforme ces raisons en langage humain sans inventer de raison supplémentaire.

---

## 17. Mémoire et conversations

Tools internes candidats :

- `conversation.list`
- `conversation.create`
- `conversation.rename`
- `memory.get_relevant`
- `memory.propose`
- `memory.confirm`
- `memory.reject`
- `memory.supersede`

Leur autorité et visibilité suivent PVB-003.

`memory.confirm` ne remplace jamais une mutation d'un objet métier canonique lorsqu'un tel objet existe.

---

## 18. Appels à projets — futur

Lorsque le module existe :

- `call.list_relevant` — N0/N1 ;
- `call.get` — N0 ;
- `application.prepare` — N2 ;
- `application.submit` — N3/N4 selon engagement ;
- `application.get_missing_requirements` — N0/N1.

Le Cerveau peut préparer une candidature ; il ne doit jamais inventer un appel ni annoncer une sélection avant décision réelle de l'émetteur.

---

## 19. Finance — futur N4

Tools potentiels :

- `finance.get_project_plan` — N0 ;
- `finance.prepare_funding_request` — N2 ;
- `finance.submit_funding_request` — N3/N4 ;
- `finance.get_decision` — N0 ;
- `wallet.get_authorized_view` — N0 ;
- `finance.prepare_disbursement_action` — N2/N4.

Interdictions absolues au Cerveau :

- modifier un solde ;
- écrire directement dans le Ledger ;
- approuver son propre financement ;
- décider une allocation ;
- déclencher un décaissement sans workflow financier spécialisé ;
- présenter une déclaration conversationnelle comme argent reçu.

---

## 20. Outils spécialisés — futur

Un outil spécialisé peut enregistrer des capacités via un contrat commun.

Exemple conceptuel :

```text
specialized_tool.capability
id: commerce.catalog.search
provider: G-POS
accepts: material_need
returns: commercial_offers
risk: N1
```

Le Cerveau peut découvrir cette capacité lorsqu'un Besoin compatible existe.

Il ne doit pas connaître les détails internes de G-POS ou d'un futur Transport/Logistique.

---

## 21. Registre des Tools

Le système doit posséder un registre applicatif contrôlé.

Conceptuellement :

```text
ToolDefinition
- key
- domain
- description
- risk_level
- input_schema
- output_schema
- required_permissions
- confirmation_policy
- handler
- enabled
- version
```

Le modèle IA reçoit seulement les définitions utiles au contexte courant, pas nécessairement le catalogue entier.

---

## 22. Permissions

Les permissions sont évaluées côté serveur par le Core/adapter.

Le Cerveau peut anticiper :

> « Vous ne semblez pas avoir l'autorisation de modifier ce Projet. »

mais cette anticipation n'est jamais la sécurité elle-même.

**Invariant : cacher un bouton n'est pas une permission.**

---

## 23. Paramètres et références

Les Tools doivent préférer les identifiants canoniques résolus côté serveur aux noms libres.

Exemple : le modèle peut comprendre « Bouaké », mais le Tool résout/valide la représentation géographique attendue.

Le modèle ne doit pas fabriquer un ID de personne, Projet, Need ou Mission.

---

## 24. Clarification avant action

Si une donnée nécessaire est ambiguë, le Cerveau demande une précision au lieu de deviner.

Exemple :

> « Invite Moussa. »

S'il existe plusieurs Moussa visibles :

> J'ai trouvé plusieurs personnes nommées Moussa. Laquelle souhaitez-vous inviter ?

L'UX peut afficher les candidats autorisés.

---

## 25. Résolution des personnes

Une personne doit être sélectionnée à partir d'un résultat réel et autorisé du Core/recommandation.

Le Cerveau ne peut pas créer une identité fictive pour satisfaire une commande.

Pour une invitation externe future, un workflow distinct doit exister.

---

## 26. Gestion des erreurs

Un Tool retourne des erreurs structurées que le Cerveau traduit humainement.

Catégories minimales :

- non autorisé ;
- introuvable ;
- validation ;
- conflit d'état ;
- action déjà réalisée ;
- dépendance indisponible ;
- erreur technique récupérable ;
- erreur technique inconnue.

Le Cerveau ne masque pas un échec derrière une réponse positive.

---

## 27. Timeouts et état inconnu

Si le Tool expire après l'envoi d'une mutation, le Cerveau ne doit pas supposer l'échec ou le succès.

Il doit vérifier l'état/idempotence avant de proposer un retry.

Réponse UX possible :

> Je n'ai pas encore pu confirmer si le besoin a été créé. Je vérifie son état avant de réessayer.

---

## 28. Traçabilité

Pour chaque action N3/N4 initiée via le Cerveau, conserver une trace suffisante de :

- utilisateur ;
- Projet ;
- conversation/message source si pertinent ;
- Tool/version ;
- brouillon présenté ;
- confirmation ;
- paramètres validés ;
- service/handler ;
- résultat ;
- objet créé/modifié ;
- erreur éventuelle ;
- horodatage.

Ne pas stocker inutilement des secrets ou données non nécessaires dans les traces.

---

## 29. Explicabilité

Pour les suggestions/recommandations, le Tool doit autant que possible retourner des raisons structurées.

Pour les mutations, le système doit pouvoir expliquer :

> « Ce besoin a été créé parce que vous avez confirmé le brouillon proposé dans cette conversation. »

L'explicabilité est une propriété du workflow, pas uniquement du texte généré.

---

## 30. Séparation lecture / écriture

Un Tool de lecture ne doit pas provoquer silencieusement une mutation métier.

Exemple : `project.get_today` ne doit pas créer une Mission « pour aider ».

Une suggestion produite pendant une lecture doit devenir un brouillon distinct avant toute écriture.

---

## 31. Transactions et atomicité

Une mutation métier qui exige plusieurs écritures cohérentes doit être gérée transactionnellement par le domaine/service concerné, pas orchestrée pas-à-pas par le modèle IA.

Le Cerveau demande :

> créer le Besoin

Le service métier prend en charge les écritures nécessaires et l'événement correspondant.

---

## 32. Événements et Fil

Le Tool ne publie pas directement dans le Fil sauf si le contrat métier canonique l'exige explicitement.

Chaîne préférée :

`Tool → Service métier → événement métier → projection/éligibilité Fil`

Ainsi le Cerveau ne possède pas une voie spéciale de publication.

---

## 33. Sécurité contre l'injection conversationnelle

Un texte utilisateur, document ou contenu externe peut contenir des instructions destinées à détourner le Cerveau.

Le système doit traiter ces contenus comme **données**, pas comme autorité permettant de :

- modifier les permissions ;
- activer un Tool interdit ;
- contourner une confirmation ;
- révéler des secrets ;
- changer de Projet silencieusement ;
- effectuer une action N4.

L'autorité provient du serveur, des permissions et du catalogue de Tools, jamais du texte lu.

---

## 34. Politique d'activation

Un Tool peut être :

- disponible globalement ;
- disponible pour certains domaines/projets ;
- désactivé ;
- expérimental ;
- conditionné à une capacité ou feature flag.

Le Cerveau ne doit pas proposer une action indisponible comme si elle était exécutable.

---

## 35. Versionnement

Les contrats Tool évoluent.

Une version doit permettre de retracer quelle définition a été utilisée pour une action passée.

Les changements incompatibles de schéma ou de sémantique doivent être explicitement versionnés/migrés.

---

## 36. Mode sans IA

Chaque mutation métier importante accessible par le Cerveau doit rester accessible, lorsque pertinent, par une interface manuelle simplifiée utilisant le même service métier.

Conceptuellement :

```text
Cerveau ───────┐
               ├── Tool/Command Adapter ── Service métier ── Core
UI manuelle ───┘
```

L'IA n'obtient donc pas un Core parallèle.

---

## 37. Exemple complet — besoin

Utilisateur :

> « Trouve-nous deux développeurs Laravel à Bouaké. »

Cerveau :

1. constate qu'aucun Need correspondant n'est ouvert ;
2. propose de créer le Besoin ;
3. prépare : capacité Laravel, quantité 2, territoire Bouaké ;
4. affiche la carte ;
5. utilisateur clique **Créer ce besoin** ;
6. `need.create` revalide permission + Projet + paramètres ;
7. service métier crée le Need ;
8. événement métier est produit ;
9. panneau Projet affiche le Need réel ;
10. Cerveau appelle ensuite, si demandé/approprié, `need.get_matches` ;
11. résultats réels + raisons sont affichés.

Important : **« trouve-nous » ne signifie pas « ajoute automatiquement deux personnes à l'équipe ».**

---

## 38. Exemple complet — mission terminée

Utilisateur :

> « On a fini de visiter les locaux. »

Si une Mission correspondante existe :

1. Cerveau identifie la Mission candidate ;
2. s'il y en a plusieurs, il clarifie ;
3. prépare `mission.complete` ;
4. demande confirmation ;
5. le service métier termine la Mission ;
6. le Cerveau demande éventuellement :
   > Avez-vous une photo, une note ou un document à ajouter comme preuve ?

La preuve reste une action séparée.

---

## 39. Exemple complet — information seulement

Utilisateur :

> « Où en sommes-nous ? »

Le Cerveau peut combiner plusieurs Tools N0 : état, besoins, missions, activité récente.

Il répond avec des faits :

> Deux besoins sont ouverts. Une mission attend une décision. Fatou a rejoint l'équipe hier.

Il ne crée aucune action et ne demande pas de confirmation inutile.

---

## 40. Frontières non négociables PVB-004

1. Aucun accès direct du Cerveau aux écritures DB métier.
2. Seuls les Tools inscrits au registre peuvent être invoqués.
3. Les permissions sont revérifiées côté serveur au moment de l'action.
4. Toute mutation N3 requiert la politique de confirmation prévue.
5. Les actions N4 restent sous workflow spécialisé et autorité humaine.
6. Un brouillon n'est jamais un objet métier.
7. Aucun succès n'est annoncé avant retour réel du Core.
8. Aucun ID métier n'est inventé par le modèle.
9. Les doubles exécutions doivent être maîtrisées par idempotence lorsque nécessaire.
10. Une lecture ne provoque pas silencieusement une écriture.
11. Les transactions complexes appartiennent aux services métier, pas au modèle.
12. Le Cerveau ne publie pas directement dans le Fil en contournant les événements métier.
13. Les contenus conversationnels/documents ne peuvent pas élever leurs propres permissions.
14. Le matching est consommé, jamais inventé.
15. Invitation envoyée ne signifie pas membre rejoint.
16. Mission terminée ne signifie pas preuve créée.
17. Déclaration financière ne signifie pas mouvement Ledger.
18. L'UI manuelle et le Cerveau convergent vers les mêmes règles métier.

---

## 41. Critères d'acceptation PVB-004

PVB-004 est correctement traduit lorsque :

- le catalogue des Tools est explicite et contrôlé ;
- chaque Tool connaît son niveau de risque ;
- une phrase naturelle peut produire un brouillon structuré ;
- une mutation nécessite la confirmation attendue ;
- les permissions sont contrôlées côté serveur ;
- le Core peut refuser proprement une action ;
- les erreurs sont rendues sans faux succès ;
- les actions créatrices sont protégées contre les doubles effets pertinents ;
- les recommandations exposent leurs raisons ;
- l'action produit les mêmes invariants qu'une action manuelle ;
- les événements métier restent la voie normale vers le Fil ;
- Finance et outils spécialisés peuvent être ajoutés sans donner de privilège universel au Cerveau.

---

## 42. Point de contrôle avant implémentation

Les identifiants de Tools de ce document sont **conceptuels**.

Avant d'écrire les handlers, il faut réaliser une cartographie du dépôt réel afin d'identifier :

- modèles et tables actuels ;
- services/actions/commands existants ;
- policies et permissions ;
- événements ;
- contrôleurs/endpoints ;
- tests existants ;
- écarts entre la doctrine PVB et le Core actuel.

Aucun nom comme `NeedService` ou `MissionService` ne doit être inventé dans le code simplement parce qu'il apparaît comme exemple architectural dans les documents précédents.

---

## 43. Suite recommandée — PVB-005

**PVB-005 — Cartographie technique Projet V2 ↔ Core actuel**

Objectif : auditer le dépôt réel et produire une matrice :

```text
Action UX / Tool
      ↕
Objet métier actuel
      ↕
Service / endpoint / policy
      ↕
Événement / tests
      ↕
Écart à combler
```

PVB-005 doit précéder l'implémentation du Cerveau afin que la nouvelle UX se branche sur la vérité existante au lieu de reconstruire un deuxième moteur Projet.
