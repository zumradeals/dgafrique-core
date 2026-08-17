{{--
    Mémoire d'expérience — CAP-035, fiche §3. Pas un second modèle métier : une vue
    chronologique agrégée des preuves accessibles de la personne, rien de plus.
--}}
<x-layouts.portal title="Mémoire d’expérience — DG Afrique">
    <x-dg.shell :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page">
            <a href="{{ route('proofs.index') }}" class="dg-crumb">← Mon Carnet de preuves</a>

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">CAP-035 · Mémoire d’expérience</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">{{ $isOwnMemory ? 'Ma mémoire d’expérience' : 'Mémoire d’expérience — '.$ownerLabel }}</h1>
                    <p>Une chronologie des preuves {{ $isOwnMemory ? 'que vous avez enregistrées' : 'que cette personne a choisi de rendre visibles' }}. Ce n’est jamais un score ni un classement.</p>
                </div>
            </div>

            @if($items->isEmpty())
                <x-dg.empty title="Aucune preuve accessible pour le moment">
                    <span>{{ $isOwnMemory ? 'Enregistrez une preuve pour commencer votre mémoire d’expérience.' : 'Rien n’est encore visible ici.' }}</span>
                </x-dg.empty>
                @if($isOwnMemory)
                    <div style="margin-top:16px">
                        <a href="{{ route('proofs.create') }}" class="dg-btn dg-btn--saffron">Enregistrer une preuve</a>
                    </div>
                @endif
            @else
                <div style="display:flex;flex-direction:column;gap:14px;max-width:760px">
                    @foreach($items as $proof)
                        <x-dg.card>
                            <div class="flex items-center justify-between gap-3">
                                <span class="dg-meta">{{ $proof->occurred_at->locale('fr')->isoFormat('D MMMM YYYY') }}</span>
                                <x-dg.badge :tone="\App\Models\Proof::STATUS_BADGE_TONES[$proof->status] ?? 'neutral'">{{ \App\Models\Proof::STATUS_LABELS[$proof->status] ?? $proof->status }}</x-dg.badge>
                            </div>
                            <h2 class="dg-display dg-display--card" style="margin-top:10px;max-width:32ch">
                                <a href="{{ route('proofs.show', $proof) }}" style="color:inherit">{{ $proof->title }}</a>
                            </h2>
                            <p class="dg-body" style="margin-top:6px">{{ \Illuminate\Support\Str::limit($proof->description, 200) }}</p>
                        </x-dg.card>
                    @endforeach
                </div>
            @endif
        </div>
    </x-dg.shell>
</x-layouts.portal>
