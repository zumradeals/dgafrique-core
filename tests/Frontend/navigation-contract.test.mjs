import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const navigation = readFileSync('resources/views/components/dg/navigation.blade.php', 'utf8');
const styles = readFileSync('resources/css/app.css', 'utf8');
const script = readFileSync('resources/js/app.js', 'utf8');
const state = readFileSync('resources/views/components/dg/state.blade.php', 'utf8');

test('the mobile navigation keeps the canonical five controls in order', () => {
    const controls = [...navigation.matchAll(/data-mobile-primary="([^"]+)"/g)].map((match) => match[1]);

    assert.deepEqual(controls, ['fil', 'discover', 'act', 'zumra', 'space']);
    assert.equal(controls.length, 5);
    assert.doesNotMatch(navigation, />\s*Plus\s*</i);
});

test('every canonical mobile control keeps a visible human label', () => {
    for (const label of ['Fil', 'Découvrir', 'Agir', 'ZUMRA', 'Espace']) {
        assert.match(navigation, new RegExp('<span>' + label + '</span>'));
    }

    assert.match(navigation, /aria-label="Mon espace"/);
    assert.match(navigation, /aria-label="Agir — ouvrir les actions disponibles"/);
});

test('discover is limited to people, needs, and projects', () => {
    assert.match(navigation, /array_slice\(\$centres, 1, 3\)/);
    assert.match(navigation, /data-discover-list/);
    assert.match(navigation, /'people'.*'needs'.*'projects'/s);
});

test('desktop keeps the six direct centres and a distinct action control', () => {
    const centreDefinitions = navigation.slice(navigation.indexOf('$centres = ['), navigation.indexOf('$discoverActive'));
    const centres = [...centreDefinitions.matchAll(/'key' => '([^']+)'/g)].map((match) => match[1]);

    assert.deepEqual(centres, ['fil', 'people', 'needs', 'projects', 'zumra', 'space']);
    assert.match(navigation, /data-desktop-centre="\{\{ \$centre\['key'\] \}\}"/);
    assert.match(navigation, /class="dg-button dg-button--solar"/);
    assert.match(navigation, /<span>Agir<\/span>/);
});

test('the navigation points only at named real contracts', () => {
    for (const route of [
        'activity.index',
        'people.index',
        'needs.index',
        'projects.index',
        'zumra.index',
        'member.space',
    ]) {
        assert.match(navigation, new RegExp(route.replace('.', '\\.')));
    }

    assert.match(navigation, /@disabled\(\$actionCount === 0\)/);
    assert.match(navigation, /Seules les actions réellement disponibles/);
});

test('mobile and low-bandwidth accessibility gates exist in the foundation', () => {
    assert.match(styles, /min-width:\s*360px/);
    assert.match(styles, /min-height:\s*3rem/);
    assert.match(styles, /env\(safe-area-inset-bottom\)/);
    assert.match(styles, /@media \(prefers-reduced-motion: reduce\)/);
    assert.match(styles, /@media \(forced-colors: active\)/);
    assert.match(styles, /html\[dir="rtl"\]/);
    assert.match(navigation, /aria-current="page"/);
    assert.match(navigation, /role="dialog"/);
    assert.match(navigation, /aria-modal="true"/);
});

test('the action sheet restores focus and handles keyboard and system back', () => {
    assert.match(script, /window\.history\.pushState/);
    assert.match(script, /window\.addEventListener\('popstate'/);
    assert.match(script, /trigger\?\.focus\(\)/);
    assert.match(script, /trapFocus\(event\)/);
    assert.match(navigation, /@keydown\.escape\.window/);
    assert.match(navigation, /@keydown\.tab/);
});

test('transversal honest states cover the UJ-00 error contract', () => {
    for (const type of ['loading', 'empty', 'error', 'offline', 'unavailable', 'forbidden', 'not-found', 'conflict', 'success']) {
        assert.match(state, new RegExp("'" + type + "'"));
    }

    assert.match(state, /role="\{\{ \$liveRole \}\}"/);
    assert.match(state, /aria-live=/);
});
