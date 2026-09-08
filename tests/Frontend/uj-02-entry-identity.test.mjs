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

test('UJ-02 ships every P0 public and identity surface under the GAMAD product identity', async () => {
  for (const surface of surfaces) {
    const content = await read(surface);
    assert.ok(content.includes('<x-layouts.public'), `${surface} must use the public foundation`);
    assert.match(content, /GAMAD/, `${surface} must expose the canonical GAMAD product identity`);
    assert.equal(/DG Afrique/i.test(content), false, `${surface} must not expose the retired DG Afrique product name`);
  }
});

test('gateway explains action before architecture and offers real next steps', async () => {
  const content = await read('resources/views/gateway.blade.php');
  assert.match(content, /Réseau social d.action/);
  assert.match(content, /GAMAD est un réseau social d.action/);
  assert.match(content, /route\('register'\)/);
  assert.match(content, /route\('landing'\)/);
  assert.match(content, /adhésion à une ZUMRA/);
});

test('public discovery stays honest when the database is empty', async () => {
  const content = await read('resources/views/foundation.blade.php');
  assert.match(content, /\$realMoments->isEmpty\(\)/);
  assert.match(content, /Aucun besoin ou projet public/);
  assert.match(content, /data-public-empty/);
  assert.match(content, /@foreach \(\$realMoments as \$moment\)/);
  assert.match(content, /\{\{ \$moment\['titre'\] \}\}/);
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

// Regression for the missing --color-* variables that made the discovery CTA unreadable.
test('public and identity surfaces reference defined color tokens', async () => {
  const styles = await read('resources/css/app.css');
  const definitions = new Set([...styles.matchAll(/(--[\w-]+)\s*:/g)].map((match) => match[1]));
  for (const surface of [...surfaces,
    'resources/views/components/dg/public-header.blade.php',
    'resources/views/components/dg/public-footer.blade.php',
  ]) {
    const content = await read(surface);
    for (const [, token] of content.matchAll(/var\((--[\w-]+)\)/g)) {
      assert.ok(definitions.has(token), `${surface} references undefined ${token}`);
    }
  }
});

test('entry illustrations exist locally with a bounded mobile payload', async () => {
  for (const name of ['ensemble', 'commencer']) {
    for (const width of [640, 1280]) {
      const bytes = await readFile(new URL(`../../public/images/entry/${name}-${width}.webp`, import.meta.url));
      assert.equal(bytes.toString('ascii', 8, 12), 'WEBP');
      assert.ok(bytes.length < (width === 640 ? 100_000 : 250_000), `${name}-${width} exceeds the image budget`);
    }
  }
});
