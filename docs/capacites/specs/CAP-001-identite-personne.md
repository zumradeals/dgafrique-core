# CAP-001 — Identité personne

## Statut

**VALIDÉ PRÉPRODUCTION — 2026-08-13**

Preuve canonique : `../proofs/CAP-001-2026-08-13.md`.

Cette fiche remplace, pour la reconstruction Laravel, la validation historique archivée dans `legacy/specs/`. Aucun résultat Next.js, Supabase ou Vercel n'est hérité.

## Finalité

Permettre à une personne d'être reconnue durablement dans DG Afrique par son identité canonique GAMAD Core, sans créer une seconde autorité d'identité dans PostgreSQL ou Laravel.

## Responsabilités

### GAMAD Core

- crée et conserve l'identité canonique ;
- authentifie et révoque la session membre ;
- décide l'échéance glissante de la session ;
- résout l'identité par CTR-01.

### DG Afrique

- transporte la session Core dans une enveloppe portail sécurisée à livrer par CAP-002 ;
- relit l'échéance attestée par Core, sans la prolonger lui-même ;
- consomme l'identité canonique comme point d'attache des données métier ;
- conserve profils, contenus, ZUMRA, projets et préférences dans ses propres modules.

## Contrat Core inspecté

Base versionnée : `${GAMAD_CORE_BASE_URL}` doit se terminer par `/api/v1`.

### Session courante

`GET /sessions/current`, avec bearer membre :

```json
{
  "entite": "PER-GAMAD-000000001",
  "assurance": "A2",
  "expire_le": "2026-08-14T02:00:00+00:00"
}
```

DG Afrique reflète `expire_le` et ne fabrique jamais une échéance supérieure.

### Résolution d'identité

`GET /identites/{reference}`, avec le même bearer membre :

```json
{
  "reference": "PER-GAMAD-000000001",
  "type": "PERSONNE",
  "libelle": "Membre DG Afrique",
  "etat": "ACTIF",
  "date_effet": "2026-08-13",
  "source": "SRC-GAMAD-001",
  "regime": "INSCRIT"
}
```

La référence retournée doit être strictement identique à celle demandée.

## Sémantique des échecs

| Réponse | Interprétation DG Afrique | Effet attendu CAP-002 |
|---|---|---|
| `401` | session absente, invalide, expirée ou révoquée | supprimer l'enveloppe portail |
| `404` | identité canonique introuvable | ne jamais créer automatiquement une identité locale |
| `429`, `5xx`, connexion impossible | indisponibilité transitoire | conserver la session, retourner un état réessayable |
| autre réponse ou document incohérent | rupture de contrat | fermer le traitement et alerter sans exposer le bearer |

## Invariants

1. Aucune table `users`, aucun modèle `App\\Models\\User` et aucun fournisseur Eloquent membre.
2. Aucun mot de passe, secret ou bearer n'est persisté comme identifiant métier.
3. Les tables métier futures référencent la chaîne canonique Core.
4. Une panne temporaire ne signifie ni suppression ni révocation d'identité.
5. Les appels portent un identifiant de corrélation, des timeouts et aucun retry implicite.
6. Les réponses Core sont validées avant de devenir des objets du domaine.

## Livrables du lot fondation

- `CoreIdentity` et `CoreSession`, objets immuables ;
- `GamadCoreClient` pour session courante et résolution CTR-01 ;
- exceptions distinctes pour refus, absence, panne et rupture de contrat ;
- configuration sans secret versionné ;
- suppression du squelette d'identité Laravel ;
- tests du contrat et garde structurelle.

## Hors périmètre

- connexion membre, cookie HttpOnly signé et déconnexion : CAP-002 ;
- création/provisionnement d'une identité Core ;
- profil métier : CAP-003 ;
- fédération vers un satellite : capacités satellites dédiées ;
- migration des anciennes données Supabase.

## Critères de sortie du gate

- [x] tests automatisés verts : 14 tests, 28 assertions ;
- [x] vérification réelle contre Core en préproduction ;
- [x] sémantique `401`, `404`, `429`, `5xx` couverte par tests typés ;
- [x] garde structurelle : aucune table, modèle ou provider membre local ;
- [x] session de preuve révoquée même en cas d'échec de résolution ;
- [x] validation du dirigeant avant passage à CAP-002.

La destruction du cookie portail sur `401` et sa conservation sur panne
transitoire appartiennent à CAP-002, car CAP-001 ne crée volontairement encore
aucune session membre DG Afrique.

## Commande de preuve préproduction

```bash
php8.4 artisan dg:core:prouver-identite PER-GAMAD-XXXXXXXXX
```

La commande demande le moyen d'accès dans une saisie invisible. Elle n'affiche
ni le secret ni le bearer, vérifie la session attestée, résout CTR-01 et révoque
la session de test avant de restituer la preuve lisible.

## Décision

CAP-001 est fermé en préproduction. CAP-002 — Compte DG Afrique devient le seul
gate autorisé. Une validation de production restera distincte de cette preuve
et n'est pas revendiquée ici.
