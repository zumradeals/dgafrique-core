@props([
    'type' => 'empty',
    'title' => null,
    'message' => null,
])

@php
    $content = [
        'loading' => ['Chargement en cours', 'Nous récupérons les informations réelles. Cela peut prendre un instant.', 'warning'],
        'empty' => ['Rien à afficher pour le moment', 'Aucune information réelle ne correspond encore à cette vue.', 'empty'],
        'error' => ['Nous n’avons pas pu terminer', 'Votre action n’a pas été perdue. Vous pouvez réessayer.', 'warning'],
        'offline' => ['Vous êtes hors ligne', 'Revenez ici dès que la connexion est disponible.', 'offline'],
        'unavailable' => ['Service temporairement indisponible', 'La situation est conservée. Réessayez dans quelques instants.', 'warning'],
        'forbidden' => ['Cette action ne vous est pas accessible', 'Votre rôle ou le contexte actuel ne permet pas cette action.', 'lock'],
        'not-found' => ['Élément introuvable', 'Il n’existe plus ou sa visibilité ne vous permet pas de le consulter.', 'empty'],
        'conflict' => ['La situation a changé', 'Rechargez les informations avant de poursuivre.', 'warning'],
        'success' => ['C’est enregistré', 'Le nouvel état a bien été confirmé.', 'success'],
    ];
    [$defaultTitle, $defaultMessage, $icon] = $content[$type] ?? $content['empty'];
    $liveRole = in_array($type, ['error', 'offline', 'unavailable'], true) ? 'alert' : 'status';
@endphp

<section role="{{ $liveRole }}" aria-live="{{ $type === 'loading' ? 'polite' : 'assertive' }}" {{ $attributes->class('dg-state dg-state--'.$type) }}>
    <div class="dg-state__icon">
        @if ($type === 'loading')
            <span class="dg-spinner" aria-hidden="true"></span>
        @else
            <x-dg.icon :name="$icon" size="28" />
        @endif
    </div>
    <h2 class="dg-state__title">{{ $title ?? $defaultTitle }}</h2>
    <p class="dg-state__message">{{ $message ?? $defaultMessage }}</p>
    @if (isset($actions) && $actions->isNotEmpty())
        <div class="dg-state__actions">{{ $actions }}</div>
    @endif
</section>
