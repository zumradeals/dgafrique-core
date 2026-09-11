import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const view = fs.readFileSync('resources/views/activity/index.blade.php', 'utf8');
const css = fs.readFileSync('resources/css/feed-ux-001.css', 'utf8');
const service = fs.readFileSync('app/Application/Activity/ActivityFeedService.php', 'utf8');

test('FEED-UX-001 matérialise la maquette GAMAD sans second moteur social', () => {
  assert.match(view, /Le Fil global GAMAD/);
  assert.match(view, /Ce qui se passe\. Ce qui avance/);
  assert.match(view, /Ceci vous concerne/);
  assert.match(view, /Activité de vos ZUMRA/);
  assert.match(view, /Besoins récents/);
  assert.match(view, /Projets récents/);
  assert.match(css, /grid-template-columns:13\.5rem minmax\(0,1fr\)18rem/);
});

test('le Fil reste une projection des contrats canoniques', () => {
  assert.match(service, /NeedService/);
  assert.match(service, /ProjectService/);
  assert.match(service, /MissionVisibilityService/);
  assert.match(service, /TransmissionVisibilityService/);
  assert.match(service, /ProofVisibilityService/);
  assert.match(view, /\$feed/);
  assert.doesNotMatch(view, /like|follower|popularité/i);
});

test('les actions de la maquette utilisent les routes métier existantes', () => {
  assert.match(view, /route\('needs\.create'\)/);
  assert.match(view, /route\('projects\.create'\)/);
  assert.match(view, /route\('zumra\.groups\.index'\)/);
  assert.match(view, /comment_url/);
  assert.match(view, /share_url/);
  assert.match(view, /contact_url/);
});
