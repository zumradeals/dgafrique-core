# Préparation VPS — prérequis de la future release

Le moteur dispose désormais de primitives versionnées pour le scheduler, la readiness, la
restauration et le rollback. Leur mode opératoire autoritatif est
`docs/production/OPERATIONS-RUNBOOK.md`. Le déploiement final restera généré après certification
du moteur et construction du nouveau frontend ; ce document ne donne donc aucune autorisation de
pointer le domaine public vers la présentation actuelle.

Pré-requis attendus :

- Ubuntu 24.04 LTS ;
- Nginx ;
- PHP 8.4 avec extensions Laravel/PostgreSQL ;
- Composer 2 ;
- PostgreSQL ;
- Redis ;
- Node.js LTS pour compiler Tailwind/Vite ;
- Supervisor ;
- certificat TLS.

Ne pas encore pointer `dgafrique.com` vers cette application. Utiliser un domaine de préproduction,
des releases immuables et le lien atomique `current`, puis conserver la release précédente pendant
toute la fenêtre de validation.
