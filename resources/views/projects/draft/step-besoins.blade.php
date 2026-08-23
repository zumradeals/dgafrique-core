{{-- UIUX-009B — étape : de quoi pourriez-vous avoir besoin. Tout est différable ici. --}}
<x-layouts.portal title="De quoi pourriez-vous avoir besoin ? — DG Afrique">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:640px">
            @include('projects.draft._header', ['step' => 'besoins'])

            <h1 class="dg-display dg-display--lead">De quoi pourriez-vous avoir besoin ?</h1>
            <p class="dg-body" style="margin-top:8px">Répondez à ce que vous savez déjà. Rien ici n’est obligatoire — « Je ne sais pas encore » est une réponse tout à fait valable, vous pourrez compléter plus tard depuis la fiche du projet.</p>

            <form method="POST" action="{{ route('projects.draft.update', [$draft, 'besoins']) }}" style="margin-top:26px;display:flex;flex-direction:column;gap:28px">
                @csrf

                <div class="dg-fieldset">
                    <legend><x-dg.label>Des capacités ou savoir-faire</x-dg.label></legend>
                    @include('projects.draft._dynamic-list', [
                        'key' => 'required_capabilities',
                        'items' => $payload['required_capabilities'] ?? [],
                        'placeholder' => 'Ex. Savoir organiser des ateliers de lecture',
                    ])
                </div>

                <div class="dg-fieldset">
                    <legend><x-dg.label>Des ressources</x-dg.label></legend>
                    @include('projects.draft._dynamic-list', [
                        'key' => 'required_resources',
                        'items' => $payload['required_resources'] ?? [],
                        'placeholder' => 'Ex. Des étagères et des livres',
                    ])
                </div>

                <div class="dg-fieldset">
                    <legend><x-dg.label>Des risques que vous voyez déjà</x-dg.label></legend>
                    @include('projects.draft._dynamic-list', [
                        'key' => 'risks',
                        'items' => $payload['risks'] ?? [],
                        'placeholder' => 'Ex. Trouver un local disponible durablement',
                    ])
                </div>

                @include('projects.draft._footer', ['continueLabel' => 'Continuer →'])
            </form>

            @include('projects.draft._abandon')
        </div>
    </x-dg.shell>
</x-layouts.portal>
