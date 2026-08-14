# CAP-005 — Apprentissage

- **Domaine :** Learning
- **Propriétaire d'exécution :** DG
- **Statut :** implémenté — preuve préproduction requise
- **Sources :** doctrine ZUMRA v1.1, CAP-003, CAP-004, CAP-006

## Finalité

Représenter ce qu'une personne souhaite apprendre comme une intention de capacité structurée, afin de préparer l'orientation et le matching explicable.

## Invariants

1. Une intention d'apprentissage est distincte d'une capacité possédée.
2. Elle ne signifie ni incapacité ni infériorité.
3. Elle est privée par défaut et n'entre dans le matching qu'avec consentement.
4. Sa suppression d'interface produit un archivage réversible.
5. Aucun parcours, formateur ou résultat n'est promis automatiquement.

## Objet

Une déclaration `dg_capability_statements` de nature `LEARNING`, créée depuis la section d'apprentissage du profil et rattachée uniquement à la référence de session.

## Critère d'acceptation

```gherkin
GIVEN un objectif d'apprentissage saisi dans le profil
WHEN le membre consent aux orientations
THEN une déclaration LEARNING privée autorisée pour le matching est disponible
```
