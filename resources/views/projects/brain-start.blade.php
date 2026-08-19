<x-layouts.portal title="Nouveau projet — Cerveau Projet">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:1180px;margin-inline:auto">
            <div style="display:flex;justify-content:space-between;gap:20px;align-items:flex-start;margin-bottom:28px">
                <div>
                    <x-dg.label tone="night">Cerveau du Projet</x-dg.label>
                    <h1 class="dg-display dg-display--screen" style="margin-top:7px">{{ isset($intent) ? ($intent->title ?: 'Projet en préparation') : 'Qu’est-ce que vous voulez réaliser ?' }}</h1>
                    <p style="max-width:720px">Pas de grand formulaire. Parlez simplement de votre idée. Le Cerveau la structure progressivement avec vous.</p>
                </div>
                <x-dg.btn variant="quiet" :href="route('projects.index')">← Projets</x-dg.btn>
            </div>

            <div style="display:grid;grid-template-columns:minmax(0,1fr) 310px;gap:22px;align-items:start">
                <section class="dg-card" style="min-height:560px;display:flex;flex-direction:column;padding:0;overflow:hidden">
                    <div style="padding:18px 22px;border-bottom:1px solid var(--dg-border,#e7e2dc);display:flex;justify-content:space-between;align-items:center">
                        <strong>Conversation</strong>
                        <span class="dg-meta">{{ isset($intent) ? 'Brouillon sauvegardé' : 'Projet zéro' }}</span>
                    </div>

                    <div style="padding:24px;display:flex;flex:1;flex-direction:column;gap:16px">
                        @if(isset($intent))
                            @foreach($intent->messages as $message)
                                <div style="max-width:82%;align-self:{{ $message['role'] === 'user' ? 'flex-end' : 'flex-start' }};padding:13px 16px;border-radius:16px;background:{{ $message['role'] === 'user' ? '#0d3b46' : '#f4f0ea' }};color:{{ $message['role'] === 'user' ? '#fff' : 'inherit' }};line-height:1.55">
                                    {{ $message['content'] }}
                                </div>
                            @endforeach
                        @else
                            <div style="max-width:82%;padding:14px 17px;border-radius:16px;background:#f4f0ea;line-height:1.55">
                                Bonjour. Dites-moi votre idée avec vos propres mots. Même si elle n’est pas encore claire, on commence avec ça.
                            </div>
                        @endif
                    </div>

                    <form method="POST" action="{{ isset($intent) ? route('projects.brain.start.reply',$intent) : route('projects.brain.start.store') }}" style="padding:18px 22px;border-top:1px solid var(--dg-border,#e7e2dc)">
                        @csrf
                        <div style="display:flex;gap:10px;align-items:flex-end">
                            <textarea name="message" rows="3" required autofocus placeholder="Ex. Je veux créer à Bouaké un centre pour initier les jeunes à l’informatique…" style="flex:1;resize:vertical;border:1px solid #d9d3cc;border-radius:14px;padding:13px 15px;font:inherit">{{ old('message') }}</textarea>
                            <x-dg.btn variant="project" type="submit">Envoyer →</x-dg.btn>
                        </div>
                        @error('message')<p style="color:#a33;margin-top:8px">{{ $message }}</p>@enderror
                    </form>
                </section>

                <aside class="dg-card" style="display:flex;flex-direction:column;gap:16px">
                    <x-dg.label tone="night">Projet vivant</x-dg.label>
                    @if(isset($intent))
                        <div><span class="dg-meta">État</span><div style="font-weight:700;margin-top:4px">En préparation</div></div>
                        <div><span class="dg-meta">Intention de départ</span><p style="margin-top:5px">{{ $intent->context['initial_intent'] ?? '—' }}</p></div>
                        <div style="padding:12px;border-radius:12px;background:#fff6ec;font-size:.92rem">Aucun Projet canonique n’est encore créé. Vos réponses construisent le brouillon ; le Core ne sera modifié qu’après une confirmation explicite.</div>
                    @else
                        <p>Votre idée n’a pas besoin d’être structurée avant de commencer.</p>
                        <div style="padding:12px;border-radius:12px;background:#f4f0ea;font-size:.92rem">Une question à la fois. Rien n’est publié automatiquement.</div>
                    @endif
                </aside>
            </div>
        </div>

        <style>
            @media (max-width: 900px){
                .dg-page > div[style*="grid-template-columns"]{grid-template-columns:1fr!important}
                .dg-page aside{order:-1}
            }
        </style>
    </x-dg.shell>
</x-layouts.portal>
