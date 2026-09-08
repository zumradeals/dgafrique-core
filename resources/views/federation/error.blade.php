<x-layouts.public title="Impossible de poursuivre">
    <div class="flex min-h-[100svh] items-center justify-center px-5 py-10">
        <x-dg.state
            type="error"
            title="Nous n’avons pas pu poursuivre vers ce service"
            :message="$message"
        >
            <x-slot:actions>
                <x-dg.button :href="route('gateway')">Revenir à GAMAD</x-dg.button>
            </x-slot:actions>
        </x-dg.state>
    </div>
</x-layouts.public>
