import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = path => readFileSync(path, 'utf8');

test('ZUMRA hub preserves the full canonical crossroads composition', () => {
  const view = read('resources/views/zumra/index.blade.php');
  const member = read('resources/css/member.css');
  const styles = read('resources/css/zumra-hub.css');
  const polish = read('resources/css/zumra-hub-polish.css');

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
  assert.match(member, /@import "\.\/zumra-hub-polish\.css"/);
  assert.match(styles, /\.dg-zumra-hub/);
  assert.match(polish, /\.dg-zumra-territory--wide/);
  assert.match(polish, /\.dg-zumra-what-pillars/);
  assert.match(polish, /@media \(max-width: 620px\)/);
});

test('ZUMRA empty state stays useful and territory leaves the sidebar', () => {
  const view = read('resources/views/zumra/index.blade.php');

  assert.match(view, /Les premières ZUMRA apparaîtront ici\./);
  assert.match(view, /Explorer les territoires/);
  assert.match(view, /id="territoires-zumra"/);
  assert.match(view, /Un projet principal/);
  assert.match(view, /Un monde d’action/);

  const asideEnd = view.indexOf('</aside>');
  const territoryStart = view.indexOf('id="territoires-zumra"');
  assert.ok(asideEnd >= 0 && territoryStart > asideEnd, 'Explorer par territoire doit être hors de la sidebar');
});

test('ZUMRA creation surface exists and posts every real creation field to the governed store route', () => {
  const view = read('resources/views/zumra/groups/create.blade.php');
  const member = read('resources/css/member.css');
  const styles = read('resources/css/zumra-create.css');

  assert.match(view, /route\('zumra\.groups\.store'\)/);
  assert.match(view, /name="name"/);
  assert.match(view, /name="domain"/);
  assert.match(view, /name="founding_objective"/);
  assert.match(view, /name="participation_mode"/);
  assert.match(view, /name="location"/);
  assert.match(view, /name="welcome_capacity"/);
  assert.match(view, /name="assume_primary_lead"/);
  assert.match(view, /name="internal_charter"/);
  assert.match(view, /activity_label\[/);
  assert.match(view, /activity_relation\[/);
  assert.match(view, /Faire naître la ZUMRA/);
  assert.match(view, /Son projet principal prend forme/);
  assert.match(member, /@import "\.\/zumra-create\.css"/);
  assert.match(styles, /\.dg-zumra-create/);
  assert.match(styles, /@media \(max-width: 700px\)/);
});

test('ZUMRA membership is free, charter-governed and keeps complete historical surfaces', () => {
  const membership = read('resources/views/zumra/membership.blade.php');
  const controller = read('app/Http/Controllers/ZumraProgramMembershipController.php');
  const paymentStatus = read('resources/views/zumra/payment-status.blade.php');
  const receipt = read('resources/views/zumra/receipt.blade.php');
  const member = read('resources/css/member.css');
  const styles = read('resources/css/zumra-membership.css');

  assert.match(membership, /route\('zumra\.membership\.store'\)/);
  assert.match(membership, /Adhérez gratuitement au Programme ZUMRA/);
  assert.match(membership, /Accepter la charte et adhérer gratuitement/);
  assert.match(membership, /Aucun paiement n’est demandé/);
  assert.match(membership, /Votre compte GAMAD reste indépendant/);
  assert.doesNotMatch(membership, /route\('zumra\.payment\.zahab\.store'\)/);
  assert.doesNotMatch(membership, /route\('zumra\.payment\.store'\)/);

  assert.match(controller, /STATUS_ACTIVE/);
  assert.match(controller, /FREE_CHARTER_ACCEPTANCE/);
  assert.match(controller, /route\('zumra\.groups\.create'\)/);

  assert.match(paymentStatus, /Votre adhésion ZUMRA est active/);
  assert.match(receipt, /Reçu d’adhésion ZUMRA/);
  assert.match(member, /@import "\.\/zumra-membership\.css"/);
  assert.match(styles, /\.dg-zumra-membership/);
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
