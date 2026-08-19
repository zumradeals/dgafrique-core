# DG Afrique — Architecture Produit V2

**Statut :** DOCTRINE PRODUIT FIGÉE — Audits 01 à 04  
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

Il peut comprendre une expression humaine, retrouver le contexte réel du Projet, signaler ce qui manque ou ce qui pourrait faire avancer, proposer une action, préparer une action structurée et expliquer pourquoi il recommande quelque chose.

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

Les satellites sont requalifiés comme **outils spécialisés contextuels**. Un Projet peut en avoir besoin selon sa nature et sa maturité. Les outils spécialisés peuvent produire de nouvelles capacités pour le réseau sans devenir des dépendances obligatoires du Core.

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

> **On peut initier une action depuis le Fil, mais une publication structurante doit produire ou utiliser un objet métier réel.**

Exemple : publier rapidement une recherche d'aide crée un vrai **Besoin** ; le Fil ne crée pas un faux post social parallèle.

### 4.4 Recommandation

**Invariant : une recommandation propose ; elle ne décide pas.**

Le matching/recommandation doit rester fondé sur des données métier réelles, compatible avec les consentements et droits, explicable, sans score de valeur humaine et sans avantage de matching acheté par contribution financière.

La recommandation doit progressivement devenir transversale : personne ↔ besoin, personne ↔ projet, personne ↔ mission, personne ↔ ZUMRA, projet ↔ capacités/personnes, projet ↔ opportunités/appels, transmission ↔ personnes ayant besoin d'apprendre.

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

Émetteurs possibles : institution publique ; partenaire de développement ; entreprise/partenaire privé ; DG Afrique/GAMAD lorsqu'un programme interne réel existe ; éventuellement une organisation habilitée selon une future politique de vérification.

Un appel doit toujours avoir un porteur identifié, des critères, une période, des conditions et un processus explicites. DG Afrique ne doit jamais inventer un appel ni laisser croire qu'il finance un appel externe.

Le moteur peut rapprocher un dossier Projet vivant d'un appel selon ses critères réels et expliquer la correspondance. Le Cerveau Projet peut aider à identifier les pièces manquantes et préparer la candidature ; la décision finale appartient au porteur de l'appel.

---

## 7. Audit 04 — Économie, contributions, fonds communautaire et financement

### 7.1 Séparation des flux

Les flux économiques ne doivent jamais être confondus :

`ADHÉSION ≠ CONTRIBUTION COMMUNAUTAIRE ≠ APPORT DU PORTEUR ≠ FINANCEMENT ≠ PAIEMENT COMMERCIAL`

L'adhésion donne un statut. La contribution facultative alimente une capacité collective selon sa finalité. L'apport démontre la participation du porteur à un Projet déterminé. Le financement est une décision d'allocation. Le paiement commercial règle un bien ou service.

### 7.2 Fonds communautaire ZUMRA

Le fonds communautaire peut être alimenté notamment par les contributions facultatives prévues par la doctrine, ainsi que par d'autres ressources explicitement autorisées.

Le fonds communautaire du Programme ZUMRA ne doit pas être confondu avec les ressources propres d'une ZUMRA locale. Une ZUMRA peut disposer de ressources propres et participer au financement de ses actions.

Sa gouvernance, son affectation, ses plafonds, sa comptabilité, ses règles d'éligibilité, de contrôle et de transparence doivent être formalisés avant tout mécanisme réel d'allocation.

### 7.3 Apport du porteur et cofinancement

Un programme de financement peut demander au porteur du Projet — notamment une ZUMRA — un **apport explicite**, par exemple un pourcentage du besoin total.

Cet apport n'est pas une contribution mensuelle et n'est pas une dette communautaire. Il appartient au plan de financement d'un Projet précis.

L'apport peut être monétaire ou, lorsque la politique du programme le permet et que sa valorisation est vérifiable, prendre la forme de ressources réelles : terrain, local, matériel, travail, véhicule, équipements ou autres actifs utiles au Projet.

Exemple de structure :

`ZUMRA 30 % + Fonds communautaire 40 % + Partenaire 30 % = financement du Projet`

Les pourcentages ne sont jamais universels : ils relèvent de la politique explicite du programme ou de la décision de financement.

### 7.4 Chaîne financière canonique

```text
CONTRIBUTION / APPORT / RESSOURCE PARTENAIRE
                    ↓
                  FONDS
                    ↓
             PLAN / DEMANDE
                    ↓
                DÉCISION
                    ↓
               ALLOCATION
                    ↓
              DÉCAISSEMENT
                    ↓
          DÉPENSE / UTILISATION
                    ↓
           MISSION / JALON
                    ↓
                 PREUVE
                    ↓
                RÉSULTAT
```

Ces étapes doivent rester distinguables et auditables.

### 7.5 Qui finance ?

Plusieurs sources peuvent coexister sans perdre leur identité :

- fonds communautaire ZUMRA ;
- ressources propres de la ZUMRA ou du porteur ;
- partenaire privé ;
- ONG/fondation/programme de développement ;
- institution publique ;
- futur fonds interne DG Afrique/GAMAD explicitement gouverné.

Le ledger doit conserver l'origine des ressources même lorsqu'elles cofinancent un même Projet.

### 7.6 Qui demande ?

Le **Projet** est l'objet principal financé. Son porteur peut être une personne, une ZUMRA ou, à terme, une organisation admissible.

Une ZUMRA n'est pas financée abstraitement parce qu'elle existe : elle porte ou soutient une action/projet identifié, sauf programme institutionnel explicitement conçu autrement.

### 7.7 Décision et décaissement

Aucun moteur automatique ou IA ne doit décider seul d'une allocation financière. Le système peut vérifier l'éligibilité, préparer le dossier, exposer les faits et signaler les pièces manquantes ; l'autorité humaine compétente décide.

Le financement peut être décaissé par tranches liées à des jalons, missions, justificatifs ou preuves plutôt qu'en totalité par défaut.

### 7.8 Appel à projets ≠ financement

Un appel à projets est une **porte d'opportunité**. Un financement est un **mécanisme d'allocation de ressources**.

Un appel peut proposer financement, accompagnement, formation, équipement ou partenariat. Un financement peut aussi être accordé sans appel public, par exemple à la suite d'une demande au fonds communautaire selon une politique interne.

---

## 8. Wallet transversal, GAMAD Finance et ZAHAB

### 8.1 Wallet comme infrastructure économique

Le Wallet est une couche transversale potentielle pour :

- adhésions ;
- contributions facultatives ;
- ressources propres ;
- financements et décaissements ;
- paiements commerciaux ;
- règlements via outils spécialisés ;
- historique et traçabilité financière.

Des portefeuilles spécialisés peuvent exister pour une **personne**, une **ZUMRA**, un **Projet**, un **fonds communautaire** ou un **acteur marchand**, mais ils doivent reposer sur un ledger cohérent et auditable.

**Invariant : le solde est une conséquence des écritures du ledger, jamais une valeur décorative ou arbitraire.**

### 8.2 ZAHAB

ZAHAB est conservé comme **couche économique future/expérimentale** de GAMAD Finance.

L'architecture financière doit fonctionner intégralement en FCFA sans dépendre d'une blockchain ou d'un crypto-actif.

Une première représentation de ZAHAB pourrait servir d'unité comptable interne à parité lisible avec le FCFA uniquement si chaque unité affichée correspond à une vérité comptable réelle et si la présentation respecte le cadre juridique applicable.

Aucune promesse de convertibilité, de stablecoin, d'adossement à l'or ou de décentralisation ne doit être faite tant que le mécanisme financier, les réserves, la gouvernance et le cadre réglementaire correspondants ne sont pas réellement établis.

L'objectif architectural est de permettre à ZAHAB de se brancher ultérieurement sur le moteur économique sans obliger à reconstruire Wallet, Ledger, Fonds et Financement.

---

## 9. Outils spécialisés extensibles

### 9.1 Principe

DG Afrique ne doit pas prévoir tous les métiers futurs dans le Core. Les outils spécialisés doivent pouvoir se brancher sur les objets métier et répondre à certains types de besoins sans devenir des dépendances obligatoires.

Exemples :

- **G-POS** : commerce, catalogue, vente, paiement marchand ;
- **Transport & Logistique** : candidat futur pour transport, livraison, acheminement et besoins logistiques ;
- autres outils futurs selon les actions réelles du réseau.

Un Projet doit continuer à fonctionner même si l'outil spécialisé correspondant n'existe pas encore.

### 9.2 Contrat conceptuel

Un outil spécialisé doit pouvoir déclarer, à terme :

- les besoins/actions auxquels il sait répondre ;
- les objets métier qu'il consomme ;
- les actions qu'il peut proposer ;
- les événements/preuves qu'il produit ;
- ses règles d'autorité, consentement et visibilité ;
- ses éventuelles interactions avec Wallet/Ledger.

Le Cerveau Projet et la recommandation peuvent découvrir les capacités de ces outils ; ils ne doivent pas contenir en dur toute leur logique métier.

### 9.3 Exemple G-POS

Une Mission ou un Besoin Projet peut nécessiter du matériel. Un catalogue marchand autorisé dans G-POS peut fournir des offres pertinentes. Après choix humain et paiement autorisé :

`Projet → Besoin matériel → G-POS → Marchand → Wallet/Paiement → Livraison → Preuve → Mission/Projet`

Le commerce devient ainsi un moyen de répondre à une action réelle, pas un marketplace parallèle sans contexte.

---

## 10. Place publique, commerce et sponsorisation

### 10.1 Le Fil comme place publique d'action

Le Fil peut faire converger plusieurs natures de contenu tout en restant une seule surface cohérente :

- activité réelle du réseau ;
- recommandations personnelles explicables ;
- opportunités/appels ;
- offres commerciales pertinentes issues d'acteurs/outils autorisés.

Chaque carte structurante doit renvoyer à un objet réel.

### 10.2 Promotion commerciale

Les acteurs de G-POS ou d'autres outils spécialisés peuvent, à terme, acheter de la visibilité commerciale : vitrine sponsorisée, produit sponsorisé, offre locale sponsorisée ou autre format explicitement défini.

Cette sponsorisation peut devenir une source de revenus du système selon une politique économique future.

### 10.3 Invariant : pertinence ≠ sponsorisation

Une recommandation organique et une promotion payante sont deux signaux distincts.

- **Pertinence** : l'objet est montré parce que des données métier indiquent qu'il peut être utile.
- **Sponsorisation** : un acteur paie pour obtenir une visibilité supplémentaire dans un emplacement commercial autorisé.

Une offre peut être à la fois pertinente et sponsorisée, mais les deux raisons doivent rester distinguables et la nature sponsorisée doit être clairement signalée.

**Interdit :** faire croire qu'une offre est recommandée objectivement parce qu'elle a payé ; permettre au paiement commercial de modifier le matching métier ; laisser une publicité écraser systématiquement les besoins/projets réellement pertinents.

### 10.4 Revenus de sponsorisation

L'affectation future des revenus commerciaux — infrastructure DG Afrique, développement d'outils, fonds communautaire ou autres destinations — doit relever d'une politique financière explicite, versionnée et auditable. Aucun pourcentage n'est figé par ce document.

---

## 11. Architecture économique cible étendue

```text
                         SOURCES
                           │
       ┌───────────────────┼────────────────────┐
       ↓                   ↓                    ↓
Contributions          Partenaires         Institutions
facultatives               │                    │
       │                   └─────────┬──────────┘
       ↓                             ↓
Fonds communautaire             Appels/programmes
       │                             │
       ├───────────┐                 │
       │           ↓                 │
       │     Apport du porteur       │
       │           │                 │
       └───────────┼─────────────────┘
                   ↓
          Plan de financement
                   ↓
          Décision humaine
                   ↓
              Wallet Projet
                   ↓
        ┌──────────┼──────────┐
        ↓          ↓          ↓
      G-POS    Transport    autres outils
        │          │          │
        └──────────┼──────────┘
                   ↓
            Missions/Jalons
                   ↓
                Preuves
                   ↓
               Résultats
                   ↓
              Fil d'action
```

Le ledger conserve l'origine, la destination, l'autorité et la finalité de chaque mouvement.

---

## 12. Frontières non négociables V2

1. Pas de deuxième source de vérité créée par l'IA, le Fil ou la recommandation.
2. Pas de score de valeur humaine.
3. Pas de mutation importante sans autorité/confirmation humaine.
4. Pas de faux Projet, faux besoin, faux appel, faux financement ou fausse preuve.
5. Pas de contribution financière transformée en avantage de matching.
6. Pas d'adhésion ZUMRA confondue avec le compte DG Afrique.
7. Pas de financement présenté comme acquis avant décision de l'autorité qui porte les fonds.
8. Toute recommandation importante doit pouvoir expliquer ses raisons à partir de données réelles.
9. Les outils spécialisés restent au service du réseau et de l'action et ne deviennent pas des dépendances obligatoires du Core.
10. La confidentialité et les consentements existants priment toujours sur la commodité du produit.
11. Le solde d'un Wallet doit être dérivable d'écritures réelles et auditables.
12. Pertinence organique et sponsorisation commerciale restent séparées et explicites.
13. Aucun actif ZAHAB ne doit être présenté comme monnaie, stablecoin, réserve-or ou valeur convertible sans fondement réel et cadre applicable.
14. Contribution, apport du porteur, allocation, décaissement et paiement commercial sont des événements économiques distincts.

---

## 13. Décisions figées et questions encore ouvertes

### Décisions figées

- DG Afrique = réseau social d'action ;
- Projet = dossier vivant ;
- Cerveau Projet = orchestrateur/conseiller, non autorité métier ;
- Fil = système circulatoire et place publique de l'action ;
- Recommandation = orientation explicable, non décision ;
- boucle Projet ↔ Recommandation ↔ Fil ↔ Action ;
- satellites = outils spécialisés contextuels ;
- outils spécialisés conçus comme extensibles ;
- séparation compte / adhésion / contribution / apport / financement / paiement commercial ;
- cofinancement multi-source possible avec origine conservée ;
- Wallet/Ledger = infrastructure économique transversale candidate ;
- ZAHAB = couche économique future/expérimentale, non dépendance actuelle ;
- G-POS = exemple majeur d'intégration outil ↔ Projet ↔ Wallet ↔ Preuve ;
- sponsorisation commerciale possible mais toujours distinguée de la pertinence ;
- Appels à projets et Financement sont des domaines cohérents avec l'architecture V2.

### À formaliser avant implémentation des nouveaux modules

- rôles habilités à publier un appel ;
- vérification des organisations/partenaires ;
- workflow de candidature et décision ;
- gouvernance du fonds communautaire ;
- politique de cofinancement et valorisation des apports non monétaires ;
- politique éventuelle de financement interne ;
- comptabilité, décaissement, contrôle et transparence ;
- contrat technique des outils spécialisés ;
- politique de sponsorisation et affectation de ses revenus ;
- architecture Wallet/Ledger et responsabilités de GAMAD Finance ;
- statut exact de ZAHAB et cadre juridique/réglementaire avant toute utilisation monétaire réelle ;
- cadre juridique et réglementaire applicable à tous les flux financiers.

---

## 14. Règle d'évolution

Toute future CAP, fonctionnalité ou refonte touchant Projet, ZUMRA, Fil, recommandation, appels, financement, Wallet, ZAHAB, sponsorisation ou outils spécialisés doit être confrontée à ce document.

Si une proposition contredit un invariant V2, la contradiction doit être explicitement arbitrée et documentée avant implémentation ; elle ne doit pas entrer silencieusement dans le produit.
