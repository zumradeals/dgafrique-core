import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = path => readFileSync(path, 'utf8');

test('ZUMRA-WORLD-001 preserves the canonical world composition', () => {
  const view = read('resources/views/zumra/groups/show.blade.php');
  const styles = read('resources/css/zumra-world.css');
  const member = read('resources/css/member.css');

  assert.match(view, /:wide="true"/);
  assert.match(view, /dg-zumra-world-hero/);
  assert.match(view, /Accueil/);
  assert.match(view, /Projet principal/);
  assert.match(view, /Membres/);
  assert.match(view, /Demandes/);
  assert.match(view, /Discussion/);
  assert.match(view, /Événements/);
  assert.match(view, /Besoins/);
  assert.match(view, /Missions/);
  assert.match(view, /À propos/);
  assert.match(view, /Navigation rapide/);
  assert.match(view, /Vision/);
  assert.match(view, /Agir maintenant/);
  assert.match(view, /Activité récente/);
  assert.match(view, /À faire maintenant/);
  assert.match(view, /Progression actuelle/);
  assert.match(view, /Prochaine étape/);
  assert.match(view, /route\('projects\.create', \['group' => \$group->public_reference\]\)/);
  assert.match(view, /route\('needs\.create', \['group' => \$group->public_reference\]\)/);
  assert.match(member, /@import "\.\/zumra-world\.css"/);
  assert.match(styles, /\.dg-zumra-world-layout/);
  assert.match(styles, /grid-template-columns:minmax\(13\.5rem/);
  assert.match(styles, /@media\(max-width:620px\)/);
});

test('the principal project is canonical and derived projects remain optional', () => {
  const view = read('resources/views/zumra/groups/show.blade.php');

  assert.match(view, /\$primaryProject = \$projectSequence->first\(\)/);
  assert.match(view, /\$derivedProjects = \$projectSequence->slice\(1\)/);
  assert.match(view, /Projet principal à formaliser/);
  assert.match(view, /Aucun projet dérivé pour le moment/);
  assert.match(view, /Une ZUMRA peut fonctionner avec son seul projet principal/);
  assert.match(view, /Une ZUMRA peut très bien fonctionner avec un seul projet principal/);
  assert.doesNotMatch(view, /projet dérivé obligatoire/i);
});

test('unimplemented world surfaces stay visible without fake backend actions', () => {
  const view = read('resources/views/zumra/groups/show.blade.php');

  assert.match(view, /aria-disabled="true" title="Le canal de discussion/);
  assert.match(view, /mini-fil ZUMRA sera raccordé/);
  assert.match(view, /Modifier la couverture/);
  assert.match(view, /Paramètres/);
});
