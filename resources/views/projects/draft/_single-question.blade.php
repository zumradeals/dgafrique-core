{{--
    Gabarit partagé des étapes « une question, un champ » — UIUX-009B.
    Attend : $step, $field, $question (H1), $help (sous-titre, optionnel), $placeholder,
    $fieldType ('input'|'textarea'), $rows (textarea), $minlength, $maxlength, $continueLabel (optionnel).
--}}
<x-layouts.portal :title="$question.' — DG Afrique'">
    <x-dg.shell current="projets" :identity="$identity" :is-administrator="$isAdministrator">
        <div class="dg-page" style="max-width:640px">
            @include('projects.draft._header', ['step' => $step])

            <h1 class="dg-display dg-display--lead">{{ $question }}</h1>
            @isset($help)
                <p class="dg-body" style="margin-top:8px">{{ $help }}</p>
            @endisset

            <form method="POST" action="{{ route('projects.draft.update', [$draft, $step]) }}" style="margin-top:26px">
                @csrf

                <div class="dg-field">
                    <label for="{{ $field }}" class="dg-sr-only" style="position:absolute;width:1px;height:1px;overflow:hidden">{{ $question }}</label>
                    @if(($fieldType ?? 'textarea') === 'input')
                        <input type="text" name="{{ $field }}" id="{{ $field }}" class="dg-input" style="font-size:17px;padding:16px"
                               value="{{ old($field, $payload[$field] ?? '') }}"
                               placeholder="{{ $placeholder ?? '' }}"
                               minlength="{{ $minlength ?? 1 }}" maxlength="{{ $maxlength ?? 255 }}" required autofocus>
                    @else
                        <textarea name="{{ $field }}" id="{{ $field }}" class="dg-textarea" style="font-size:16px;padding:16px" rows="{{ $rows ?? 5 }}"
                                  placeholder="{{ $placeholder ?? '' }}"
                                  minlength="{{ $minlength ?? 1 }}" maxlength="{{ $maxlength ?? 2400 }}" required autofocus>{{ old($field, $payload[$field] ?? '') }}</textarea>
                    @endif
                    @isset($hint)
                        <p class="dg-hint">{{ $hint }}</p>
                    @endisset
                </div>

                @include('projects.draft._footer')
            </form>

            @include('projects.draft._abandon')
        </div>
    </x-dg.shell>
</x-layouts.portal>
