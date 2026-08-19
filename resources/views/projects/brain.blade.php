<x-layouts.portal title="Cerveau — {{ $project->name }} — DG Afrique">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <style>
            .brain-shell{display:grid;grid-template-columns:260px minmax(0,1fr) 330px;min-height:calc(100vh - 110px);border:1px solid var(--dg-line);border-radius:18px;overflow:hidden;background:#fff}
            .brain-side{background:#f7f8f4;border-right:1px solid var(--dg-line);padding:18px}.brain-right{border-left:1px solid var(--dg-line);padding:18px;background:#fbfbf8}.brain-main{display:flex;flex-direction:column;min-height:720px}
            .brain-project{display:block;padding:11px 12px;border-radius:12px;color:var(--dg-text);text-decoration:none;margin:3px 0}.brain-project.active{background:#e9efe7;color:var(--dg-forest);font-weight:700}.brain-project:hover{background:#eef1eb}
            .brain-head{padding:20px 24px;border-bottom:1px solid var(--dg-line);display:flex;align-items:center;justify-content:space-between;gap:16px}.brain-thread{padding:26px;display:flex;flex-direction:column;gap:16px;flex:1;overflow:auto;background:linear-gradient(#fff,#fcfcfa)}
            .brain-msg{max-width:78%;padding:13px 15px;border-radius:16px;line-height:1.55;font-size:14px}.brain-msg.user{align-self:flex-end;background:var(--dg-forest);color:white;border-bottom-right-radius:5px}.brain-msg.assistant,.brain-msg.system{align-self:flex-start;background:#f0f2ed;color:var(--dg-text);border-bottom-left-radius:5px}.brain-msg.system{font-size:13px;border:1px solid var(--dg-line)}
            .brain-compose{padding:18px 22px;border-top:1px solid var(--dg-line);background:#fff}.brain-compose textarea{width:100%;min-height:82px;border:1px solid var(--dg-line);border-radius:14px;padding:13px;resize:vertical}.brain-actions{display:flex;justify-content:flex-end;margin-top:9px}
            .brain-draft{align-self:flex-start;max-width:88%;border:1px solid #d9dfd3;border-radius:16px;padding:16px;background:#fff;box-shadow:0 8px 24px rgba(26,50,35,.06)}.brain-draft dl{display:grid;grid-template-columns:100px 1fr;gap:7px 12px;font-size:13px;margin:12px 0}.brain-draft dt{color:#6b746c}.brain-draft dd{font-weight:600}.brain-draft form{display:inline-block;margin-right:6px}
            .brain-card{border:1px solid var(--dg-line);border-radius:14px;padding:14px;margin-top:10px}.brain-card h4{font-size:14px;margin:0 0 5px}.brain-kicker{text-transform:uppercase;letter-spacing:.09em;font-size:11px;font-weight:800;color:#758077}.brain-empty{color:#788078;font-size:13px;line-height:1.5}
            @media(max-width:1100px){.brain-shell{grid-template-columns:210px minmax(0,1fr)}.brain-right{display:none}}@media(max-width:760px){.brain-shell{display:block}.brain-side{display:none}.brain-main{min-height:calc(100vh - 130px)}.brain-msg,.brain-draft{max-width:94%}.brain-head{padding:16px}.brain-thread{padding:16px}}
        </style>
        <div class="dg-page" style="max-width:none;padding-left:18px;padding-right:18px">
            @if(session('status'))<div class="dg-band" style="margin-bottom:12px">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="dg-band" style="margin-bottom:12px;border-color:var(--dg-copper);color:var(--dg-copper)">{{ $errors->first() }}</div>@endif

            <div class="brain-shell">
                <aside class="brain-side">
                    <div class="brain-kicker">Mes projets</div>
                    <h2 style="font-size:20px;margin:6px 0 16px">Projet V2</h2>
                    @foreach($visibleProjects as $candidate)
                        <a class="brain-project {{ $candidate->id === $project->id ? 'active' : '' }}" href="{{ route('projects.brain.show',$candidate) }}">
                            <div style="font-size:14px">{{ $candidate->name }}</div>
                            <div style="font-size:11px;opacity:.65;margin-top:3px">{{ str_replace('_',' ',mb_strtolower($candidate->status)) }}</div>
                        </a>
                    @endforeach
                    <a href="{{ route('projects.index') }}" class="brain-project" style="margin-top:14px">← Vue classique</a>
                </aside>

                <main class="brain-main">
                    <header class="brain-head">
                        <div><div class="brain-kicker">Cerveau du projet</div><h1 style="font-size:22px;margin:3px 0">{{ $project->name }}</h1></div>
                        <a href="{{ route('projects.show',$project) }}" class="dg-btn dg-btn--secondary">Fiche projet</a>
                    </header>

                    <section class="brain-thread">
                        @if($messages->isEmpty())
                            <div class="brain-msg assistant">Bonjour. Dites-moi ce qui manque au projet. Pour cette première version, je peux préparer un <strong>Besoin</strong>. Je ne crée rien avant votre confirmation.</div>
                        @endif
                        @foreach($messages as $message)
                            <div class="brain-msg {{ mb_strtolower($message->role) }}">{{ $message->content }}</div>
                            @if(($message->meta['draft_reference'] ?? null) && ($draft = $drafts->get($message->meta['draft_reference'])))
                                @php($p=$draft->payload)
                                <div class="brain-draft">
                                    <div class="brain-kicker">Brouillon — aucune mutation</div>
                                    <h3 style="font-size:17px;margin:5px 0">{{ $p['title'] }}</h3>
                                    <dl>
                                        <dt>Catégorie</dt><dd>{{ $needConfiguration['categories'][$p['category']] ?? $p['category'] }}</dd>
                                        <dt>Lieu</dt><dd>{{ $p['location'] ?? 'Non précisé' }}</dd>
                                        <dt>Mode</dt><dd>{{ $p['collaboration_mode'] }}</dd>
                                        <dt>Visibilité</dt><dd>Privée au projet</dd>
                                    </dl>
                                    <form method="POST" action="{{ route('projects.brain.needs.confirm',[$project,$draft]) }}">@csrf<button class="dg-btn" type="submit">Créer ce besoin</button></form>
                                    <form method="POST" action="{{ route('projects.brain.drafts.cancel',[$project,$draft]) }}">@csrf<button class="dg-btn dg-btn--secondary" type="submit">Abandonner</button></form>
                                </div>
                            @endif
                        @endforeach
                    </section>

                    <form class="brain-compose" method="POST" action="{{ route('projects.brain.needs.prepare',$project) }}">
                        @csrf
                        <textarea name="message" required minlength="8" maxlength="3000" placeholder="Ex. Il nous faut deux développeurs Laravel à Bouaké…">{{ old('message') }}</textarea>
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:8px"><span class="brain-empty">Préparation locale V1 · confirmation obligatoire</span><button class="dg-btn" type="submit">Préparer</button></div>
                    </form>
                </main>

                <aside class="brain-right">
                    <div class="brain-kicker">État réel du Core</div>
                    <h3 style="font-size:18px;margin:6px 0 16px">Aujourd’hui</h3>
                    <div class="brain-card"><div class="brain-kicker">Projet</div><h4>{{ $project->name }}</h4><div class="brain-empty">{{ str_replace('_',' ',mb_strtolower($project->status)) }}</div></div>
                    <div style="margin-top:22px;display:flex;justify-content:space-between;align-items:center"><div class="brain-kicker">Besoins actifs</div><strong>{{ $projectNeeds->count() }}</strong></div>
                    @forelse($projectNeeds as $need)
                        <a href="{{ route('needs.show',$need) }}" class="brain-card" style="display:block;text-decoration:none;color:inherit"><h4>{{ $need->title }}</h4><div class="brain-empty">{{ $needConfiguration['categories'][$need->category] ?? $need->category }} · {{ str_replace('_',' ',mb_strtolower($need->status)) }}</div></a>
                    @empty
                        <p class="brain-empty" style="margin-top:10px">Aucun besoin actif. Le Cerveau peut en préparer un avec vous.</p>
                    @endforelse
                </aside>
            </div>
        </div>
    </x-dg.shell>
</x-layouts.portal>
