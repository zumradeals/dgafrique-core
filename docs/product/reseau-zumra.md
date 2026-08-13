# Réseau ZUMRA — fondation et direction produit

## Positionnement

ZUMRA est un programme et un réseau social d'action intégré à DG Afrique. Il relie apprentissage, transmission, capacités, groupes humains, projets et capacité financière communautaire.

## Invariants

- Compte DG Afrique gratuit et distinct de l'adhésion ZUMRA.
- L'identité canonique reste GAMAD Core (`IDN-PER-...`).
- L'adhésion ZUMRA est payante et son paiement initial est distinct de la contribution mensuelle.
- Aucun diplôme n'est requis ; une personne peut commencer sans compétence déclarée et avec des objectifs d'apprentissage.
- Une Zumra peut être physique, numérique ou hybride.
- Une Zumra devient active à partir de 5 membres actifs et lorsque les cinq responsabilités fondatrices sont attribuées à cinq personnes distinctes.
- ZUMRA peut avoir fil d'activité, messagerie, commentaires, partage et relations entre membres ; ces fonctions servent l'action et ne créent pas un classement de valeur humaine.

## Adhésion initiale — état réel

Le parcours `dossier → charte → pending_payment → GeniusPay → completed → active` est implémenté en sandbox et a été éprouvé. Le paiement a pour finalité `membership`. La Carte ZUMRA et l'accès réseau sont liés à l'adhésion active.

## Contribution mensuelle — objectif stratégique

La contribution mensuelle individuelle est un flux différent du paiement initial d'adhésion. Les états métier déjà prévus sont : `not_started`, `up_to_date`, `grace`, `late`.

Son objectif premier est de **construire une capacité financière communautaire permettant d'amorcer et de financer des projets de la communauté avant que des partenaires externes s'y intéressent**. Le membre doit progressivement pouvoir voir ce que sa participation rend possible : collecte, fonds disponibles ou engagés, projets soutenus, décaissements, preuves et résultats.

Contribution ≠ investissement. Un montant supérieur ne doit pas acheter de rang, de visibilité ou de pouvoir social ; aucune promesse de rendement individuel n'est implicite. Les conséquences exactes d'un état `late` ne sont pas décidées et ne doivent pas être inventées.

## Moteur de la Zumra

1. Seul un membre ZUMRA actif peut créer ou rejoindre une Zumra.
2. Le créateur devient `Responsable principal`.
3. Les membres sont recrutés par invitations à usage unique.
4. Responsabilités fondatrices : principal, adjoint 1, adjoint 2, finance, affaires sociales.
5. Une Zumra passe de `forming` à `active` avec au moins 5 membres actifs et les 5 responsabilités attribuées.
6. Les événements structurants sont tracés.

## Frontière Core / DG / satellites

GAMAD Core conserve identité, sessions, fédération et primitives transversales. DG Afrique/ZUMRA conserve les données métier du programme : capacités, apprentissages, adhésion, contribution, vie des Zumra, besoins, projets et expérience utilisateur, sous réserve d'affinements par capacité. Les satellites conservent leur propre métier.

## Prochain travail de spécification

- `monthly_contribution` comme purpose distinct ;
- capacité/fonds communautaire et règles d'affectation ;
- traçabilité collecte → engagement → décaissement → projet → preuve ;
- écrans de transparence et d'impact ;
- gouvernance des décisions de financement ;
- règles de grâce/retard ;
- social ZUMRA lié aux besoins, apprentissages et projets.
