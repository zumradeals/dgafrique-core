import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const read = (path) => fs.readFileSync(path, 'utf8');

test('NEED-UX-001 — le Carrefour Besoins est dense, autonome et transversal', () => {
    const index = read('resources/views/needs/index.blade.php');
    const css = read('resources/css/need-ux-001.css');
    const memberCss = read('resources/css/member.css');

    assert.match(index, /CARREFOUR BESOINS · GAMAD/);
    assert.match(index, /Un besoin peut devenir le point de départ d’une action/);
    assert.match(index, /Besoins à découvrir/);
    assert.match(index, /Explorer par catégorie/);
    assert.match(index, /Explorer par territoire/);
    assert.match(index, /Où puis-je aider/);
    assert.match(index, /Déclarer un besoin n’est pas créer un projet/);
    assert.match(index, /commencer-640\.webp/);
    assert.match(css, /dg-needs-layout/);
    assert.match(css, /dg-needs-aside/);
    assert.match(css, /dg-needs-grid/);
    assert.match(memberCss, /need-ux-001\.css/);
});

test('NEED-UX-001 — la page reste branchée sur le moteur Need canonique', () => {
    const controller = read('app/Http/Controllers/NeedController.php');
    const service = read('app/Application/Needs/NeedService.php');
    const index = read('resources/views/needs/index.blade.php');

    assert.match(service, /OWNER_PERSON/);
    assert.match(service, /OWNER_GROUP/);
    assert.match(service, /OWNER_PROJECT/);
    assert.match(service, /canView/);
    assert.match(controller, /NeedConfiguration/);
    assert.match(controller, /urgentNeeds/);
    assert.match(index, /route\('needs\.create'\)/);
    assert.match(index, /route\('needs\.show'/);
    assert.doesNotMatch(index, /like|followers|popularité/i);
});
