# Politique de frontière IA — Cerveau Projet

## Statut

`DÉSACTIVÉ PAR DÉFAUT` — DeepSeek ne peut recevoir aucune donnée tant que
`DEEPSEEK_ENABLED=true` **et** `DEEPSEEK_DATA_POLICY_VERSION` n'ont pas été configurés par un
geste opérationnel explicite. La présence d'une clé API ne suffit jamais à l'activer.

## Finalité autorisée

L'IA aide la personne à structurer un projet et peut préparer une proposition de Besoin. Elle ne
peut ni créer, ni modifier, ni supprimer une donnée Core. Une proposition reste un brouillon privé
jusqu'à une confirmation humaine distincte, authentifiée et autorisée par les règles métier.

## Données transmises

Le moteur borne l'historique aux 24 derniers messages et transmet seulement :

- le texte volontairement envoyé dans la conversation Cerveau Projet ;
- les champs de travail du projet utiles au conseil ;
- au maximum 20 besoins actifs, sous forme de titre, catégorie, lieu et statut.

Sont interdits dans ce contexte : secrets d'authentification, bearer GAMAD Core, données de
paiement, clés API, coordonnées bancaires, contenu d'autres projets privés et journaux techniques.

## Rétention et transparence

DG Afrique conserve localement la conversation conformément à sa politique produit. La durée de
rétention du prestataire doit être vérifiée contractuellement avant activation et reportée dans
la version renseignée par `DEEPSEEK_DATA_POLICY_VERSION`. La future interface doit informer la
personne avant son premier envoi à l'IA et offrir un parcours sans IA. Jusqu'à cette preuve UX et
contractuelle, `DEEPSEEK_ENABLED` doit rester `false` en production.

## Incidents et arrêt

Une réponse HTTP en erreur, une panne de transport, un timeout, une réponse vide ou un JSON
invalide échouent explicitement. Le message utilisateur est conservé localement, aucune action
Core n'est exécutée et le fournisseur peut être coupé immédiatement par `DEEPSEEK_ENABLED=false`.
