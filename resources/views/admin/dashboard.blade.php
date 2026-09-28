@extends('admin.layouts.app')

@section('content')
    @php
        $canManageProtectedWorkflows = $canManageProtectedWorkflows ?? false;
        $siteName = $adminSiteName ?? \App\Support\AdminWeb::siteName();

        $stats = $dashboardStats ?? [];
        $today = $dashboardTodayStats ?? [];
        $tasks = $taskHealth ?? [];
        $materials = $materialHealth ?? [];
        $ai = $aiHealth ?? [];
        $distribution = $distributionHealth ?? [];
        $urlImport = $urlImportHealth ?? [];

        $totalArticles = (int) ($stats['total_articles'] ?? 0);
        $publishedArticles = (int) ($stats['published_articles'] ?? 0);
        $draftArticles = (int) ($stats['draft_articles'] ?? 0);
        $aiGenerated = (int) ($stats['ai_generated_articles'] ?? 0);
        $approvedArticles = (int) ($stats['approved_articles'] ?? 0);
        $pendingReview = (int) ($stats['pending_review'] ?? 0);
        $totalViews = (int) ($stats['total_views'] ?? 0);
        $totalTasks = (int) ($stats['total_tasks'] ?? 0);
        $totalTitles = (int) ($stats['total_titles'] ?? 0);

        $activeTasks = (int) ($tasks['active_tasks'] ?? $stats['active_tasks'] ?? 0);
        $pausedTasks = (int) ($tasks['paused_tasks'] ?? 0);
        $runningJobs = (int) ($tasks['running_jobs'] ?? $stats['running_jobs'] ?? 0);
        $pendingJobs = (int) ($tasks['pending_jobs'] ?? $stats['pending_jobs'] ?? 0);
        $failedJobs = (int) ($tasks['failed_jobs'] ?? $stats['failed_jobs'] ?? 0);
        $recentFailures = is_iterable($tasks['recent_failures'] ?? null) ? $tasks['recent_failures'] : [];

        $chatModels = (int) ($ai['chat_models'] ?? 0);
        $embeddingModels = (int) ($ai['embedding_models'] ?? 0);
        $aiUsedToday = (int) ($ai['used_today'] ?? 0);
        $aiTotalUsed = (int) ($ai['total_used'] ?? 0);

        $keywordLibraries = (int) ($materials['keyword_libraries'] ?? 0);
        $titleLibraries = (int) ($materials['title_libraries'] ?? 0);
        $knowledgeBases = (int) ($materials['knowledge_bases'] ?? 0);
        $imageLibraries = (int) ($materials['image_libraries'] ?? 0);
        $authors = (int) ($materials['authors'] ?? 0);
        $knowledgeChunks = (int) ($materials['knowledge_chunks'] ?? 0);
        $vectorizedChunks = (int) ($materials['vectorized_chunks'] ?? 0);
        $unvectorizedChunks = (int) ($materials['unvectorized_chunks'] ?? 0);
        $materialLibraryCount = $keywordLibraries + $titleLibraries + $knowledgeBases + $imageLibraries + $authors;

        $todayArticles = (int) ($today['today_articles'] ?? 0);
        $todayVisits = (int) ($today['today_views'] ?? 0);
        $aiBotCount = (int) ($today['today_ai_bot_views'] ?? 0);
        $urlImportTotal = (int) ($urlImport['total'] ?? 0);
        $urlImportRunning = (int) ($urlImport['running'] ?? 0);
        $urlImportCompleted = (int) ($urlImport['completed'] ?? 0);
        $urlImportFailed = (int) ($urlImport['failed'] ?? 0);

        $publishRate = $totalArticles > 0 ? (int) round($publishedArticles / $totalArticles * 100) : 0;
        $aiRatio = $totalArticles > 0 ? (int) round($aiGenerated / $totalArticles * 100) : 0;

        $toneStyles = [
            'red' => 'bg-red-50 text-red-700 ring-red-100',
            'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
        ];

        // 需要关注：仅在存在真实问题时出现，统一跳转到对应管理页
        $todos = [];
        if ($failedJobs > 0) {
            $todos[] = ['label' => __('admin.dashboard.todo_failed_jobs'), 'count' => $failedJobs, 'href' => route('admin.tasks.index'), 'tone' => 'red', 'icon' => 'triangle-alert'];
        }
        if ($pendingReview > 0) {
            $todos[] = ['label' => __('admin.dashboard.todo_pending_review'), 'count' => $pendingReview, 'href' => route('admin.articles.index', ['review_status' => 'pending']), 'tone' => 'amber', 'icon' => 'badge-check'];
        }
        if ($chatModels === 0) {
            $todos[] = ['label' => __('admin.dashboard.todo_no_chat_model'), 'count' => null, 'href' => route('admin.ai-models.index'), 'tone' => 'red', 'icon' => 'cpu'];
        }
        if ($knowledgeBases > 0 && $embeddingModels === 0) {
            $todos[] = ['label' => __('admin.dashboard.todo_no_embedding_model'), 'count' => null, 'href' => route('admin.ai-models.index'), 'tone' => 'amber', 'icon' => 'cpu'];
        }
        if ($unvectorizedChunks > 0) {
            $todos[] = ['label' => __('admin.dashboard.todo_unvectorized_chunks'), 'count' => $unvectorizedChunks, 'href' => route('admin.knowledge-bases.index'), 'tone' => 'amber', 'icon' => 'database-zap'];
        }
        if ($totalTitles < 10) {
            $todos[] = ['label' => __('admin.dashboard.todo_low_titles'), 'count' => $totalTitles, 'href' => route('admin.title-libraries.index'), 'tone' => 'slate', 'icon' => 'library-big'];
        }
        if ($canManageProtectedWorkflows && $urlImportFailed > 0) {
            $todos[] = ['label' => __('admin.dashboard.todo_url_import_failed'), 'count' => $urlImportFailed, 'href' => route('admin.url-import'), 'tone' => 'red', 'icon' => 'link-2'];
        }

        $kpis = [
            ['label' => __('admin.dashboard.total_articles'), 'value' => $totalArticles, 'meta' => __('admin.dashboard.today_added', ['count' => $todayArticles]), 'icon' => 'file-text', 'href' => route('admin.articles.index')],
            ['label' => __('admin.dashboard.published'), 'value' => $publishedArticles, 'meta' => __('admin.dashboard.publish_rate', ['rate' => $publishRate]), 'icon' => 'send', 'href' => route('admin.articles.index')],
            ['label' => __('admin.dashboard.pending_review'), 'value' => $pendingReview, 'meta' => __('admin.dashboard.approved_articles').' '.$approvedArticles, 'icon' => 'badge-check', 'href' => route('admin.articles.index', ['review_status' => 'pending'])],
            ['label' => __('admin.dashboard.active_tasks'), 'value' => $activeTasks, 'meta' => __('admin.dashboard.active_tasks_detail', ['running' => $runningJobs, 'pending' => $pendingJobs]), 'icon' => 'workflow', 'href' => route('admin.tasks.index')],
            ['label' => __('admin.dashboard.total_views'), 'value' => $totalViews, 'meta' => __('admin.dashboard.today_views', ['count' => $todayVisits]), 'icon' => 'eye', 'href' => route('admin.analytics')],
            ['label' => __('admin.dashboard.ai_models'), 'value' => $chatModels + $embeddingModels, 'meta' => __('admin.dashboard.automation.metric_chat_models', ['count' => $chatModels]), 'icon' => 'cpu', 'href' => route('admin.ai-models.index')],
            ['label' => __('admin.dashboard.material_total'), 'value' => $materialLibraryCount, 'meta' => __('admin.dashboard.automation.metric_vectorized', ['done' => $vectorizedChunks, 'total' => $knowledgeChunks]), 'icon' => 'database', 'href' => route('admin.materials.index')],
            ['label' => __('admin.dashboard.ai_generated'), 'value' => $aiGenerated, 'meta' => __('admin.dashboard.ai_generated_ratio', ['rate' => $aiRatio]), 'icon' => 'sparkles', 'href' => route('admin.articles.index')],
        ];

        $funnelMax = max(1, $totalTitles, $draftArticles, $pendingReview, $publishedArticles, $totalViews);
        $funnel = [
            ['label' => __('admin.dashboard.funnel_titles'), 'value' => $totalTitles, 'color' => 'bg-blue-500'],
            ['label' => __('admin.dashboard.funnel_drafts'), 'value' => $draftArticles, 'color' => 'bg-indigo-500'],
            ['label' => __('admin.dashboard.funnel_pending_review'), 'value' => $pendingReview, 'color' => 'bg-amber-500'],
            ['label' => __('admin.dashboard.funnel_published'), 'value' => $publishedArticles, 'color' => 'bg-emerald-500'],
            ['label' => __('admin.dashboard.funnel_viewed'), 'value' => $totalViews, 'color' => 'bg-violet-500'],
        ];
    @endphp

    <div class="px-4 sm:px-0">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.dashboard.heading') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('admin.dashboard.subtitle', ['site' => $siteName]) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.dashboard') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                    <i data-lucide="refresh-cw" class="mr-2 h-4 w-4"></i>
                    {{ __('admin.dashboard.refresh') }}
                </a>
                <a href="{{ route('admin.tasks.create') }}" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                    {{ __('admin.dashboard.quick_start.task_button') }}
                </a>
            </div>
        </div>

        <section class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
            @foreach ($kpis as $kpi)
                <a href="{{ $kpi['href'] }}" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-blue-200 hover:shadow-md">
                    <div class="flex items-center justify-between gap-3">
                        <span class="truncate text-sm font-medium text-gray-500">{{ $kpi['label'] }}</span>
                        <i data-lucide="{{ $kpi['icon'] }}" class="h-4 w-4 shrink-0 text-gray-400"></i>
                    </div>
                    <div class="mt-3 text-2xl font-bold text-gray-900">{{ number_format($kpi['value']) }}</div>
                    <div class="mt-1 truncate text-xs text-gray-400">{{ $kpi['meta'] }}</div>
                </a>
            @endforeach
        </section>

        <section class="mb-6 grid grid-cols-1 gap-5 lg:grid-cols-[380px_minmax(0,1fr)]">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-2">
                    <i data-lucide="bell-ring" class="h-5 w-5 text-amber-500"></i>
                    <h2 class="text-base font-semibold text-gray-900">{{ __('admin.dashboard.todo_title') }}</h2>
                </div>
                <div class="mt-4 space-y-2">
                    @forelse ($todos as $todo)
                        @php($toneClass = $toneStyles[$todo['tone']] ?? $toneStyles['slate'])
                        <a href="{{ $todo['href'] }}" class="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50 p-3 transition hover:border-blue-100 hover:bg-blue-50">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ring-1 {{ $toneClass }}">
                                <i data-lucide="{{ $todo['icon'] }}" class="h-4 w-4"></i>
                            </span>
                            <span class="min-w-0 flex-1 truncate text-sm font-medium text-gray-700">{{ $todo['label'] }}</span>
                            @if (!is_null($todo['count']))
                                <span class="shrink-0 rounded-full bg-white px-2 py-0.5 text-xs font-bold text-gray-700 ring-1 ring-gray-200">{{ $todo['count'] }}</span>
                            @endif
                            <i data-lucide="chevron-right" class="h-4 w-4 shrink-0 text-gray-400"></i>
                        </a>
                    @empty
                        <div class="flex items-center gap-2 rounded-lg border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-700">
                            <i data-lucide="circle-check" class="h-4 w-4"></i>
                            {{ __('admin.dashboard.todo_empty') }}
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-gray-900">{{ __('admin.dashboard.content_funnel') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('admin.dashboard.content_funnel_desc') }}</p>
                <div class="mt-5 space-y-4">
                    @foreach ($funnel as $stage)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-gray-600">{{ $stage['label'] }}</span>
                                <span class="font-bold text-gray-900">{{ number_format($stage['value']) }}</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full {{ $stage['color'] }}" style="width: {{ max(4, (int) round($stage['value'] / $funnelMax * 100)) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mb-6 grid grid-cols-1 gap-5 lg:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('admin.dashboard.task_health') }}</h3>
                    <a href="{{ route('admin.tasks.index') }}" class="text-xs font-medium text-blue-600 hover:text-blue-700">{{ __('admin.dashboard.view_all') }}</a>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="text-xs text-gray-500">{{ __('admin.dashboard.task_active') }}</div>
                        <div class="mt-1 text-xl font-bold text-gray-900">{{ $activeTasks }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="text-xs text-gray-500">{{ __('admin.dashboard.task_paused') }}</div>
                        <div class="mt-1 text-xl font-bold text-gray-900">{{ $pausedTasks }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="text-xs text-gray-500">{{ __('admin.dashboard.task_running') }}</div>
                        <div class="mt-1 text-xl font-bold text-blue-600">{{ $runningJobs }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="text-xs text-gray-500">{{ __('admin.dashboard.url_import_failed') }}</div>
                        <div class="mt-1 text-xl font-bold {{ $failedJobs > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $failedJobs }}</div>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('admin.dashboard.recent_failures') }}</div>
                    @forelse ($recentFailures as $failure)
                        <div class="flex items-center justify-between gap-3 border-t border-gray-100 py-2 text-sm">
                            <span class="min-w-0 flex-1 truncate text-gray-700">{{ $failure->task_name ?? __('admin.dashboard.unknown_task') }}</span>
                            <span class="shrink-0 text-xs text-gray-400">{{ \Illuminate\Support\Str::limit((string) ($failure->error_message ?? ''), 24) }}</span>
                        </div>
                    @empty
                        <div class="text-sm text-gray-400">{{ __('admin.dashboard.no_failures') }}</div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('admin.dashboard.material_health') }}</h3>
                    <a href="{{ route('admin.materials.index') }}" class="text-xs font-medium text-blue-600 hover:text-blue-700">{{ __('admin.dashboard.view_all') }}</a>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <div class="flex items-center justify-between"><span class="text-gray-500">{{ __('admin.dashboard.material_knowledge') }}</span><span class="font-semibold text-gray-900">{{ $knowledgeBases }}</span></div>
                    <div class="flex items-center justify-between"><span class="text-gray-500">{{ __('admin.dashboard.material_titles') }}</span><span class="font-semibold text-gray-900">{{ $titleLibraries }}</span></div>
                    <div class="flex items-center justify-between"><span class="text-gray-500">{{ __('admin.dashboard.material_keywords') }}</span><span class="font-semibold text-gray-900">{{ $keywordLibraries }}</span></div>
                    <div class="flex items-center justify-between"><span class="text-gray-500">{{ __('admin.dashboard.material_images') }}</span><span class="font-semibold text-gray-900">{{ $imageLibraries }}</span></div>
                    <div class="flex items-center justify-between"><span class="text-gray-500">{{ __('admin.dashboard.material_authors') }}</span><span class="font-semibold text-gray-900">{{ $authors }}</span></div>
                </div>
                <div class="mt-4">
                    <div class="mb-1 flex items-center justify-between text-sm">
                        <span class="text-gray-500">{{ __('admin.dashboard.material_vectorized') }}</span>
                        <span class="font-semibold text-gray-900">{{ $vectorizedChunks }} / {{ $knowledgeChunks }}</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ $knowledgeChunks > 0 ? (int) round($vectorizedChunks / $knowledgeChunks * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('admin.dashboard.ai_health') }}</h3>
                    <a href="{{ route('admin.ai-models.index') }}" class="text-xs font-medium text-blue-600 hover:text-blue-700">{{ __('admin.dashboard.view_all') }}</a>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="text-xs text-gray-500">{{ __('admin.dashboard.ai_chat_models') }}</div>
                        <div class="mt-1 text-xl font-bold {{ $chatModels > 0 ? 'text-gray-900' : 'text-red-600' }}">{{ $chatModels }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="text-xs text-gray-500">{{ __('admin.dashboard.ai_embedding_models') }}</div>
                        <div class="mt-1 text-xl font-bold text-gray-900">{{ $embeddingModels }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="text-xs text-gray-500">{{ __('admin.dashboard.ai_used_today') }}</div>
                        <div class="mt-1 text-xl font-bold text-gray-900">{{ number_format($aiUsedToday) }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="text-xs text-gray-500">{{ __('admin.dashboard.ai_total_calls') }}</div>
                        <div class="mt-1 text-xl font-bold text-gray-900">{{ number_format($aiTotalUsed) }}</div>
                    </div>
                </div>
            </div>

            @if ($canManageProtectedWorkflows)
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-gray-900">{{ __('admin.dashboard.url_import_health') }}</h3>
                        <a href="{{ route('admin.url-import') }}" class="text-xs font-medium text-blue-600 hover:text-blue-700">{{ __('admin.dashboard.view_all') }}</a>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-gray-50 p-3">
                            <div class="text-xs text-gray-500">{{ __('admin.dashboard.url_import_total') }}</div>
                            <div class="mt-1 text-xl font-bold text-gray-900">{{ $urlImportTotal }}</div>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3">
                            <div class="text-xs text-gray-500">{{ __('admin.dashboard.url_import_running') }}</div>
                            <div class="mt-1 text-xl font-bold text-blue-600">{{ $urlImportRunning }}</div>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3">
                            <div class="text-xs text-gray-500">{{ __('admin.dashboard.url_import_completed') }}</div>
                            <div class="mt-1 text-xl font-bold text-emerald-600">{{ $urlImportCompleted }}</div>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3">
                            <div class="text-xs text-gray-500">{{ __('admin.dashboard.url_import_failed') }}</div>
                            <div class="mt-1 text-xl font-bold {{ $urlImportFailed > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $urlImportFailed }}</div>
                        </div>
                    </div>
                </div>
            @endif

            <a href="{{ route('admin.analytics') }}" class="flex flex-col justify-between rounded-lg border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-5 shadow-sm transition hover:shadow-md">
                <div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="chart-no-axes-combined" class="h-5 w-5 text-blue-600"></i>
                        <h3 class="text-base font-semibold text-gray-900">{{ __('admin.dashboard.navigation.analytics_title') }}</h3>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-gray-500">{{ __('admin.dashboard.navigation.analytics_desc') }}</p>
                </div>
                <div class="mt-4 flex items-center gap-4 text-sm">
                    <span class="text-gray-600">{{ __('admin.dashboard.automation.metric_today_visits', ['count' => $todayVisits]) }}</span>
                    <span class="text-gray-600">{{ __('admin.dashboard.automation.metric_ai_bots', ['count' => $aiBotCount]) }}</span>
                </div>
            </a>
        </section>
    </div>
@endsection
