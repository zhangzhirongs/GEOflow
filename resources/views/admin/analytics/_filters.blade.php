@php
    $filterData = $filters->toArray();
    $presetOptions = ['today', 'yesterday', '7d', '30d', '90d', 'custom'];
    $today = now()->startOfDay();
    $presetRanges = [
        'today' => [$today->toDateString(), $today->toDateString()],
        'yesterday' => [$today->copy()->subDay()->toDateString(), $today->copy()->subDay()->toDateString()],
        '7d' => [$today->copy()->subDays(6)->toDateString(), $today->toDateString()],
        '30d' => [$today->copy()->subDays(29)->toDateString(), $today->toDateString()],
        '90d' => [$today->copy()->subDays(89)->toDateString(), $today->toDateString()],
        'custom' => [$filterData['date_from'], $filterData['date_to']],
    ];
@endphp

<section class="mb-5 rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm">
    <div class="mb-3 flex items-center">
        <h2 class="text-sm font-semibold text-gray-900">{{ __('admin.analytics.filters.title') }}</h2>
    </div>
    <form id="analytics-filter-form" method="GET" action="{{ $analyticsFilterRoute ?? route('admin.analytics.content') }}" class="space-y-3" data-analytics-filter-form>
        <input type="hidden" name="preset" value="{{ $filterData['preset'] }}">
        <fieldset class="flex flex-wrap items-end gap-2">
            <legend class="sr-only">{{ __('admin.analytics.filters.preset') }}</legend>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($presetOptions as $preset)
                    @php
                        $presetClass = $filterData['preset'] === $preset
                            ? 'border-blue-600 bg-blue-50 text-blue-700'
                            : 'border-gray-200 text-gray-600 hover:border-blue-200 hover:bg-blue-50';
                        [$presetFrom, $presetTo] = $presetRanges[$preset];
                    @endphp
                    <button
                        type="button"
                        class="inline-flex min-h-8 cursor-pointer items-center rounded-md border px-2.5 py-1 text-xs font-medium transition duration-[120ms] motion-reduce:transition-none active:scale-[.98] motion-reduce:active:scale-100 {{ $presetClass }}"
                        data-preset="{{ $preset }}"
                        data-date-from="{{ $presetFrom }}"
                        data-date-to="{{ $presetTo }}"
                        data-analytics-preset-button
                        aria-pressed="{{ $filterData['preset'] === $preset ? 'true' : 'false' }}"
                    >
                        {{ __('admin.analytics.filters.'.$preset) }}
                    </button>
                @endforeach
            </div>
        <div class="flex flex-wrap items-end gap-2 {{ $filterData['preset'] === 'custom' ? '' : 'hidden' }}" data-analytics-custom-date-fields>
            <div class="min-w-[8.5rem] flex-1 sm:flex-none">
                <label for="analytics-date-from" class="mb-1 block text-xs font-medium text-gray-600">{{ __('admin.analytics.filters.date_from') }}</label>
                <input id="analytics-date-from" type="date" name="date_from" value="{{ $filterData['date_from'] }}" max="{{ now()->toDateString() }}" data-analytics-custom-date class="block min-h-9 w-full rounded-md border-gray-300 text-xs shadow-sm">
            </div>
            <div class="min-w-[8.5rem] flex-1 sm:flex-none">
                <label for="analytics-date-to" class="mb-1 block text-xs font-medium text-gray-600">{{ __('admin.analytics.filters.date_to') }}</label>
                <input id="analytics-date-to" type="date" name="date_to" value="{{ $filterData['date_to'] }}" max="{{ now()->toDateString() }}" data-analytics-custom-date class="block min-h-9 w-full rounded-md border-gray-300 text-xs shadow-sm">
            </div>
        </div>
        </fieldset>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ $analyticsFilterRoute ?? route('admin.analytics.content') }}" class="inline-flex min-h-9 items-center rounded-md border border-gray-300 bg-white px-3 py-1 text-xs font-medium text-gray-600 transition hover:border-blue-300 hover:text-blue-700">{{ __('admin.analytics.filters.reset') }}</a>
        </div>

        @php
            $analyticsMoreOpen = (int) ($filterData['task_id'] ?? 0) > 0
                || (int) ($filterData['category_id'] ?? 0) > 0
                || (int) ($filterData['article_id'] ?? 0) > 0
                || (int) ($filterData['channel_id'] ?? 0) > 0;
        @endphp
        <div>
            <button type="button"
                    class="admin-filter-btn admin-filter-btn--more"
                    data-filter-more-toggle
                    aria-controls="analytics-more-filters"
                    aria-expanded="{{ $analyticsMoreOpen ? 'true' : 'false' }}">
                <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                {{ __('admin.button.more_filters') }}
                <i data-lucide="chevron-down" class="w-4 h-4 admin-filter-more-caret"></i>
            </button>
        </div>
        <div id="analytics-more-filters" class="admin-filter-advanced {{ $analyticsMoreOpen ? 'is-open' : '' }}" data-filter-advanced>
            <div class="grid w-full grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                @if ($canManageProtectedWorkflows ?? false)
                <div>
                    <label for="analytics-channel-id" class="mb-1 block text-sm font-medium text-gray-700">{{ __('admin.analytics.filters.channel') }}</label>
                    <select id="analytics-channel-id" name="channel_id" class="block min-h-10 w-full rounded-md border-gray-300 text-sm shadow-sm">
                        <option value="">{{ __('admin.analytics.filters.all') }}</option>
                        @foreach ($filterOptions['channels'] as $channel)
                            <option value="{{ $channel->id }}" @selected((int) $filterData['channel_id'] === (int) $channel->id)>{{ $channel->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div>
                    <label for="analytics-task-id" class="mb-1 block text-sm font-medium text-gray-700">{{ __('admin.analytics.filters.task') }}</label>
                    <select id="analytics-task-id" name="task_id" class="block min-h-10 w-full rounded-md border-gray-300 text-sm shadow-sm">
                        <option value="">{{ __('admin.analytics.filters.all') }}</option>
                        @foreach ($filterOptions['tasks'] as $task)
                            <option value="{{ $task->id }}" @selected((int) $filterData['task_id'] === (int) $task->id)>{{ $task->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="analytics-category-id" class="mb-1 block text-sm font-medium text-gray-700">{{ __('admin.analytics.filters.category') }}</label>
                    <select id="analytics-category-id" name="category_id" class="block min-h-10 w-full rounded-md border-gray-300 text-sm shadow-sm">
                        <option value="">{{ __('admin.analytics.filters.all') }}</option>
                        @foreach ($filterOptions['categories'] as $category)
                            <option value="{{ $category->id }}" @selected((int) $filterData['category_id'] === (int) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="analytics-article-id" class="mb-1 block text-sm font-medium text-gray-700">{{ __('admin.analytics.filters.article') }}</label>
                    <select id="analytics-article-id" name="article_id" class="block min-h-10 w-full rounded-md border-gray-300 text-sm shadow-sm">
                        <option value="">{{ __('admin.analytics.filters.all') }}</option>
                        @foreach ($filterOptions['articles'] as $article)
                            <option value="{{ $article->id }}" @selected((int) $filterData['article_id'] === (int) $article->id)>{{ $article->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

    </form>
</section>
