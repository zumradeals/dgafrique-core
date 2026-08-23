@include('projects.draft._single-question', [
    'step' => 'solution',
    'field' => 'proposed_solution',
    'fieldType' => 'textarea',
    'rows' => 5,
    'question' => 'Comment pensez-vous y répondre ?',
    'help' => 'Votre idée pour changer cette situation — même encore imparfaite.',
    'placeholder' => 'Ex. Installer une petite bibliothèque dans un local prêté, animée par des bénévoles du quartier.',
    'minlength' => 40,
    'maxlength' => 2400,
])
