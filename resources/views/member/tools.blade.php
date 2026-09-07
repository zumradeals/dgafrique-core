<section id="mes-outils" class="dg-space-section dg-space-tools" aria-labelledby="outils-title">
    <p class="dg-space-eyebrow">POUR PASSER À L’ACTION</p><h2 id="outils-title">Mes outils</h2><p>Retrouvez les ressources utiles à votre participation.</p>
    @foreach ([
        ['Apprendre et transmettre', [['transmissions.index', 'Transmissions', 'Partager et recevoir un savoir', 'transmission']]],
        ['Agir et garder une trace', [['missions.index', 'Missions', 'Retrouver les actions auxquelles vous participez', 'project'], ['proofs.index', 'Preuves de réalisation', 'Garder une trace de vos actions', 'proof']]],
        ['Contribuer et suivre', [['contributions.dashboard', 'Contributions', 'Suivre vos contributions', 'need'], ['zahab.wallet.dashboard', 'ZAHAB', 'Consulter votre portefeuille', 'project']]],
        ['Trouver une possibilité', [['opportunities.index', 'Opportunités', 'Des possibilités liées à votre situation', 'discover']]],
    ] as [$heading, $entries])
        <details class="dg-space-tool-group" open><summary>{{ $heading }}</summary>
            @foreach ($entries as [$destination, $label, $description, $icon])
                <a class="dg-space-row" href="{{ route($destination) }}"><x-dg.icon :name="$icon" /><span><strong>{{ $label }}</strong><small>{{ $description }}</small></span><span aria-hidden="true">→</span></a>
            @endforeach
        </details>
    @endforeach
</section>
<section class="dg-space-section" aria-labelledby="connected-title"><h2 id="connected-title">Outils connectés</h2>
@forelse ($connectedTools as $tool)
<form method="POST" action="{{ route('federation.continue', $tool->slug) }}">@csrf
<h3>{{ $tool->display_name }}</h3><p>{{ $tool->description }}</p><x-dg.button type="submit">Ouvrir {{ $tool->display_name }}</x-dg.button>
</form>
@empty<p>Aucun outil connecté actif n’est proposé pour le moment.</p>@endforelse
</section>
