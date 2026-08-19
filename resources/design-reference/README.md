# Fixtures d'exemple — jamais des données métier

`demo-content.json` est la copie exacte de `donnees-de-demonstration/demo-content.json` du
handoff Claude (`docs/design/reference/claude-2026-08-16/`). Chaque objet porte
`"__demonstration": true`.

`landing-portal-demo.json` est un second fichier de fixtures, introduit pour la refonte
« portail » de la landing (voir l'addendum daté dans `docs/design/DESIGN-INVARIANTS.md` §1) :
statistiques d'orientation réseau (nombre de membres/projets/pays) et exemples d'activité
« en ce moment ». Il ne prétend pas être une copie du handoff du 16 août — c'est un contenu
nouveau, gardé sous la même discipline `__demonstration` que `demo-content.json`.

Règles, conformes à `docs/design/DESIGN-INVARIANTS.md` §11 :

- Ces fichiers ne sont **jamais** seedés dans les tables métier (`database/seeders/DatabaseSeeder.php`
  reste intentionnellement vide).
- Ils ne sont lus que par la landing (`resources/views/foundation.blade.php`), dans les sections
  qui illustrent à quoi ressemble un besoin, un projet ou l'activité du réseau — chaque bloc issu
  de ces fichiers est visiblement annoncé par le mot **« Exemple »**.
- Un utilisateur réel connecté (Fil, Mon espace) ne voit jamais ce contenu : ces écrans
  n'affichent que des objets réels ou leur état vide honnête.
