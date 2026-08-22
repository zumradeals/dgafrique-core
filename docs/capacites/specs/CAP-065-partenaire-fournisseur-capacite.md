# CAP-065 — Partenaire comme fournisseur de capacité

## Statut

**Audit et implémentation V1 — 2026-08-20.** Aucune spec ni aucun code ne portait CAP-065 avant ce chantier ; le concept n'existait que comme une ligne `NOT_IMPLEMENTED` dans `CAPABILITY-COVERAGE.md` (domaine « Partners », le seul CAP de ce domaine).

## Qu'est-ce qu'un Partenaire ?

**Un Partenariat est une RELATION, jamais un nouvel acteur.** Un acteur déjà réel — une Personne (`core_identity_reference`) ou une Organisation (CAP-066) — met une capacité réelle à disposition dans un contexte explicite (Projet, ZUMRA ou Besoin). « Partenaire » n'est donc pas un type d'identité concurrent de Personne/Organisation : c'est le rôle que joue un acteur existant dans une relation de partenariat.

Ancrage doctrinal : `ZUMRA-DOCTRINE-INVARIANTE.md` §3.3 nomme explicitement « les partenaires » parmi les catégories d'objets que DG Afrique organise, aux côtés des ZUMRA, projets et satellites — CAP-065 n'est donc pas une invention, mais la formalisation d'un concept déjà canonique et jusque-là non implémenté.

## Partenaire ≠ Organisation

Une Organisation (CAP-066) est une structure durable de gouvernance ; elle ne devient « partenaire » que dans le cadre d'un `Partnership` explicite reliant cette Organisation à un contexte précis. `Organization` n'a reçu aucun champ `is_partner`/`partner_type`/`partner_score` — le rôle contextuel appartient à la relation, jamais à l'identité fondamentale de l'objet.

## Fournisseur de capacité — réutilisation du domaine Capacité

- **Personne fournisseur** : doit lier une `CapabilityStatement` réelle qui lui appartient (`core_identity_reference` correspondant, `matching_consent = true`, non archivée) — `capability_statement_id` est obligatoire, sans quoi la proposition est refusée (422). Le libellé du partenariat (`capability_label`) est dérivé du `label` de cette `CapabilityStatement`, jamais déclaré librement par le client : CAP-065 est « Partenaire comme fournisseur de **capacité** », donc une Personne ne peut jamais offrir une capacité purement textuelle qui contredirait le domaine Capacité déjà existant (correctif appliqué après revue). Aucune duplication du domaine Capacité.
- **Organisation fournisseur** : depuis la convergence CAP-065/CAP-067, une Organisation fournisseur doit elle aussi lier une `CapabilityStatement` réelle — celle qu'elle a explicitement déclarée sur sa propre fiche via `OrganizationCapabilityService::declare()` (CAP-067, `holder_type = ORGANIZATION`). `PartnershipService::assertCapability()` vérifie que la `CapabilityStatement` référencée appartient bien à l'Organisation choisie (`organization_id` correspondant), qu'elle n'est pas archivée, et que l'acteur est un membre `OWNER`/`ADMIN` habilité de cette Organisation (`OrganizationService::isManager`). Une capacité personnelle du manager, ou une capacité d'une autre Organisation, ne vaut jamais capacité de l'Organisation choisie. Contrairement au fournisseur Personne, le consentement au rapprochement (`matching_consent`) n'est pas exigé côté Organisation : `OrganizationCapabilityService::declare()` le fixe toujours à `false` (les capacités d'Organisation ne sont volontairement pas raccordées au moteur de matching), l'exiger aurait rendu le scénario Organisation définitivement impossible. Le champ `capability_label` du `Partnership` reste renseigné mais devient un simple instantané historique dérivé du `label` de la `CapabilityStatement` au moment de la proposition — plus jamais une déclaration libre fournie par le client, pour les deux types de fournisseur.
- **Cycle de vie et archivage** : archiver une `CapabilityStatement` d'Organisation après qu'un `Partnership` l'a référencée ne modifie ni ne bloque ce `Partnership` — `assertCapability()` n'est appelée qu'à `propose()`, jamais par `pause()`/`resume()`/`withdraw()`/`end()`. Un partenariat déjà conclu reste donc un fait historique intelligible même si la capacité qui l'a fondé n'est plus active aujourd'hui ; seule une **nouvelle** proposition référençant cette capacité archivée est refusée (422).

**Le lien Organisation → `CapabilityStatement` réelle, identifié en `PARTIAL` lors de l'audit initial (structurellement impossible avant CAP-067), est désormais démontré : CAP-065 reste fermée `CLOSED` — le critère de fermeture (acteur → relation → capacité réelle → contexte → consentement/visibilité → gouvernance) est maintenant démontré intégralement, pour les Personnes comme pour les Organisations, sans aucune capacité fabriquée en texte libre.**

## CAP-016 — relation avec le partenaire existant

`ProjectAccompanimentAction::SOURCE_PARTNER` + `provider_label` (texte libre) reste le mécanisme de CAP-016 pour journaliser qu'une action d'accompagnement a été délivrée par un partenaire. CAP-065 ne modifie pas ce code (CAP-016 est fermée et stable, hors périmètre de ce chantier) : les deux représentations du mot « partenaire » restent volontairement distinctes pour cette V1 — `Partnership` est une relation gouvernable et consultable dans la durée, `provider_label` reste une étiquette descriptive ponctuelle sur une action déjà accomplie. Une réconciliation éventuelle (ex. `ProjectAccompanimentAction` référençant un `Partnership` réel) est une dépendance documentée pour un futur CAP, non traitée ici.

## Gouvernance et cycle de vie

`Partnership.status` : `PROPOSED → ACTIVE ⇄ PAUSED → ENDED` (terminal).

- **propose()** : le fournisseur (la Personne elle-même, ou un membre `OWNER`/`ADMIN` habilité de l'Organisation via `OrganizationService::isManager`) initie ; le contexte doit être réellement consultable (`NeedService::canView`/`ProjectService::canView`/adhésion active ou responsable ZUMRA) ; disponibilité respectée (`PersonProfile::AVAILABILITY_PAUSED` bloque une nouvelle proposition).
- **activate()** : seule l'autorité de décision du contexte (`NeedService::canDecide`/`ProjectService::canDecide`/`ZumraGroupService::isLeader`) peut faire passer `PROPOSED → ACTIVE`.
- **pause()/resume()** : geste du fournisseur sur sa propre disponibilité.
- **withdraw()** (le fournisseur retire sa capacité) et **end()** (le contexte met fin) mènent tous deux à `ENDED`, avec des événements distincts (`CAPABILITY_WITHDRAWN` vs `PARTNERSHIP_ENDED`) pour rester auditables selon qui a agi.

## Visibilité

`canView()` : le fournisseur voit toujours son propre partenariat ; l'autorité de **décision** du contexte (jamais un simple lecteur) voit tout partenariat qui lui est soumis, même privé, pour pouvoir le gouverner ; sinon un partenariat n'est visible que s'il est `ACTIVE` et `visibility = PUBLIC`. Un simple lecteur du contexte partagé (ex. tout membre d'un Projet public) ne voit jamais un partenariat `PRIVATE` par la seule visibilité de son contexte — corrigé pendant l'implémentation après qu'un test d'isolation l'a révélé à tort permissif.

## Matching

Aucun `PartnerMatchingEngine` n'a été créé. `MissionMatchingEngine`, `PersonRecommendationEngine` et `OpportunityEngine` restent strictement personnels (`PersonProfile`/`CapabilityStatement`) et ne sont pas généralisés dans ce chantier — CAP-065 connecte des domaines existants, il ne duplique aucun algorithme de correspondance.

## Financement

Aucun wallet, transaction, budget ou paiement. CAP-063 (Financement de projet) reste un domaine distinct, non touché.

## V1 implémentée

- `Partnership`, `PartnershipEvent` (`dg_partnerships`, `dg_partnership_events`) ;
- `PartnershipService` : propose/activate/pause/resume/withdraw/end/canView, réutilisant `NeedService`, `ProjectService`, `ZumraGroupService`, `OrganizationService` — aucune règle d'autorisation dupliquée ;
- exposition minimale : `GET/POST /partenariats`, `GET /partenariats/{partnership}`, actions `activation`/`pause`/`reprise`/`retrait`/`fin` — aucun portail partenaire.

## Preuve

`tests/Feature/PartnershipTest.php` — 35 cas : proposition avec `CapabilityStatement` réelle (Personne et Organisation), refus d'une Personne sans `capability_statement_id`, `capability_label` contradictoire écrasé par le libellé réel de la `CapabilityStatement`, capacité d'autrui refusée, consentement au rapprochement requis (Personne), contexte inaccessible/inexistant refusé, disponibilité en pause bloque une proposition, activation réservée à l'autorité de contexte, transitions invalides refusées, pause/reprise réservées au fournisseur, retrait vs fin de partenariat distincts et audités, Organisation avec capacité active proposable, Organisation sans capacité ne peut fabriquer une capacité libre (422), capacité appartenant à une autre Organisation refusée, capacité personnelle du manager ne vaut jamais capacité Organisation, capacité archivée refusée pour une nouvelle proposition, archivage d'une capacité après création d'un Partnership n'affecte jamais rétroactivement ce Partnership, Organisation fournisseur exige un membre habilité réel, aucune `CapabilityStatement`/Organisation/Projet/Besoin/ZUMRA/Mission créée automatiquement, isolation entre organisations, absence de fuite dans la liste, visibilité publique/active, absence de mutation en lecture, cycle de vie complet et événements attendus, multi-partenariat, contextes ZUMRA et Besoin. `tests/Feature/PartnershipHttpTest.php` — 14 cas HTTP/service couvrant les mêmes garanties de bout en bout (formulaires UIUX-005 inclus), tous mis à jour pour soumettre `capability_statement_id` au lieu d'un `capability_label` libre côté Organisation.
