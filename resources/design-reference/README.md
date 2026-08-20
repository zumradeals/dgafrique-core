# Fixtures d'exemple — jamais des données métier

`demo-content.json` est la copie exacte de `donnees-de-demonstration/demo-content.json` du
handoff Claude (`docs/design/reference/claude-2026-08-16/`). Chaque objet porte
`"__demonstration": true`.

`landing-portal-demo.json` est un second fichier de fixtures, introduit pour la refonte
« portail » de la landing (voir l'addendum daté dans `docs/design/DESIGN-INVARIANTS.md` §1) :
statistiques d'orientation réseau (nombre de membres/projets/pays) et exemples d'activité
« en ce moment ». Il ne prétend pas être une copie du handoff du 16 août — c'est un contenu
nouveau, gardé sous la même discipline `__demonstration` que `demo-content.json`.

`fil-demo.json` est un troisième fichier de fixtures, introduit pour le Fil V2 (voir l'addendum
daté du 19 août 2026 dans `docs/design/DESIGN-INVARIANTS.md` §17) : trois cartes d'exemple
(Besoin, Projet, ZUMRA) sous la règle **DEMO-FIRST, REAL-DATA-TAKES-OVER** — elles ne
s'affichent, filtre par filtre, que tant qu'aucune donnée réelle n'existe pour ce filtre, et
disparaissent dès qu'un objet réel apparaît. **Ceci modifie l'invariant précédent** qui excluait
tout contenu de démonstration du Fil pour un utilisateur connecté ; le changement est assumé et
documenté, pas silencieux.

`projets-demo.json` est un quatrième fichier de fixtures, introduit pour le portail Projets (voir
l'addendum daté du 20 août 2026 dans `docs/design/DESIGN-INVARIANTS.md` §18) : des statistiques
d'orientation réseau (« Aperçu de la communauté ») et jusqu'à trois cartes de projet d'exemple
(dont « GAMAD Technology »), sous la même règle **DEMO-FIRST, REAL-DATA-TAKES-OVER** que le Fil
V2 — chaque carte de démonstration ne s'affiche, domaine par domaine, que tant qu'aucun projet
réel visible n'existe pour ce domaine, et disparaît dès qu'un projet réel apparaît. Chargé et
filtré par `App\Application\Projects\ProjectDirectoryDemoContent`, jamais lu directement par la vue.

Règles, conformes à `docs/design/DESIGN-INVARIANTS.md` §11 et à son addendum §17 :

- Ces fichiers ne sont **jamais** seedés dans les tables métier (`database/seeders/DatabaseSeeder.php`
  reste intentionnellement vide).
- `demo-content.json` et `landing-portal-demo.json` ne sont lus que par la landing
  (`resources/views/foundation.blade.php`) — chaque bloc issu de ces fichiers est visiblement
  annoncé par le mot **« Exemple »**.
- `fil-demo.json` n'est lu que par le Fil (`resources/views/activity/index.blade.php`), et
  uniquement en l'absence de donnée réelle pour le filtre affiché — chaque carte porte le
  suffixe **« · EXEMPLE »** sur son badge et désactive toutes ses actions (aucune n'est
  rattachée à un objet réel), avec la raison accessible « Objet de démonstration — aucune
  action réelle n'est rattachée. ».
- `projets-demo.json` n'est lu que par le portail Projets (`resources/views/projects/index.blade.php`
  via `ProjectDirectoryDemoContent`), et uniquement en l'absence de projet réel visible pour le
  domaine de la carte — même suffixe **« · Exemple »**, mêmes actions désactivées avec la même
  raison accessible, mêmes statistiques réseau annoncées **« · Exemple »**.
- Mon espace n'affiche toujours aucun contenu de démonstration : ces écrans restent réels ou
  à état vide honnête.
