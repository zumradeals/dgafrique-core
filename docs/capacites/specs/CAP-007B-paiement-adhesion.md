# CAP-007B — Paiement réel de l’adhésion ZUMRA

## Invariants

- Prix canonique : **500 FCFA (XOF), une seule fois**.
- L’adhésion et les contributions mensuelles sont deux objets financiers distincts.
- Une redirection, un paramètre d’URL ou une réponse du navigateur ne peut jamais activer une adhésion.
- Seule une lecture serveur-à-serveur auprès du prestataire, cohérente sur la référence, le montant, la devise, l’objet et l’environnement `live`, peut produire `COMPLETED`.
- La transition `PENDING_PAYMENT → ACTIVE`, son événement et le reçu sont atomiques et idempotents.
- Un reçu appartient exclusivement à la référence d’identité canonique qui a payé.
- Les données de carte, codes Mobile Money, clés et secrets ne sont jamais stockés par DG Afrique.

## Registres

`dg_zumra_payments` conserve la tentative, la référence prestataire, l’état vérifié et une empreinte du snapshot minimal. `dg_zumra_payment_receipts` conserve une preuve immuable, numérotée et munie d’une empreinte d’intégrité.

## Activation opérationnelle

Le bouton réel reste fermé tant que `ZUMRA_PAYMENT_ENABLED=false`. L’ouverture en production exige les identifiants GeniusPay live, une création et une lecture de paiement validées sur le compte marchand, puis une preuve de bout en bout de faible montant. Aucun mode sandbox ne peut activer le lot réel.
