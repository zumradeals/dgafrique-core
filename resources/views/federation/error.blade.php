<x-layouts.public title="Impossible de poursuivre">
    <div class="flex min-h-[100svh] items-center justify-center px-5 py-10">
        <x-dg.state
            tone="danger"
            title="Nous n’avons pas pu poursuivre vers ce service"
            :description="$message"
            :action="['label' => 'Revenir à GAMAD', 'href' => route('gateway')]"
        />
    </div>
</x-layouts.public>
