# CAP-007 — PROGRAMME ZUMRA

## Décision de reconstruction

CAP-007 est reconstruit en deux lots :

- **CAP-007A** : dossier, charte, états et administration ;
- **CAP-007B** : paiement réel, confirmation, reçu et activation.

CAP-007A prépare le dossier sans encaisser. CAP-007B ajoute le registre de
paiement live, la confirmation serveur, le reçu et l'activation atomique.

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
| `ACTIVE` | paiement d'adhésion attesté par vérification serveur |
| `SUSPENDED` | accès limité, historique conservé |
| `CLOSED` | dossier fermé, historique conservé |

Le seul chemin applicatif vers `ACTIVE` appartient au rapprochement CAP-007B.
Le retour navigateur n'est jamais une preuve.

## Administration

Sans déploiement, un administrateur autorisé peut :

- configurer les textes du parcours ;
- publier une nouvelle version de charte ;
- consulter l'historique des versions.

La disponibilité du paiement et ses secrets sont des réglages d'exploitation,
jamais des réglages éditoriaux. Le prix canonique n'est pas administrable.

## Critères de preuve

1. aucune charte publiée : aucun dossier possible ;
2. acceptation explicite : dossier `PENDING_PAYMENT`, version et empreinte conservées ;
3. aucun débit, reçu ou activation inventé ;
4. une charte retirée ne peut être acceptée ;
5. les versions antérieures restent en base ;
6. chaque dossier est isolé par identité canonique.
7. un état distant non final ne peut pas activer l'adhésion ;
8. une confirmation répétée ne crée qu'un événement et un reçu ;
9. le reçu n'est lisible que par son identité canonique.
