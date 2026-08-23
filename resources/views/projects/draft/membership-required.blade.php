{{--
    UIUX-009B — la naissance d'un Projet exige déjà une adhésion Programme ZUMRA active
    (ProjectService::create(), inchangé). Plutôt que de laisser quelqu'un remplir sept étapes pour
    échouer à la toute fin, la règle est expliquée immédiatement, avant la première question.
--}}
<x-layouts.portal title="Adhésion nécessaire — DG Afrique">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:560px">
            <a href="{{ route('projects.index') }}" class="dg-crumb">← Tous les projets</a>

            <h1 class="dg-display dg-display--lead">Une adhésion active est nécessaire</h1>
            <p class="dg-body" style="margin-top:12px">Proposer un Projet suppose d’avoir déjà rejoint le Programme ZUMRA. Ce n’est pas encore votre cas — c’est la seule chose qui manque avant de pouvoir commencer.</p>

            <x-dg.btn variant="project" :href="route('zumra.membership.show')" style="margin-top:20px">Adhérer au Programme ZUMRA →</x-dg.btn>
        </div>
    </x-dg.shell>
</x-layouts.portal>
