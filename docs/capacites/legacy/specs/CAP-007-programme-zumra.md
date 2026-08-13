> **Archive du portail précédent.** À réauditer avant toute implémentation Laravel. Ce fichier ne valide aucun CAP dans `dgafrique-core`.

# CAP-007 — PROGRAMME ZUMRA

- **Domaine :** ZUMRA
- **Propriétaire d'exécution :** HYBRID — identité Core, métier ZUMRA dans DG/Supabase, paiement fournisseur
- **Statut :** adhésion initiale implémentée ; expérience complète à consolider

## Parcours canonique

`Compte DG Afrique → dossier ZUMRA → charte → pending_payment → paiement initial GeniusPay → active → Carte ZUMRA → réseau / groupes / projets`

## Invariants

- Le paiement initial d'adhésion et la contribution mensuelle sont deux flux différents.
- Seul un paiement `purpose=membership` confirmé peut activer une adhésion `pending_payment`.
- La Carte ZUMRA représente l'adhésion ; ce n'est ni un wallet ni une carte bancaire.
- Une personne peut utiliser DG Afrique sans être membre ZUMRA.
- Après activation, la contribution mensuelle devient un axe de participation communautaire, selon les règles définies par CAP-061.

## États membres existants

`pending_payment | active | suspended | closed`

## États contribution existants

`not_started | up_to_date | grace | late`

## Critères d'acceptation

```gherkin
GIVEN une adhésion pending_payment
WHEN GeniusPay confirme le paiement membership comme completed
THEN l'adhésion devient active et member_since est renseigné

GIVEN une contribution mensuelle completed
WHEN l'adhésion est pending_payment
THEN cette contribution ne peut pas activer l'adhésion
```
