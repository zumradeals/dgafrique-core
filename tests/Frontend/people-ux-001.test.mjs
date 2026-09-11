import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const read = (path) => fs.readFileSync(path, 'utf8');

test('PEOPLE-UX-002 — le Carrefour Personnes adopte la grammaire visuelle des hubs GAMAD', () => {
    const index = read('resources/views/discovery/index.blade.php');
    const css = read('resources/css/people-ux-002.css');
    const memberCss = read('resources/css/member.css');

    assert.match(index, /HUMAINS · CAPACITÉS · ACTION/);
    assert.match(index, /PERSONNES/);
    assert.match(index, /Découvrir qui peut agir avec vous/);
    assert.match(index, /Ma présence dans GAMAD/);
    assert.match(index, /Explorer par capacité/);
    assert.match(index, /Des personnes proches de l’action/);
    assert.match(index, /Pourquoi les personnes sont-elles au cœur de GAMAD/);
    assert.match(index, /GAMAD n’affiche ni téléphone/);
    assert.match(css, /dg-people-hub-grid/);
    assert.match(css, /dg-people-hero-v2__art/);
    assert.match(css, /dg-people-wide-section/);
    assert.match(memberCss, /people-ux-002\.css/);
});

test('PEOPLE-UX-002 — le Carrefour réutilise consentement, capacités, matching et messagerie existants', () => {
    const controller = read('app/Http/Controllers/PeopleDiscoveryController.php');
    const index = read('resources/views/discovery/index.blade.php');

    assert.match(controller, /orientation_consent/);
    assert.match(controller, /discovery_consent/);
    assert.match(controller, /CapabilityStatement/);
    assert.match(controller, /PersonRecommendationEngine/);
    assert.match(controller, /selfProfile/);
    assert.match(index, /route\('messages\.direct'/);
    assert.doesNotMatch(controller, /create.*table/i);
    assert.doesNotMatch(index, /followers|likes|score humain/i);
});

test('PEOPLE-UX-002 — la présence personnelle reste séparée de la découverte publique', () => {
    const controller = read('app/Http/Controllers/PeopleDiscoveryController.php');
    const index = read('resources/views/discovery/index.blade.php');

    assert.match(controller, /whereKey\(\$identity->reference\)/);
    assert.match(controller, /where\('core_identity_reference', '!=', \$identity->reference\)/);
    assert.match(index, /Visible seulement par vous/);
    assert.match(index, /Visible dans le réseau/);
    assert.match(index, /Voir \/ compléter mon profil/);
});

test('PEOPLE-UX-002 — le profil public conserve les capacités structurées', () => {
    const show = read('resources/views/discovery/show.blade.php');
    assert.match(show, /Une personne, avant tout/);
    assert.match(show, /Capacités actuelles/);
    assert.match(show, /Apprentissage/);
    assert.match(show, /Transmission/);
});
