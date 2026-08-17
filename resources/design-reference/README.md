# Fixtures d'exemple — jamais des données métier

`demo-content.json` est la copie exacte de `donnees-de-demonstration/demo-content.json` du
handoff Claude (`docs/design/reference/claude-2026-08-16/`). Chaque objet porte
`"__demonstration": true`.

Règles, conformes à `docs/design/DESIGN-INVARIANTS.md` §11 :

- Ce fichier n'est **jamais** seedé dans les tables métier (`database/seeders/DatabaseSeeder.php`
  reste intentionnellement vide).
- Il n'est lu que par la landing (`resources/views/foundation.blade.php`), dans la section qui
  illustre à quoi ressemble un besoin ou un projet réel — chaque bloc issu de ce fichier est
  visiblement annoncé par le mot **« Exemple »**.
- Un utilisateur réel connecté (Fil, Mon espace) ne voit jamais ce contenu : ces écrans
  n'affichent que des objets réels ou leur état vide honnête.
