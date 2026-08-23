@include('projects.draft._single-question', [
    'step' => 'probleme',
    'field' => 'problem',
    'fieldType' => 'textarea',
    'rows' => 5,
    'question' => 'Quel problème ou quel manque avez-vous observé ?',
    'help' => 'Ce qui ne va pas aujourd’hui, tel que vous l’avez constaté.',
    'placeholder' => 'Ex. Les enfants du quartier n’ont nulle part où lire ou emprunter un livre après l’école.',
    'minlength' => 40,
    'maxlength' => 2400,
])
