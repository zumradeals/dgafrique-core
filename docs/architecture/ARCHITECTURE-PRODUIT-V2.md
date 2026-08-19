# DG Afrique — Architecture Produit V2

**Statut :** DOCTRINE PRODUIT FIGÉE — Audits 01 à 03  
**Date :** 19 août 2026  
**Portée :** architecture produit, relations entre domaines, invariants et directions d'évolution.  
**Principe :** ce document ne remplace pas les CAP canoniques ; il fixe leur lecture d'ensemble et les conclusions structurantes issues des audits afin d'éviter les régressions conceptuelles.

---

## 1. Thèse produit

DG Afrique est un **réseau social d'action** : le réseau humain est au centre ; les outils spécialisés émergent pour permettre aux personnes, capacités, ZUMRA et projets de produire une action réelle.

La réussite du produit n'est pas l'attention captée à l'écran. Elle peut se produire hors écran : rencontre, travail, transmission, projet, mission, preuve, résultat.

Architecture directrice :

`PERSONNES → CAPACITÉS → ZUMRA / PROJETS → OUTILS SPÉCIALISÉS → ACTION → PREUVES → NOUVELLES CAPACITÉS`

Les satellites ne sont pas des produits autonomes concurrents du réseau : ce sont des **outils spécialisés contextuels** au service d'une action réelle.

---

## 2. Audit 01 — ZUMRA, adhésion, contribution et économie communautaire

### 2.1 Séparations obligatoires

Toujours distinguer :

- **Compte DG Afrique** : accès au portail et au réseau ;
- **Adhésion au Programme ZUMRA** : engagement distinct ;
- **Appartenance à une ZUMRA** : relation à un groupe réel ;
- **Consentement** : autorisation explicite selon le contexte ;
- **Contribution** : soutien financier distinct de l'adhésion.

Aucune de ces notions ne doit être déduite automatiquement d'une autre.

### 2.2 Politique actuellement retenue

- adhésion au Programme ZUMRA : **500 FCFA / 1 USD, une seule fois à vie** ;
- compte DG Afrique utilisable sans adhésion ZUMRA ;
- contribution individuelle : **500 FCFA/mois, facultative** ;
- contribution d'une ZUMRA validée : **2 500 FCFA/mois, facultative** ;
- aucune dette automatique ;
- aucun rattrapage obligatoire ;
- aucune suspension automatique pour non-contribution ;
- une contribution financière ne doit jamais améliorer un score de matching ou la valeur d'une personne.

### 2.3 État architecture

Le moteur d'adhésion/paiement est un socle à conserver. Le fonds communautaire, les contributions récurrentes et les mécanismes de financement restent à construire/formaliser.

---

## 3. Audit 02 — Projet et Cerveau Projet

### 3.1 Projet est un dossier vivant

Un Projet ne doit pas être réduit à un formulaire administratif. Il devient progressivement un **dossier vivant d'action** qui rassemble sa réalité : intention, équipe, besoins, capacités, gouvernance, missions, événements, accompagnement, preuves, progression et résultats.

### 3.2 Le Cerveau Projet

Le **Cerveau Projet** est la couche d'orchestration cognitive d'un Projet particulier.

Il peut :

- comprendre une expression humaine ;
- retrouver le contexte réel du Projet ;
- signaler ce qui manque ou ce qui pourrait faire avancer ;
- proposer une action ;
- préparer une action structurée ;
- expliquer pourquoi il recommande quelque chose.

Il ne doit jamais devenir une deuxième source de vérité métier.

**Invariant : le Cerveau conseille, l'humain décide.**

Toute mutation importante reste confirmée par une personne autorisée et exécutée par les services métier canoniques.

### 3.3 Quatre notions à ne pas confondre

- conversation avec le Cerveau ;
- mémoire structurée utile au Projet ;
- journal d'action / événements métier ;
- preuves.

Une conversation n'est pas automatiquement une preuve. Une mémoire n'est pas automatiquement une décision métier.

### 3.4 Satellites

Les satellites sont requalifiés comme **outils spécialisés contextuels**. Un Projet peut en avoir besoin selon sa nature et sa maturité. Un satellite arrivé à maturité peut ensuite produire de nouvelles capacités pour le réseau.

---

## 4. Audit 03 — Fil d'action et recommandation

### 4.1 Fil

Le Fil est le **système circulatoire de l'action DG Afrique**.

Il ne possède pas sa propre vérité. Il projette des événements et objets issus des domaines métier réels.

Le Fil doit simultanément répondre à deux questions :

1. **Que se passe-t-il dans le réseau ?**
2. **Qu'est-ce qui peut être utile pour moi et pourquoi ?**

### 4.2 Invariants du Fil

- pas de popularité comme finalité ;
- pas de viralité comme moteur de classement ;
- pas de score d'influence humain ;
- droits et visibilité des objets métier restent l'autorité ;
- pagination/lecture finie préférable au défilement infini d'attention ;
- chaque élément doit conduire vers une action ou un objet réel.

### 4.3 Publication depuis le Fil

La doctrine évolue de « pas de publication depuis le Fil » vers :

> **On peut initier une action depuis le Fil, mais une publication structurante doit produire ou utiliser un objet métier réel.**

Exemple : publier rapidement une recherche d'aide crée un vrai **Besoin** ; le Fil ne crée pas un faux post social parallèle.

### 4.4 Recommandation

**Invariant : une recommandation propose ; elle ne décide pas.**

Le matching/recommandation doit rester :

- fondé sur des données métier réelles ;
- compatible avec les consentements et droits ;
- explicable (« pourquoi je vois ceci ? ») ;
- sans score humain affiché ou caché qui réduirait une personne à un nombre ;
- sans avantage de matching acheté par contribution financière.

La recommandation doit progressivement dépasser `personne → personne` et devenir transversale :

- personne ↔ besoin ;
- personne ↔ projet ;
- personne ↔ mission ;
- personne ↔ ZUMRA ;
- projet ↔ capacités/personnes ;
- projet ↔ opportunités/appels ;
- transmission ↔ personnes ayant besoin d'apprendre.

Il ne faut pas créer un algorithme universel opaque. Chaque domaine garde ses règles de compatibilité ; une couche d'orchestration assemble des recommandations explicables.

---

## 5. Boucle structurante V2

```text
CERVEAU PROJET
      ↓
Besoin / capacité manquante
      ↓
Matching / recommandation explicable
      ↓
Fil personnalisé
      ↓
Personne / ZUMRA / opportunité pertinente
      ↓
Action humaine confirmée
      ↓
Équipe / Mission / Transmission
      ↓
Preuve / résultat
      ↓
Fil d'action
      ↓
Nouvelles capacités et nouvelles actions
```

Cette boucle **Projet ↔ Recommandation ↔ Fil ↔ Action** est une structure de référence V2.

---

## 6. Modules candidats — Appels à projets

**Statut : candidat sérieux, doctrine fonctionnelle à formaliser avant implémentation.**

Un **Appel à projets** est une opportunité publiée par un acteur légitime qui recherche, sélectionne ou accompagne des projets selon des critères explicites.

Émetteurs possibles, sans les confondre :

- **institution publique** : État, collectivité, agence, programme public ;
- **partenaire de développement** : ONG, fondation, programme international, organisme d'appui ;
- **entreprise / partenaire privé** : programme d'innovation, RSE, incubation, sous-traitance ou partenariat ;
- **DG Afrique / GAMAD**, uniquement lorsqu'un programme interne réel et financé existe ;
- éventuellement une **organisation habilitée** dans DG Afrique selon une future politique de vérification.

Un appel doit toujours avoir un **porteur identifié**, des critères, une période, des conditions et un processus explicites. DG Afrique ne doit jamais inventer un appel ni laisser croire qu'il finance un appel externe.

Le moteur peut rapprocher un dossier Projet vivant d'un appel selon ses critères réels et expliquer la correspondance. Le Cerveau Projet peut aider à identifier les pièces manquantes et préparer la candidature ; la décision finale appartient au porteur de l'appel.

---

## 7. Modules candidats — Financement

**Statut : domaine transversal candidat. Aucune promesse de financement automatique.**

Le terme **Financement** doit distinguer plusieurs sources, car elles n'ont ni les mêmes droits ni les mêmes obligations.

### 7.1 Fonds communautaire ZUMRA

Il peut être alimenté par les **contributions facultatives** prévues par la doctrine : 500 FCFA/mois pour un membre et 2 500 FCFA/mois pour une ZUMRA validée, ainsi que par d'autres apports explicitement autorisés à l'avenir.

Ce fonds n'est pas une dette due par les membres. Sa gouvernance, son affectation, ses plafonds, sa comptabilité, ses règles d'éligibilité et de transparence doivent être formalisés avant tout mécanisme de financement.

### 7.2 Partenaires et institutions

Un partenaire ou une institution peut financer :

- un appel à projets qu'il porte ;
- un Projet déterminé ;
- un programme thématique ;
- une ZUMRA ou une action collective selon ses critères ;
- de la formation, de l'équipement ou de l'accompagnement.

DG Afrique sert alors d'infrastructure de découverte, dossier, critères, traçabilité et preuves ; il ne devient pas propriétaire de l'argent du partenaire par défaut.

### 7.3 Financement interne de projets

Une politique spéciale de financement interne peut être créée plus tard, mais **elle doit être une politique explicite**, pas une conséquence automatique de l'adhésion ou de la contribution.

Elle devra définir au minimum : origine des fonds, gouvernance, éligibilité, sélection, plafonds, conflits d'intérêts, décaissement, justification, preuves, contrôle et clôture.

Le financement interne pourra s'appuyer sur le dossier Projet vivant et ses preuves, mais **aucun score opaque ne doit décider seul de l'attribution**.

### 7.4 Principe économique V2

Les trois circuits doivent rester séparés :

`ADHÉSION ≠ CONTRIBUTION COMMUNAUTAIRE ≠ FINANCEMENT DE PROJET`

Une adhésion donne un statut d'adhésion selon la doctrine ; elle ne constitue pas automatiquement une cagnotte personnelle.

Une contribution soutient un fonds/objectif défini ; elle n'achète ni priorité, ni influence, ni matching.

Un financement est une décision d'affectation de ressources à une action ou un Projet selon une politique et une autorité identifiées.

---

## 8. Architecture économique cible

```text
                    SOURCES
                      │
     ┌────────────────┼────────────────┐
     ↓                ↓                ↓
Contributions     Partenaires      Institutions
facultatives          │                │
     │                └────────┬───────┘
     ↓                         ↓
Fonds communautaire       Appels / programmes
ZUMRA                         │
     │                         │
     └────────────┬────────────┘
                  ↓
        Politique d'allocation
        + décision humaine
                  ↓
        Projet / ZUMRA / action
                  ↓
             Missions
                  ↓
              Preuves
                  ↓
        Traçabilité / résultat
```

À terme, un **Fonds interne DG Afrique/ZUMRA** peut constituer une quatrième source ou une politique d'allocation spécifique, uniquement lorsqu'il est juridiquement, financièrement et opérationnellement défini.

---

## 9. Frontières non négociables V2

1. Pas de deuxième source de vérité créée par l'IA, le Fil ou la recommandation.
2. Pas de score de valeur humaine.
3. Pas de mutation importante sans autorité/confirmation humaine.
4. Pas de faux Projet, faux besoin, faux appel, faux financement ou fausse preuve.
5. Pas de contribution financière transformée en avantage de matching.
6. Pas d'adhésion ZUMRA confondue avec le compte DG Afrique.
7. Pas de financement présenté comme acquis avant décision de l'autorité qui porte les fonds.
8. Toute recommandation importante doit pouvoir expliquer ses raisons à partir de données réelles.
9. Les outils spécialisés restent au service du réseau et de l'action.
10. La confidentialité et les consentements existants priment toujours sur la commodité du produit.

---

## 10. Décisions figées et questions encore ouvertes

### Décisions figées

- DG Afrique = réseau social d'action ;
- Projet = dossier vivant ;
- Cerveau Projet = orchestrateur/conseiller, non autorité métier ;
- Fil = système circulatoire de l'action ;
- Recommandation = orientation explicable, non décision ;
- boucle Projet ↔ Recommandation ↔ Fil ↔ Action ;
- satellites = outils spécialisés contextuels ;
- séparation compte / adhésion / contribution / financement ;
- Appels à projets et Financement sont des modules candidats cohérents avec l'architecture V2.

### À formaliser avant implémentation des nouveaux modules

- rôles habilités à publier un appel ;
- vérification des organisations/partenaires ;
- workflow de candidature et décision ;
- gouvernance du fonds communautaire ;
- politique éventuelle de financement interne ;
- comptabilité, décaissement, contrôle et transparence ;
- cadre juridique et réglementaire applicable aux flux financiers.

---

## 11. Règle d'évolution

Toute future CAP, fonctionnalité ou refonte touchant Projet, ZUMRA, Fil, recommandation, appels ou financement doit être confrontée à ce document.

Si une proposition contredit un invariant V2, la contradiction doit être explicitement arbitrée et documentée avant implémentation ; elle ne doit pas entrer silencieusement dans le produit.
