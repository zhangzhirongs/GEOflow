@php
    $contentFilterData = $filters->toArray();
    $logFilterData = $logFilters->toArray();
    $logPresetOptions = ['7d', '30d', '60d'];
    $trafficOptions = ['all', 'human', 'search_bot', 'ai_bot', 'other_bot', 'unknown'];
    $logSourceOptions = ['all', 'local', 'server', 'channel'];
    $sourceLabelKey = $logFilters->source === 'channel' ? 'channel_source' : $logFilters->source;
    $currentSourceLabel = $logFilters->source === 'all'
        ? __('admin.analytics.logs_all_supported_sources')
        : __('admin.analytics.filters.'.$sourceLabelKey);
    $trendRows = collect($logSummary['traffic_trend'] ?? [])->values();
    $defaultTrendRow = $trendRows->last() ?? ['date' => '', 'pv' => 0, 'unique_ip' => 0, 'ai_bot_pv' => 0, 'errors' => 0];
    $chartWidth = 720;
    $chartHeight = 226;
    $plotLeft = 48;
    $plotRight = 704;
    $plotTop = 18;
    $plotBottom = 194;
    $plotHeight = $plotBottom - $plotTop;
    $trendMax = max(1, (int) ($trendRows->max('pv') ?? 0));
    $trendCount = $trendRows->count();
    $trendPoints = $trendRows->map(function ($row, $index) use ($trendCount, $plotLeft, $plotRight, $plotBottom, $plotHeight, $trendMax) {
        $x = $trendCount > 1
            ? $plotLeft + (($plotRight - $plotLeft) * $index / ($trendCount - 1))
            : ($plotLeft + $plotRight) / 2;

        return [
            'x' => $x,
            'pv_y' => $plotBottom - (((int) ($row['pv'] ?? 0) / $trendMax) * $plotHeight),
            'ai_y' => $plotBottom - (((int) ($row['ai_bot_pv'] ?? 0) / $trendMax) * $plotHeight),
        ];
    });
    $pvPolyline = $trendPoints->map(fn ($point) => number_format($point['x'], 2, '.', '').','.number_format($point['pv_y'], 2, '.', ''))->implode(' ');
    $aiPolyline = $trendPoints->map(fn ($point) => number_format($point['x'], 2, '.', '').','.number_format($point['ai_y'], 2, '.', ''))->implode(' ');
    $defaultPoint = $trendPoints->last() ?? ['x' => ($plotLeft + $plotRight) / 2, 'pv_y' => $plotBottom, 'ai_y' => $plotBottom];
@endphp

<section id="log-attribution" class="mb-8 rounded-lg bg-white shadow-sm ring-1 ring-gray-200" data-analytics-log-section>
    <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
        <h2 class="text-base font-semibold text-gray-900">{{ __('admin.analytics.logs_title') }}</h2>
        <p class="mt-2 flex items-start gap-1.5 text-xs leading-5 text-amber-700">
            <i data-lucide="info" class="mt-0.5 h-3.5 w-3.5 shrink-0"></i>
            <span>{{ __('admin.analytics.logs_boundary_note') }}</span>
        </p>
    </div>

    <div class="border-b border-gray-100 bg-gray-50/70 px-5 py-4 sm:px-6">
        <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
            <h3 class="text-sm font-semibold text-gray-900">{{ __('admin.analytics.logs_filter_title') }}</h3>
            <div class="text-xs text-gray-500">
                {{ __('admin.analytics.logs_current_source') }}
                <span class="font-semibold text-gray-800">{{ $currentSourceLabel }}</span>
            </div>
        </div>

        <form id="analytics-log-filter-form" method="GET" action="{{ $analyticsLogRoute ?? route('admin.analytics.traffic') }}" class="space-y-4" data-analytics-auto-submit>
            @foreach (['preset', 'date_from', 'date_to', 'channel_id'] as $name)
                <input type="hidden" name="{{ $name }}" value="{{ $contentFilterData[$name] ?? '' }}">
            @endforeach

            <div class="flex flex-wrap items-end gap-2 {{ $logFilters->preset === 'custom' ? '' : 'hidden' }}" data-analytics-custom-date-fields>
                @foreach ($logPresetOptions as $preset)
                    @php
                        $presetClass = $logFilters->preset === $preset
                            ? 'border-blue-600 bg-blue-600 text-white'
                            : 'border-gray-300 bg-white text-gray-700 hover:border-blue-300 hover:text-blue-700';
                    @endphp
                    <button type="submit" name="log_preset" value="{{ $preset }}" class="inline-flex min-h-8 items-center rounded-md border px-2.5 py-1 text-xs font-medium transition duration-[120ms] motion-reduce:transition-none active:scale-[.98] motion-reduce:active:scale-100 {{ $presetClass }}" aria-pressed="{{ $logFilters->preset === $preset ? 'true' : 'false' }}">
                        {{ __('admin.analytics.filters.'.$preset) }}
                    </button>
                @endforeach
            </div>

            <div class="flex flex-wrap items-end gap-2">
                <div class="min-w-[8.5rem] flex-1 sm:flex-none">
                    <label class="mb-1 block text-xs font-medium text-gray-600" for="log-date-from">{{ __('admin.analytics.filters.date_from') }}</label>
                    <input id="log-date-from" type="date" name="log_date_from" value="{{ $logFilterData['log_date_from'] }}" max="{{ now()->toDateString() }}" class="block min-h-10 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="min-w-[8.5rem] flex-1 sm:flex-none">
                    <label class="mb-1 block text-xs font-medium text-gray-600" for="log-date-to">{{ __('admin.analytics.filters.date_to') }}</label>
                    <input id="log-date-to" type="date" name="log_date_to" value="{{ $logFilterData['log_date_to'] }}" max="{{ now()->toDateString() }}" class="block min-h-10 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="min-w-[10rem] flex-1 sm:flex-none">
                    <label class="mb-1 block text-xs font-medium text-gray-600" for="log-traffic-type">{{ __('admin.analytics.filters.traffic_type') }}</label>
                    <select id="log-traffic-type" name="log_traffic_type" class="block min-h-10 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach ($trafficOptions as $option)
                            <option value="{{ $option }}" @selected($logFilters->trafficType === $option)>{{ __('admin.analytics.filters.'.$option) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[10rem] flex-1 sm:flex-none">
                    <label class="mb-1 block text-xs font-medium text-gray-600" for="log-source">{{ __('admin.analytics.filters.log_source') }}</label>
                    <select id="log-source" name="log_source" class="block min-h-10 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach ($logSourceOptions as $option)
                            @php
                                $labelKey = $option === 'channel' ? 'channel_source' : $option;
                                $label = $option === 'all' ? __('admin.analytics.logs_all_supported_sources') : __('admin.analytics.filters.'.$labelKey);
                            @endphp
                            <option value="{{ $option }}" @selected($logFilters->source === $option)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="log-task-id">{{ __('admin.analytics.filters.task') }}</label>
                    <select id="log-task-id" name="task_id" class="block min-h-10 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">{{ __('admin.analytics.filters.all') }}</option>
                        @foreach ($filterOptions['tasks'] as $task)
                            <option value="{{ $task->id }}" @selected((int) ($contentFilterData['task_id'] ?? 0) === (int) $task->id)>{{ $task->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="log-category-id">{{ __('admin.analytics.filters.category') }}</label>
                    <select id="log-category-id" name="category_id" class="block min-h-10 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">{{ __('admin.analytics.filters.all') }}</option>
                        @foreach ($filterOptions['categories'] as $category)
                            <option value="{{ $category->id }}" @selected((int) ($contentFilterData['category_id'] ?? 0) === (int) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="log-article-id">{{ __('admin.analytics.filters.article') }}</label>
                    <select id="log-article-id" name="article_id" class="block min-h-10 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">{{ __('admin.analytics.filters.all') }}</option>
                        @foreach ($filterOptions['articles'] as $article)
                            <option value="{{ $article->id }}" @selected((int) ($contentFilterData['article_id'] ?? 0) === (int) $article->id)>{{ $article->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

        </form>

        @if ((int) ($logSummary['excluded_source_rows'] ?? 0) > 0)
            <div class="mt-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-800">
                <i data-lucide="shield-check" class="mt-0.5 h-4 w-4 shrink-0"></i>
                <p>{{ __('admin.analytics.logs_excluded_sources', ['count' => number_format((int) $logSummary['excluded_source_rows'])]) }}</p>
            </div>
        @endif
    </div>

    @if (empty($logSummary['has_data']))
        <div class="px-6 py-12 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                <i data-lucide="file-search" class="h-7 w-7 text-gray-400"></i>
            </div>
            <p class="mt-4 text-sm font-medium text-gray-500">{{ __('admin.analytics.logs_title') }}</p>
            <h3 class="mt-4 text-lg font-semibold text-gray-900">{{ __('admin.analytics.logs_empty_title') }}</h3>
            <p class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-gray-500">{{ __('admin.analytics.logs_empty_desc') }}</p>
        </div>
    @else
        <div class="space-y-6 p-5 sm:p-6">
            <div>
                <h4 class="mb-4 text-sm font-semibold text-gray-900">{{ __('admin.analytics.logs_overview') }}</h4>
                <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                    @foreach ([
                        ['key' => 'pv', 'icon' => 'mouse-pointer-click', 'tone' => 'text-blue-600'],
                        ['key' => 'unique_ip', 'icon' => 'network', 'tone' => 'text-emerald-600'],
                        ['key' => 'ai_bot_pv', 'icon' => 'bot', 'tone' => 'text-purple-600'],
                        ['key' => 'errors', 'icon' => 'triangle-alert', 'tone' => 'text-red-600'],
                    ] as $card)
                        <div class="rounded-lg bg-gray-50 p-4">
                            <div class="flex items-center gap-3">
                                <i data-lucide="{{ $card['icon'] }}" class="h-5 w-5 shrink-0 {{ $card['tone'] }}"></i>
                                <div class="min-w-0 flex-1">
                                    <div class="whitespace-nowrap text-xs font-medium text-gray-500">{{ __('admin.analytics.logs_kpi.'.$card['key']) }}</div>
                                    <div class="mt-1 text-xl font-bold tabular-nums text-gray-900">{{ number_format((int) ($logSummary['kpis'][$card['key']] ?? 0)) }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-xs leading-5 text-gray-400">{{ __('admin.analytics.logs_recorded_error_note') }}</p>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div class="rounded-lg border border-gray-100">
                    <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">{{ __('admin.analytics.logs_trend') }}</h3>
                            <p class="mt-1 text-xs leading-5 text-gray-500">{{ __('admin.analytics.logs_trend_help') }}</p>
                        </div>
                        <div class="flex items-center gap-4 text-xs font-medium text-gray-600">
                            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span>{{ __('admin.analytics.logs_series.pv') }}</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-purple-600"></span>{{ __('admin.analytics.logs_series.ai') }}</span>
                        </div>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div
                            class="rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                            tabindex="0"
                            role="group"
                            aria-label="{{ __('admin.analytics.logs_chart_aria') }}"
                            aria-describedby="analytics-log-chart-help analytics-log-chart-detail"
                            aria-keyshortcuts="ArrowLeft ArrowRight Enter Escape"
                            data-analytics-log-chart
                            data-default-index="{{ max(0, $trendCount - 1) }}"
                            data-preview-label="{{ __('admin.analytics.logs_detail.preview') }}"
                            data-pinned-label="{{ __('admin.analytics.logs_detail.pinned') }}"
                            data-aria-template="{{ __('admin.analytics.logs_chart_point_aria') }}"
                        >
                            <span id="analytics-log-chart-help" class="sr-only">{{ __('admin.analytics.logs_trend_help') }}</span>
                            <script type="application/json" data-log-chart-data>@json($trendRows->all())</script>
                            <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="block h-auto w-full touch-manipulation" aria-hidden="true" data-log-chart-surface>
                                @foreach ([0, 0.5, 1] as $ratio)
                                    @php $gridY = $plotBottom - ($plotHeight * $ratio); @endphp
                                    <line x1="{{ $plotLeft }}" y1="{{ $gridY }}" x2="{{ $plotRight }}" y2="{{ $gridY }}" stroke="#e5e7eb" stroke-width="1" />
                                    <text x="{{ $plotLeft - 8 }}" y="{{ $gridY + 4 }}" text-anchor="end" fill="#6b7280" font-size="11" class="tabular-nums">{{ (int) round($trendMax * $ratio) }}</text>
                                @endforeach

                                <polyline points="{{ $pvPolyline }}" fill="none" stroke="#2563eb" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                <polyline points="{{ $aiPolyline }}" fill="none" stroke="#9333ea" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />

                                @foreach ($trendPoints as $index => $point)
                                    <circle cx="{{ $point['x'] }}" cy="{{ $point['pv_y'] }}" r="2.5" fill="#2563eb" data-log-chart-point data-index="{{ $index }}" data-x="{{ $point['x'] }}" data-pv-y="{{ $point['pv_y'] }}" data-ai-y="{{ $point['ai_y'] }}" />
                                @endforeach

                                <line x1="{{ $defaultPoint['x'] }}" y1="{{ $plotTop }}" x2="{{ $defaultPoint['x'] }}" y2="{{ $plotBottom }}" stroke="#94a3b8" stroke-width="1" stroke-dasharray="4 4" data-log-chart-guide />
                                <circle cx="{{ $defaultPoint['x'] }}" cy="{{ $defaultPoint['pv_y'] }}" r="5" fill="#ffffff" stroke="#2563eb" stroke-width="3" data-log-chart-pv-marker />
                                <circle cx="{{ $defaultPoint['x'] }}" cy="{{ $defaultPoint['ai_y'] }}" r="4" fill="#ffffff" stroke="#9333ea" stroke-width="2.5" data-log-chart-ai-marker />

                                @if ($trendCount > 0)
                                    @php
                                        $middleIndex = (int) floor(($trendCount - 1) / 2);
                                        $axisRows = [
                                            ['index' => 0, 'anchor' => 'start'],
                                            ['index' => $middleIndex, 'anchor' => 'middle'],
                                            ['index' => $trendCount - 1, 'anchor' => 'end'],
                                        ];
                                    @endphp
                                    @foreach ($axisRows as $axis)
                                        <text x="{{ $trendPoints[$axis['index']]['x'] }}" y="218" text-anchor="{{ $axis['anchor'] }}" fill="#6b7280" font-size="11">{{ \Illuminate\Support\Carbon::parse($trendRows[$axis['index']]['date'])->format('m-d') }}</text>
                                    @endforeach
                                @endif
                            </svg>

                            <div id="analytics-log-chart-detail" class="mt-3 rounded-lg bg-slate-50 p-4" aria-live="polite">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <span class="text-sm font-semibold text-gray-900" data-log-detail-date>{{ $defaultTrendRow['date'] }}</span>
                                    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-gray-500 ring-1 ring-gray-200" data-log-detail-state>{{ __('admin.analytics.logs_detail.preview') }}</span>
                                </div>
                                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 sm:grid-cols-4">
                                    @foreach ([
                                        ['key' => 'pv', 'attribute' => 'data-log-detail-pv'],
                                        ['key' => 'unique_ip', 'attribute' => 'data-log-detail-unique-ip'],
                                        ['key' => 'ai_bot_pv', 'attribute' => 'data-log-detail-ai'],
                                        ['key' => 'errors', 'attribute' => 'data-log-detail-errors'],
                                    ] as $detail)
                                        <div>
                                            <dt class="text-xs text-gray-500">{{ __('admin.analytics.logs_detail.'.$detail['key']) }}</dt>
                                            <dd class="mt-1 text-right text-base font-semibold tabular-nums text-gray-900" {{ $detail['attribute'] }}>{{ number_format((int) $defaultTrendRow[$detail['key']]) }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-100">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h3 class="text-base font-semibold text-gray-900">{{ __('admin.analytics.logs_bot_breakdown') }}</h3>
                    </div>
                    <div class="space-y-4 p-5">
                        @php
                            $botMax = max(1, ...array_map(fn ($row) => (int) ($row['count'] ?? 0), $logSummary['bot_breakdown'] ?? []));
                        @endphp
                        @foreach (($logSummary['bot_breakdown'] ?? []) as $row)
                            @php $percent = min(100, round(((int) $row['count'] / $botMax) * 100)); @endphp
                            <div>
                                <div class="mb-1 flex items-center justify-between gap-4 text-sm">
                                    <span class="font-medium text-gray-700">{{ $row['label'] }}</span>
                                    <span class="whitespace-nowrap text-right font-semibold tabular-nums text-gray-900">{{ number_format((int) $row['count']) }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                                    <div class="h-full rounded-full bg-slate-700" style="width: {{ $percent }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                @foreach ([
                    ['key' => 'top_articles', 'title' => 'logs_top_articles', 'first' => 'article'],
                    ['key' => 'top_paths', 'title' => 'logs_top_paths', 'first' => 'path'],
                ] as $table)
                    <div class="rounded-lg border border-gray-100">
                        <div class="border-b border-gray-100 px-5 py-4">
                            <h3 class="text-base font-semibold text-gray-900">{{ __('admin.analytics.'.$table['title']) }}</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500">{{ __('admin.analytics.logs_table.'.$table['first']) }}</th>
                                        <th class="whitespace-nowrap px-5 py-3 text-right text-xs font-semibold text-gray-500">{{ __('admin.analytics.logs_table.views') }}</th>
                                        <th class="whitespace-nowrap px-5 py-3 text-right text-xs font-semibold text-gray-500">{{ __('admin.analytics.logs_table.unique_ip') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @forelse (($logSummary[$table['key']] ?? []) as $row)
                                        <tr>
                                            <td class="min-w-[18rem] px-5 py-4 text-sm font-medium text-gray-900">
                                                @if ($table['first'] === 'article')
                                                    <a href="{{ route('admin.articles.edit', ['articleId' => $row['article_id']]) }}" class="hover:text-blue-600">{{ $row['title'] }}</a>
                                                @else
                                                    <span class="font-mono">{{ $row['path'] }}</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-5 py-4 text-right text-sm tabular-nums text-gray-700">{{ number_format((int) $row['views']) }}</td>
                                            <td class="whitespace-nowrap px-5 py-4 text-right text-sm tabular-nums text-gray-500">{{ number_format((int) $row['unique_ip']) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-5 py-8 text-center text-sm text-gray-500">{{ __('admin.analytics.no_data') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
