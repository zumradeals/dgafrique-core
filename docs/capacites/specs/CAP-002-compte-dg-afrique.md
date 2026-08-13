# CAP-002 — Compte DG Afrique

## Statut

**EN DÉVELOPPEMENT — GATE ACTIF**

CAP-001 est validé en préproduction. CAP-003 à CAP-084 restent bloqués.

## Finalité

Donner à une personne une porte d'accès personnelle, gratuite et sécurisée à
DG Afrique, adossée à son Compte GAMAD sans dupliquer son identité ni son moyen
d'accès dans PostgreSQL.

## Invariants

1. Compte DG Afrique gratuit ≠ adhésion au Programme ZUMRA.
2. GAMAD Core authentifie, atteste l'échéance et révoque la session.
3. DG Afrique ne conserve jamais le mot de passe.
4. Le bearer Core est chiffré avant stockage dans la session serveur.
5. L'identifiant de session Laravel est régénéré après connexion.
6. Un vrai `401` détruit la session portail.
7. Une panne Core transitoire retourne `503` et préserve la session.
8. `next` n'accepte qu'une destination locale commençant par un seul `/`.
9. La déconnexion tente la révocation Core puis détruit toujours la session locale.
10. La session locale ne porte aucun rôle métier ni adhésion implicite.

## Lots

### CAP-002A — Accès d'un Compte GAMAD existant

- écran de connexion fidèle au handoff Claude ;
- authentification e-mail, téléphone ou référence canonique ;
- session serveur Redis chiffrée ;
- garde de `Mon espace` par attestation `/sessions/current` ;
- page `503` non destructive ;
- retour local sûr ;
- déconnexion centrale ;
- tableau de bord minimal, sans simuler CAP-003 ou ZUMRA.

### CAP-002B — Création et vérification gratuite

À spécifier après inspection des contrats Core d'inscription et de vérification.
Aucun faux formulaire local ne sera créé. WhatsApp, oubli du mot de passe et
création restent explicitement « bientôt » tant que leurs contrats ne sont pas
livrés et éprouvés.

## Stockage de session

La session de production utilise Redis. Le cookie navigateur ne contient que
l'identifiant opaque de session Laravel, avec `HttpOnly`, `Secure` et
`SameSite=Lax`. Les données du store sont chiffrées par Laravel et le bearer est
en plus chiffré explicitement par `PortalMemberSession`.

Durée locale maximale : 480 minutes d'inactivité. À chaque entrée protégée,
Core restitue son `expire_le` attesté ; DG Afrique n'invente aucune prolongation
Core. Le raccordement exact du cookie à cette échéance sera éprouvé avant la
validation finale de CAP-002.

## Critères CAP-002A

- connexion réelle avec un Compte GAMAD existant ;
- accès à `/espace` et redirection d'un visiteur vers `/connexion` ;
- absence du mot de passe dans session, flash, logs et base ;
- bearer absent du cookie et chiffré dans le store serveur ;
- tests `401` destructif et `503` conservateur ;
- rejet d'une redirection externe ;
- révocation Core et session locale détruite à la déconnexion ;
- interface desktop et mobile validée en préproduction.

## Hors périmètre

- profil de capacités : CAP-003 ;
- adhésion et contribution ZUMRA : capacités ZUMRA ;
- ouverture d'un satellite : capacités de fédération ;
- mot de passe oublié et WhatsApp sans contrat Core confirmé.
