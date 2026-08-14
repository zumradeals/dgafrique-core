# CAP-006 — Transmission

- **Domaine :** Learning
- **Propriétaire d'exécution :** DG
- **Statut :** implémenté — preuve préproduction requise
- **Sources :** doctrine ZUMRA v1.1, CAP-003, CAP-004, CAP-005

## Finalité

Représenter ce qu'une personne accepte de transmettre comme une offre de connaissance distincte de la simple possession d'une capacité.

## Invariants

1. Savoir faire ne signifie pas automatiquement accepter d'enseigner.
2. Proposer de transmettre ne prouve pas un niveau ni une qualité pédagogique.
3. Une transmission est privée par défaut et utilisable pour l'orientation seulement avec consentement.
4. Aucun paiement, rang ou visibilité ne transforme une déclaration en attestation.
5. L'archivage conserve l'historique.

## Objet

Une déclaration `dg_capability_statements` de nature `TRANSMISSION`, synchronisée depuis la section correspondante du profil.

## Critère d'acceptation

```gherkin
GIVEN une connaissance proposée en transmission
WHEN le profil est enregistré
THEN elle reste distincte des capacités possédées et des apprentissages recherchés
```
