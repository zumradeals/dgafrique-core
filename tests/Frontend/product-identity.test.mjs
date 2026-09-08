import assert from 'node:assert/strict';
import { readdir, readFile } from 'node:fs/promises';
import { join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

const root = fileURLToPath(new URL('../../', import.meta.url));
const viewsRoot = join(root, 'resources/views');

async function bladeFiles(directory) {
  const entries = await readdir(directory, { withFileTypes: true });
  const files = [];

  for (const entry of entries) {
    const path = join(directory, entry.name);
    if (entry.isDirectory()) {
      files.push(...await bladeFiles(path));
    } else if (entry.isFile() && entry.name.endsWith('.blade.php')) {
      files.push(path);
    }
  }

  return files;
}

test('retired DG Afrique product name is absent from every rendered Blade surface', async () => {
  const violations = [];

  for (const file of await bladeFiles(viewsRoot)) {
    const content = await readFile(file, 'utf8');
    if (/DG Afrique/i.test(content)) {
      violations.push(relative(viewsRoot, file));
    }
  }

  assert.deepEqual(
    violations,
    [],
    `retired DG Afrique product name remains in: ${violations.join(', ')}`,
  );
});

test('canonical GAMAD identity is present on the shared public and member brand surfaces', async () => {
  for (const path of [
    'components/dg/public-header.blade.php',
    'components/dg/public-footer.blade.php',
    'components/layouts/public.blade.php',
    'components/layouts/member.blade.php',
    'components/dg/navigation.blade.php',
  ]) {
    const content = await readFile(join(viewsRoot, path), 'utf8');
    assert.match(content, /GAMAD/, `${path} must expose the canonical GAMAD identity`);
  }
});

test('canonical product and journey authorities encode GAMAD public and GAMAD Core invisible', async () => {
  const product = await readFile(join(root, 'docs/product/EXPERIENCE-PRODUIT-CANONIQUE.md'), 'utf8');
  const journey = await readFile(join(root, 'docs/roadmap/USER-JOURNEY-001.md'), 'utf8');

  assert.match(product, /^# Expérience produit canonique — GAMAD/m);
  assert.match(product, /GAMAD est le nom canonique du réseau social d’action visible/);
  assert.match(product, /GAMAD Core reste le moteur invisible de confiance/);
  assert.doesNotMatch(product, /DG Afrique est la porte publique et humaine/i);
  assert.doesNotMatch(product, /mot « GAMAD ».*institution invisible/i);

  assert.match(journey, /^# USER-JOURNEY-001 — Opération Parcours de l'Utilisateur GAMAD/m);
  assert.match(journey, /expliquer \*\*GAMAD comme réseau social d’action\*\*/);
  assert.match(journey, /compte GAMAD/);
  assert.match(journey, /architecture de GAMAD Core/);
});
