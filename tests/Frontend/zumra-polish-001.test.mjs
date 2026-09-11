import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const read = (path) => fs.readFileSync(path, 'utf8');

test('ZUMRA-POLISH-001 — tous les sous-espaces partagent la même navigation locale', () => {
    const app = read('resources/js/app.js');

    assert.match(app, /connectZumraPolishNavigation/);
    assert.match(app, /activite\|membres\|formation\|discussion\|evenements/);
    assert.match(app, /⌂ Accueil/);
    assert.match(app, /◉ Fil/);
    assert.match(app, /♙ Membres/);
    assert.match(app, /◈ Formation/);
    assert.match(app, /▢ Discussion/);
    assert.match(app, /▣ Événements/);
    assert.match(app, /aria-current/);
});

test('ZUMRA-POLISH-001 — la cohérence visuelle reste une couche de présentation', () => {
    const css = read('resources/css/zumra-polish-001.css');
    const memberCss = read('resources/css/member.css');
    const app = read('resources/js/app.js');

    assert.match(memberCss, /zumra-polish-001\.css/);
    assert.match(css, /dg-zumra-polish-tabs/);
    assert.match(css, /dg-zumra-members/);
    assert.match(css, /dg-event-space/);
    assert.match(css, /dg-zumra-discussion/);
    assert.match(css, /dg-formation/);

    assert.doesNotMatch(app, /createFeedEngine|createMemberDirectory|createDiscussionEngine|createEventEngine/);
    assert.doesNotMatch(css, /visibility:\s*hidden/);
});

test('ZUMRA-POLISH-001 — le monde racine conserve ses raccords certifiés', () => {
    const app = read('resources/js/app.js');

    assert.match(app, /connectZumraEventTab/);
    assert.match(app, /connectZumraMembersTab/);
    assert.match(app, /connectZumraActivityTab/);
});
