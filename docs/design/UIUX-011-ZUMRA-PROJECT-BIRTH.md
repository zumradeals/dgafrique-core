# UIUX-011 — naissance d’un Projet depuis l’Espace ZUMRA

Le point d’entrée `projects.create?group={reference}` conserve le moteur de brouillon UIUX-009B.
Une ZUMRA préselectionnée n’est jamais acceptée silencieusement : l’étape `audience` existante
confirme l’adhésion active, la gouvernance collective et le quota avant de poursuivre.

Les dix validations déterministes existantes sont présentées dans cinq moments visuels : idée,
besoin et impact, manière d’agir, ressources et cadre, validation. Chaque écran continue pourtant
d’enregistrer exactement son ancienne clé de brouillon. La confirmation finale converge sans
variante vers `ProjectDraftService::confirm()` puis `ProjectService::create()`.

Le parcours ne crée aucun objet Besoin à partir du texte du problème. Il peut seulement conserver
la liaison facultative à un Besoin existant déjà autorisé. Aucun membre, rôle projet, responsabilité,
financement ou statistique n’est créé ou simulé. « Enregistrer comme brouillon » utilise le statut
et la persistance déjà réels de `ProjectDraft`.

La ZUMRA porteuse reste visible dans le hero, le rail interne et l’aperçu vivant. Les étapes
pédagogiques et l’aide contextuelle n’ajoutent aucun état métier. Le parcours global de création
Projet demeure inchangé lorsqu’il n’est pas ouvert depuis une ZUMRA.
