<section id="mes-outils" class="dg-cockpit-tools" aria-labelledby="outils-title">
    <div class="dg-cockpit-section-heading">
        <div>
            <p class="dg-cockpit-kicker">POUR PASSER À L’ACTION</p>
            <h2 id="outils-title">Mes outils</h2>
            <p>Retrouvez les ressources utiles à votre participation.</p>
        </div>
    </div>

    @foreach ([
        ['Apprendre et transmettre', [['transmissions.index', 'Transmissions', 'Vos savoir-faire, apprentissages et ressources.', 'transmission']]],
        ['Agir et suivre', [['missions.index', 'Missions', 'Vos missions en cours et passées.', 'project'], ['proofs.index', 'Preuves de réalisation', 'Vos réalisations et leur impact.', 'proof']]],
        ['Contribuer', [['contributions.dashboard', 'Contributions', 'Vos contributions à la communauté.', 'need'], ['zahab.wallet.dashboard', 'ZAHAB', 'Soutenez et faites grandir les initiatives.', 'project']]],
    ] as [$heading, $entries])
        <div class="dg-cockpit-tool-group">
            <h3>{{ $heading }}</h3>
            <div class="dg-cockpit-tool-list">
                @foreach ($entries as [$destination, $label, $description, $icon])
                    <a class="dg-cockpit-tool-row" href="{{ route($destination) }}">
                        <span class="dg-cockpit-tool-icon"><x-dg.icon :name="$icon" /></span>
                        <span><strong>{{ $label }}</strong><small>{{ $description }}</small></span>
                        <span aria-hidden="true">→</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach

    @if ($connectedTools->isNotEmpty())
        <div class="dg-cockpit-tool-group">
            <h3>Outils connectés</h3>
            <div class="dg-cockpit-tool-list">
                @foreach ($connectedTools as $tool)
                    <form class="dg-cockpit-connected-form" method="POST" action="{{ route('federation.continue', $tool->slug) }}">
                        @csrf
                        <button class="dg-cockpit-tool-row" type="submit">
                            <span class="dg-cockpit-tool-icon"><x-dg.icon name="project" /></span>
                            <span><strong>{{ $tool->display_name }}</strong><small>{{ $tool->description ?: 'Ouvrir cet outil connecté.' }}</small></span>
                            <span aria-hidden="true">→</span>
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    @endif
</section>
