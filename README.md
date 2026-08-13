# DG Afrique Core

Portail applicatif de **DG Afrique**, reconstruit sur la stack familiale GAMAD.

## Stack canonique

- PHP 8.4 et Laravel ;
- PostgreSQL ;
- Blade + Livewire pour les interfaces applicatives ;
- Tailwind CSS et Alpine.js ;
- Redis pour les files, le cache et les verrous distribués ;
- Nginx, PHP-FPM, Supervisor et scheduler Laravel sur VPS ;
- GAMAD Core comme autorité d'identité, de session et de fédération ;
- GeniusPay pour les flux de paiement autorisés.

## Sources de vérité

1. `Design/` : handoff haute fidélité fourni par Claude ;
2. `docs/capacites/CAPABILITY-INDEX.md` : référentiel CAP-001 à CAP-084 ;
3. `docs/capacites/CAP-MASTER-TRACKER.md` : progression réelle de la nouvelle application ;
4. `docs/capacites/OVERRIDES.md` : décisions métier prioritaires ;
5. `docs/architecture/ADR-001-stack-canonique.md` : architecture technique.

Les anciennes réalisations Next.js/Supabase ne sont pas importées comme code. Les spécifications utiles sont conservées dans `docs/capacites/legacy/` uniquement comme matière d'audit.

## Règles fondatrices

- aucune seconde identité membre : GAMAD Core reste canonique ;
- compte DG Afrique gratuit et distinct de l'adhésion ZUMRA ;
- adhésion initiale et contribution mensuelle sont deux flux différents ;
- ZUMRA est un réseau social d'action, sans classement de valeur humaine ;
- les satellites restent autonomes et sont ouverts par fédération ;
- aucun mock ne doit être présenté comme une donnée réelle ;
- aucune capacité n'est déclarée validée sans preuve sur cette nouvelle base.

Lire `docs/AI-HANDOFF.md` avant toute modification.
