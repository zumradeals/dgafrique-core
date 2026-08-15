<x-layouts.portal title="Vérification Carte ZUMRA — DG Afrique">
    @php $valid = $state === 'ACTIVE'; $labels = ['ACTIVE' => 'Carte active', 'SUSPENDED' => 'Carte suspendue', 'CLOSED' => 'Carte fermée', 'REVOKED' => 'Carte révoquée']; @endphp
    <main class="card-verification-page">
        <a class="brand verification-brand" href="/"><span class="brand-mark">G</span><span>DG Afrique</span></a>
        <section class="verification-result {{ $valid ? 'valid' : 'invalid' }}">
            <span class="verification-symbol">{{ $valid ? '✓' : '!' }}</span>
            <p class="eyebrow dark">VÉRIFICATION OFFICIELLE</p>
            <h1>{{ $labels[$state] ?? 'Carte indisponible' }}</h1>
            <p>{{ $valid ? 'Cette Carte ZUMRA correspond à une adhésion active au moment de cette vérification.' : 'Cette carte ne doit pas être présentée comme une adhésion active.' }}</p>
            <dl><div><dt>Titulaire</dt><dd>{{ $card->holder_label }}</dd></div><div><dt>Référence membre</dt><dd>{{ $card->core_identity_reference }}</dd></div><div><dt>Pays</dt><dd>{{ $card->country_code ?: 'Non renseigné' }}</dd></div><div><dt>Adhésion depuis</dt><dd>{{ $membership->activated_at?->format('d/m/Y') ?? '—' }}</dd></div><div><dt>Carte</dt><dd>{{ $card->public_reference }}</dd></div></dl>
            <small>Cette attestation ne constitue ni un moyen de paiement, ni une preuve de contribution, ni une garantie financière.</small>
        </section>
    </main>
</x-layouts.portal>
