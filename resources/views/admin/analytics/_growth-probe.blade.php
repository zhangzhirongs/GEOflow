<section id="growth-ai-probe" class="mb-6 rounded-lg border border-violet-200 bg-white p-5 shadow-sm sm:p-6" data-growth-probe>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-violet-600 text-white">
                    <i data-lucide="message-circle-question" class="h-5 w-5"></i>
                </span>
                <div>
                    <h2 class="text-lg font-semibold text-gray-950">{{ __('admin.analytics.overview.probe.title') }}</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">{{ __('admin.analytics.overview.probe.subtitle') }}</p>
                </div>
            </div>
        </div>
        @if (! $growthProbeConfigured)
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.ai-models.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-violet-300 bg-violet-50 px-4 text-sm font-semibold text-violet-800 hover:bg-violet-100">
                    {{ __('admin.analytics.overview.probe.setup') }}
                </a>
                <a href="{{ route('admin.analytics.ai-visibility') }}" class="inline-flex min-h-10 items-center justify-center rounded-md border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    {{ __('admin.analytics.overview.probe.setup_visibility') }}
                </a>
            </div>
        @endif
    </div>

    @if (! $growthProbeConfigured)
        <p class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">{{ __('admin.analytics.overview.probe.unconfigured') }}</p>
    @endif

    <form method="POST" action="{{ route('admin.analytics.ai-probes.store') }}" class="mt-5 space-y-3">
        @csrf
        <label for="growth-probe-question" class="block text-sm font-medium text-gray-700">{{ __('admin.analytics.overview.probe.question_label') }}</label>
        <textarea id="growth-probe-question" name="question" rows="3" maxlength="2000" @disabled(! $growthProbeConfigured) class="block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500" placeholder="{{ __('admin.analytics.overview.probe.placeholder') }}">{{ old('question') }}</textarea>
        @error('question')
            <p class="text-sm text-rose-600">{{ $message }}</p>
        @enderror
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="sm:max-w-xs sm:flex-1">
                <label for="growth-probe-provider" class="mb-1 block text-sm font-medium text-gray-700">{{ __('admin.analytics.overview.probe.model_label') }}</label>
                <select id="growth-probe-provider" name="provider_type" @disabled(! $growthProbeConfigured) class="block min-h-10 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
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
            <button type="submit" @disabled(! $growthProbeConfigured) class="inline-flex min-h-10 items-center justify-center rounded-md bg-violet-600 px-4 text-sm font-semibold text-white transition duration-[120ms] hover:bg-violet-700 disabled:cursor-not-allowed disabled:bg-gray-300">
                {{ __('admin.analytics.overview.probe.submit') }}
            </button>
        </div>
        <p class="text-xs leading-5 text-gray-500">{{ __('admin.analytics.overview.probe.hint') }} {{ __('admin.analytics.overview.probe.auto_refresh_hint') }}</p>
    </form>

    <form method="POST" action="{{ route('admin.analytics.ai-probes.schedule.update') }}" class="mt-5 rounded-md border border-violet-100 bg-violet-50/40 p-4 space-y-3">
        @csrf
        <label class="flex items-center gap-2 text-sm font-medium text-gray-800"><input type="checkbox" name="enabled" value="1" @checked(($growthAiSchedule['enabled'] ?? false))> 启用自动定时提问</label>
        <input name="question" value="{{ $growthAiSchedule['question'] ?? '' }}" maxlength="2000" required class="block w-full rounded-md border-gray-300 text-sm" placeholder="自动提问内容">
        <div class="grid gap-3 sm:grid-cols-3"><select name="provider_type" class="rounded-md border-gray-300 text-sm">@foreach (($growthProbeChannels ?? []) as $channel)<option value="{{ $channel['value'] }}" @selected(($growthAiSchedule['provider_type'] ?? 'auto') === $channel['value'])>{{ $channel['label'] }}</option>@endforeach</select><input type="number" name="interval_minutes" min="1" max="10080" value="{{ $growthAiSchedule['interval_minutes'] ?? 60 }}" class="rounded-md border-gray-300 text-sm"><button class="rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white">保存自动分析设置</button></div>
        <p class="text-xs text-gray-500">按分钟设置周期，后台调度器会自动入队并在本页保留分析结果。</p>
    </form>

    <div class="mt-6 space-y-4" data-growth-probe-list>
        @forelse ($growthProbes as $probe)
            @php
                $analysis = is_array($probe->analysis_json) ? $probe->analysis_json : [];
                $ownedArticles = $analysis['owned_articles'] ?? [];
                $ownedPosts = $analysis['owned_posts'] ?? [];
                $ownedKeywords = $analysis['owned_keywords'] ?? [];
                $competitors = $analysis['competitors'] ?? [];
            @endphp
            <article class="rounded-lg border border-gray-200 bg-gray-50 p-4" data-growth-probe-item data-probe-uuid="{{ $probe->uuid }}" data-probe-status="{{ $probe->status }}">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-950">{{ $probe->question }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $probe->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }} · {{ $probe->providerLabel() }}</p>
                    </div>
                    <span @class([
                            'inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-semibold',
                            'bg-amber-100 text-amber-800' => $probe->isPending(),
                            'bg-emerald-100 text-emerald-800' => $probe->status === 'completed' && $probe->verdict === 'owned_content',
                            'bg-blue-100 text-blue-800' => $probe->status === 'completed' && $probe->verdict === 'owned_brand',
                            'bg-orange-100 text-orange-800' => $probe->status === 'completed' && $probe->verdict === 'competitor',
                            'bg-gray-200 text-gray-700' => $probe->status === 'completed' && $probe->verdict === 'none',
                            'bg-rose-100 text-rose-800' => $probe->status === 'failed',
                        ])>{{ __('admin.analytics.overview.probe.status.'.$probe->status) }}@if($probe->status === 'completed' && $probe->verdict) · {{ __('admin.analytics.overview.probe.verdict.'.$probe->verdict) }}@endif</span>
                </div>

                @if ($probe->status === 'failed')
                    <div class="mt-3 rounded-md border border-rose-200 bg-rose-50 px-3 py-2">
                        <p class="text-sm text-rose-800">{{ $probe->error_message }}</p>
                        <p class="mt-1 text-xs leading-5 text-rose-700">{{ __('admin.analytics.overview.probe.failed_help') }}</p>
                        <div class="mt-2 flex flex-wrap gap-3">
                            <a href="{{ route('admin.ai-models.index') }}" class="inline-flex text-sm font-semibold text-rose-800 hover:underline">{{ __('admin.analytics.overview.probe.setup') }}</a>
                            <a href="{{ route('admin.analytics.ai-visibility') }}" class="inline-flex text-sm font-semibold text-rose-800 hover:underline">{{ __('admin.analytics.overview.probe.setup_visibility') }}</a>
                        </div>
                    </div>
                @elseif ($probe->isPending())
                    <p class="mt-3 text-sm text-amber-800">{{ __('admin.analytics.overview.probe.pending') }}</p>
                @else
                    <p class="mt-3 text-sm leading-6 text-gray-700">{{ $analysis['summary'] ?? $probe->answer_excerpt }}</p>
                    @if ($probe->answer_excerpt)
                        <details class="mt-3 rounded-md bg-white p-3">
                            <summary class="cursor-pointer text-sm font-medium text-gray-800">{{ __('admin.analytics.overview.probe.answer') }}</summary>
                            <p class="mt-2 whitespace-pre-wrap text-sm leading-6 text-gray-600">{{ $probe->answer_excerpt }}</p>
                        </details>
                    @endif

                    @if ($ownedArticles !== [] || $ownedPosts !== [])
                        <div class="mt-4 grid gap-3 lg:grid-cols-2">
                            <div class="rounded-md bg-white p-3">
                                <h3 class="text-sm font-semibold text-gray-900">{{ __('admin.analytics.overview.probe.owned_content') }}</h3>
                                <ul class="mt-2 space-y-2 text-sm text-gray-700">
                                    @foreach ($ownedArticles as $article)
                                        <li>
                                            <a href="{{ $article['url'] }}" class="font-medium text-violet-700 hover:underline">{{ $article['title'] }}</a>
                                            <span class="ml-2 text-xs text-gray-400">{{ __('admin.analytics.overview.probe.matched_on.'.$article['matched_on']) }}</span>
                                            @if (! empty($article['mentioned_keywords']))
                                                <div class="mt-1 flex flex-wrap gap-1">
                                                    @foreach ($article['mentioned_keywords'] as $keyword)
                                                        <span class="rounded-full bg-violet-50 px-2 py-0.5 text-xs text-violet-800">{{ $keyword }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </li>
                                    @endforeach
                                    @foreach ($ownedPosts as $post)
                                        <li>
                                            {{ $post['title'] ?: $post['platform'] }}
                                            @if (! empty($post['remote_url']))
                                                <a href="{{ $post['remote_url'] }}" target="_blank" rel="noopener noreferrer" class="ml-2 text-xs text-violet-700 hover:underline">{{ $post['platform'] }}</a>
                                            @endif
                                            @if (! empty($post['mentioned_keywords']))
                                                <div class="mt-1 flex flex-wrap gap-1">
                                                    @foreach ($post['mentioned_keywords'] as $keyword)
                                                        <span class="rounded-full bg-violet-50 px-2 py-0.5 text-xs text-violet-800">{{ $keyword }}</span>
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
                                            'bg-violet-600 text-white' => $inAnswer,
                                            'bg-violet-50 text-violet-800' => ! $inAnswer,
                                        ])>{{ $term }}</span>
                                    @empty
                                        <p class="text-sm text-gray-500">{{ __('admin.analytics.overview.probe.no_keywords') }}</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @elseif ($competitors !== [])
                        <div class="mt-4 rounded-md bg-white p-3">
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
            <p class="rounded-lg bg-gray-50 p-4 text-sm text-gray-500">{{ __('admin.analytics.overview.probe.empty') }}</p>
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

            return;
            const root = document.querySelector('[data-overview-refresh]');
            if (!root) return;
            const interval = Math.max(5000, Number(root.getAttribute('data-overview-refresh') || 30000));
            const question = document.getElementById('growth-probe-question');
            const shouldSkip = () => {
                if (document.hidden) return true;
                if (question && (document.activeElement === question || question.value !== question.defaultValue)) return true;
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
