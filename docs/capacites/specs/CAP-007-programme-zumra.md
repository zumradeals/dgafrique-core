# CAP-007 — PROGRAMME ZUMRA

## Décision de reconstruction

CAP-007 est reconstruit en deux lots :

- **CAP-007A** : dossier, charte, états et administration ;
- **CAP-007B** : paiement réel, confirmation, reçu et activation.

Le présent lot n'encaisse rien et n'active aucune adhésion.

## Invariants

- le Compte DG Afrique reste utilisable sans adhésion au Programme ZUMRA ;
- l'adhésion coûte 500 FCFA ou 1 USD, une seule fois et reste historiquement acquise ;
- les contributions mensuelles individuelles et collectives sont facultatives ;
- une contribution ne crée ni dette, ni score, ni supériorité, ni adhésion ;
- une adhésion ne rattache automatiquement le membre à aucune ZUMRA ;
- seule une confirmation de paiement `membership` pourra activer un dossier dans CAP-007B ;
- une charte acceptée est identifiée par sa version et son empreinte ;
- une version publiée n'est jamais modifiée ni supprimée ;
- tout changement d'état produit un événement attribué et daté.

## États

| État | Signification |
|---|---|
| `PENDING_PAYMENT` | dossier et charte valides, aucun paiement confirmé |
| `ACTIVE` | réservé à CAP-007B après paiement attesté |
| `SUSPENDED` | accès limité, historique conservé |
| `CLOSED` | dossier fermé, historique conservé |

CAP-007A ne possède aucun chemin applicatif vers `ACTIVE`.

## Administration

Sans déploiement, un administrateur autorisé peut :

- configurer les textes du parcours ;
- publier une nouvelle version de charte ;
- consulter l'historique des versions.

La disponibilité du paiement demeure désactivée dans CAP-007A. Les montants
canoniques ne sont pas un réglage éditorial ordinaire.

## Critères de preuve

1. aucune charte publiée : aucun dossier possible ;
2. acceptation explicite : dossier `PENDING_PAYMENT`, version et empreinte conservées ;
3. aucun débit, reçu ou activation inventé ;
4. une charte retirée ne peut être acceptée ;
5. les versions antérieures restent en base ;
6. chaque dossier est isolé par identité canonique.
