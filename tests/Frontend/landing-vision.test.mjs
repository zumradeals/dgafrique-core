import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');

test('gateway explains the network vision before exposing internal architecture', async () => {
  const gateway = await read('resources/views/gateway.blade.php');
  assert.match(gateway, /Réseau social d.action/);
  assert.match(gateway, /développement humain/i);
  assert.match(gateway, /ZUMRA/);
  assert.match(gateway, /Formation · Travail · Adoration/);
  assert.match(gateway, /La personne décide/);
  assert.match(gateway, /visibilité se choisit/);
  assert.match(gateway, /réalisations|preuves/i);
  assert.doesNotMatch(gateway, /GAMAD Core|GeniusPay|DeepSeek|CAP-\d+/i);
});

test('gateway distinguishes current public discovery from progressive network construction', async () => {
  const gateway = await read('resources/views/gateway.blade.php');
  assert.match(gateway, /Que puis-je découvrir aujourd’hui sans compte/);
  assert.match(gateway, /besoins et les projets réellement partagés publiquement/);
  assert.match(gateway, /autres parcours du réseau se construisent progressivement/);
  assert.match(gateway, /compte.*gratuit/i);
  assert.match(gateway, /adhésion.*ZUMRA.*distincte/i);
});
