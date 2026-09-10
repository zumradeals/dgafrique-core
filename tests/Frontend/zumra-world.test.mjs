import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = path => readFileSync(path, 'utf8');

test('ZUMRA-WORLD-001 preserves the canonical world composition', () => {
  const view = read('resources/views/zumra/groups/show.blade.php');
  const styles = read('resources/css/zumra-world.css');
  const formation = read('resources/css/zumra-world-formation.css');
  const member = read('resources/css/member.css');

  assert.match(view, /:wide="true"/);
  assert.match(view, /dg-zumra-world-hero/);
  assert.match(view, /Accueil/);
  assert.match(view, /Formation/);
  assert.match(view, /Projets/);
  assert.match(view, /Membres/);
  assert.match(view, /Demandes/);
  assert.match(view, /Discussion/);
  assert.match(view, /Événements/);
  assert.match(view, /Besoins/);
  assert.match(view, /Missions/);
  assert.match(view, /À propos/);
  assert.match(view, /Navigation rapide/);
  assert.match(view, /Vision/);
  assert.match(view, /Agir maintenant/);
  assert.match(view, /Activité récente/);
  assert.match(view, /À faire maintenant/);
  assert.match(view, /Progression actuelle/);
  assert.match(view, /Prochaine étape/);
  assert.match(view, /FORMATION · TRAVAIL · ADORATION/);
  assert.match(view, /Apprendre, progresser, transmettre/);
  assert.match(view, /route\('projects\.create', \['group' => \$group->public_reference\]\)/);
  assert.match(view, /route\('needs\.create', \['group' => \$group->public_reference\]\)/);
  assert.match(member, /@import "\.\/zumra-world\.css"/);
  assert.match(member, /@import "\.\/zumra-world-formation\.css"/);
  assert.match(styles, /\.dg-zumra-world-layout/);
  assert.match(styles, /grid-template-columns:minmax\(13\.5rem/);
  assert.match(styles, /@media\(max-width:620px\)/);
  assert.match(formation, /\.dg-zumra-world-formation/);
  assert.match(formation, /\.dg-zumra-world-formation__path/);
});

test('user-facing project language stays simple and other projects remain optional', () => {
  const view = read('resources/views/zumra/groups/show.blade.php');

  assert.match(view, /Créer un projet/);
  assert.match(view, /Proposer un projet/);
  assert.match(view, /Aucun autre projet pour le moment/);
  assert.match(view, /Une ZUMRA peut très bien avancer avec un seul projet/);
  assert.doesNotMatch(view, /Projet principal/);
  assert.doesNotMatch(view, /Projets dérivés/);
  assert.doesNotMatch(view, /projet dérivé obligatoire/i);
});

test('Formation is a first-class ZUMRA surface backed by real Transmissions without fabricated training data', () => {
  const view = read('resources/views/zumra/groups/show.blade.php');

  assert.match(view, /id="formation"/);
  assert.match(view, /La première mission d’une ZUMRA est de faire grandir ses membres/);
  assert.match(view, /Entrer dans l’espace Formation/);
  assert.match(view, /route\('zumra\.groups\.formation', \$group\)/);
  assert.match(view, /Les apprentissages réels de cette ZUMRA sont désormais portés par les Transmissions GAMAD/);
  assert.match(view, /Aucun cours, niveau ou résultat n’est inventé/);
  assert.doesNotMatch(view, /Parcours de formation à raccorder/);
});

test('unimplemented world surfaces stay visible without fake backend actions', () => {
  const view = read('resources/views/zumra/groups/show.blade.php');

  assert.match(view, /aria-disabled="true" title="Le canal de discussion/);
  assert.match(view, /mini-fil ZUMRA sera raccordé/);
  assert.match(view, /Modifier la couverture/);
  assert.match(view, /Paramètres/);
});
