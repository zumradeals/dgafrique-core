{{-- UIUX-009B — étape : objectifs (collection dynamique, au moins un élément). --}}
<x-layouts.portal title="Qu’aimeriez-vous accomplir ? — DG Afrique">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:640px">
            @include('projects.draft._header', ['step' => 'objectifs'])

            <h1 class="dg-display dg-display--lead">Qu’aimeriez-vous accomplir ?</h1>
            <p class="dg-body" style="margin-top:8px">Un objectif à la fois. Une phrase simple suffit pour chacun.</p>

            <form method="POST" action="{{ route('projects.draft.update', [$draft, 'objectifs']) }}" style="margin-top:26px">
                @csrf

                @include('projects.draft._dynamic-list', [
                    'key' => 'objectives',
                    'items' => $payload['objectives'] ?? [],
                    'placeholder' => 'Ex. Ouvrir l’espace deux après-midis par semaine',
                ])

                @include('projects.draft._footer')
            </form>

            @include('projects.draft._abandon')
        </div>
    </x-dg.shell>
</x-layouts.portal>
