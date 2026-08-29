import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const read = (path) => readFile(new URL(`../../${path}`, import.meta.url), 'utf8');

const surfaces = [
  'resources/views/gateway.blade.php',
  'resources/views/foundation.blade.php',
  'resources/views/auth/login.blade.php',
  'resources/views/auth/register.blade.php',
  'resources/views/auth/verify-account.blade.php',
];

test('UJ-02 ships every P0 public and identity surface', async () => {
  for (const surface of surfaces) {
    const content = await read(surface);
    assert.ok(content.includes('<x-layouts.public'), `${surface} must use the public foundation`);
    assert.equal(/GAMAD/i.test(content), false, `${surface} must not expose GAMAD jargon`);
  }
});

test('gateway explains action before architecture and offers real next steps', async () => {
  const content = await read('resources/views/gateway.blade.php');
  assert.match(content, /Réseau social d.action/);
  assert.match(content, /route\('register'\)/);
  assert.match(content, /route\('landing'\)/);
  assert.match(content, /adhésion à une ZUMRA/);
});

test('public discovery stays honest when the database is empty', async () => {
  const content = await read('resources/views/foundation.blade.php');
  assert.match(content, /\$realMoments->isEmpty\(\)/);
  assert.match(content, /Aucun besoin ou projet public/);
  assert.match(content, /faux contenus/);
  assert.doesNotMatch(content, /faker|fixture|demo data/i);
});

test('identity forms post only to governed named routes', async () => {
  const login = await read('resources/views/auth/login.blade.php');
  const register = await read('resources/views/auth/register.blade.php');
  const verify = await read('resources/views/auth/verify-account.blade.php');

  assert.match(login, /route\('login\.store'\)/);
  assert.match(register, /route\('register\.store'\)/);
  assert.match(verify, /route\('register\.verify\.store'\)/);
  assert.match(verify, /route\('register\.verify\.resend'\)/);
});

test('secrets and verification codes are never flashed back into fields', async () => {
  const login = await read('resources/views/auth/login.blade.php');
  const register = await read('resources/views/auth/register.blade.php');
  const verify = await read('resources/views/auth/verify-account.blade.php');

  assert.doesNotMatch(login, /old\(['"]secret/);
  assert.doesNotMatch(register, /old\(['"]password/);
  assert.doesNotMatch(verify, /old\(['"]code/);
});
