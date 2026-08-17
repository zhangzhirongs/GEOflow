@extends('admin.layouts.app')

@php
    $workbench = $workbench ?? [];
    $skills = $workbench['skills'] ?? [];
    $quickStats = $workbench['stats'] ?? [];
    $shortcuts = $workbench['shortcuts'] ?? [];
    $taskSeed = $workbench['task']['seed'] ?? [];
    $articleSeed = $workbench['article']['seed'] ?? [];
    $taskSeedUrl = route('admin.tasks.create', array_merge(['workbench' => 1], array_filter($taskSeed, static fn ($value) => $value !== null && $value !== '' && $value !== [])));
    $articleSeedUrl = route('admin.articles.create', array_merge(['workbench' => 1], array_filter($articleSeed, static fn ($value) => $value !== null && $value !== '' && $value !== [])));
    $toneClasses = [
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-100',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-100',
        'teal' => 'bg-teal-50 text-teal-700 ring-teal-100',
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'orange' => 'bg-orange-50 text-orange-700 ring-orange-100',
    ];
@endphp

@section('content')
    <div class="px-4 sm:px-0">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">{{ __('admin.ai_configurator.workbench_eyebrow') }}</p>
                <h1 class="mt-2 text-3xl font-bold text-gray-900">{{ __('admin.ai_configurator.heading') }}</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600">{{ __('admin.ai_configurator.subtitle') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($shortcuts as $shortcut)
                    <a href="{{ $shortcut['href'] }}" class="inline-flex h-10 items-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                        <i data-lucide="{{ $shortcut['icon'] }}" class="mr-2 h-4 w-4"></i>
                        {{ $shortcut['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        <section class="mb-8 overflow-hidden rounded-lg border border-blue-100 bg-white shadow-sm">
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_340px]">
                <div class="p-6">
                    <div class="mb-5">
                        <h2 class="text-xl font-semibold text-gray-900">{{ __('admin.ai_configurator.intent_title') }}</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-600">{{ __('admin.ai_configurator.intent_desc') }}</p>
                    </div>

                    <form id="ai-workbench-intent-form" class="space-y-4">
                        @csrf
                        <label for="ai-workbench-intent" class="sr-only">{{ __('admin.ai_configurator.intent_label') }}</label>
                        <textarea
                            id="ai-workbench-intent"
                            name="input"
                            rows="5"
                            maxlength="2000"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="{{ __('admin.ai_configurator.intent_placeholder') }}"
                        ></textarea>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div id="ai-workbench-status" class="min-h-5 text-sm text-gray-500">{{ __('admin.ai_configurator.intent_hint') }}</div>
                            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <i data-lucide="wand-sparkles" class="mr-2 h-4 w-4"></i>
                                {{ __('admin.ai_configurator.intent_button') }}
                            </button>
                        </div>
                    </form>
                </div>

                <aside class="border-t border-blue-100 bg-blue-50/50 p-6 lg:border-l lg:border-t-0">
                    <h2 class="text-base font-semibold text-gray-900">{{ __('admin.ai_configurator.skills_title') }}</h2>
                    <p class="mt-1 text-sm leading-6 text-gray-600">{{ __('admin.ai_configurator.skills_desc') }}</p>
                    <div class="mt-5 grid gap-3">
                        @foreach ($skills as $skill)
                            @php($toneClass = $toneClasses[$skill['tone']] ?? $toneClasses['slate'])
                            <a href="{{ $skill['route'] }}" class="grid grid-cols-[38px_minmax(0,1fr)_auto] items-center gap-3 rounded-lg border border-white bg-white p-3 shadow-sm hover:border-blue-200 hover:bg-blue-50">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg ring-1 {{ $toneClass }}">
                                    <i data-lucide="{{ $skill['icon'] }}" class="h-4 w-4"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-gray-900">{{ $skill['title'] }}</span>
                                    <span class="mt-0.5 block truncate text-xs text-gray-500">{{ $skill['desc'] }}</span>
                                </span>
                                <i data-lucide="arrow-right" class="h-4 w-4 text-gray-400"></i>
                            </a>
                        @endforeach
                    </div>
                </aside>
            </div>
        </section>

        <section class="mb-8 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
            @foreach ([
                ['label' => __('admin.ai_configurator.stats.models'), 'value' => (int) ($quickStats['task_models'] ?? 0), 'icon' => 'cpu'],
                ['label' => __('admin.ai_configurator.stats.prompts'), 'value' => (int) ($quickStats['content_prompts'] ?? 0), 'icon' => 'message-square-text'],
                ['label' => __('admin.ai_configurator.stats.titles'), 'value' => (int) ($quickStats['title_libraries'] ?? 0), 'icon' => 'library-big'],
                ['label' => __('admin.ai_configurator.stats.knowledge'), 'value' => (int) ($quickStats['knowledge_bases'] ?? 0), 'icon' => 'database'],
                ['label' => __('admin.ai_configurator.stats.authors'), 'value' => (int) ($quickStats['authors'] ?? 0), 'icon' => 'user-pen'],
                ['label' => __('admin.ai_configurator.stats.categories'), 'value' => (int) ($quickStats['categories'] ?? 0), 'icon' => 'folders'],
            ] as $stat)
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-gray-500">{{ $stat['label'] }}</span>
                        <i data-lucide="{{ $stat['icon'] }}" class="h-4 w-4 text-gray-400"></i>
                    </div>
                    <div class="mt-3 text-2xl font-bold text-gray-900">{{ $stat['value'] }}</div>
                </div>
            @endforeach
        </section>

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <article class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700 ring-1 ring-blue-100">
                        <i data-lucide="workflow" class="h-5 w-5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('admin.ai_configurator.task_seed_title') }}</h2>
                        <p class="mt-1 text-sm leading-6 text-gray-600">{{ __('admin.ai_configurator.task_seed_desc') }}</p>
                        <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-gray-500">{{ __('admin.ai_configurator.seed_fields.name') }}</dt>
                                <dd class="mt-1 truncate font-semibold text-gray-900">{{ $taskSeed['task_name'] ?? '' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('admin.ai_configurator.seed_fields.status') }}</dt>
                                <dd class="mt-1 font-semibold text-gray-900">{{ $taskSeed['status'] ?? '' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('admin.ai_configurator.seed_fields.interval') }}</dt>
                                <dd class="mt-1 font-semibold text-gray-900">{{ $taskSeed['publish_interval'] ?? 60 }} {{ __('admin.common.minutes') }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('admin.ai_configurator.seed_fields.scope') }}</dt>
                                <dd class="mt-1 font-semibold text-gray-900">{{ $taskSeed['publish_scope'] ?? '' }}</dd>
                            </div>
                        </dl>
                        <a href="{{ $taskSeedUrl }}" class="mt-5 inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">
                            <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                            {{ __('admin.ai_configurator.task_seed_button') }}
                        </a>
                    </div>
                </div>
            </article>

            <article class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                        <i data-lucide="file-pen-line" class="h-5 w-5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('admin.ai_configurator.article_seed_title') }}</h2>
                        <p class="mt-1 text-sm leading-6 text-gray-600">{{ __('admin.ai_configurator.article_seed_desc') }}</p>
                        <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-gray-500">{{ __('admin.ai_configurator.seed_fields.title') }}</dt>
                                <dd class="mt-1 truncate font-semibold text-gray-900">{{ $articleSeed['title'] ?? '' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('admin.ai_configurator.seed_fields.review') }}</dt>
                                <dd class="mt-1 font-semibold text-gray-900">{{ $articleSeed['review_status'] ?? '' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('admin.ai_configurator.seed_fields.status') }}</dt>
                                <dd class="mt-1 font-semibold text-gray-900">{{ $articleSeed['status'] ?? '' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('admin.ai_configurator.seed_fields.ai_mark') }}</dt>
                                <dd class="mt-1 font-semibold text-gray-900">{{ ($articleSeed['is_ai_generated'] ?? '0') === '1' ? __('admin.common.yes') : __('admin.common.no') }}</dd>
                            </div>
                        </dl>
                        <a href="{{ $articleSeedUrl }}" class="mt-5 inline-flex h-10 items-center rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white hover:bg-emerald-700">
                            <i data-lucide="file-plus-2" class="mr-2 h-4 w-4"></i>
                            {{ __('admin.ai_configurator.article_seed_button') }}
                        </a>
                    </div>
                </div>
            </article>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('ai-workbench-intent-form');
            const input = document.getElementById('ai-workbench-intent');
            const status = document.getElementById('ai-workbench-status');
            const resolveUrl = @json(route('admin.ai.configurator.resolve'));
            const csrf = @json(csrf_token());
            const resolvingText = @json(__('admin.ai_configurator.intent_resolving'));
            const failedText = @json(__('admin.ai_configurator.intent_failed'));

            if (!form || !input || !status) {
                return;
            }

            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const value = input.value.trim();
                if (!value) {
                    status.textContent = @json(__('admin.ai_configurator.intent_required'));
                    return;
                }

                status.textContent = resolvingText;

                try {
                    const response = await fetch(resolveUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ input: value }),
                    });
                    const payload = await response.json();
                    if (!response.ok || !payload.redirect_url) {
                        throw new Error(payload.message || failedText);
                    }

                    status.textContent = payload.skill?.title
                        ? @json(__('admin.ai_configurator.intent_matched')).replace(':skill', payload.skill.title)
                        : @json(__('admin.ai_configurator.intent_opening'));

                    window.location.assign(payload.redirect_url);
                } catch (error) {
                    status.textContent = error.message || failedText;
                }
            });
        })();
    </script>
@endpush
