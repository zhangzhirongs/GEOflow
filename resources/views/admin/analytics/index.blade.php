@extends('admin.layouts.app')

@php
    $cards = $overview['cards'] ?? [];
    $alert = $overview['alert'] ?? null;
    $modules = [
        [
            'key' => 'content',
            'route' => 'admin.analytics.content',
            'tone' => 'bg-blue-600',
            'icon' => 'files',
            'main' => (int) ($cards['content']['published'] ?? 0),
            'main_label' => __('admin.analytics.overview.cards.content.main'),
            'secondary' => [
                [__('admin.analytics.overview.cards.content.created'), (int) ($cards['content']['created'] ?? 0)],
                [__('admin.analytics.overview.cards.content.failed'), (int) ($cards['content']['failed_tasks'] ?? 0)],
            ],
            'period' => __('admin.analytics.overview.periods.7d'),
        ],
        [
            'key' => 'ai_visibility',
            'route' => 'admin.analytics.ai-visibility',
            'tone' => 'bg-violet-600',
            'icon' => 'scan-search',
            'main' => number_format((float) ($cards['ai_visibility']['visibility'] ?? 0), 1).'%',
            'main_label' => __('admin.analytics.overview.cards.ai_visibility.main'),
            'secondary' => [
                [__('admin.analytics.overview.cards.ai_visibility.top3'), number_format((float) ($cards['ai_visibility']['top3'] ?? 0), 1).'%'],
                [__('admin.analytics.overview.cards.ai_visibility.samples'), (int) ($cards['ai_visibility']['samples'] ?? 0)],
            ],
            'period' => __('admin.analytics.overview.periods.60d'),
        ],
        [
            'key' => 'leads',
            'route' => 'admin.analytics.leads',
            'tone' => 'bg-amber-500',
            'icon' => 'contact-round',
            'main' => (int) ($cards['leads']['submissions_30d'] ?? 0),
            'main_label' => __('admin.analytics.overview.cards.leads.main'),
            'secondary' => [
                [__('admin.analytics.overview.cards.leads.new'), (int) ($cards['leads']['new_30d'] ?? 0)],
                [__('admin.analytics.overview.cards.leads.converted'), (int) ($cards['leads']['converted_30d'] ?? 0)],
            ],
            'period' => __('admin.analytics.overview.periods.30d'),
        ],
    ];
    if ($canManageProtectedWorkflows) {
        $modules[] = [
            'key' => 'distribution',
            'route' => 'admin.analytics.distribution',
            'tone' => 'bg-slate-700',
            'icon' => 'radio-tower',
            'main' => (int) ($cards['distribution']['total'] ?? 0),
            'main_label' => __('admin.analytics.overview.cards.distribution.main'),
            'secondary' => [
                [__('admin.analytics.overview.cards.distribution.synced'), (int) ($cards['distribution']['synced'] ?? 0)],
                [__('admin.analytics.overview.cards.distribution.failed'), (int) ($cards['distribution']['failed'] ?? 0)],
            ],
            'period' => __('admin.analytics.overview.periods.current'),
        ];
    }
@endphp

@section('content')
    <div class="growth-center growth-overview px-4 sm:px-0" data-overview-refresh="30000" data-overview-url="{{ route('admin.analytics') }}">
        @include('admin.analytics._page-header', [
            'title' => __('admin.analytics.overview.title'),
            'subtitle' => __('admin.analytics.overview.subtitle'),
        ])

        <section class="growth-metrics grid grid-cols-1 gap-4 sm:grid-cols-2 {{ count($modules) === 4 ? 'xl:grid-cols-4' : 'xl:grid-cols-3' }}" aria-label="{{ __('admin.analytics.overview.modules_label') }}">
            @foreach ($modules as $module)
                <a href="{{ route($module['route']) }}" class="growth-metric" data-growth-metric="{{ $module['key'] }}">
                    <div class="flex items-center justify-between gap-3">
                        <span class="growth-metric-icon {{ $module['tone'] }}"><i data-lucide="{{ $module['icon'] }}" class="h-5 w-5"></i></span>
                        <span class="growth-period">{{ $module['period'] }}</span>
                    </div>
                    <h2 class="mt-4 text-sm font-medium text-slate-500">{{ __('admin.analytics.pages.'.$module['key'].'.title') }}</h2>
                    <div class="growth-metric-number">{{ $module['main'] }}</div>
                    <p class="text-xs text-slate-500">{{ $module['main_label'] }}</p>
                    <dl class="mt-5 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4">
                        @foreach ($module['secondary'] as [$label, $value])
                            <div class="min-w-0"><dt class="truncate text-xs text-slate-500">{{ $label }}</dt><dd class="mt-1 text-lg font-semibold tabular-nums text-slate-800">{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                    <span class="growth-metric-link">{{ __('admin.analytics.overview.view_analysis') }}<i data-lucide="arrow-up-right" class="h-4 w-4"></i></span>
                </a>
            @endforeach
        </section>

        <div data-growth-alert>
        @if ($alert)
            <aside class="mb-6 flex flex-col gap-4 rounded-lg border border-amber-200 bg-amber-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between" data-analytics-priority-alert>
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                        <i data-lucide="bell-ring" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-amber-950">{{ __('admin.analytics.overview.alerts.'.$alert['type'].'.title', ['count' => $alert['count']]) }}</p>
                        <p class="mt-1 text-sm leading-6 text-amber-800">{{ __('admin.analytics.overview.alerts.'.$alert['type'].'.description') }}</p>
                    </div>
                </div>
                <a href="{{ $alert['href'] }}" class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-md bg-amber-600 px-4 text-sm font-semibold text-white transition duration-[120ms] motion-reduce:transition-none hover:bg-amber-700 active:scale-[.98] motion-reduce:active:scale-100">
                    {{ __('admin.analytics.overview.alerts.view') }}
                    <i data-lucide="arrow-right" class="ml-2 h-4 w-4"></i>
                </a>
            </aside>
        @endif
        </div>

        @include('admin.analytics._growth-probe')



    </div>
@endsection
