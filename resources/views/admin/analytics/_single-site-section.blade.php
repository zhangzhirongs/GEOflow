@php
    $singleSiteKpis = [
        ['key' => 'articles', 'icon' => 'file-text', 'tone' => 'text-blue-600'],
        ['key' => 'published', 'icon' => 'globe', 'tone' => 'text-emerald-600'],
        ['key' => 'running_tasks', 'icon' => 'activity', 'tone' => 'text-amber-600'],
        ['key' => 'failed_tasks', 'icon' => 'triangle-alert', 'tone' => 'text-red-600'],
        ['key' => 'ai_calls', 'icon' => 'cpu', 'tone' => 'text-indigo-600'],
    ];
@endphp

<section class="mb-8" data-analytics-single-site-section>
    <div class="mb-4 flex items-baseline justify-between gap-3">
        <h2 class="text-lg font-semibold text-gray-900">{{ __('admin.analytics.single_site_title') }}</h2>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
        @foreach ($singleSiteKpis as $card)
            <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:p-5">
                <div class="flex items-center gap-3">
                    <i data-lucide="{{ $card['icon'] }}" class="h-5 w-5 shrink-0 {{ $card['tone'] }}"></i>
                    <div class="min-w-0">
                        <div class="truncate text-xs font-medium text-gray-500">{{ __('admin.analytics.kpi.'.$card['key']) }}</div>
                        <div class="mt-1 text-xl font-bold tabular-nums text-gray-900">{{ number_format((int) ($kpis[$card['key']] ?? 0)) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @include('admin.analytics._content-section')
</section>
