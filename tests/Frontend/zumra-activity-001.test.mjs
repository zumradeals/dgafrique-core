import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

test('ZUMRA-ACTIVITY-001 — le monde ZUMRA raccorde le mini Fil sans moteur navigateur', () => {
    const app = read('resources/js/app.js');
    const routes = read('routes/cap021.php');
    const view = read('resources/views/zumra/groups/activity.blade.php');

    assert.match(routes, /zumra\.groups\.activity/);
    assert.match(app, /connectZumraActivityTab/);
    assert.match(app, /\/activite/);
    assert.match(view, /FIL · ZUMRA/);
    assert.match(view, /Aucun post libre/);
    assert.doesNotMatch(app, /createZumraFeedEngine|publishZumraPost|likeZumraActivity/);
});

test('ZUMRA-ACTIVITY-001 — la projection reste adossée aux autorités métier existantes', () => {
    const projection = read('app/Application/Activity/ZumraActivityProjection.php');

    for (const authority of [
        'MissionVisibilityService',
        'TransmissionVisibilityService',
        'ProofVisibilityService',
        'CommunityEventService',
        'ZumraGroupEvent',
    ]) {
        assert.match(projection, new RegExp(authority));
    }

    assert.doesNotMatch(projection, /Schema::create|DB::table\(.+insert|->create\(\[/);
});
