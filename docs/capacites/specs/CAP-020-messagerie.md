# CAP-020 — Messagerie

## Contrat canonique

**Finalité.** Permettre aux personnes et groupes de communiquer dans un contexte opérationnel.

**Capacité.** La messagerie peut relier personne↔personne, personne↔ZUMRA, projet↔participants et DG Afrique↔utilisateur.

**Points clés.**
- Conversation directe.
- Conversation de groupe.
- Lien possible avec projets, besoins et invitations.

**Garde-fou.** La messagerie est un moyen de coordination, pas la finalité principale du produit.

Le marqueur historique `[PLUS TARD]` du référentiel décrit l'ordre de livraison initial. Dans la reconstruction séquentielle, CAP-020 est désormais le lot actif.

## Interprétation DG Afrique

CAP-020 fournit une messagerie privée et bornée autour d'identités et d'objets métier déjà existants. Il ne crée ni réseau social parallèle, ni identité locale, ni statut de participant à un projet.

### Conversations prises en charge

1. **Personne ↔ personne**
   - le premier contact part d'un profil volontairement découvrable ;
   - seule la référence publique de découverte est utilisée par l'interface ;
   - la référence d'identité canonique reste interne.

2. **Personne ↔ ZUMRA**
   - une conversation de ZUMRA est réservée aux membres actifs ;
   - un membre qui n'est plus actif ne conserve pas l'accès par la seule présence historique dans la conversation.

3. **Invitation ZUMRA**
   - une personne invitée peut échanger avec les responsables avant d'accepter ;
   - ouvrir ou utiliser la conversation ne vaut jamais acceptation de l'invitation.

4. **Projet ↔ participants de coordination**
   - seul un décideur du projet ouvre le fil de coordination ;
   - ajouter une personne à la conversation ne crée aucun statut de membre du projet ;
   - une personne ajoutée doit déjà avoir le droit de voir le projet dans son niveau de visibilité courant.

5. **Besoin ↔ interlocuteur(s)**
   - un membre qui peut voir un besoin peut ouvrir un échange avec son porteur ou les responsables de la ZUMRA porteuse ;
   - l'archivage ou la perte du droit de visibilité coupe l'accès contextuel.

6. **DG Afrique ↔ utilisateur**
   - un utilisateur peut ouvrir un échange privé avec les administrateurs provisionnés du portail ;
   - aucun compte d'assistance fictif n'est créé.

## Modèle de données

Trois tables seulement :
- `dg_message_conversations` ;
- `dg_message_participants` ;
- `dg_message_entries`.

Une conversation contient : type direct/groupe, contexte optionnel, créateur, clé de déduplication et date du dernier message. Les participants ne stockent qu'une référence d'identité canonique et les repères de lecture. Les messages sont append-only : expéditeur, corps, date d'envoi.

Aucune table `dg_messages`, `dg_comments`, `dg_shares`, `dg_likes`, `dg_followers` ou `dg_project_memberships` n'est créée.

## Sécurité et confidentialité

- Toutes les routes sont derrière `core.member`.
- L'existence d'une conversation privée n'est pas révélée à un non-participant : accès refusé en 404.
- Les contextes besoin/projet/ZUMRA sont réévalués au moment de l'accès ; être participant historique ne suffit pas.
- Les messages ne sont jamais injectés dans CAP-019 Fil d'activité.
- Les corps de messages sont rendus échappés par Blade.
- Longueur maximale : 3 000 caractères.
- Aucun edit/delete public de message dans CAP-020 ; l'historique reste factuel.
- Aucun appel d'écriture vers GAMAD Core.

## Frontières

### Hors CAP-020

- **CAP-021 — Commentaire** : contribution courte attachée publiquement ou contextuellement à un objet.
- **CAP-022 — Partage** : circulation d'un objet existant sans duplication.
- réactions, likes, followers, score d'engagement ;
- pièces jointes, appels audio/vidéo, présence temps réel ;
- création automatique d'une adhésion ZUMRA ou d'un statut de participant projet.

## Preuve attendue

La validation VPS doit démontrer :
- migration des trois tables ;
- routes `/messages` chargées avec `core.member` ;
- tests CAP-020 ciblés verts ;
- suite complète sans régression ;
- build Vite réussi pour les nouvelles interfaces ;
- `https://beta.dgafrique.com` en HTTP 200.
