import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const read = (path) => fs.readFileSync(path, 'utf8');

test('PEOPLE-UX-003 — le Carrefour Personnes est un dashboard autonome et dense', () => {
    const index = read('resources/views/discovery/index.blade.php');
    const css = read('resources/css/people-ux-002.css');
    const memberCss = read('resources/css/member.css');

    assert.match(index, /CARREFOUR PERSONNES · GAMAD/);
    assert.match(index, /Des personnes pour transformer/);
    assert.match(index, /Ma présence dans GAMAD/);
    assert.match(index, /Personnes à découvrir/);
    assert.match(index, /Explorer par capacité/);
    assert.match(index, /Explorer par territoire/);
    assert.match(index, /Pourquoi les personnes sont au cœur de GAMAD/);
    assert.match(index, /ensemble-640\.webp/);
    assert.doesNotMatch(index, /commencer-640\.webp/);
    assert.match(css, /people3-shell/);
    assert.match(css, /people3-left/);
    assert.match(css, /people3-right/);
    assert.match(css, /people3-cards/);
    assert.match(memberCss, /people-ux-002\.css/);
});

test('PEOPLE-UX-003 — le Carrefour réutilise consentement, capacités, matching et messagerie existants', () => {
    const controller = read('app/Http/Controllers/PeopleDiscoveryController.php');
    const index = read('resources/views/discovery/index.blade.php');

    assert.match(controller, /orientation_consent/);
    assert.match(controller, /discovery_consent/);
    assert.match(controller, /CapabilityStatement/);
    assert.match(controller, /PersonRecommendationEngine/);
    assert.match(controller, /selfProfile/);
    assert.match(index, /route\('messages\.direct'/);
    assert.match(index, /recommendations\.index/);
    assert.doesNotMatch(controller, /create.*table/i);
    assert.doesNotMatch(index, /followers|likes|score humain/i);
});

test('PEOPLE-UX-003 — la présence personnelle reste séparée de la découverte publique', () => {
    const controller = read('app/Http/Controllers/PeopleDiscoveryController.php');
    const index = read('resources/views/discovery/index.blade.php');

    assert.match(controller, /whereKey\(\$identity->reference\)/);
    assert.match(controller, /where\('core_identity_reference', '!=', \$identity->reference\)/);
    assert.match(index, /profil public/);
    assert.match(index, /Confidentialité par choix/);
    assert.match(index, /Compléter mon profil/);
});

test('PEOPLE-UX-003 — le profil public conserve les capacités structurées', () => {
    const show = read('resources/views/discovery/show.blade.php');
    assert.match(show, /Une personne, avant tout/);
    assert.match(show, /Capacités actuelles/);
    assert.match(show, /Apprentissage/);
    assert.match(show, /Transmission/);
});
