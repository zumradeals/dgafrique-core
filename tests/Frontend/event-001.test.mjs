import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = path => readFileSync(path, 'utf8');

test('EVENT-001 exposes a real ZUMRA event space backed by CAP-068', () => {
  const view = read('resources/views/zumra/groups/events.blade.php');
  const routes = read('routes/cap068.php');
  const controller = read('app/Http/Controllers/ZumraEventSpaceController.php');

  assert.match(view, /Se retrouver pour apprendre et agir/);
  assert.match(view, /community-events\.zumra\.create/);
  assert.match(view, /community-events\.show/);
  assert.match(routes, /ZumraEventSpaceController::class, 'index'/);
  assert.match(controller, /CommunityEventService/);
  assert.match(controller, /expectsJson\(\)/);
  assert.doesNotMatch(controller, /CommunityEvent::query\(\)->create/);
});

test('the ZUMRA world Events tab is connected to the real event route', () => {
  const app = read('resources/js/app.js');

  assert.match(app, /dg-zumra-world-tabs a\[href="#evenements"\]/);
  assert.match(app, /\/evenements`/);
  assert.match(app, /EVENT-001/);
});

test('EVENT-001 restores the existing CAP-068 creation and detail surfaces without a parallel engine', () => {
  const create = read('resources/views/community-events/create.blade.php');
  const show = read('resources/views/community-events/show.blade.php');
  const styles = read('resources/css/event-001.css');
  const member = read('resources/css/member.css');

  assert.match(create, /Créer l’événement/);
  assert.match(show, /Vous organisez cet événement/);
  assert.match(show, /M'inscrire/);
  assert.match(styles, /\.dg-event-space/);
  assert.match(member, /@import "\.\/event-001\.css"/);
  assert.doesNotMatch(create + show, /nouveau moteur|calendrier parallèle/i);
});
