# FICHE D'IMPLÉMENTATION TRANSVERSALE — CARNET DE PREUVES

**Statut :** CONCEPTION
**Version :** 0.1
**Racine référentielle :** CAP-036 — PREUVE DE CAPACITÉ
**Capacité proche :** CAP-035 — MÉMOIRE D'EXPÉRIENCE (voir §3)
**Expression produit :** CARNET DE PREUVES
**Nouveau CAP :** non
**Nature :** module transversal natif de DG Afrique, référencé par MISSIONS et TRANSMISSION mais non implémenté par eux
**Base de conception :** référentiel des 84 capacités, doctrine canonique ZUMRA (`docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md`), invariants de design (`docs/design/DESIGN-INVARIANTS.md`), les fiches MISSIONS (`docs/capacites/specs/MISSIONS.md` §13) et TRANSMISSION (`docs/capacites/specs/TRANSMISSION.md` §13), qui annoncent toutes deux ce module sans l'implémenter.

Ce document est un **contrat d'implémentation** en préparation. Il ne contient encore aucun code. Cinq points restent à trancher par un humain (§21) avant de passer à READY FOR IMPLEMENTATION.

---

## 1. Intention

MISSIONS et TRANSMISSION promettent toutes deux, mot pour mot, la même chose :

> MISSIONS.md §13 : *« Une `MissionSubmission` peut référencer des preuves contextuelles. Le Carnet de preuves sera l'autorité métier de preuve... MISSIONS ne certifie jamais automatiquement la vérité d'une preuve et ne devient pas un stockage documentaire généraliste. »*
>
> TRANSMISSION.md §13 : *« Ni le Carnet de preuves (CAP-036) ni une capacité de preuve transversale n'existent en code... TRANSMISSION ne certifie jamais automatiquement une preuve. »*

Ce module tient cette promesse : il devient l'**autorité métier de la preuve** — le lieu où une trace de ce qui s'est réellement passé est enregistrée, datée, éventuellement corroborée par une autre personne réelle, et conservée.

> **Une preuve enregistre qu'une chose réelle s'est produite. Elle ne certifie jamais automatiquement une compétence, un niveau ou une vérité, et son existence ne devient jamais un score de valeur humaine.**

Distinctions invariantes (même famille que MISSIONS §1 et TRANSMISSION §1) :

> **Soumettre une preuve n'est pas la faire confirmer. Un témoin qui confirme ne certifie pas. Une autorité de contexte qui reconnaît une preuve ne garantit pas sa véracité. Accumuler des preuves n'est jamais un classement.**

## 2. Ce que CARNET DE PREUVES n'est pas

- **pas** un système de certification, diplôme ou badge ;
- **pas** un moteur de notation ou de classement des personnes par nombre de preuves ;
- **pas** un service de stockage documentaire généraliste (upload de fichiers, gestion de version de documents) — cela reste le rôle d'un futur GamaDrive fédéré, **qui n'existe pas encore en code** (voir §14) ;
- **pas** une autorité qui décide à la place de la personne concernée si sa capacité progresse — seule la personne déclare, jamais le module.

## 3. Position dans le référentiel

Rattachements principaux, par ordre d'intensité :

- **CAP-004 — Compétences** (`dg_capability_statements`) : `CapabilityStatement::status` porte déjà les valeurs `DECLARED`/`VERIFIED`/`ATTESTED` mais **aucun code n'écrit jamais que `DECLARED`** aujourd'hui (vérifié : zéro usage de `STATUS_VERIFIED`/`STATUS_ATTESTED` hors la déclaration de constante elle-même). CARNET DE PREUVES est le module qui peut enfin donner un sens à `VERIFIED`, sous décision humaine explicite uniquement (§13).
- **CAP-035 — Mémoire d'expérience** : n'est **pas** un second module à construire ici. C'est la vue de lecture agrégée, sur le profil d'une personne, de ses preuves `DISCOVERABLE` dans le temps — un journal, pas un objet métier séparé. `PersonProfile.experience_highlights`/`experience_proofs` (déjà existants, texte libre déclaré au profil) restent la déclaration narrative ; le Carnet de preuves devient la source structurée et datée sous-jacente, sans forcer une migration de ces champs (§21.3).
- **CAP-069 — Missions** : `MissionContribution.evidence_context` (json libre) reste tel quel — CARNET DE PREUVES n'y touche pas, il propose un rattachement optionnel additif (§21.1).
- **CAP-006 — Transmission** : `Transmission.evidence_context` et `TransmissionContribution.evidence_context` (json libre) restent tels quels, même logique additive.
- **CAP-013 — Besoin** : `Need` ne porte aujourd'hui aucun champ de preuve — une résolution de Besoin peut optionnellement citer une preuve, sans modification du modèle `Need` lui-même (§21.4).
- **CAP-047-050 / GamaDrive** : seule intégration réelle en code aujourd'hui est une redirection SSO de continuité fédérée (`routes/federation.php`, `FederationContinuationController`) — **aucune API de stockage documentaire n'existe**. Le Carnet de preuves ne peut donc pas s'appuyer sur un stockage GamaDrive réel en v1 (§14).

CARNET DE PREUVES ne remplace ni Mission, ni Transmission, ni Besoin, ni Profil. Il leur est référencé, jamais fusionné.

## 4. Doctrine à ne jamais casser

Fondée sur les citations exactes du canon (`docs/canon/ZUMRA-DOCTRINE-INVARIANTE.md`) :

1. Une preuve reste conservée même quand son contexte disparaît : *« Son historique, ses membres, ses décisions, ses contributions et ses preuves sont conservés »* même lors de la suspension d'une ZUMRA (L.313).
2. Une suppression d'interface ne détruit jamais une preuve : *« Une suppression d'interface ne détruit pas une preuve soumise à conservation »* (L.698). Archivage réversible uniquement, jamais de suppression physique depuis l'UI.
3. Le retrait de consentement ne peut effacer une preuve dont la conservation est légalement ou contractuellement requise (L.610) — hors périmètre juridique de cette fiche, mais l'archivage doit rester distinct de la suppression.
4. Les preuves financières et communications officielles ne sont jamais modifiées silencieusement ; *« Leurs versions, auteurs et dates sont conservés »* (L.522) — le Carnet de preuves applique le même principe d'immutabilité à toute preuve : une correction crée une nouvelle version tracée, jamais une réécriture silencieuse.
5. Aucun score humain, aucun classement par nombre ou qualité de preuves (précédent Missions/Transmission, appliqué ici même si le canon historique mentionne vaguement les « preuves » comme facteur de matching — ce module n'introduit aucun score).
6. Aucune certification, diplôme ou paiement ne transforme une preuve en vérité garantie.
7. Aucune donnée de démonstration réelle.

## 5. Acteurs

- **Auteur** : la personne (ou, pour une preuve collective, un responsable de ZUMRA/Projet agissant en son nom déclaré) qui soumet la preuve.
- **Témoin** (optionnel) : une personne nommément désignée, en général déjà co-présente dans le contexte source (co-exécutant d'une Mission, transmetteur/apprenant d'une Transmission), invitée à corroborer.
- **Autorité contextuelle** (optionnelle) : si la preuve est rattachée à un Projet/ZUMRA/Mission/Besoin, l'autorité déjà existante de ce contexte peut « reconnaître » la preuve — jamais garantir sa vérité, jamais une nouvelle autorité créée.
- **DG Afrique (le produit)** : n'évalue jamais la véracité d'une preuve. Il organise l'enregistrement, la corroboration humaine et la conservation.

## 6. Déclencheurs

Une preuve peut être soumise depuis, au minimum :

1. **Une contribution ou soumission de Mission** — `MissionContribution`/`MissionSubmission` existants restent inchangés ; un lien optionnel additif permet de citer une preuve du Carnet.
2. **Une clôture de Transmission** — `confirmCompletion()`/`validateByContext()` existants restent inchangés ; la preuve déjà exigée par `validateByContext()` (au moins une `TransmissionContribution` ou un `evidence_context` non vide) peut optionnellement devenir une vraie entrée du Carnet plutôt qu'un JSON libre, sans que ce soit une obligation en v1.
3. **Une résolution de Besoin** — citer une preuve de résolution, sans modifier `Need`.
4. **Une soumission autonome** — une personne enregistre une preuve d'une chose réelle qu'elle a faite, sans aucun contexte source (`origin_type = NONE`), exactement comme TRANSMISSION permet une origine `NONE`/`INTERACTION`.

## 7. Objet et modèle de données proposé

```text
dg_proofs
  id, public_reference,
  owner_type (PERSON|GROUP), owner_reference,
  title, description (text),
  capability_label (nullable), normalized_label (nullable), catalog_item_id (nullable, FK dg_capability_catalog),
  origin_type (NONE|MISSION|TRANSMISSION|NEED|PROJECT|ZUMRA|INTERACTION), origin_reference (nullable),
  occurred_at, submitted_by_core_reference, submitted_at,
  visibility (PRIVATE|DISCOVERABLE) default PRIVATE,
  status (SUBMITTED|WITNESSED|ACKNOWLEDGED|DISPUTED|ARCHIVED),
  context_acknowledged_by_core_reference (nullable), context_acknowledged_at (nullable),
  disputed_by_core_reference (nullable), disputed_at (nullable), dispute_note (nullable text),
  archived_at (nullable),
  timestamps

dg_proof_references                 -- documents/liens, jamais un stockage généraliste
  id, proof_id, type (EXTERNAL_URL|FREE_TEXT|GAMADRIVE_FEDERATED), value, label,
  timestamps
  -- GAMADRIVE_FEDERATED réservé, inerte tant que la fédération documentaire n'existe pas (§14)

dg_proof_witnesses                  -- corroboration humaine optionnelle
  id, proof_id, core_identity_reference, status (INVITED|CONFIRMED|DECLINED),
  invited_by_core_reference, responded_at,
  timestamps
  -- unique(proof_id, core_identity_reference)

dg_proof_events                     -- append-only
  id, proof_id, event, actor_core_reference, from_state, to_state, context (json), occurred_at,
  timestamps
```

Toute correction de `title`/`description` après soumission crée un nouvel enregistrement versionné plutôt qu'une réécriture (doctrine L.522) — mécanisme exact à trancher à l'implémentation (nouvelle ligne vs. champ `superseded_by_proof_id`), pas bloquant pour cette fiche.

## 8. Machine d'état

```text
SUBMITTED --(un témoin invité confirme)--> WITNESSED
SUBMITTED --(autorité contextuelle reconnaît, si contexte rattaché)--> ACKNOWLEDGED
WITNESSED --(autorité contextuelle reconnaît)--> ACKNOWLEDGED
SUBMITTED|WITNESSED|ACKNOWLEDGED --(un témoin ou l'autorité conteste)--> DISPUTED
SUBMITTED|WITNESSED|ACKNOWLEDGED|DISPUTED --(auteur archive)--> ARCHIVED (réversible)
```

Aucun état ne s'appelle « VÉRIFIÉE » ni « CERTIFIÉE » — le vocabulaire est délibérément choisi pour ne jamais laisser croire à une garantie de vérité. `WITNESSED`/`ACKNOWLEDGED` décrivent une corroboration humaine, pas un verdict.

## 9. Permissions et consentement

- **Soumettre** : toute personne membre, pour elle-même ou au nom déclaré d'un groupe qu'elle dirige.
- **Confirmer/décliner un témoignage** : exclusivement la personne invitée comme témoin, jamais un tiers.
- **Reconnaître par le contexte** : l'autorité déjà existante du Projet/ZUMRA/Mission/Besoin rattaché — jamais un droit de confirmer à la place d'un témoin (même invariant que TRANSMISSION §5.A : reconnaître ≠ témoigner à la place de quelqu'un).
- **Contester** : un témoin invité ou l'autorité contextuelle, jamais l'auteur de sa propre preuve.
- **Archiver** : l'auteur uniquement, réversible.

## 10. Visibilité

Privée par défaut. `DISCOVERABLE` uniquement par choix explicite de l'auteur, auquel cas la preuve peut apparaître dans la Mémoire d'expérience publique de son profil (CAP-035, §3) — jamais utilisée pour un classement ou un score visible.

## 11. Matching et explicabilité

Aucun moteur de matching dédié en v1 : une preuve `DISCOVERABLE` liée à une capacité peut enrichir les raisons déjà produites par `PersonRecommendationEngine`/`TransmissionMatchingEngine` (ex. « cette personne a une preuve de cette capacité »), en réutilisant leur mécanisme d'explicabilité existant plutôt qu'en construisant un cinquième moteur bespoke — extension mineure de ces moteurs à l'implémentation, pas un nouveau service.

## 12. Lien avec CapabilityStatement (CAP-004)

Une preuve peut référencer optionnellement une `CapabilityStatement` (`catalog_item_id`/`normalized_label` partagés). Le passage `DECLARED → VERIFIED` reste **une action strictement volontaire de la personne elle-même** sur sa propre déclaration, jamais automatique, jamais déclenchée par le nombre de preuves ou de témoins — cohérent avec TRANSMISSION §5.D (« peut proposer une évolution de CapabilityStatement... jamais une écriture automatique »). Le palier `ATTESTED` reste hors périmètre v1 : il supposerait une autorité externe reconnue qui n'existe pas encore dans le référentiel (§21.2).

## 13. Aucune certification automatique — invariant central

- Un témoin qui confirme ne certifie rien : il atteste seulement avoir été présent/informé.
- Une autorité contextuelle qui reconnaît une preuve ne garantit jamais sa véracité.
- Aucune preuve, seule ou combinée, ne modifie automatiquement une `CapabilityStatement`, un rôle, une visibilité de profil ou un accès.
- Aucun compteur de preuves n'est affiché comme un score.

## 14. GamaDrive et stockage documentaire

Vérifié en code : GamaDrive n'a aujourd'hui qu'une continuité SSO fédérée (`routes/federation.php`, `FederationContinuationController`, `config/federation.php`) — **aucune API de stockage ou de référence documentaire n'existe**. `dg_proof_references.type = GAMADRIVE_FEDERATED` est réservé dans le schéma mais **désactivé/rejeté en v1** : seuls `EXTERNAL_URL` (lien externe quelconque, à la charge de l'utilisateur) et `FREE_TEXT` sont utilisables tant que cette fédération documentaire n'est pas construite. Ce module ne construit pas de stockage de fichiers généraliste.

## 15. Fil unique

Un seul Fil DG Afrique. Événements Preuve éligibles, uniquement si rattachés à un contexte visible :

```text
PROOF_SUBMITTED
PROOF_ACKNOWLEDGED
```

Repliés dans les buckets existants (PROJECTS/ZUMRA/NEEDS) selon le contexte source, même patron que Missions/Transmission. Pas de Fil Preuve autonome.

## 16. Mon espace

Sections proposées : **Témoignages demandés** (invitations de témoin en attente), **Mes preuves** (soumises par moi), **À reconnaître** (si j'ai une autorité contextuelle sur une preuve rattachée). Prochaine action : un témoignage en attente prend priorité, même patron `nextAction()` que Missions/Transmission.

## 17. Commentaires, partage, messagerie

Mêmes extensions CAP-020/021/022 que Missions/Transmission : `CONTEXT_PROOF`/`SOURCE_PROOF`, mêmes invariants (append-only, visibilité revalidée, pas de popularité).

## 18. Audit

`dg_proof_events` append-only, même patron que `dg_mission_events`/`dg_transmission_events`.

## 19. États UX obligatoires

État vide honnête sur `/preuves` si rien n'existe. Aucune donnée fictive. Badges de statut cohérents (`x-dg.badge`) : `decision` pour SUBMITTED (en attente), `action` pour WITNESSED, `project` pour ACKNOWLEDGED, `need` pour DISPUTED, `neutral` pour ARCHIVED.

## 20. Hors périmètre v1

- certification, diplôme, badge, niveau calculé ;
- stockage documentaire GamaDrive réel (attend la fédération, §14) ;
- paiement lié à une preuve ;
- palier `ATTESTED` sur une CapabilityStatement (attend une autorité externe reconnue, §21.2) ;
- moteur de matching dédié (extension mineure des moteurs existants seulement) ;
- migration ou suppression des champs `evidence_context` déjà livrés sur Mission/Transmission.

## 21. Points à trancher avant READY FOR IMPLEMENTATION

1. **Portée d'intégration.** Proposition : additive uniquement — aucune migration des `evidence_context` déjà livrés sur `MissionContribution`/`Transmission`/`TransmissionContribution`, qui restent tels quels. Le Carnet de preuves devient la source structurée pour tout nouvel usage, sans toucher au code déjà mergé.
2. **Paliers de CapabilityStatement.** Proposition : `VERIFIED` reste strictement auto-déclaratif par la personne elle-même (jamais déclenché automatiquement par des témoins/reconnaissances) ; `ATTESTED` reste hors périmètre v1 tant qu'aucune autorité externe reconnue n'est définie dans le référentiel.
3. **CAP-035 Mémoire d'expérience.** Proposition : pas un second module — uniquement la vue de lecture agrégée des preuves `DISCOVERABLE` sur le profil, sans nouveau modèle ni migration des champs `experience_highlights`/`experience_proofs` existants.
4. **Preuve de résolution de Besoin.** Proposition : aucune modification du modèle `Need` — le rattachement se fait uniquement côté `Proof` (`origin_type = NEED`), jamais par l'ajout d'un champ sur `Need`.
5. **Corroboration par témoin obligatoire ou non.** Proposition : optionnelle — une preuve `SUBMITTED` sans témoin reste pleinement valide et affichable ; le témoignage est un renforcement volontaire, jamais une condition de validité.

Tant que ces cinq points ne sont pas tranchés par un humain, aucune implémentation ne doit démarrer.

## 22. Instruction d'arrêt

Ne pas commencer l'implémentation avant validation explicite des points §21. Si un point non listé ici s'avère bloquant pendant l'implémentation, documenter le conflit et arrêter cette partie pour revue, comme pour Missions et Transmission.
