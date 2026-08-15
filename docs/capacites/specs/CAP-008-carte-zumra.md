# CAP-008 — Carte ZUMRA

## Finalité

Donner une représentation vérifiable de l'adhésion au Programme ZUMRA. La
carte est une attestation d'identification communautaire ; elle n'est ni une
carte bancaire, ni un wallet, ni un instrument financier.

## Délivrance

- une carte ne peut être délivrée qu'à une adhésion `ACTIVE` et datée ;
- une adhésion ne possède qu'une carte active courante ; les anciennes cartes
  révoquées restent conservées ;
- une consultation répétée ne produit ni doublon ni nouvel événement ;
- la carte reste associée à l'identité canonique du membre ;
- une suspension conserve la carte et son histoire mais retire immédiatement
  toute présentation comme active ;
- une révocation administrative exige un motif, conserve le registre et rend
  l'état révoqué vérifiable ; si l'adhésion reste active, une nouvelle carte
  distincte peut ensuite être délivrée.

## Données

La face membre contient : nom attesté au moment de la délivrance, référence
membre, pays déclaré s'il existe, date d'adhésion, état et référence publique
de carte. L'appartenance à une ZUMRA particulière n'est affichée que lorsqu'un
contrat réel existera.

La vérification publique exclut téléphone, capacités, besoins, consentements,
paiements, contributions et données financières.

## Vérification

Le QR est généré localement dans l'interface et encode une URL DG Afrique
signée. Une URL absente, altérée ou reconstruite est rejetée. La page consulte
l'adhésion au moment de la vérification : `ACTIVE`, `SUSPENDED`, `CLOSED` et
`REVOKED` ne peuvent donc pas être confondus.

Une version physique pourra reprendre la même référence et le même contrat de
vérification dans un lot ultérieur.
