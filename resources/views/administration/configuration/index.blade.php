<x-layouts.admin title="Configuration — Administration" current="configuration">
    <div class="ac-page-head">
        <div>
            <p class="dg-label dg-label--saffron">Configuration</p>
            <h1>Configuration</h1>
            <p>Chaque réglage réutilise la configuration existante (PortalSetting), organisée ici par domaine. Les invariants doctrinaux (parité ZAHAB, règles Ledger, prix d’adhésion, secrets prestataires) ne deviennent jamais configurables — ils n’apparaissent pas ici.</p>
        </div>
    </div>

    <div class="ac-section-grid">
        @foreach($groups as $group => $items)
            <section class="ac-section">
                <div class="ac-section__head"><h2>{{ $group }}</h2></div>
                <div class="ac-list">
                    @foreach($items as $item)
                        <a href="{{ route($item['route']) }}" class="ac-list__row" style="text-decoration:none">
                            <div><strong>{{ $item['label'] }}</strong><small>{{ $item['description'] }}</small></div>
                            <span aria-hidden="true">→</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-layouts.admin>
