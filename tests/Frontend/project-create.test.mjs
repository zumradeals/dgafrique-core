import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = path => readFileSync(path, 'utf8');

test('PROJECT-CREATE-001 renders an immersive guided creation journey without changing the draft engine', () => {
  const view = read('resources/views/projects/draft/form.blade.php');
  const styles = read('resources/css/project-create.css');
  const member = read('resources/css/member.css');

  assert.match(view, /:wide="true"/);
  assert.match(view, /Donnez vie à votre projet\./);
  assert.match(view, /MONDE D’ANCRAGE/);
  assert.match(view, /ÉTAPE \{\{ \$stepNumber \}\} SUR/);
  assert.match(view, /VOTRE CHEMIN/);
  assert.match(view, /De l’idée à l’action\./);
  assert.match(view, /Votre brouillon vous appartient\./);
  assert.match(view, /La ZUMRA porte le projet/);
  assert.match(view, /Je porte le projet/);
  assert.match(view, /Faire naître le projet/);
  assert.match(view, /route\('projects\.draft\.update'/);
  assert.match(view, /route\('projects\.draft\.confirm'/);
  assert.match(view, /name="zumra_group_reference"/);
  assert.match(view, /name="owner_type"/);
  assert.match(view, /name="image"/);

  assert.match(member, /@import "\.\/project-create\.css"/);
  assert.match(styles, /\.dg-project-create__hero/);
  assert.match(styles, /\.dg-project-create__progress/);
  assert.match(styles, /\.dg-project-create__layout/);
  assert.match(styles, /@media \(max-width: 760px\)/);
});

test('PROJECT-CREATE-001 keeps the canonical ten-step draft path visible', () => {
  const service = read('app/Application/Projects/ProjectDraftService.php');
  const view = read('resources/views/projects/draft/form.blade.php');

  for (const step of ['audience', 'nom', 'resume', 'probleme', 'solution', 'beneficiaires', 'logistique', 'objectifs', 'besoins', 'relire']) {
    assert.match(service, new RegExp(`'${step}'`));
  }
  assert.match(view, /ProjectDraftService::STEPS/);
  assert.match(view, /ProjectDraftService::STEP_LABELS/);
  assert.match(view, /Enregistrer et reprendre plus tard/);
});
