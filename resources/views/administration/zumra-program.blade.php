<x-layouts.portal title="Administration ZUMRA — DG Afrique">
    <main class="admin-content">
        <div class="admin-heading"><div><p class="eyebrow dark">Administration DG Afrique</p><h1>Programme ZUMRA</h1><p>Gérez la présentation et publiez les versions de la charte sans altérer les acceptations passées.</p></div><div class="admin-links"><a href="{{ route('administration.profile.edit') }}">Profil de capacités</a><a href="{{ route('member.space') }}">Mon espace</a></div></div>
        @if (session('status'))<div class="alert success">{{ session('status') }}</div>@endif @if ($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif

        <form method="POST" action="{{ route('administration.zumra.update') }}" class="admin-form">@csrf @method('PUT')
            <section><h2>Présentation membre</h2><label>Titre<input name="title" value="{{ old('title', $configuration['title']) }}" required></label><label>Introduction<textarea name="introduction" rows="3" required>{{ old('introduction', $configuration['introduction']) }}</textarea></label><label>Rappel sur l’engagement<textarea name="commitment_notice" rows="3" required>{{ old('commitment_notice', $configuration['commitment_notice']) }}</textarea></label><label>Titre du dossier en attente<input name="pending_payment_title" value="{{ old('pending_payment_title', $configuration['pending_payment_title']) }}" required></label><label>Explication de l’attente<textarea name="pending_payment_help" rows="3" required>{{ old('pending_payment_help', $configuration['pending_payment_help']) }}</textarea></label><label>Libellé d’acceptation<input name="accept_label" value="{{ old('accept_label', $configuration['accept_label']) }}" required></label><label>Texte du bouton<input name="submit_label" value="{{ old('submit_label', $configuration['submit_label']) }}" required></label><label>Notice paiement indisponible<textarea name="payment_unavailable_notice" rows="3" required>{{ old('payment_unavailable_notice', $configuration['payment_unavailable_notice']) }}</textarea></label></section>
            <button class="primary-button" type="submit">Enregistrer la présentation</button>
        </form>

        <form method="POST" action="{{ route('administration.zumra.charter.publish') }}" class="admin-form charter-admin-form">@csrf
            <section><h2>Publier une nouvelle charte</h2><p class="admin-notice">Une version publiée est immuable. Sa publication retire la version précédente sans la supprimer.</p><label>Version<input name="version" value="{{ old('version') }}" placeholder="2026.1" required></label><label>Titre<input name="charter_title" value="{{ old('charter_title', $activeCharter?->title) }}" required></label><label>Texte intégral<textarea name="charter_body" rows="18" required>{{ old('charter_body', $activeCharter?->body) }}</textarea></label></section>
            <button class="primary-button" type="submit">Publier cette version</button>
        </form>

        <section class="charter-history"><h2>Historique des versions</h2>@forelse($charters as $charter)<article><div><strong>{{ $charter->version }}</strong><span>{{ $charter->title }}</span></div><div><small>{{ $charter->published_at->format('d/m/Y H:i') }}</small><b class="{{ strtolower($charter->status) }}">{{ $charter->status === 'PUBLISHED' ? 'En vigueur' : 'Conservée' }}</b></div></article>@empty<p>Aucune charte n’est encore publiée.</p>@endforelse</section>
    </main>
</x-layouts.portal>
