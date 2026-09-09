import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = path => readFileSync(path, 'utf8');

test('PROJECT-001 exposes a wide living project cockpit anchored in its ZUMRA', () => {
  const view = read('resources/views/projects/show.blade.php');
  const controller = read('app/Http/Controllers/ProjectController.php');
  const member = read('resources/css/member.css');
  const styles = read('resources/css/project-cv.css');

  assert.match(view, /:wide="true"/);
  assert.match(view, /ZUMRA MÈRE/);
  assert.match(view, /Comprendre en quelques secondes/);
  assert.match(view, /Jalons du projet/);
  assert.match(view, /Missions du projet/);
  assert.match(view, /Ce qu’il faut réunir pour avancer/);
  assert.match(view, /ÉTAT DU PROJET/);
  assert.match(view, /BESOINS DU PROJET/);
  assert.match(view, /PARTENAIRES & ACCOMPAGNEMENT/);
  assert.match(view, /Notre chemin vers la réussite/);
  assert.match(view, /Vers une organisation/);

  assert.match(controller, /\$project->load\(\['milestones', 'zumraGroup', 'autonomyPathway'\]\)/);
  assert.match(controller, /\$group = \$project->zumraGroup/);
  assert.match(controller, /MissionVisibilityService/);
  assert.match(controller, /canViewMission/);
  assert.match(controller, /'projectMissions'/);

  assert.match(member, /@import "\.\/project-cv\.css"/);
  assert.match(styles, /\.dg-project-cv__hero/);
  assert.match(styles, /\.dg-project-cv__layout/);
  assert.match(styles, /\.dg-project-cv__maturity-path/);
  assert.match(styles, /@media \(max-width: 700px\)/);
});

test('PROJECT-001 never invents project progress, funding, tasks or target dates', () => {
  const view = read('resources/views/projects/show.blade.php');

  assert.match(view, /\$progressPercentage !== null/);
  assert.match(view, /Non mesurée/);
  assert.match(view, /\$funding \?/);
  assert.match(view, /Non déclaré/);
  assert.match(view, /\$project->milestones/);
  assert.match(view, /\$projectMissions/);
  assert.doesNotMatch(view, /65%/);
  assert.doesNotMatch(view, /2 500 000/);
  assert.doesNotMatch(view, /Priorité haute/i);
  assert.doesNotMatch(view, /Tâches en cours/i);
  assert.doesNotMatch(view, /Échéance cible/i);
});

test('PROJECT-001 uses real governed routes for contribution pathways', () => {
  const view = read('resources/views/projects/show.blade.php');

  assert.match(view, /route\('zumra\.groups\.show'/);
  assert.match(view, /route\('projects\.missions\.create'/);
  assert.match(view, /route\('missions\.show'/);
  assert.match(view, /route\('needs\.create'/);
  assert.match(view, /route\('projects\.team\.request'/);
  assert.match(view, /route\('projects\.team\.invitation\.accept'/);
  assert.match(view, /route\('projects\.matching'/);
  assert.match(view, /route\('projects\.milestones\.complete'/);
  assert.match(view, /route\('projects\.autonomy\.show'/);
  assert.match(view, /route\('projects\.accompaniment\.show'/);
});
