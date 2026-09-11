import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const read = (path) => fs.readFileSync(path, 'utf8');

test('PEOPLE-UX-001 — le Carrefour Personnes met la personne et ses capacités au premier plan', () => {
    const index = read('resources/views/discovery/index.blade.php');
    const show = read('resources/views/discovery/show.blade.php');

    assert.match(index, /CARREFOUR PERSONNES · GAMAD/);
    assert.match(index, /Des personnes avec qui agir/);
    assert.match(index, /Par capacité/);
    assert.match(index, /Des rapprochements qui ont du sens/);
    assert.match(index, /Pourquoi ce profil apparaît/);

    assert.match(show, /Une personne, avant tout/);
    assert.match(show, /Capacités actuelles/);
    assert.match(show, /Apprentissage/);
    assert.match(show, /Transmission/);
});

test('PEOPLE-UX-001 — le Carrefour réutilise consentement, capacités, matching et messagerie existants', () => {
    const controller = read('app/Http/Controllers/PeopleDiscoveryController.php');
    const index = read('resources/views/discovery/index.blade.php');

    assert.match(controller, /orientation_consent/);
    assert.match(controller, /discovery_consent/);
    assert.match(controller, /CapabilityStatement/);
    assert.match(controller, /PersonRecommendationEngine/);
    assert.match(index, /route\('messages\.direct'/);
    assert.doesNotMatch(controller, /create.*table/i);
    assert.doesNotMatch(index, /followers|likes|score humain/i);
});

test('PEOPLE-UX-001 — les filtres restent des projections de lecture et ne fabriquent pas de profils', () => {
    const controller = read('app/Http/Controllers/PeopleDiscoveryController.php');
    const css = read('resources/css/people-ux-001.css');
    const memberCss = read('resources/css/member.css');

    assert.match(controller, /availability/);
    assert.match(controller, /recent/);
    assert.match(controller, /where\('discovery_consented_at', '>=', now\(\)->subMonth\(\)\)/);
    assert.match(memberCss, /people-ux-001\.css/);
    assert.match(css, /dg-people-hero/);
    assert.match(css, /dg-person-profile/);
});
