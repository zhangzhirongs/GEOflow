@extends('admin.layouts.app')

@php
    $workbench = $workbench ?? [];
    $skills = $workbench['skills'] ?? [];
    $capabilityGroups = $workbench['capability_groups'] ?? [];
    $shortcuts = $workbench['shortcuts'] ?? [];
    $articleOptions = $workbench['article']['options'] ?? [];
    $articleSeed = $workbench['article']['seed'] ?? [];
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
            <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(360px,0.9fr)]">
                <div class="min-w-0 border-b border-blue-100 p-5 lg:border-b-0 lg:border-r">
                    <span id="ai-workbench-intent-form" class="hidden">{{ __('admin.ai_configurator.intent_title') }}</span>
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">{{ __('admin.ai_configurator.workbench_eyebrow') }}</p>
                            <h2 class="mt-1 text-xl font-semibold text-gray-900">{{ __('admin.ai_configurator.chat_title') }}</h2>
                        </div>
                        <span id="ai-workbench-status" class="text-xs text-gray-500" role="status"></span>
                    </div>
                    <div id="ai-workbench-messages" class="mb-4 min-h-[260px] max-h-[420px] space-y-3 overflow-y-auto rounded-lg bg-gray-50 p-4" aria-live="polite">
                        <div class="max-w-[90%] rounded-lg border border-gray-200 bg-white p-3 text-sm text-gray-700 shadow-sm">{{ __('admin.ai_configurator.chat_welcome') }}</div>
                    </div>
                    <form id="ai-workbench-chat-form" class="space-y-3">
                        @csrf
                        <div class="grid gap-3 sm:grid-cols-2">
                            <select id="ai-workbench-draft" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm sm:col-span-2"><option value="">{{ __('admin.ai_configurator.draft_placeholder') }}</option>@foreach(($articleOptions['drafts'] ?? []) as $draft)<option value="{{ $draft['id'] }}" data-title="{{ $draft['title'] }}" data-keywords="{{ $draft['keywords'] }}" data-content="{{ base64_encode($draft['content']) }}">{{ $draft['title'] }}</option>@endforeach</select>
                            <input id="ai-workbench-title" type="text" maxlength="500" value="{{ $articleSeed['title'] ?? '' }}" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="{{ __('admin.ai_configurator.article_title_placeholder') }}" required>
                            <input id="ai-workbench-keyword" type="text" maxlength="200" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="{{ __('admin.ai_configurator.article_keyword_placeholder') }}">
                        </div>
                        <textarea id="ai-workbench-instruction" rows="3" maxlength="4000" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="{{ __('admin.ai_configurator.chat_placeholder') }}"></textarea>
                        <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto]">
                            <input id="ai-workbench-search-query" type="search" maxlength="500" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm" placeholder="{{ __('admin.ai_configurator.tool_search_placeholder') }}">
                            <button id="ai-workbench-search" type="button" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50"><i data-lucide="globe-2" class="mr-2 h-4 w-4"></i>{{ __('admin.ai_configurator.tool_search_button') }}</button>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <select id="ai-workbench-knowledge" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm"><option value="">{{ __('admin.ai_configurator.knowledge_placeholder') }}</option>@foreach(($articleOptions['knowledge_bases'] ?? []) as $option)<option value="{{ $option['id'] }}">{{ $option['name'] }}</option>@endforeach</select>
                            <select id="ai-workbench-prompt" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm"><option value="">{{ __('admin.ai_configurator.prompt_placeholder') }}</option>@foreach(($articleOptions['content_prompts'] ?? []) as $option)<option value="{{ $option['id'] }}">{{ $option['name'] }}</option>@endforeach</select>
                            <select id="ai-workbench-model" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm" required><option value="">{{ __('admin.ai_configurator.model_placeholder') }}</option>@foreach(($articleOptions['ai_models'] ?? []) as $option)<option value="{{ $option['id'] }}">{{ $option['name'] }}@if($option['model_id'] !== '') · {{ $option['model_id'] }}@endif</option>@endforeach</select>
                            <select id="ai-workbench-skill" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm"><option value="article-builder">{{ __('admin.ai_configurator.article_skill') }}</option></select>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <span id="ai-workbench-progress" class="text-xs text-gray-500"></span>
                            <div class="flex gap-2">
                                <button id="ai-workbench-stop" type="button" class="hidden inline-flex h-10 items-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50"><i data-lucide="square" class="mr-2 h-4 w-4"></i>{{ __('admin.ai_configurator.stop_button') }}</button>
                                <button id="ai-workbench-generate" type="submit" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"><i data-lucide="wand-sparkles" class="mr-2 h-4 w-4"></i>{{ __('admin.ai_configurator.generate_button') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
                <aside class="min-w-0 bg-gray-50/70 p-5">
                    <div class="flex items-center justify-between gap-3"><h2 class="text-base font-semibold text-gray-900">{{ __('admin.ai_configurator.preview_title') }}</h2><span id="ai-workbench-preview-state" class="text-xs text-gray-500">{{ __('admin.ai_configurator.preview_empty') }}</span></div>
                    <article id="ai-workbench-preview" class="mt-4 min-h-[420px] overflow-y-auto rounded-lg border border-gray-200 bg-white p-5 text-sm leading-7 text-gray-800"><p class="text-gray-400">{{ __('admin.ai_configurator.preview_empty') }}</p></article>
                    <div id="ai-workbench-quality" class="mt-4 hidden rounded-lg border border-gray-200 bg-white p-3 text-xs text-gray-600"></div>
                    <form id="ai-workbench-save-form" method="POST" action="{{ route('admin.articles.store') }}" data-create-action="{{ route('admin.articles.store') }}" data-update-base="{{ route('admin.articles.update', ['articleId' => 0]) }}" class="mt-4 grid gap-3">
                        @csrf
                        <input type="hidden" name="title" id="ai-workbench-save-title"><input type="hidden" name="content" id="ai-workbench-save-content"><input type="hidden" name="excerpt" id="ai-workbench-save-excerpt"><input type="hidden" name="keywords" id="ai-workbench-save-keywords"><input type="hidden" name="meta_description" id="ai-workbench-save-meta"><input type="hidden" name="category_id" id="ai-workbench-save-category"><input type="hidden" name="author_id" id="ai-workbench-save-author"><input type="hidden" name="status" value="draft"><input type="hidden" name="review_status" value="pending"><input type="hidden" name="is_ai_generated" value="1"><input type="hidden" name="_method" id="ai-workbench-save-method">
                        <div class="grid gap-3 sm:grid-cols-2"><select id="ai-workbench-category" required class="block w-full rounded-lg border-gray-300 text-sm shadow-sm"><option value="">{{ __('admin.ai_configurator.category_placeholder') }}</option>@foreach(($articleOptions['categories'] ?? []) as $option)<option value="{{ $option['id'] }}">{{ $option['name'] }}</option>@endforeach</select><select id="ai-workbench-author" required class="block w-full rounded-lg border-gray-300 text-sm shadow-sm"><option value="">{{ __('admin.ai_configurator.author_placeholder') }}</option>@foreach(($articleOptions['authors'] ?? []) as $option)<option value="{{ $option['id'] }}">{{ $option['name'] }}</option>@endforeach</select></div>
                        <div class="grid gap-2 sm:grid-cols-2"><button id="ai-workbench-quality-button" type="button" disabled class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 enabled:hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"><i data-lucide="shield-check" class="mr-2 h-4 w-4"></i>{{ __('admin.ai_configurator.quality_button') }}</button><button id="ai-workbench-save" type="submit" disabled class="inline-flex h-10 items-center justify-center rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm enabled:hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-40"><i data-lucide="file-check-2" class="mr-2 h-4 w-4"></i>{{ __('admin.ai_configurator.save_draft_button') }}</button></div>
                    </form>
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
            const form = document.getElementById('ai-workbench-chat-form');
            const status = document.getElementById('ai-workbench-status');
            const messages = document.getElementById('ai-workbench-messages');
            const preview = document.getElementById('ai-workbench-preview');
            const previewState = document.getElementById('ai-workbench-preview-state');
            const progress = document.getElementById('ai-workbench-progress');
            const stop = document.getElementById('ai-workbench-stop');
            const saveForm = document.getElementById('ai-workbench-save-form');
            const saveButton = document.getElementById('ai-workbench-save');
            const qualityButton = document.getElementById('ai-workbench-quality-button');
            const qualityBox = document.getElementById('ai-workbench-quality');
            const draftSelect = document.getElementById('ai-workbench-draft');
            const saveMethod = document.getElementById('ai-workbench-save-method');
            const searchButton = document.getElementById('ai-workbench-search');
            const searchQuery = document.getElementById('ai-workbench-search-query');
            const csrf = @json(csrf_token());
            const generateUrl = @json(route('admin.ai.configurator.generate'));
            const qualityUrl = @json(route('admin.ai.configurator.quality'));
            const searchUrl = @json(route('admin.ai.configurator.tools.search'));
            let controller = null;
            let content = '';

            if (!form || !status || !messages || !preview || !saveForm) {
                return;
            }

            const appendMessage = (text, tone = 'assistant') => {
                const node = document.createElement('div');
                node.className = tone === 'user'
                    ? 'ml-auto max-w-[90%] rounded-lg bg-blue-600 p-3 text-sm text-white shadow-sm'
                    : 'max-w-[90%] rounded-lg border border-gray-200 bg-white p-3 text-sm text-gray-700 shadow-sm';
                node.textContent = text;
                messages.append(node);
                messages.scrollTop = messages.scrollHeight;
                return node;
            };

            const parseMarkdown = (value) => value
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/^### (.*)$/gm, '<h3 class="mt-4 text-base font-semibold text-gray-900">$1</h3>')
                .replace(/^## (.*)$/gm, '<h2 class="mt-5 text-lg font-semibold text-gray-900">$1</h2>')
                .replace(/^# (.*)$/gm, '<h1 class="mb-4 text-2xl font-bold text-gray-900">$1</h1>')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\n\n/g, '</p><p class="mt-3">').replace(/\n/g, '<br>');

            const setBusy = (busy) => {
                form.querySelectorAll('input, textarea, select, button[type="submit"]').forEach((node) => { node.disabled = busy; });
                stop.classList.toggle('hidden', !busy);
            };

            draftSelect?.addEventListener('change', () => {
                const option = draftSelect.selectedOptions[0];
                if (!option?.value) {
                    saveForm.action = saveForm.dataset.createAction;
                    saveMethod.value = '';
                    return;
                }
                document.getElementById('ai-workbench-title').value = option.dataset.title || '';
                document.getElementById('ai-workbench-keyword').value = option.dataset.keywords || '';
                try {
                    content = atob(option.dataset.content || '');
                    preview.innerHTML = `<p>${parseMarkdown(content)}</p>`;
                    previewState.textContent = @json(__('admin.ai_configurator.draft_loaded'));
                    qualityButton.disabled = false;
                    saveButton.disabled = false;
                    document.getElementById('ai-workbench-save-title').value = option.dataset.title || '';
                    document.getElementById('ai-workbench-save-content').value = content;
                saveForm.action = saveForm.dataset.updateBase.replace(/\/0$/, `/${option.value}`);
                saveMethod.value = 'PUT';
                } catch (error) { status.textContent = @json(__('admin.ai_configurator.draft_load_failed')); }
            });

            const readStream = async (response, assistantNode) => {
                if (!response.body) throw new Error(@json(__('admin.ai_configurator.stream_failed')));
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';
                while (true) {
                    const { value, done } = await reader.read();
                    buffer += decoder.decode(value || new Uint8Array(), { stream: !done });
                    let split = buffer.match(/\r?\n\r?\n/);
                    while (split && split.index !== undefined) {
                        const block = buffer.slice(0, split.index);
                        buffer = buffer.slice(split.index + split[0].length);
                        const payload = block.split(/\r?\n/).filter((line) => line.startsWith('data:')).map((line) => line.slice(5).trim()).join('');
                        if (payload && payload !== '[DONE]') {
                            try {
                                const event = JSON.parse(payload);
                                if (event.type === 'text_delta' && typeof event.delta === 'string') {
                                    content += event.delta;
                                    assistantNode.innerHTML = parseMarkdown(content);
                                    preview.innerHTML = `<p>${parseMarkdown(content)}</p>`;
                                    progress.textContent = `${content.length} {{ __('admin.ai_configurator.characters') }}`;
                                    previewState.textContent = @json(__('admin.ai_configurator.preview_streaming'));
                                    messages.scrollTop = messages.scrollHeight;
                                }
                            } catch (error) { /* ignore keepalive or malformed provider chunks */ }
                        }
                        split = buffer.match(/\r?\n\r?\n/);
                    }
                    if (done) break;
                }
            };

            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                if (controller) return;
                const title = document.getElementById('ai-workbench-title').value.trim();
                const prompt = document.getElementById('ai-workbench-instruction').value.trim();
                const model = document.getElementById('ai-workbench-model').value;
                if (!title || !model) {
                    status.textContent = @json(__('admin.ai_configurator.article_required'));
                    return;
                }
                content = '';
                appendMessage(`${title}${prompt ? `：${prompt}` : ''}`, 'user');
                const assistantNode = appendMessage('');
                controller = new AbortController();
                setBusy(true);
                status.textContent = @json(__('admin.ai_configurator.generating_status'));
                try {
                    const response = await fetch(generateUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'text/event-stream, application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            title,
                            keyword: document.getElementById('ai-workbench-keyword').value.trim(),
                            prompt: prompt || null,
                            knowledge_base_id: document.getElementById('ai-workbench-knowledge').value || null,
                            prompt_id: document.getElementById('ai-workbench-prompt').value || null,
                            ai_model_id: Number(model),
                        }),
                        signal: controller.signal,
                    });
                    if (!response.ok) {
                        const payload = await response.json().catch(() => ({}));
                        throw new Error(payload.message || @json(__('admin.ai_configurator.generate_failed')));
                    }
                    await readStream(response, assistantNode);
                    if (!content.trim()) throw new Error(@json(__('admin.ai_configurator.empty_result')));
                    document.getElementById('ai-workbench-save-title').value = title;
                    document.getElementById('ai-workbench-save-content').value = content;
                    document.getElementById('ai-workbench-save-excerpt').value = content.replace(/[#*`]/g, '').slice(0, 200);
                    document.getElementById('ai-workbench-save-keywords').value = document.getElementById('ai-workbench-keyword').value.trim();
                    document.getElementById('ai-workbench-save-meta').value = document.getElementById('ai-workbench-keyword').value.trim();
                    document.getElementById('ai-workbench-save-category').value = document.getElementById('ai-workbench-category').value;
                    document.getElementById('ai-workbench-save-author').value = document.getElementById('ai-workbench-author').value;
                    saveButton.disabled = false;
                    qualityButton.disabled = false;
                    status.textContent = @json(__('admin.ai_configurator.completed_status'));
                    previewState.textContent = @json(__('admin.ai_configurator.preview_ready'));
                } catch (error) {
                    if (error.name === 'AbortError') status.textContent = @json(__('admin.ai_configurator.stopped_status'));
                    else { status.textContent = error.message || @json(__('admin.ai_configurator.generate_failed')); appendMessage(error.message || @json(__('admin.ai_configurator.generate_failed'))); }
                } finally {
                    controller = null;
                    setBusy(false);
                }
            });
            stop.addEventListener('click', () => controller?.abort());
            searchButton?.addEventListener('click', async () => {
                const query = searchQuery.value.trim();
                if (!query || !window.confirm(@json(__('admin.ai_configurator.tool_search_confirm')))) return;
                searchButton.disabled = true;
                try {
                    const response = await fetch(searchUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ query, confirmed: true }) });
                    const result = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(result.message || @json(__('admin.ai_configurator.tool_search_failed')));
                    const sourceText = (result.sources || []).map((source) => `${source.title || ''}\n${source.url || ''}\n${source.snippet || ''}`).join('\n\n');
                    appendMessage(`${@json(__('admin.ai_configurator.tool_search_result'))}\n${result.answer || sourceText}`, 'assistant');
                } catch (error) { appendMessage(error.message || @json(__('admin.ai_configurator.tool_search_failed'))); }
                finally { searchButton.disabled = false; }
            });
            qualityButton?.addEventListener('click', async () => {
                if (!content.trim()) return;
                qualityBox.classList.remove('hidden');
                qualityBox.textContent = @json(__('admin.ai_configurator.quality_running'));
                const response = await fetch(qualityUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ title: document.getElementById('ai-workbench-title').value.trim(), content, keywords: document.getElementById('ai-workbench-keyword').value.trim() }) });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) { qualityBox.textContent = result.message || @json(__('admin.ai_configurator.quality_failed')); return; }
                const matches = Number(result.match_count || 0);
                qualityBox.innerHTML = `<strong>${matches === 0 ? @json(__('admin.ai_configurator.quality_clean')) : @json(__('admin.ai_configurator.quality_warning'))}</strong><span class="ml-2">${matches} {{ __('admin.ai_configurator.quality_matches') }}</span>`;
            });
            saveForm.addEventListener('submit', () => {
                document.getElementById('ai-workbench-save-category').value = document.getElementById('ai-workbench-category').value;
                document.getElementById('ai-workbench-save-author').value = document.getElementById('ai-workbench-author').value;
            });
        })();
    </script>
@endpush
