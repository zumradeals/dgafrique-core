<x-layouts.admin title="Satellites — Administration DG Afrique" current="configuration">
    @include('projects.partials.accompaniment-styles')
    <main class="admin-content"><div class="admin-heading"><div><p class="eyebrow dark">Administration DG Afrique · CAP‑048</p><h1>Registre des satellites</h1><p>Les satellites officiellement raccordés à DG Afrique. Aucun identifiant interne n’est présenté aux membres — seul le nom d’affichage l’est.</p></div><div class="admin-links"><a href="{{ route('member.space') }}">Mon espace</a></div></div>
        @if(session('status'))<div class="alert success">{{ session('status') }}</div>@endif @if($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif

        <section><h2>Déclarer un satellite</h2>
            <form method="POST" action="{{ route('administration.satellites.store') }}" class="admin-form">@csrf
                <label>Référence produit<input name="product_reference" value="{{ old('product_reference') }}" placeholder="Référence technique du produit" required></label>
                <label>Nom d’affichage<input name="display_name" value="{{ old('display_name') }}" required></label>
                <label class="wide">Description<textarea name="description" maxlength="1000">{{ old('description') }}</textarea></label>
                <label>URL de rappel (https)<input name="callback_url" value="{{ old('callback_url') }}" placeholder="https://…"></label>
                <button class="primary-button">Déclarer</button>
            </form>
        </section>

        <section class="admin-accompaniment-list"><div class="section-heading"><div><p class="eyebrow dark">Registre</p><h2>Satellites déclarés</h2></div><small>{{ $satellites->count() }} satellite(s)</small></div>
            @forelse($satellites as $satellite)
                <article class="admin-accompaniment-card"><header><div><span>{{ $satellite->is_active ? 'Actif' : 'Désactivé' }}</span><h3>{{ $satellite->display_name }}</h3><p>Référence produit : {{ $satellite->product_reference }} · URL de continuité : /federation/continue/{{ $satellite->slug }}</p></div></header>
                    @if($satellite->description)<p>{{ $satellite->description }}</p>@endif
                    <form method="POST" action="{{ route('administration.satellites.update', $satellite) }}" class="accompaniment-action-form">@csrf @method('PUT')
                        <label>Nom d’affichage<input name="display_name" value="{{ $satellite->display_name }}" required></label>
                        <label>Référence produit<input name="product_reference" value="{{ $satellite->product_reference }}" required></label>
                        <label class="wide">Description<textarea name="description" maxlength="1000">{{ $satellite->description }}</textarea></label>
                        <label>URL de rappel (https)<input name="callback_url" value="{{ $satellite->callback_url }}" placeholder="https://…"></label>
                        <button class="primary-button">Enregistrer</button>
                    </form>
                    <form method="POST" action="{{ route('administration.satellites.toggle', $satellite) }}" class="accompaniment-action-form">@csrf @method('PUT')
                        <button class="primary-button">{{ $satellite->is_active ? 'Désactiver' : 'Réactiver' }}</button>
                    </form>
                </article>
            @empty<div class="accompaniment-empty"><strong>Aucun satellite déclaré.</strong></div>@endforelse
        </section>
    </main>
</x-layouts.admin>
