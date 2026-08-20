# Décisions design — provenance du 16 août 2026

> **Archive lisible.** Ce document explique l'origine de décisions de design ; il n'est pas un contrat actif autonome.

La référence du 16 août 2026 a conduit à plusieurs orientations ensuite consolidées dans `docs/design/DESIGN-INVARIANTS.md` : DG Afrique comme réseau d'action, ZUMRA comme moteur humain, satellites comme outils secondaires, absence de métriques de popularité, priorité claire dans Mon espace, meilleure exploitation des grands écrans et traitement explicite du mobile.

DOC-001 fixe désormais la règle suivante : une ancienne maquette ou décision de handoff ne peut jamais annuler silencieusement ce qui a été livré depuis. Toute évolution substantielle doit modifier les invariants actifs dans une PR explicite et être vérifiée contre le code courant.

Pour les décisions applicables aujourd'hui, lire `docs/design/DESIGN-INVARIANTS.md`.
