@include('projects.draft._single-question', [
    'step' => 'nom',
    'field' => 'name',
    'fieldType' => 'input',
    'question' => 'Comment voulez-vous appeler ce projet ?',
    'help' => 'Un nom simple suffit — vous pourrez toujours l’ajuster plus tard.',
    'placeholder' => 'Ex. Bibliothèque solidaire',
    'minlength' => 5,
    'maxlength' => 180,
])
