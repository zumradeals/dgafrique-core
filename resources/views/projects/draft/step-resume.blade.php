@include('projects.draft._single-question', [
    'step' => 'resume',
    'field' => 'summary',
    'fieldType' => 'textarea',
    'rows' => 3,
    'question' => 'En une ou deux phrases, de quoi s’agit-il ?',
    'help' => 'Comme si vous le racontiez à quelqu’un qui ne sait rien encore de votre idée.',
    'placeholder' => 'Ex. Ouvrir un petit espace de lecture pour les enfants du quartier, avec des livres donnés par les habitants.',
    'minlength' => 40,
    'maxlength' => 1200,
])
