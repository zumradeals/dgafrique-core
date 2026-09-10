import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = path => readFileSync(path, 'utf8');

test('FORMATION-001 makes Formation a real ZUMRA surface backed by existing Transmission contracts', () => {
  const view = read('resources/views/zumra/groups/formation.blade.php');
  const controller = read('app/Http/Controllers/ZumraFormationController.php');
  const routes = read('routes/cap006.php');
  const world = read('resources/views/zumra/groups/show.blade.php');

  assert.match(view, /FORMATION · TRAVAIL · ADORATION/);
  assert.match(view, /Apprendre\. Pratiquer\. Transmettre\./);
  assert.match(view, /Transmissions de la ZUMRA/);
  assert.match(view, /Je veux apprendre/);
  assert.match(view, /Je peux transmettre/);
  assert.match(view, /route\('transmissions\.show'/);
  assert.match(view, /route\('transmissions\.create'/);

  assert.match(controller, /CapabilityStatement::KIND_LEARNING/);
  assert.match(controller, /CapabilityStatement::KIND_TRANSMISSION/);
  assert.match(controller, /Transmission::CONTEXT_ZUMRA/);
  assert.match(controller, /TransmissionVisibilityService/);
  assert.match(controller, /->filter\(fn \(Transmission \$transmission\): bool => \$visibility->canView/);

  assert.match(routes, /name\('zumra\.groups\.formation'\)/);
  assert.match(world, /route\('zumra\.groups\.formation', \$group\)/);
});

test('FORMATION-001 does not invent an LMS, human scores or fake progress', () => {
  const view = read('resources/views/zumra/groups/formation.blade.php');

  assert.match(view, /On ne fabrique ni cours ni progression fictive/);
  assert.match(view, /Aucun classement, aucun score humain/);
  assert.doesNotMatch(view, /65%/);
  assert.doesNotMatch(view, /niveau 1/i);
  assert.doesNotMatch(view, /certificat automatique/i);
});

test('Transmission creation exists and validates ZUMRA context through the existing options', () => {
  const view = read('resources/views/transmissions/create.blade.php');

  assert.match(view, /collect\(\$contextOptions\)/);
  assert.match(view, /name="context_choice"/);
  assert.match(view, /name="initiator_role"/);
  assert.match(view, /name="capability_label"/);
  assert.match(view, /name="learning_objective"/);
  assert.match(view, /route\('transmissions\.store'\)/);
  assert.match(view, /acceptation explicite/);
});

test('Project trajectory states the startup and mother ZUMRA feedback loop without automatic legal creation', () => {
  const project = read('resources/views/projects/show.blade.php');

  assert.match(project, /startup ou une autre organisation durable/);
  assert.match(project, /Rien n’est créé automatiquement/);
  assert.match(project, /circuler dans les deux sens/);
  assert.match(project, /route\('zumra\.groups\.formation', \$group\)/);
});
