{{--
    ZAHAB-002 — surface minimale, jamais un redesign Wallet : solde, acquisition, historique.
    L'historique EST la preuve du crédit — jamais un état optimiste affiché sur un simple retour
    navigateur (art. 8 du mandat).
--}}
<x-layouts.portal title="Mon Wallet ZAHAB — DG Afrique">
    <x-dg.shell current="espace" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:1000px">
            <a href="{{ route('member.space') }}" class="dg-crumb">← Mon espace</a>

            @if(session('status'))
                <div class="dg-band" style="margin-bottom:20px">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="dg-band" style="margin-bottom:20px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>
            @endif

            <div class="dg-page-header">
                <div>
                    <x-dg.label tone="saffron">Wallet ZAHAB</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:6px">Mon Wallet ZAHAB</h1>
                    <p>1 ZAHAB = 1 FCFA. Unité interne du réseau DG Afrique — aucun retrait, aucune conversion externe.</p>
                </div>
                <div style="text-align:right">
                    <div class="dg-display" style="font-size:28px">{{ number_format($balance, 0, ',', ' ') }} <small style="font-size:14px">ZAHAB</small></div>
                    <div class="dg-meta">Solde disponible</div>
                </div>
            </div>

            <x-dg.card style="margin-bottom:24px">
                <x-dg.label>Acquérir des ZAHAB</x-dg.label>
                <p class="dg-body" style="margin-top:8px">Réglez avec GeniusPay ; le crédit n’intervient qu’après confirmation réelle du paiement — jamais sur un simple retour de la page GeniusPay.</p>
                <form method="POST" action="{{ route('zahab.acquisitions.store') }}" style="margin-top:16px;display:flex;gap:12px;align-items:end;flex-wrap:wrap">
                    @csrf
                    <label style="display:flex;flex-direction:column;gap:4px">
                        <span class="dg-meta">Montant (FCFA = ZAHAB)</span>
                        <input type="number" name="amount" min="1" step="1" value="5000" class="dg-input" style="max-width:160px">
                    </label>
                    <button type="submit" class="dg-btn dg-btn--primary">Acquérir des ZAHAB</button>
                </form>
            </x-dg.card>

            <x-dg.card>
                <x-dg.label>Historique</x-dg.label>

                @if($movements->isEmpty() && $acquisitions->isEmpty())
                    <p class="dg-body" style="margin-top:8px">Aucun mouvement pour le moment.</p>
                @else
                    @if($acquisitions->isNotEmpty())
                        <p class="dg-meta" style="margin-top:12px">Acquisitions</p>
                        <ul style="margin-top:8px;display:flex;flex-direction:column;gap:6px">
                            @foreach($acquisitions as $acquisition)
                                <li class="dg-body" style="font-size:14px">
                                    {{ $acquisition->created_at->format('d/m/Y à H:i') }} —
                                    {{ number_format($acquisition->amount, 0, ',', ' ') }} ZAHAB —
                                    {{ match($acquisition->status) { 'COMPLETED' => 'Confirmée et créditée', 'PENDING' => 'En attente', 'PROCESSING' => 'En cours de traitement', 'FAILED' => 'Échouée', 'CANCELLED' => 'Annulée', default => $acquisition->status } }}
                                    @if($acquisition->credited_at)
                                        <span class="dg-meta">· créditée le {{ $acquisition->credited_at->format('d/m/Y à H:i') }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if($movements->isNotEmpty())
                        <p class="dg-meta" style="margin-top:16px">Mouvements du Wallet</p>
                        <ul style="margin-top:8px;display:flex;flex-direction:column;gap:6px">
                            @foreach($movements as $movement)
                                <li class="dg-body" style="font-size:14px">
                                    {{ $movement->occurred_at->format('d/m/Y à H:i') }} —
                                    {{ $movement->direction === 'CREDIT' ? '+' : '−' }}{{ number_format($movement->amount, 0, ',', ' ') }} ZAHAB
                                    <span class="dg-meta">· {{ $movement->purpose_code }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            </x-dg.card>
        </div>
    </x-dg.shell>
</x-layouts.portal>
