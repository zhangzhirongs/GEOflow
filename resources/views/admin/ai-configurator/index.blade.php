@extends('admin.layouts.app')

@php
    $workbench = $workbench ?? [];
    $skills = $workbench['skills'] ?? [];
    $capabilityGroups = $workbench['capability_groups'] ?? [];
    $shortcuts = $workbench['shortcuts'] ?? [];
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
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="mt-2 text-3xl font-bold text-gray-900">{{ __('admin.ai_configurator.heading') }}</h1>
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
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_380px]">
                <div class="p-6">
                    <div class="mb-5">
                        <h2 class="text-xl font-semibold text-gray-900">{{ __('admin.ai_configurator.intent_title') }}</h2>
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
                            <div id="ai-workbench-status" class="min-h-5 text-sm text-gray-500"></div>
                            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <i data-lucide="wand-sparkles" class="mr-2 h-4 w-4"></i>
                                {{ __('admin.ai_configurator.intent_button') }}
                            </button>
                        </div>
                    </form>
                </div>

                <aside class="border-t border-blue-100 bg-blue-50/50 p-6 lg:border-l lg:border-t-0">
                    <h2 class="text-base font-semibold text-gray-900">{{ __('admin.ai_configurator.skills_title') }}</h2>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                        @foreach ($skills as $skill)
                            @php($toneClass = $toneClasses[$skill['tone']] ?? $toneClasses['slate'])
                            <a href="{{ $skill['route'] }}" class="grid grid-cols-[38px_minmax(0,1fr)_auto] items-center gap-3 rounded-lg border border-white bg-white p-3 shadow-sm hover:border-blue-200 hover:bg-blue-50">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg ring-1 {{ $toneClass }}">
                                    <i data-lucide="{{ $skill['icon'] }}" class="h-4 w-4"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-gray-900">{{ $skill['title'] }}</span>
                                </span>
                                <i data-lucide="arrow-right" class="h-4 w-4 text-gray-400"></i>
                            </a>
                        @endforeach
                    </div>
                </aside>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            @foreach ($capabilityGroups as $group)
                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $group['title'] }}</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($group['items'] as $item)
                            <a href="{{ $item['href'] }}" class="grid min-h-[86px] grid-cols-[36px_minmax(0,1fr)_auto] items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 hover:border-blue-200 hover:bg-blue-50">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-gray-700 ring-1 ring-gray-200">
                                    <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-gray-900">{{ $item['label'] }}</span>
                                </span>
                                <i data-lucide="arrow-right" class="h-4 w-4 text-gray-400"></i>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
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
