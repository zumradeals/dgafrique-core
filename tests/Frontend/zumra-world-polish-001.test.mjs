import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = path => readFileSync(path, 'utf8');

test('ZUMRA-WORLD-POLISH-001 keeps Formation and Project as the two visual anchors without inventing product state', () => {
  const view = read('resources/views/zumra/groups/show.blade.php');
  const member = read('resources/css/member.css');
  const polish = read('resources/css/zumra-world-polish-001.css');

  assert.match(member, /@import "\.\/zumra-world-polish-001\.css"/);
  assert.match(view, /id="formation"/);
  assert.match(view, /id="projets"/);
  assert.match(view, /route\('zumra\.groups\.formation', \$group\)/);
  assert.match(view, /\$projectProgress === null \? 'Non mesurée'/);

  assert.match(polish, /\.dg-zumra-world-center > #formation/);
  assert.match(polish, /\.dg-zumra-world-center > #projets/);
  assert.match(polish, /\.dg-zumra-world-tabs\s*\{/);
  assert.match(polish, /position:\s*sticky/);
  assert.match(polish, /@media \(max-width: 620px\)/);

  for (const forbidden of ['faux compteur', 'score humain calculé', 'progression automatique', '100% par défaut']) {
    assert.doesNotMatch(polish, new RegExp(forbidden, 'i'));
  }
});

test('ZUMRA-WORLD-POLISH-001 does not replace real links with decorative placeholders', () => {
  const view = read('resources/views/zumra/groups/show.blade.php');

  assert.match(view, /Entrer dans l’espace Formation/);
  assert.match(view, /\{\{ \$projectAction \}\}/);
  assert.match(view, /route\('projects\.create', \['group' => \$group->public_reference\]\)/);
  assert.match(view, /route\('needs\.create', \['group' => \$group->public_reference\]\)/);
});
