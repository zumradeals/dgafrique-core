# Registre maître — reconstruction DG Afrique Core

> L'index complet et les titres CAP-001 à CAP-084 se trouvent dans `CAPABILITY-INDEX.md`.

## État courant

| CAP | Capacité | Statut | Preuve nouvelle stack |
|---|---|---|---|
| CAP-001 | IDENTITÉ PERSONNE | **VALIDÉ PRÉPRODUCTION — 2026-08-13** | `proofs/CAP-001-2026-08-13.md` |
| CAP-002 | COMPTE DG AFRIQUE | **VALIDÉ PRÉPRODUCTION — 2026-08-13** | `proofs/CAP-002A-2026-08-13.md` · `proofs/CAP-002B-2026-08-13.md` |
| CAP-003 | PROFIL DE CAPACITÉS | **EN DÉVELOPPEMENT — GATE ACTIF** | Profil PostgreSQL indépendant de ZUMRA, rattaché à la référence Core |
| CAP-004 à CAP-084 | Voir index canonique | BLOQUÉS | — |

## Règles

- aucune validation Next.js, Supabase ou Vercel n'est héritée ;
- une fonction historique peut informer la spec sans valider le CAP ;
- chaque CAP doit être prouvé sur Laravel/PostgreSQL et l'infrastructure VPS ;
- les overrides métier restent applicables ;
- le premier travail consiste à spécifier le contrat d'identité entre DG Afrique et GAMAD Core.
