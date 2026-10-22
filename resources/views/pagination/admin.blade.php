<nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-wrap items-center justify-between gap-3">
    <span class="text-sm text-gray-500">
        {{ __('admin.articles.pagination.summary', ['from' => $paginator->firstItem() ?? 0, 'to' => $paginator->lastItem() ?? 0, 'total' => $paginator->total()]) }}
    </span>
    <div class="flex flex-wrap items-center gap-1">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-400">{{ __('pagination.previous') }}</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('pagination.previous') }}</a>
        @endif
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-2 text-sm text-gray-400">{{ $element }}</span>
            @else
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="rounded-md border border-blue-600 bg-blue-600 px-3 py-2 text-sm font-medium text-white">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('pagination.next') }}</a>
        @else
            <span aria-disabled="true" class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-400">{{ __('pagination.next') }}</span>
        @endif
    </div>
</nav>
