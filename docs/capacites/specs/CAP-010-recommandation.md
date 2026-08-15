# CAP-010 — Recommandation

- **Domaine :** Discovery & Matching
- **Propriétaire d’exécution :** DG Afrique
- **Statut :** implémenté — preuve VPS requise
- **Sources :** doctrine ZUMRA v1.1, CAP-003 à CAP-006, CAP-009, CAP-029 à CAP-031

## Finalité

Proposer à un membre des rapprochements utiles à partir de son profil et de
profils volontairement découvrables, en donnant pour chaque suggestion des
raisons compréhensibles. Une recommandation propose ; elle ne décide pas.

Le premier périmètre réel est `personne → personne`. Les recommandations vers
une ZUMRA particulière, une formation, un projet ou une opportunité seront
ouvertes lorsque leurs objets canoniques existeront dans leurs CAP respectifs.

## Correspondances permises dans ce lot

1. objectif d’apprentissage du membre ↔ transmission proposée par une personne ;
2. transmission proposée par le membre ↔ apprentissage recherché par une personne ;
3. capacité actuelle partagée ;
4. domaine d’intérêt partagé ;
5. ville commune et mode de participation commun uniquement comme raisons de
   contexte ajoutées à une correspondance principale.

## Explicabilité

Chaque carte affiche des phrases factuelles issues des données consenties, par
exemple : « Vous souhaitez apprendre la couture et cette personne propose de la
transmettre. » Aucun nombre de compatibilité n’est affiché ou persisté.

L’ordre interne privilégie le type de correspondance et le nombre de raisons
disponibles. Il mesure la pertinence d’une piste dans un contexte donné, jamais
la qualité, la dignité, la moralité ou la valeur globale de la personne.

## Consentement et contrôle

- le membre doit avoir consenti aux orientations ;
- la personne proposée doit avoir consenti aux orientations et à la découverte ;
- seules ses déclarations `DISCOVERABLE` sont comparées et affichées ;
- le membre peut masquer puis restaurer une suggestion ;
- une décision de masquage appartient uniquement à son identité ;
- retirer un consentement ou archiver une capacité agit immédiatement sur les
  calculs suivants.

## Administration

Sans déploiement, l’administration peut configurer les textes, les bornes du
moteur et activer ou désactiver chaque famille de raisons. Une configuration ne
peut pas autoriser un score humain, contourner un consentement ou publier une
donnée privée.

## Invariants

1. aucun profil fictif ou résultat inventé ;
2. aucune recommandation sans consentement du destinataire ;
3. aucun téléphone, preuve privée ou référence d’identité dans les cartes ;
4. aucune nomination, adhésion, relation ou prise de contact automatique ;
5. aucune contribution financière utilisée dans la correspondance ;
6. calcul borné, limité en débit et sans décision opaque persistée ;
7. toutes les raisons visibles sont dérivables des données disponibles ;
8. la séparation entre Compte DG Afrique et Programme ZUMRA demeure intacte.

## Critères de preuve

- complément apprentissage/transmission correctement expliqué ;
- profil sans correspondance principale absent ;
- absence de calcul sans consentement ;
- masquage isolé par identité et restauration effective ;
- retrait de visibilité immédiatement respecté ;
- familles de raisons et bornes administrables ;
- tests ciblés, non-régression, migration et compilation verts sur VPS.
