# Source manifest — Claude Design handoff

**Adopted:** 2026-08-16  
**Original uploaded archive:** `Doctrine ZUMRA et formulaire.zip`  
**Original archive size:** 67,922 bytes  
**SHA-256:** `eea8528396c99bed1c811387ff9b4a224718539bd63c8eb8d29d7b871c42e1d0`

The complete original ZIP is preserved losslessly as Base64 text chunks under `archive/` because the GitHub connector used for this adoption writes UTF-8 repository content.

To reconstruct it:

```bash
cat docs/design/reference/claude-2026-08-16/archive/claude-design-handoff.zip.b64.part* \
  | tr -d '\n' \
  | base64 -d \
  > /tmp/claude-design-handoff.zip

sha256sum /tmp/claude-design-handoff.zip
```

The resulting SHA-256 **must** equal:

`eea8528396c99bed1c811387ff9b4a224718539bd63c8eb8d29d7b871c42e1d0`

`README.md` and `DECISIONS.md` are additionally extracted beside this manifest for immediate human/AI reading. The ZIP remains the byte-exact authority for every other file in the handoff.

Do not silently overwrite this reference. A materially new design direction must be stored as a new dated version and explicitly update `docs/design/DESIGN-INVARIANTS.md`.
