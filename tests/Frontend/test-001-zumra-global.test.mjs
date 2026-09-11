import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

test('TEST-001 — le monde ZUMRA expose ses cinq surfaces cohérentes', () => {
    const routes = read('routes/cap006.php') + read('routes/cap021.php') + read('routes/cap068.php');
    const world = read('resources/views/zumra/groups/show.blade.php');
    const app = read('resources/js/app.js');

    assert.match(routes, /zumra\.groups\.members/);
    assert.match(routes, /zumra\.groups\.discussion/);
    assert.match(routes, /zumra\.groups\.formation/);
    assert.match(routes, /community-events\.zumra\.index/);

    assert.match(world, /Membres/);
    assert.match(world, /Formation/);
    assert.match(world, /Discussion/);
    assert.match(world, /Événements|Evenements/);

    // Les raccords de présentation doivent rester des destinations ZUMRA,
    // sans introduire de moteur social parallèle côté navigateur.
    assert.match(app, /\/zumra\/groupes\//);
    assert.doesNotMatch(app, /createMessageEngine|createMemberDirectory|createEventEngine/);
});

test('TEST-001 — chaque surface réutilise les autorités métier existantes', () => {
    const discussion = read('app/Http/Controllers/ZumraDiscussionController.php');
    const members = read('app/Http/Controllers/ZumraMembersController.php');
    const events = read('app/Http/Controllers/ZumraEventSpaceController.php');

    assert.match(discussion, /ContextCommentService/);
    assert.match(members, /ZumraGroupMembership/);
    assert.match(members, /ZumraGroupRole/);
    assert.match(members, /PersonProfile/);
    assert.match(events, /CommunityEventService/);
});

test('TEST-001 — la recette globale conserve les tests métier certifiés', () => {
    const workflow = read('.github/workflows/member-space.yml');
    for (const contract of [
        'ZumraWorldSmokeTest.php',
        'ZumraFormationSmokeTest.php',
        'ZumraDiscussionTest.php',
        'ZumraMembersTest.php',
        'ZumraEventSpaceTest.php',
        'CommunityEventTest.php',
        'IdentityAuthorityGuardTest.php',
    ]) {
        assert.match(workflow, new RegExp(contract.replace('.', '\\.')));
    }
});
