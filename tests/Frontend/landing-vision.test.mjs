import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
const read = path => readFileSync(path, 'utf8');

test('landing navigation resolves to unique server-rendered sections', () => {
  const html = read('resources/views/gateway.blade.php');
  const ids = [...html.matchAll(/\bid="([^"]+)"/g)].map(m => m[1]);
  assert.equal(new Set(ids).size, ids.length, 'duplicate anchors');
  for (const [, target] of html.matchAll(/href="#([^"]+)"/g)) {
    assert.ok(ids.includes(target), `missing anchor ${target}`);
  }
  assert.equal((html.match(/<h1\b/g) || []).length, 1);
  assert.equal((html.match(/<details>/g) || []).length, (html.match(/<summary>/g) || []).length);
  assert.doesNotMatch(html, /GAMAD|GeniusPay|DeepSeek|CAP-\d/i);
});

test('only canonical public pages appear in the sitemap; identity is noindex', () => {
  const sitemap = read('public/sitemap.xml');
  const urls = [...sitemap.matchAll(/<loc>([^<]+)<\/loc>/g)].map(m => m[1]);
  assert.deepEqual(urls, ['https://dgafrique.com/', 'https://dgafrique.com/decouvrir']);
  for (const [path, url] of [['gateway', urls[0]], ['foundation', urls[1]]]) {
    assert.ok(read(`resources/views/${path}.blade.php`).includes(`canonical="${url}"`));
  }
  const layout = read('resources/views/components/layouts/public.blade.php');
  assert.match(layout, /@if \(\$canonical\)[\s\S]*rel="canonical"[\s\S]*@else[\s\S]*noindex, follow/);
  for (const path of ['login', 'register', 'verify-account']) {
    assert.doesNotMatch(read(`resources/views/auth/${path}.blade.php`), /canonical=/);
  }
  assert.match(read('public/robots.txt'), /Sitemap: https:\/\/dgafrique.com\/sitemap.xml/);
});
