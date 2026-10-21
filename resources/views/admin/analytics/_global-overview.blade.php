@php
    $cards = [
        [
            'label' => __('admin.dashboard.total_articles'),
            'value' => $globalOverview['total_articles'] ?? 0,
            'hint' => __('admin.dashboard.today_added', ['count' => $globalOverview['today_articles'] ?? 0]),
            'icon' => 'file-text',
            'tone' => 'text-blue-600',
        ],
        [
            'label' => __('admin.dashboard.published'),
            'value' => $globalOverview['published_articles'] ?? 0,
            'hint' => __('admin.dashboard.publish_rate', ['rate' => $globalOverview['publish_rate'] ?? 0]),
            'icon' => 'globe',
            'tone' => 'text-emerald-600',
        ],
        [
            'label' => __('admin.dashboard.ai_generated'),
            'value' => $globalOverview['ai_generated_articles'] ?? 0,
            'hint' => __('admin.dashboard.ai_generated_ratio', ['rate' => $globalOverview['ai_generated_ratio'] ?? 0]),
            'icon' => 'brain',
            'tone' => 'text-purple-600',
        ],
        [
            'label' => __('admin.dashboard.active_tasks'),
            'value' => number_format((int) ($globalOverview['running_jobs'] ?? 0) + (int) ($globalOverview['pending_jobs'] ?? 0)).' / '.number_format((int) ($globalOverview['total_tasks'] ?? 0)),
            'hint' => __('admin.dashboard.active_tasks_detail', ['running' => $globalOverview['running_jobs'] ?? 0, 'pending' => $globalOverview['pending_jobs'] ?? 0]),
            'icon' => 'activity',
            'tone' => 'text-amber-600',
        ],
        [
            'label' => __('admin.dashboard.ai_models'),
            'value' => $globalOverview['active_ai_models'] ?? 0,
            'hint' => __('admin.ai_models.status_active'),
            'icon' => 'cpu',
            'tone' => 'text-indigo-600',
        ],
        [
            'label' => __('admin.dashboard.material_total'),
            'value' => $globalOverview['material_total'] ?? 0,
            'hint' => __('admin.nav.materials'),
            'icon' => 'database',
            'tone' => 'text-teal-600',
        ],
        [
            'label' => __('admin.dashboard.pending_review'),
            'value' => $globalOverview['pending_review'] ?? 0,
            'hint' => __('admin.articles.filters.review_status'),
            'icon' => 'clock',
            'tone' => 'text-red-600',
        ],
    ];
@endphp

<section class="mb-8" data-analytics-global-overview>
    <div class="mb-4 flex items-baseline justify-between gap-3">
        <h2 class="text-lg font-semibold text-gray-900">{{ __('admin.analytics.overall_title') }}</h2>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $card)
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <div class="flex items-center gap-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-gray-50">
                        <i data-lucide="{{ $card['icon'] }}" class="h-5 w-5 {{ $card['tone'] }}"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-xs font-medium text-gray-500">{{ $card['label'] }}</div>
                        <div class="mt-1 text-2xl font-bold tabular-nums text-gray-900">{{ is_numeric($card['value']) ? number_format((int) $card['value']) : $card['value'] }}</div>
                    </div>
                </div>
                <div class="mt-2 truncate border-t border-gray-50 pt-2 text-xs text-gray-400">{{ $card['hint'] }}</div>
            </div>
        @endforeach
    </div>
</section>
