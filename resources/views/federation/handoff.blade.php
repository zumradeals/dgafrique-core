<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Ouverture de {{ $displayName }}</title>
<style nonce="{{ $nonce }}">body{font:18px system-ui;background:#f6f5f0;color:#181715;max-width:36rem;margin:15vh auto;padding:1.5rem}button{background:#00658c;color:white;border:0;border-radius:.75rem;padding:1rem;font:inherit}</style></head><body>
<h1>Ouverture de {{ $displayName }}</h1><p>Vous allez poursuivre dans cet outil avec votre compte.</p>
<form id="continuation" method="POST" action="{{ $callbackUrl }}"><input type="hidden" name="jeton" value="{{ $token }}"><button type="submit">Continuer vers {{ $displayName }}</button></form>
<script nonce="{{ $nonce }}">document.getElementById('continuation').submit();</script>
</body></html>
