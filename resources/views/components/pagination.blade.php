@if ($paginator->hasPages())
    @once
        <style>
            .app-pagination { display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: .5rem; font: inherit; font-size: .8rem; }
            .app-pagination-control { display: inline-flex; align-items: center; justify-content: center; padding: .35rem .65rem; min-height: 32px; border: 1px solid #eadde1; border-radius: 8px; background: #fff; color: #8b1828; font: inherit; text-decoration: none; }
            .app-pagination-control:disabled { opacity: .45; cursor: not-allowed; }
            .app-pagination-control:focus-visible { outline: 2px solid #8b1828; outline-offset: 2px; }
            .app-pagination-position { color: #776b71; white-space: nowrap; }
        </style>
    @endonce
    <nav class="app-pagination" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <button type="button" class="app-pagination-control" disabled>Previous</button>
        @else
            <a class="app-pagination-control" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
        @endif
        <span class="app-pagination-position" aria-live="polite">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a class="app-pagination-control" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
        @else
            <button type="button" class="app-pagination-control" disabled>Next</button>
        @endif
    </nav>
@endif
