# Byte-exact Claude handoff archive

The files `claude-design-handoff.zip.b64.part01` … `part07` are consecutive chunks of the Base64 representation of the original ZIP supplied on 16 August 2026.

Reconstruction:

```bash
cat claude-design-handoff.zip.b64.part* | tr -d '\n' | base64 -d > claude-design-handoff.zip
sha256sum claude-design-handoff.zip
```

Expected SHA-256:

`eea8528396c99bed1c811387ff9b4a224718539bd63c8eb8d29d7b871c42e1d0`

Expected ZIP size after decoding: **67,922 bytes**.
