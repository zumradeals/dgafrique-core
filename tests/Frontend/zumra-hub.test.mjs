import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = path => readFileSync(path, 'utf8');

test('ZUMRA hub preserves the full canonical crossroads composition', () => {
  const view = read('resources/views/zumra/index.blade.php');
  const member = read('resources/css/member.css');
  const styles = read('resources/css/zumra-hub.css');

  assert.match(view, /:wide="true"/);
  assert.match(view, /COMMUNAUTÉS · PROJETS · IMPACT/);
  assert.match(view, /Grandir et agir ensemble\./);
  assert.match(view, /Vous pouvez utiliser GAMAD sans appartenir à une ZUMRA\./);
  assert.match(view, /Mes ZUMRA/);
  assert.match(view, /ZUMRA à découvrir/);
  assert.match(view, /Les ZUMRA en chiffres/);
  assert.match(view, /Vous ne trouvez pas de ZUMRA qui correspond \?/);
  assert.match(view, /Explorer par territoire/);
  assert.match(view, /Qu’est-ce qu’une ZUMRA \?/);
  assert.match(view, /Abidjan/);
  assert.match(view, /Yamoussoukro/);
  assert.match(view, /Bouaké/);
  assert.match(view, /Korhogo/);
  assert.match(view, /San Pedro/);
  assert.match(view, /Man/);
  assert.match(view, /route\('zumra\.groups\.show'/);
  assert.match(view, /route\('zumra\.groups\.create'/);
  assert.match(view, /route\('zumra\.membership\.show'/);
  assert.match(view, /\['location' => \$territory\]/);

  assert.match(member, /@import "\.\/zumra-hub\.css"/);
  assert.match(styles, /\.dg-zumra-hub/);
  assert.match(styles, /\.dg-zumra-territory/);
  assert.match(styles, /@media \(max-width: 620px\)/);
});

test('ZUMRA hub never hard-codes mockup demo metrics as production truth', () => {
  const view = read('resources/views/zumra/index.blade.php');
  const controller = read('app/Http/Controllers/ZumraSpaceController.php');

  for (const fake of ['+ 320', '+320', '+ 18 000', '+18 000', '+ 1 200', '+1 200']) {
    assert.doesNotMatch(view, new RegExp(fake.replace(/[+]/g, '\\+')));
  }
  assert.match(view, /\$stats\['groups'\]/);
  assert.match(view, /\$stats\['members'\]/);
  assert.match(view, /\$stats\['projects'\]/);
  assert.match(view, /\$stats\['territories'\]/);
  assert.match(controller, /'projects' => \$projectsOngoing/);
  assert.match(controller, /'territories'/);
  assert.match(controller, /visible_projects_count/);
});
