import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');

test('gateway explains GAMAD in human language before any internal architecture', async () => {
  const gateway = await read('resources/views/gateway.blade.php');
  assert.match(gateway, /Réseau social d.action/);
  assert.match(gateway, /De vos idées/);
  assert.match(gateway, /À nos actions/);
  assert.match(gateway, /savoir-faire, un besoin ou l.envie de participer/i);
  assert.match(gateway, /Vous n’avez pas besoin.*d’avoir déjà un projet/is);
  assert.match(gateway, /Formation · Travail · Adoration/);
  assert.match(gateway, /La personne décide/);
  assert.match(gateway, /visibilité se choisit/);
  assert.match(gateway, /valeur ne se mesure pas en likes/i);
  assert.doesNotMatch(gateway, /GAMAD Core|GeniusPay|DeepSeek|CAP-\d+/i);
});

test('gateway is the only public marketing entry and sends people to account or in-page explanation', async () => {
  const gateway = await read('resources/views/gateway.blade.php');
  const header = await read('resources/views/components/dg/public-header.blade.php');

  assert.match(gateway, /Voir comment ça marche/);
  assert.match(gateway, /J’ai déjà un compte/);
  assert.match(gateway, /Vous pouvez utiliser GAMAD sans appartenir à une ZUMRA/);
  assert.doesNotMatch(gateway, /route\('landing'\)/);
  assert.doesNotMatch(gateway, /Découvrir le réseau/);
  assert.doesNotMatch(header, /route\('landing'\)/);
  assert.match(header, /#comment-agir/);
  assert.match(header, /#zumra/);
});

test('standalone public discovery view has been retired while member discovery language is untouched', async () => {
  const controller = await read('app/Http/Controllers/LandingController.php');
  assert.match(controller, /redirect\(\)->route\('gateway', status: 301\)/);
  assert.match(controller, /Découvrir.*fonction du réseau/s);
});
