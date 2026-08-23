@include('projects.draft._single-question', [
    'step' => 'beneficiaires',
    'field' => 'beneficiaries',
    'fieldType' => 'textarea',
    'rows' => 3,
    'question' => 'À qui cela doit-il être utile ?',
    'help' => 'Les personnes concernées en premier par ce projet.',
    'placeholder' => 'Ex. Les enfants de 6 à 12 ans du quartier et leurs familles.',
    'minlength' => 20,
    'maxlength' => 1200,
])
