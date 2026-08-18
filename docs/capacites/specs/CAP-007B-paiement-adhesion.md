# CAP-007B — Paiement réel de l’adhésion ZUMRA

## Invariants

- Prix canonique : **500 FCFA (XOF), une seule fois**.
- L’adhésion et les contributions mensuelles sont deux objets financiers distincts.
- Une redirection, un paramètre d’URL ou une réponse du navigateur ne peut jamais activer une adhésion.
- Seule une lecture serveur-à-serveur auprès du prestataire, cohérente sur la référence, le montant, la devise, l’objet et l’environnement (identique à celui de la tentative d’origine), peut produire `COMPLETED`.
- La transition `PENDING_PAYMENT → ACTIVE`, son événement et le reçu sont atomiques et idempotents.
- Un reçu appartient exclusivement à la référence d’identité canonique qui a payé.
- Les données de carte, codes Mobile Money, clés et secrets ne sont jamais stockés par DG Afrique.

## Registres

`dg_zumra_payments` conserve la tentative, la référence prestataire, l’état vérifié et une empreinte du snapshot minimal. `dg_zumra_payment_receipts` conserve une preuve immuable, numérotée et munie d’une empreinte d’intégrité.

## Activation opérationnelle

Le bouton réel reste fermé tant que `ZUMRA_PAYMENT_ENABLED=false`. L’ouverture en production exige les identifiants GeniusPay live, une création et une lecture de paiement validées sur le compte marchand, puis une preuve de bout en bout de faible montant.

## Décision — sandbox et activation (2026-08-18)

Point tranché par revue humaine après conflit documenté entre « aucun mode sandbox ne peut activer
le lot réel » (version initiale de cette fiche) et le besoin réel d'exercer le parcours complet
(paiement → adhésion active → reste de la plateforme) avant un vrai lancement.

**Décision validée :** un paiement `sandbox` peut activer une adhésion, mais uniquement si
l'interrupteur dédié `GENIUSPAY_SANDBOX_ACTIVATION_ALLOWED` (`payments.geniuspay.sandbox_activation_allowed`)
est explicitement activé — **jamais déduit de `APP_ENV`**, qui peut légitimement valoir
`production` sur le domaine réel (`dgafrique.com`) pendant une phase de test. Off par défaut
partout. À désactiver explicitement dès que de vrais membres commencent à payer pour de vrai —
ce n'est pas automatique, c'est un geste opérationnel conscient à chaque bascule.

Garanties inchangées, y compris en sandbox : prix canonique 500 FCFA, environnement de la
réconciliation identique à celui de la tentative d'origine (un paiement amorcé en sandbox ne peut
jamais se réconcilier en `live` et inversement), transition atomique et idempotente, reçu
strictement lié à la référence d'identité canonique.

## Correctif — statut absent à la création sandbox (2026-08-18)

Diagnostiqué en conditions réelles : `POST /payments` sur le sandbox GeniusPay peut répondre
`HTTP 201`, `success=true`, référence/montant/environnement/checkout valides, mais `status=null`.
`GeniusPayClient::normalize()` accepte désormais ce cas **uniquement à la création**
(`createMembershipPayment()`, `allowMissingInitialStatus=true`) : un statut absent ou vide sur une
réponse par ailleurs valide (référence non vide, montant valide, environnement `live`/`sandbox`,
`checkout_url` HTTPS) est normalisé en `PENDING`. **Jamais** à la lecture/réconciliation
(`payment()`) : un statut manquant y reste toujours `PAYMENT_PROVIDER_RESPONSE_INVALID`. Un statut
absent n'est jamais transformé en `COMPLETED`, à la création comme à la réconciliation.
