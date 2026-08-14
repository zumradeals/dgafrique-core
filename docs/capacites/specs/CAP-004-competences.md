# CAP-004 — Compétences

- **Domaine :** Profiles & Capabilities
- **Propriétaire d'exécution :** DG
- **Statut :** implémenté — preuve préproduction requise
- **Sources :** doctrine ZUMRA v1.1, CAP-003, CAP-023, CAP-024, CAP-036

## Finalité

Transformer les savoir-faire libres du profil en déclarations de capacités distinctes, interrogeables et historisées, sans créer de score humain.

## Invariants

1. Une déclaration appartient à la référence de session, jamais à une référence envoyée par le navigateur.
2. Une capacité déclarée n'est ni vérifiée ni attestée.
3. La visibilité est privée par défaut.
4. Le consentement au matching ne rend pas la déclaration publique.
5. Le retrait du profil archive la déclaration ; il ne détruit pas son histoire.
6. Une réintroduction réactive la même déclaration normalisée.
7. Le catalogue administrable peut être vide : aucune nomenclature fictive n'est semée.

## Objets et données

- `dg_capability_catalog` : futur référentiel gouverné et administrable ;
- `dg_capability_statements` : identité, nature, libellé, forme normalisée, niveau facultatif, statut, visibilité, consentement, source et dates ;
- nature CAP-004 : `POSSESSED` ;
- statuts autorisés par le socle : `DECLARED`, `VERIFIED`, `ATTESTED` ;
- l'archivage est une date indépendante du statut d'attestation.

## Critères d'acceptation

```gherkin
GIVEN un membre qui enregistre deux savoir-faire
WHEN son profil est sauvegardé
THEN deux déclarations POSSESSED privées et DECLARED sont synchronisées

GIVEN une déclaration retirée puis réintroduite
WHEN le profil est sauvegardé à chaque étape
THEN la déclaration est archivée puis réactivée sans doublon
```

## Garde-fous

- aucune auto-attestation ;
- aucun niveau déduit du diplôme, de l'âge ou du paiement ;
- aucune exposition publique avant un consentement de visibilité distinct.
