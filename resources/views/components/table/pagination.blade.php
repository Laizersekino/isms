@props(['paginator'])

@if($paginator->hasPages())
    <nav class="mt-4 flex flex-wrap items-center justify-between gap-3" aria-label="Pagination">
        <p class="text-sm text-slate-600">
            Showing {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }}
        </p>
        <div class="flex flex-wrap items-center gap-1">
            @if($paginator->onFirstPage())
                <span class="rounded-md px-3 py-1.5 text-sm text-slate-400">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">Previous</a>
            @endif
            @for($page = max(1, $paginator->currentPage() - 2); $page <= min($paginator->lastPage(), $paginator->currentPage() + 2); $page++)
                <a href="{{ $paginator->url($page) }}" @if($page === $paginator->currentPage()) aria-current="page" @endif
                    @class([
                        'rounded-md px-3 py-1.5 text-sm',
                        'bg-primary-600 text-white' => $page === $paginator->currentPage(),
                        'border border-slate-300 text-slate-700 hover:bg-slate-50' => $page !== $paginator->currentPage(),
                    ])>{{ $page }}</a>
            @endfor
            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">Next</a>
            @else
                <span class="rounded-md px-3 py-1.5 text-sm text-slate-400">Next</span>
            @endif
        </div>
    </nav>
@endif
