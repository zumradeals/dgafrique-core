# Source manifest — Claude Design handoff

**Adopted:** 2026-08-16  
**Historical uploaded archive:** `Doctrine ZUMRA et formulaire.zip`  
**Historical archive size:** 67,922 bytes  
**Historical SHA-256:** `eea8528396c99bed1c811387ff9b4a224718539bd63c8eb8d29d7b871c42e1d0`

Ce fichier conserve uniquement la provenance de la référence design adoptée le 16 août 2026.

Dans le cadre de DOC-001, les sept fragments Base64 du ZIP ont été retirés de l'arbre courant : Git conserve déjà leur historique, et leur présence dans `docs/` créait une archive opaque de plus de 90 Ko susceptible d'être traitée à tort comme une autorité documentaire active.

`README.md` et `DECISIONS.md` restent disponibles comme contexte historique lisible. Les règles actives appartiennent à `docs/design/DESIGN-INVARIANTS.md`, puis à l'interface réellement présente sur `main`.

Une nouvelle direction design substantielle doit être une décision explicite et versionnée ; elle ne doit jamais remplacer silencieusement les invariants courants.
