> **Archive du portail précédent.** À réauditer avant toute implémentation Laravel. Ce fichier ne valide aucun CAP dans `dgafrique-core`.

# CAP-061 — CONTRIBUTIONS FINANCIÈRES

- **Domaine :** ZUMRA / Finance
- **Propriétaire d'exécution :** HYBRID
- **Statut :** adhésion initiale implémentée ; contribution mensuelle / fonds à spécifier puis développer

## Flux séparés

| Purpose | Nature | Effet |
|---|---|---|
| `membership` | paiement initial | peut activer l'adhésion ZUMRA |
| `monthly_contribution` | contribution périodique | alimente la capacité financière communautaire |
| `project_financing` | financement ciblé | finance un projet selon un mécanisme explicite |
| service / formation / satellite | paiement commercial ou opérationnel | reste séparé |

## Objectif stratégique de la contribution mensuelle

La contribution mensuelle est un mécanisme d'autonomie collective : elle permet à la communauté d'amorcer des projets avec ses propres moyens **avant l'arrivée éventuelle de partenaires externes**.

Le produit doit donc rendre visible la chaîne :

`membres → contributions → capacité collective → décisions/engagements → projets soutenus → décaissements → preuves/résultats → partenaires éventuels`

## Transparence attendue

Selon les permissions et la gouvernance définie :

- situation individuelle de contribution ;
- total collecté sur une période ;
- montant disponible / engagé / décaissé ;
- projets bénéficiaires ;
- historique et purpose de chaque mouvement ;
- preuves ou jalons associés aux dépenses ;
- résultat concret produit par le financement communautaire.

## Garde-fous

- contribution ≠ investissement ;
- aucune promesse de rendement ;
- montant plus élevé ≠ rang ou visibilité ;
- ne pas inventer automatiquement une suspension d'adhésion en cas de `late`.

## Critères d'acceptation

```gherkin
GIVEN un membre ZUMRA actif
WHEN une contribution mensuelle est confirmée
THEN elle est enregistrée avec purpose monthly_contribution et n'altère pas l'historique du paiement initial d'adhésion

GIVEN des contributions communautaires affectées à un projet
WHEN un membre consulte la transparence autorisée
THEN le système peut relier collecte, engagement, décaissement, projet et preuve sans présenter une promesse de rendement
```
