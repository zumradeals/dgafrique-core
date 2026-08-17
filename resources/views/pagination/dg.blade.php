{{-- Pagination stylée DG Afrique — utilisée explicitement par le Fil, sans affecter les autres listes. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between gap-4" style="padding-top:8px">
        @if ($paginator->onFirstPage())
            <span class="dg-btn dg-btn--quiet" aria-disabled="true">← Précédent</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="dg-btn dg-btn--quiet">← Précédent</a>
        @endif

        <span class="dg-meta">Page {{ $paginator->currentPage() }} sur {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="dg-btn dg-btn--quiet">Suivant →</a>
        @else
            <span class="dg-btn dg-btn--quiet" aria-disabled="true">Suivant →</span>
        @endif
    </nav>
@endif
