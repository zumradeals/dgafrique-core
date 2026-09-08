import assert from 'node:assert/strict';
import { readFileSync, existsSync } from 'node:fs';
import test from 'node:test';
const read = path => readFileSync(path, 'utf8');

test('space local entry points have unique reachable anchors without requiring JavaScript', () => {
  const html = read('resources/views/member/space.blade.php') + read('resources/views/member/tools.blade.php');
  const ids = [...html.matchAll(/\bid="([^"$]+)"/g)].map(match => match[1]);
  assert.equal(new Set(ids).size, ids.length);
  for (const [, target] of html.matchAll(/href="#([^"]+)"/g)) assert.ok(ids.includes(target), target);
  assert.doesNotMatch(html, /localStorage|onboarding_completed|x-show|x-cloak/);
});

test('tool destinations use human HTML surfaces and the protected POST continuation', () => {
  const tools = read('resources/views/member/tools.blade.php');
  const space = read('resources/views/member/space.blade.php');
  assert.match(tools, /contributions\.dashboard/);
  assert.match(tools, /zahab\.wallet\.dashboard/);
  assert.match(space, /opportunities\.index/);
  assert.doesNotMatch(tools, /contributions\.index|zahab\.wallet\.person|callback_url/);
  assert.match(tools, /method="POST"[\s\S]*federation\.continue[\s\S]*@csrf/);
  for (const path of ['contributions/dashboard', 'wallet/dashboard', 'missions/index', 'transmissions/index', 'proofs/index', 'opportunities/index', 'federation/handoff', 'federation/error']) {
    assert.ok(existsSync(`resources/views/${path}.blade.php`), path);
  }
});

test('UJ-03A member shell is universal while Mon espace remains the only wide cockpit', () => {
  const space = read('resources/views/member/space.blade.php');
  const layout = read('resources/views/components/layouts/member.blade.php');
  const memberEntry = read('resources/css/member.css');
  const memberSpaceStyles = read('resources/css/member-space.css');
  const vite = read('vite.config.js');

  assert.match(space, /:wide="true"/);
  assert.match(layout, /'wide' => false/);
  assert.match(layout, /dg-member-wide/);
  assert.match(layout, /resources\/css\/member\.css/);
  assert.match(vite, /resources\/css\/member\.css/);
  assert.match(memberEntry, /@import "\.\/member-space\.css"/);
  assert.match(memberEntry, /\.dg-desktop-nav__inner[\s\S]*96rem[\s\S]*grid-template-columns: auto minmax\(0, 1fr\) auto auto/);
  assert.match(memberEntry, /\.dg-member-account[\s\S]*display: flex/);
  assert.match(memberSpaceStyles, /\.dg-member-wide \.dg-app-main/);
});

test('UJ-03A cockpit keeps newcomer language honest', () => {
  const space = read('resources/views/member/space.blade.php');

  assert.match(space, /Bienvenue chez vous\./);
  assert.match(space, /Quel sera votre premier pas \?/);
  assert.match(space, /Premier pas à choisir/);
  assert.match(space, /Choisissez ce qui compte pour vous aujourd’hui\./);
  assert.match(space, /Votre situation/);
  assert.match(space, /Ce que vous pouvez faire maintenant/);
  assert.match(space, /Vos accès rapides/);
  assert.match(space, />Personnes</);
  assert.match(space, />Projets</);
  assert.doesNotMatch(space, />Mes personnes</);
  assert.doesNotMatch(space, />Mes projets</);
  assert.doesNotMatch(space, /Tout mon espace/);
  assert.match(space, /valeur ne se mesure pas en likes/);
});

test('UJ-03B widens the hero and keeps the welcome on one line on large screens', () => {
  const memberEntry = read('resources/css/member.css');

  assert.match(memberEntry, /\.dg-cockpit-hero[\s\S]*2\.05fr[\s\S]*0\.75fr/);
  assert.match(memberEntry, /\.dg-cockpit-hero-main[\s\S]*1\.25fr[\s\S]*0\.75fr[\s\S]*min-height: 31rem/);
  assert.match(memberEntry, /\.dg-cockpit-hero-copy h1[\s\S]*line-height: 1\.06/);
  assert.match(memberEntry, /\.dg-cockpit-intro[\s\S]*max-width: 46ch[\s\S]*font-size: 1\.05rem/);
  assert.match(memberEntry, /@media \(min-width: 1440px\)[\s\S]*1\.32fr[\s\S]*0\.68fr[\s\S]*white-space: nowrap/);
});

test('logout remains a governed POST with CSRF and is shared by navigation', () => {
  const navigation = read('resources/views/components/dg/navigation.blade.php');
  assert.match(navigation, /method="POST" action="{{ route\('logout'\) }}"/);
  assert.match(navigation, /@csrf/);
  assert.match(navigation, /dg-member-account__avatar/);
  assert.match(navigation, />Déconnexion</);
});

test('federation handoff keeps the token in a POST field with controller-provided CSP nonce', () => {
  const handoff = read('resources/views/federation/handoff.blade.php');
  assert.match(handoff, /method="POST" action="{{ \$callbackUrl }}"/);
  assert.match(handoff, /type="hidden" name="jeton" value="{{ \$token }}"/);
  assert.match(handoff, /<script nonce="{{ \$nonce }}">/);
  assert.doesNotMatch(handoff, /@vite|@livewire|localStorage|console\./);
});
