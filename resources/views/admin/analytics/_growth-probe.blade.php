<section id="growth-ai-probe" class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm" data-growth-probe>
    <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-violet-50 text-violet-600">
                <i data-lucide="message-circle-question" class="h-4.5 w-4.5"></i>
            </span>
            <div>
                <h2 class="text-base font-semibold text-gray-950">{{ __('admin.analytics.overview.probe.title') }}</h2>
            </div>
        </div>
        @if (! $growthProbeConfigured)
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.ai-models.index') }}" class="inline-flex min-h-9 items-center rounded-md bg-blue-600 px-3 text-sm font-semibold text-white hover:bg-blue-700">
                    {{ __('admin.analytics.overview.probe.setup') }}
                </a>
                <a href="{{ route('admin.analytics.ai-visibility') }}" class="inline-flex min-h-9 items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    {{ __('admin.analytics.overview.probe.setup_visibility') }}
                </a>
            </div>
        @endif
    </div>

    <div class="px-5 py-4 sm:px-6">
        @if (! $growthProbeConfigured)
            <p class="rounded-md border border-amber-100 bg-amber-50 px-3 py-2 text-sm text-amber-800">{{ __('admin.analytics.overview.probe.unconfigured') }}</p>
        @endif

        <form method="POST" action="{{ route('admin.analytics.ai-probes.store') }}" class="space-y-3">
            @csrf
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start">
                <div class="flex-1">
                    <label for="growth-probe-question" class="mb-1 block text-xs font-medium text-gray-500">{{ __('admin.analytics.overview.probe.question_label') }}</label>
                    <textarea id="growth-probe-question" name="question" rows="2" maxlength="2000" @disabled(! $growthProbeConfigured) class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="{{ __('admin.analytics.overview.probe.placeholder') }}">{{ old('question') }}</textarea>
                    @error('question')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex shrink-0 items-end gap-2 lg:w-80">
                    <div class="flex-1">
                        <label for="growth-probe-provider" class="mb-1 block text-xs font-medium text-gray-500">{{ __('admin.analytics.overview.probe.model_label') }}</label>
                        <select id="growth-probe-provider" name="provider_type" @disabled(! $growthProbeConfigured) class="block min-h-10 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @forelse (($growthProbeChannels ?? []) as $channel)
                                <option value="{{ $channel['value'] }}" @selected(old('provider_type', 'auto') === $channel['value'])>{{ $channel['label'] }}</option>
                            @empty
                                <option value="auto">{{ __('admin.analytics.overview.probe.channels.auto') }}</option>
                            @endforelse
                        </select>
                        @error('provider_type')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" @disabled(! $growthProbeConfigured) class="inline-flex min-h-10 items-center rounded-md bg-blue-600 px-4 text-sm font-semibold text-white transition duration-[120ms] hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-300">
                        {{ __('admin.analytics.overview.probe.submit') }}
                    </button>
                </div>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.analytics.ai-probes.schedule.update') }}" class="mt-4 rounded-md border border-gray-100 bg-gray-50 p-3" data-growth-probe-schedule>
            @csrf
            <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
                <label class="flex shrink-0 items-center gap-2 text-sm font-medium text-gray-700"><input type="checkbox" name="enabled" value="1" @checked(($growthAiSchedule['enabled'] ?? false)) class="h-4 w-4 rounded border-gray-300 text-blue-600"> 自动定时提问</label>
                <input name="question" value="{{ $growthAiSchedule['question'] ?? '' }}" maxlength="2000" required placeholder="自动提问内容" class="block min-h-9 flex-1 rounded-md border-gray-300 text-sm">
                <select name="provider_type" class="block min-h-9 rounded-md border-gray-300 text-sm lg:w-44">@foreach (($growthProbeChannels ?? []) as $channel)<option value="{{ $channel['value'] }}" @selected(($growthAiSchedule['provider_type'] ?? 'auto') === $channel['value'])>{{ $channel['label'] }}</option>@endforeach</select>
                <input type="number" name="interval_minutes" min="1" max="10080" value="{{ $growthAiSchedule['interval_minutes'] ?? 60 }}" title="间隔（分钟）" class="block min-h-9 w-24 rounded-md border-gray-300 text-sm">
                <button class="inline-flex min-h-9 shrink-0 items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 hover:bg-gray-100">保存</button>
            </div>
            <p class="mt-2 text-xs text-gray-400">按分钟为周期自动提问，结果保留在本页。</p>
        </form>
    </div>

    <div class="space-y-4 px-5 pb-5 sm:px-6" data-growth-probe-list>
        @forelse ($growthProbes as $probe)
            @php
                $probeView = $growthProbeData[$probe->uuid] ?? [];
                $analysis = is_array($probeView['analysis'] ?? null) ? $probeView['analysis'] : (is_array($probe->analysis_json) ? $probe->analysis_json : []);
                $results = is_array($probeView['results'] ?? null) ? $probeView['results'] : [];
                $answerText = (string) ($probeView['answer_text'] ?? $probe->answer_excerpt ?? '');
                $sources = is_array($probeView['sources'] ?? null) ? $probeView['sources'] : [];
                $ownedArticles = $analysis['owned_articles'] ?? [];
                $ownedPosts = $analysis['owned_posts'] ?? [];
                $ownedKeywords = $analysis['owned_keywords'] ?? [];
                $competitors = $analysis['competitors'] ?? [];
            @endphp
            <article class="rounded-md border border-gray-200 bg-gray-50 p-4" data-growth-probe-item data-probe-uuid="{{ $probe->uuid }}" data-probe-status="{{ $probe->status }}">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-gray-950">{{ $probe->question }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $probe->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }} · {{ $probe->providerLabel() }}</p>
                    </div>
                    <span @class([
                            'inline-flex w-fit shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold',
                            'bg-amber-100 text-amber-800' => $probe->isPending(),
                            'bg-emerald-100 text-emerald-800' => $probe->status === 'completed' && $probe->verdict === 'owned_content',
                            'bg-blue-100 text-blue-800' => $probe->status === 'completed' && $probe->verdict === 'owned_brand',
                            'bg-orange-100 text-orange-800' => $probe->status === 'completed' && $probe->verdict === 'competitor',
                            'bg-gray-200 text-gray-700' => $probe->status === 'completed' && $probe->verdict === 'none',
                            'bg-rose-100 text-rose-800' => $probe->status === 'failed',
                        ])>{{ __('admin.analytics.overview.probe.status.'.$probe->status) }}@if($probe->status === 'completed' && $probe->verdict) · {{ __('admin.analytics.overview.probe.verdict.'.$probe->verdict) }}@endif</span>
                </div>

                @if ($probe->status === 'failed')
                    <div class="mt-3 rounded-md border border-rose-100 bg-rose-50 px-3 py-2">
                        <p class="text-sm text-rose-800">{{ $probe->error_message }}</p>
                        <p class="mt-1 text-xs leading-5 text-rose-700">{{ __('admin.analytics.overview.probe.failed_help') }}</p>
                        <div class="mt-2 flex flex-wrap gap-3">
                            <a href="{{ route('admin.ai-models.index') }}" class="text-sm font-semibold text-rose-800 hover:underline">{{ __('admin.analytics.overview.probe.setup') }}</a>
                            <a href="{{ route('admin.analytics.ai-visibility') }}" class="text-sm font-semibold text-rose-800 hover:underline">{{ __('admin.analytics.overview.probe.setup_visibility') }}</a>
                        </div>
                    </div>
                @elseif ($probe->isPending())
                    <p class="mt-3 text-sm text-amber-800">{{ __('admin.analytics.overview.probe.pending') }}</p>
                @else
                    <p class="mt-3 line-clamp-3 text-sm leading-6 text-gray-700">{{ $analysis['summary'] ?? $answerText }}</p>
                    @if ($answerText)
                        <details class="mt-2">
                            <summary class="cursor-pointer text-xs font-semibold text-gray-500 hover:text-gray-700">{{ __('admin.analytics.overview.probe.answer') }}</summary>
                            <p class="mt-2 max-h-72 overflow-y-auto whitespace-pre-wrap rounded-md bg-white p-3 text-sm leading-6 text-gray-600">{{ $answerText }}</p>
                        </details>
                    @endif

                    @if ($results !== [])
                        <div class="mt-3">
                            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('admin.analytics.overview.probe.platform_results') }}</h3>
                            <div class="space-y-2">
                            @foreach ($results as $result)
                                @php $resultSources = is_array($result['sources'] ?? null) ? $result['sources'] : []; @endphp
                                <details class="rounded-md border border-gray-200 bg-white">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-3 py-2 text-sm font-semibold text-gray-900"><span>{{ $result['provider_label'] ?? $result['provider_type'] ?? __('admin.analytics.overview.probe.unknown_platform') }}</span><span class="text-xs font-medium text-gray-500">{{ __('admin.analytics.overview.probe.status.'.($result['status'] ?? 'failed')) }}</span></summary>
                                    <div class="border-t border-gray-100 px-3 py-2">
                                    @if (! empty($result['error_message']))<p class="rounded bg-rose-50 px-2 py-1.5 text-sm text-rose-700">{{ $result['error_message'] }}</p>@endif
                                    @if (! empty($result['answer_text']))<p class="mt-2 max-h-72 overflow-y-auto whitespace-pre-wrap text-sm leading-6 text-gray-700">{{ $result['answer_text'] }}</p>@endif
                                    @if ($resultSources !== [])<div class="mt-2 border-t border-gray-100 pt-2"><h4 class="text-xs font-semibold text-gray-500">{{ __('admin.analytics.overview.probe.sources') }}</h4><ul class="mt-1.5 space-y-1.5">@foreach ($resultSources as $source)<li class="text-sm">@if (! empty($source['url']))<a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer" class="font-medium text-blue-600 hover:underline">{{ $source['title'] ?? $source['url'] }}</a>@else<span class="font-medium text-gray-800">{{ $source['title'] ?? __('admin.analytics.overview.probe.untitled_source') }}</span>@endif <span class="ml-1 text-xs text-gray-500">{{ $source['site_name'] ?? $source['domain'] ?? '' }}{{ ! empty($source['citation_key']) ? ' · '.$source['citation_key'] : '' }}</span>@if (! empty($source['snippet']))<p class="mt-0.5 text-xs leading-5 text-gray-500">{{ $source['snippet'] }}</p>@endif</li>@endforeach</ul></div>@endif
                                    </div>
                                </details>
                            @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($ownedArticles !== [] || $ownedPosts !== [])
                        <div class="mt-3 grid gap-3 lg:grid-cols-2">
                            <div class="rounded-md bg-white p-3">
                                <h3 class="text-sm font-semibold text-gray-900">{{ __('admin.analytics.overview.probe.owned_content') }}</h3>
                                <ul class="mt-2 space-y-2 text-sm text-gray-700">
                                    @foreach ($ownedArticles as $article)
                                        <li>
                                            <a href="{{ $article['url'] }}" class="font-medium text-blue-600 hover:underline">{{ $article['title'] }}</a>
                                            <span class="ml-2 text-xs text-gray-400">{{ __('admin.analytics.overview.probe.matched_on.'.$article['matched_on']) }}</span>
                                            @if (! empty($article['mentioned_keywords']))
                                                <div class="mt-1 flex flex-wrap gap-1">
                                                    @foreach ($article['mentioned_keywords'] as $keyword)
                                                        <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-700">{{ $keyword }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </li>
                                    @endforeach
                                    @foreach ($ownedPosts as $post)
                                        <li>
                                            {{ $post['title'] ?: $post['platform'] }}
                                            @if (! empty($post['remote_url']))
                                                <a href="{{ $post['remote_url'] }}" target="_blank" rel="noopener noreferrer" class="ml-2 text-xs text-blue-600 hover:underline">{{ $post['platform'] }}</a>
                                            @endif
                                            @if (! empty($post['mentioned_keywords']))
                                                <div class="mt-1 flex flex-wrap gap-1">
                                                    @foreach ($post['mentioned_keywords'] as $keyword)
                                                        <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-700">{{ $keyword }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="rounded-md bg-white p-3">
                                <h3 class="text-sm font-semibold text-gray-900">{{ __('admin.analytics.overview.probe.keywords') }}</h3>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @forelse ($ownedKeywords as $keyword)
                                        @php
                                            $term = is_array($keyword) ? ($keyword['term'] ?? '') : $keyword;
                                            $inAnswer = is_array($keyword) && ($keyword['in_answer'] ?? false);
                                        @endphp
                                        <span @class([
                                            'rounded-full px-2.5 py-1 text-xs font-medium',
                                            'bg-blue-600 text-white' => $inAnswer,
                                            'bg-blue-50 text-blue-700' => ! $inAnswer,
                                        ])>{{ $term }}</span>
                                    @empty
                                        <p class="text-sm text-gray-500">{{ __('admin.analytics.overview.probe.no_keywords') }}</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @elseif ($competitors !== [])
                        <div class="mt-3 rounded-md bg-white p-3">
                            <h3 class="text-sm font-semibold text-gray-900">{{ __('admin.analytics.overview.probe.competitors') }}</h3>
                            <div class="mt-3 grid gap-3 lg:grid-cols-2">
                                @foreach ($competitors as $competitor)
                                    <article class="rounded-md border border-gray-100 p-3">
                                        <p class="text-sm font-semibold text-gray-900">{{ $competitor['name'] }}</p>
                                        @if (! empty($competitor['domain']))
                                            <p class="mt-0.5 text-xs text-gray-500">{{ $competitor['domain'] }}</p>
                                        @endif
                                        <ul class="mt-2 space-y-1 text-sm text-gray-700">
                                            @foreach (($competitor['posts'] ?? []) as $post)
                                                <li>
                                                    @if (! empty($post['url']))
                                                        <a href="{{ $post['url'] }}" target="_blank" rel="noopener noreferrer" class="hover:underline">{{ $post['title'] }}</a>
                                                    @else
                                                        {{ $post['title'] }}
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach (($competitor['keywords'] ?? []) as $keyword)
                                                <span class="rounded-full bg-orange-50 px-2 py-0.5 text-xs text-orange-800">{{ $keyword }}</span>
                                            @endforeach
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            </article>
        @empty
            <p class="rounded-md bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">{{ __('admin.analytics.overview.probe.empty') }}</p>
        @endforelse
    </div>
</section>

@if ($growthProbes->contains(fn ($probe) => $probe->isPending()))
    @push('scripts')
        <script>
            (() => {
                const endpoints = {
                    @foreach ($growthProbes->filter(fn ($probe) => $probe->isPending()) as $pendingProbe)
                        @json($pendingProbe->uuid): @json(route('admin.analytics.ai-probes.show', $pendingProbe)),
                    @endforeach
                };
                const poll = async () => {
                    for (const url of Object.values(endpoints)) {
                        try {
                            const response = await fetch(url, {
                                headers: { 'Accept': 'application/json' },
                                credentials: 'same-origin',
                            });
                            if (!response.ok) continue;
                            const payload = await response.json();
                            if (payload.status !== 'queued' && payload.status !== 'running') {
                                window.location.reload();
                                return;
                            }
                        } catch (error) {}
                    }
                    window.setTimeout(poll, 4000);
                };
                window.setTimeout(poll, 4000);
            })();
        </script>
    @endpush
@endif

@push('scripts')
    <script>
        (() => {
            const params = new URLSearchParams(window.location.search);
            if (params.get('probe')) {
                document.getElementById('growth-ai-probe')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            const root = document.querySelector('[data-overview-refresh]');
            if (!root) return;
            const interval = Math.max(5000, Number(root.getAttribute('data-overview-refresh') || 30000));
            const question = document.getElementById('growth-probe-question');
            const schedule = document.querySelector('[data-growth-probe-schedule]');
            const shouldSkip = () => {
                if (document.hidden) return true;
                if (question && (document.activeElement === question || question.value !== question.defaultValue)) return true;
                if (schedule && [...schedule.elements].some((field) => field.name && field.value !== field.defaultValue)) return true;
                return Boolean(document.querySelector('[data-probe-status="queued"], [data-probe-status="running"]'));
            };
            const tick = () => {
                if (!shouldSkip()) {
                    window.location.reload();
                    return;
                }
                window.setTimeout(tick, interval);
            };
            window.setTimeout(tick, interval);
        })();
    </script>
@endpush
