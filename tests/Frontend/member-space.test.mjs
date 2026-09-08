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
  assert.match(tools, /contributions\.dashboard/);
  assert.match(tools, /zahab\.wallet\.dashboard/);
  assert.doesNotMatch(tools, /contributions\.index|zahab\.wallet\.person|callback_url/);
  assert.match(tools, /method="POST"[\s\S]*federation\.continue[\s\S]*@csrf/);
  for (const path of ['contributions/dashboard', 'wallet/dashboard', 'missions/index', 'transmissions/index', 'proofs/index', 'opportunities/index', 'federation/handoff', 'federation/error']) {
    assert.ok(existsSync(`resources/views/${path}.blade.php`), path);
  }
});

test('federation handoff keeps the token in a POST field with controller-provided CSP nonce', () => {
  const handoff = read('resources/views/federation/handoff.blade.php');
  assert.match(handoff, /method="POST" action="{{ \$callbackUrl }}"/);
  assert.match(handoff, /type="hidden" name="jeton" value="{{ \$token }}"/);
  assert.match(handoff, /<script nonce="{{ \$nonce }}">/);
  assert.doesNotMatch(handoff, /@vite|@livewire|localStorage|console\./);
});
