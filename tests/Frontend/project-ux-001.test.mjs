import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const read = (path) => fs.readFileSync(path, 'utf8');

test('PROJECT-UX-001 — le Carrefour Projets est un monde GAMAD dense et autonome', () => {
    const index = read('resources/views/projects/index.blade.php');
    const css = read('resources/css/project-ux-001.css');
    const memberCss = read('resources/css/member.css');

    assert.match(index, /CARREFOUR PROJETS · GAMAD/);
    assert.match(index, /Des idées qui deviennent des actions/);
    assert.match(index, /Projets en vedette/);
    assert.match(index, /Explorer par domaine d'action/);
    assert.match(index, /Explorer par territoire/);
    assert.match(index, /Un projet n'est pas un besoin/);
    assert.match(index, /Comment ça marche/);
    assert.match(css, /project-hub__layout/);
    assert.match(css, /project-hub__aside/);
    assert.match(css, /project-hub__cards/);
    assert.match(memberCss, /project-ux-001\.css/);
});

test('PROJECT-UX-001 — la page réutilise les routes et données canoniques du moteur Projet', () => {
    const controller = read('app/Http/Controllers/ProjectController.php');
    const index = read('resources/views/projects/index.blade.php');

    assert.match(controller, /ProjectHubPresentation/);
    assert.match(controller, /ProjectService/);
    assert.match(controller, /canView\(\$project, \$identity->reference\)/);
    assert.match(index, /route\('projects\.show'/);
    assert.match(index, /route\('projects\.create'/);
    assert.match(index, /\$networkStats/);
    assert.match(index, /\$categoryDistribution/);
    assert.match(index, /\$filterLocations/);
    assert.match(index, /\$cards/);
    assert.doesNotMatch(index, /followers|likes|popularité/i);
});

test('PROJECT-UX-001 — la progression affichée reste factuelle et non inventée', () => {
    const presentation = read('app/Application/Projects/ProjectHubPresentation.php');
    const index = read('resources/views/projects/index.blade.php');

    assert.match(presentation, /milestoneProgressPercentage/);
    assert.match(presentation, /Aucun jalon défini/);
    assert.match(index, /Non mesuré/);
    assert.match(index, /progress_label/);
    assert.doesNotMatch(index, /score d'impact|score humain/i);
});

test('PROJECT-UX-001 — la création reste protégée par le parcours ZUMRA existant', () => {
    const draft = read('app/Http/Controllers/ProjectDraftController.php');
    const index = read('resources/views/projects/index.blade.php');

    assert.match(draft, /hasActiveProgramMembership/);
    assert.match(draft, /zumra_group_reference/);
    assert.match(draft, /ProjectDraftService::confirm\(\).*ProjectService::create\(\)/s);
    assert.match(index, /cadre ZUMRA/);
    assert.match(index, /route\('projects\.create'/);
});