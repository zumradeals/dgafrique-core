# BRAND-DOCTRINE-001 — Doctrine de marque DG Afrique

| Propriété | Valeur |
|---|---|
| Statut | CANONIQUE — ADOPTÉ |
| Décision | BRAND-DOCTRINE-001 |
| Date | 27 août 2026 |
| Autorité visuelle source | `docs/brand/assets/gamad-logo-source.jpg` |
| Tokens normatifs | `docs/brand/tokens.json` |

## 1. Décision

DG Afrique conserve et fait certifier son moteur, retire entièrement sa présentation actuelle,
puis construit une interface unique et neuve lorsque le backend est déclaré prêt.

La future interface n'est ni un rafraîchissement, ni une correction progressive, ni une
recomposition de l'ancien frontend. Elle est une traduction neuve du produit canonique :

> DG Afrique est un réseau social d'action où des personnes, des capacités et des outils
> spécialisés transforment des besoins en actions vérifiables.

Le logo GAMAD fourni est l'origine de la nouvelle charte. Les écrans existants, leurs feuilles de
style, le handoff Claude et les palettes antérieures sont des archives ; ils ne constituent plus
une autorité visuelle pour la prochaine interface.

Cette doctrine fixe la direction. Elle ne déclenche pas la reconstruction du frontend avant la
certification du moteur.

## 2. Hiérarchie d'autorité

En cas de conflit, l'ordre suivant s'applique :

1. doctrine GAMAD, souveraineté humaine, sécurité, confidentialité et consentement ;
2. doctrine métier et contrats du moteur ;
3. expérience produit canonique de DG Afrique ;
4. présente doctrine de marque et ses tokens ;
5. futur design system et composants d'interface ;
6. décisions locales d'un écran.

Une préférence graphique ne peut jamais contredire une règle métier ni rendre une action moins
compréhensible, moins accessible ou moins sûre.

## 3. Source et intégrité

Le fichier source reçu est conservé sans retouche :

| Propriété | Valeur |
|---|---|
| Fichier canonique reçu | `gamad-logo-source.jpg` |
| Format | JPEG, 720 × 713 px |
| SHA-256 | `233662ddec714e4a3a66ddf78173895ee5a23c6f20d92261e94c73ddc4d4a70a` |
| Inscription arabe | `غاماد` |
| Inscription latine | `GAMAD` |

Ce JPEG est l'autorité d'intention, mais pas encore un master de production. Avant toute diffusion
publique à grande échelle, le symbole doit être redessiné proprement en vectoriel, contrôlé à la
main et validé par l'autorité de marque. Une vectorisation automatique ne devient jamais le logo
officiel par défaut.

## 4. Ce que le symbole signifie

La future interface reprend le sens du logo, pas sa forme de manière décorative et répétitive.

| Élément | Sens retenu | Traduction dans le produit |
|---|---|---|
| Mains ouvertes | service, protection, capacité d'agir | l'interface aide sans confisquer la décision |
| Globe centré sur l'Afrique | ancrage africain et ouverture au monde | contexte local, portée transfrontalière |
| Soleil et lune | continuité, vigilance, cycles humains | services fiables, temps et états explicites |
| Étoiles | orientation et progression | jalons lisibles, jamais scores de valeur humaine |
| Socle jaune | fondation commune | repères stables, appels à l'action clairs |
| Arabe et latin | pluralité culturelle et linguistique | contenu multilingue digne, structure compatible bidi/RTL |

Les symboles ne doivent pas être détournés en mécanismes de compétition, de classement social ou
de stimulation artificielle.

## 5. Personnalité de marque

DG Afrique doit être perçu comme :

- humain avant d'être technologique ;
- actif avant d'être spectaculaire ;
- digne avant d'être promotionnel ;
- clair avant d'être dense ;
- africain sans folklore ni stéréotype ;
- collectif sans effacer la souveraineté individuelle ;
- ambitieux sans surpromesse.

La marque n'emprunte pas l'esthétique d'un tableau de bord administratif générique, d'une fintech,
d'un jeu social ou d'un assemblage de mini-applications.

## 6. Palette canonique

Les couleurs sources sont extraites du fichier canonique. Elles appartiennent à l'identité ; leur
usage fonctionnel dépend toutefois du contraste requis.

### 6.1 Couleurs sources

| Token | Valeur | Rôle de marque |
|---|---:|---|
| `source.solar` | `#F4D312` | énergie, appel, fondation commune |
| `source.networkBlue` | `#029FE2` | relation, circulation, ouverture |
| `source.growthGreen` | `#009752` | capacité, progression, vivant |
| `source.ink` | `#181715` | autorité, lisibilité, structure |
| `source.white` | `#FFFFFF` | respiration, clarté |

Ces valeurs remplacent, pour le futur frontend, les anciennes approximations de palette présentes
dans les CSS et documents historiques.

### 6.2 Dérivés accessibles

Les couleurs du logo bleu et verte sont trop claires pour recevoir du petit texte blanc. Des
variantes plus profondes sont donc normées pour les contrôles et les surfaces fonctionnelles.

| Token | Valeur | Usage |
|---|---:|---|
| `action.blue` | `#00658C` | action primaire avec texte blanc |
| `action.green` | `#00643A` | validation ou progression avec texte blanc |
| `surface.deep` | `#083B56` | surface forte avec texte blanc |
| `surface.canvas` | `#F6F5F0` | fond général chaud et calme |
| `surface.solarSoft` | `#FFF7CC` | information ou mise en lumière |
| `surface.blueSoft` | `#E8F7FD` | contexte relationnel |
| `surface.greenSoft` | `#E8F6EF` | contexte de progression |
| `text.muted` | `#5B5D58` | texte secondaire accessible |
| `border.default` | `#D9D8D1` | séparation non décorative |
| `state.danger` | `#B42318` | erreur ou danger uniquement |
| `state.dangerSoft` | `#FEE4E2` | fond d'erreur |

Le rouge est un état fonctionnel, pas une couleur de marque.

### 6.3 Couples autorisés

| Fond | Contenu recommandé | Règle |
|---|---|---|
| jaune source | encre | jamais de texte blanc |
| bleu source | encre | réservé surtout au graphisme, icônes et grandes surfaces |
| vert source | encre | pas de petit texte blanc |
| bleu action | blanc | boutons et contrôles accessibles |
| vert action | blanc | progression confirmée, pas action primaire systématique |
| surface profonde | blanc | zones structurantes rares |
| blanc ou canevas | encre / texte atténué | contenu courant |

La couleur seule ne communique jamais un état. Tout état possède aussi un libellé, une icône ou une
structure perceptible.

## 7. Répartition des couleurs

La charte est vive ; l'interface ne doit pas devenir criarde.

- blanc et canevas chaud dominent les surfaces de lecture ;
- l'encre porte la structure et le texte ;
- le bleu porte l'action principale et les relations ;
- le vert exprime une capacité acquise, une progression ou une confirmation ;
- le jaune attire l'attention sur un point important et sert d'accent identitaire ;
- les trois couleurs de marque ne sont pas placées à poids égal dans chaque écran ;
- gradients, néons, ombres colorées et fonds multicolores permanents sont exclus.

## 8. Typographie

Le lettrage contenu dans le JPEG n'est pas une police d'interface et ne doit pas être imité.

La sélection finale des familles typographiques interviendra pendant la construction du portail,
après vérification des licences, des performances web et de la couverture linguistique. Les rôles
et qualités suivantes sont déjà obligatoires :

| Rôle | Qualités requises |
|---|---|
| Interface et lecture | sans humaniste, formes ouvertes, excellente lisibilité mobile |
| Titres d'action | sans forte et chaleureuse, directe, sans effet publicitaire |
| Arabe | famille native de qualité, poids compatibles, lecture RTL correcte |
| Références techniques | chiffres tabulaires si nécessaire, jamais esthétique terminal généralisée |

Règles :

- taille minimale du corps : `16px` ;
- hauteur de ligne du corps : au moins `1.5` ;
- aucune longue phrase en capitales ;
- un titre nomme l'objet ou l'action, il ne remplit pas l'espace ;
- arabe, français et autres langues conservent leur dignité typographique ;
- les polices système restent un repli acceptable sur réseau contraint.

## 9. Formes et composition

Le logo associe courbes protectrices et traits noirs nets. La future interface traduit ce contraste
par des surfaces accueillantes, mais des décisions franches.

- cartes : rayon modéré, contour perceptible, pas d'empilement gratuit ;
- contrôles : forme stable et cible tactile d'au moins `48 × 48px` ;
- images et avatars : formes circulaires possibles lorsqu'elles représentent une personne ;
- contenus et actions : alignements nets, hiérarchie explicite ;
- largeur de lecture : environ `68ch` au maximum ;
- densité : adaptée à la tâche, jamais réduite artificiellement pour paraître « premium » ;
- espace : crée des groupes compréhensibles, il n'est pas un substitut au contenu.

Les courbes du logo ne justifient ni des pilules partout, ni des cartes imbriquées à l'infini.

## 10. Images, illustrations et icônes

### Photographie

Les images montrent des personnes et des actions réelles, avec consentement et contexte. Elles
évitent le stock générique, la pauvreté mise en scène, l'exotisation, la carte de l'Afrique comme
raccourci systématique et toute prétention à représenter un pays par un cliché unique.

### Illustration

Une illustration peut reprendre le vocabulaire du logo — ligne noire, aplats francs, courbes
organiques — sans copier le logo dans chaque scène. Elle sert l'explication d'une action ou d'un
état vide réel.

### Icônes

Les icônes sont simples, cohérentes, accompagnées d'un libellé pour les actions importantes et ne
remplacent jamais une information métier. Aucun symbole religieux ou culturel ambigu n'est utilisé
comme décoration.

## 11. Voix et microcontenu

DG Afrique parle avec respect, précision et capacité d'action.

Chaque écran répond clairement à trois questions :

1. que se passe-t-il ?
2. pourquoi cette information est-elle présente ?
3. que puis-je faire maintenant ?

Les verbes concrets sont préférés : « publier », « rejoindre », « proposer », « vérifier »,
« transmettre ». Les formulations culpabilisantes, les urgences artificielles, le jargon interne,
les promesses absolues et les métriques de vanité sont proscrits.

Les états vides disent la vérité : absence de données, absence de droit, chargement, erreur ou
première utilisation. Ils n'inventent ni membres, ni activité, ni réussite.

## 12. Interaction et mouvement

- tout élément ressemblant à un contrôle est opérationnel ;
- tout contrôle possède des états repos, survol si pertinent, focus, pressé, chargement, succès,
  erreur et désactivé lorsque le métier l'autorise ;
- une action destructive demande une intention explicite et offre une récupération lorsque cela
  est possible ;
- le mouvement explique une transition ou un changement d'état ; il ne récompense pas la simple
  présence ;
- durée standard cible : `220ms`, interaction rapide : `120ms` ;
- `prefers-reduced-motion` supprime les mouvements non indispensables ;
- infinite scroll, compteurs anxiogènes, confettis automatiques et mécanismes de dopamine ne sont
  pas des motifs de marque.

## 13. Accessibilité et contraintes africaines

La qualité visuelle inclut les conditions réelles d'accès.

- conformité cible : WCAG 2.2 AA au minimum ;
- contraste du texte courant : au moins `4.5:1` ;
- focus clavier visible sur chaque contrôle ;
- parcours complet au clavier et technologies d'assistance ;
- structure compatible avec les textes bidirectionnels ;
- première qualité vérifiée à `360px` de large ;
- mode données contraintes : images adaptatives, chargement différé, aucun média décoratif bloquant ;
- interface utile sur connexion lente et appareil modeste ;
- dates, nombres, monnaies, noms et langues ne sont pas supposés uniformes ;
- aucun contenu essentiel n'est enfermé dans une image.

Les contrastes de référence calculés sur la palette sont consignés dans `tokens.json`. Ils doivent
être revalidés automatiquement lors de la création du design system.

## 14. Usage du logo

Jusqu'à livraison d'un master vectoriel approuvé :

- le JPEG est utilisé uniquement à une taille qui ne révèle pas sa pixellisation ;
- le logo complet n'est ni étiré, ni incliné, ni recoloré, ni détouré automatiquement ;
- aucune partie — arabe, GAMAD, mains, astres — n'est retirée du logo complet ;
- aucun effet, ombre, contour supplémentaire, gradient ou animation n'est ajouté ;
- le logo ne sert pas de motif de fond ni de filigrane ;
- une zone de respiration au moins équivalente au diamètre d'une petite étoile est conservée ;
- taille numérique provisoire minimale du logo complet : `96px` de large ;
- en dessous, aucun « petit logo » n'est improvisé : un symbole simplifié devra être conçu et
  approuvé séparément.

DG Afrique ne reçoit pas automatiquement un nouveau logotype à partir du fichier GAMAD. Son futur
wordmark et son éventuel symbole simplifié constituent une mission distincte, soumise à validation.

## 15. Architecture de marque

DG Afrique est la marque d'expérience principale. GAMAD en est la fondation doctrinale. Les moteurs
spécialisés sont des outils au service de l'action, accessibles dans le contexte où ils deviennent
utiles.

Ils ne doivent pas transformer la navigation principale en catalogue de produits, ni créer chacun
leur propre identité concurrente. Une couleur, une icône ou une page dédiée peut distinguer une
fonction ; elle reste gouvernée par cette doctrine commune.

## 16. Ce qui est explicitement rejeté

- reprendre un ancien écran parce qu'il existe déjà ;
- convertir l'ancienne CSS en nouveau design system ;
- juxtaposer plusieurs styles issus des tentatives précédentes ;
- faire de la page d'accueil un catalogue de moteurs ;
- fabriquer des données pour rendre un écran visuellement riche ;
- masquer une absence fonctionnelle par un bouton sans effet ;
- coder une action avant que son contrat métier et ses permissions soient certifiés ;
- utiliser le jaune, le bleu et le vert sans rôle précis ;
- confondre densité fonctionnelle et chaos visuel ;
- mesurer la valeur humaine par l'engagement, la popularité ou un score opaque.

## 17. Portes de validation du futur frontend

Une surface n'est intégrable que si elle satisfait simultanément les critères suivants :

1. **Vérité métier** — données réelles, états honnêtes, contrat API connu.
2. **Couverture fonctionnelle** — chaque action visible aboutit ou explique précisément pourquoi
   elle est indisponible.
3. **Cohérence de marque** — tokens, voix, images et hiérarchie conformes à cette doctrine.
4. **Accessibilité** — clavier, focus, contraste, lecture assistée et mouvement réduit vérifiés.
5. **Résilience** — chargement, vide, refus, erreur, perte réseau et reprise traités.
6. **Qualité mobile** — parcours complet validé à partir de `360px` et avec réseau contraint.
7. **Traçabilité** — tests et preuve de vérification liés à la surface.

Une maquette attractive ne contourne aucune de ces portes.

## 18. Gouvernance

`BRAND-DOCTRINE-001.md` fixe le sens et les règles. `tokens.json` fixe les valeurs consommables par
le futur design system. En cas de divergence involontaire, la doctrine détermine l'intention et la
divergence doit être corrigée dans les deux sources au même changement.

Toute évolution doit :

- être explicite, datée et motivée ;
- conserver la compatibilité avec la doctrine GAMAD et le produit canonique ;
- joindre une preuve de contraste si une couleur change ;
- être validée sur français et arabe lorsque la typographie ou la direction de lecture est touchée ;
- ne jamais être déduite silencieusement d'un composant ou d'une maquette.

## 19. Séquence de transformation

La séquence autorisée reste :

1. certifier le moteur et ses contrats ;
2. cartographier ce que le frontend peut supprimer sans toucher au moteur ;
3. retirer entièrement la présentation historique ;
4. construire les fondations de l'interface neuve à partir de cette doctrine ;
5. intégrer les parcours par tranches verticales testées de bout en bout ;
6. passer les portes de production.

La marque est donc prête comme fondation. Elle n'est pas une autorisation de commencer la
carrosserie avant la certification du moteur.
