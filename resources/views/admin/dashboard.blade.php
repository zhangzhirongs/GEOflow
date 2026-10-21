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
        $urlImport = $urlImportHealth ?? [];

        $totalArticles = (int) ($stats['total_articles'] ?? 0);
        $publishedArticles = (int) ($stats['published_articles'] ?? 0);
        $draftArticles = (int) ($stats['draft_articles'] ?? 0);
        $approvedArticles = (int) ($stats['approved_articles'] ?? 0);
        $pendingReview = (int) ($stats['pending_review'] ?? 0);
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
        $todayArticles = (int) ($today['today_articles'] ?? 0);
        $urlImportTotal = (int) ($urlImport['total'] ?? 0);
        $urlImportRunning = (int) ($urlImport['running'] ?? 0);
        $urlImportCompleted = (int) ($urlImport['completed'] ?? 0);
        $urlImportFailed = (int) ($urlImport['failed'] ?? 0);

        $publishRate = $totalArticles > 0 ? (int) round($publishedArticles / $totalArticles * 100) : 0;

        // 提示色仅承载语义：红=风险、琥珀=待处理、灰=提示
        $toneStyles = [
            'red' => ['icon' => 'bg-red-50 text-red-600', 'count' => 'text-red-700'],
            'amber' => ['icon' => 'bg-amber-50 text-amber-600', 'count' => 'text-amber-700'],
            'slate' => ['icon' => 'bg-gray-100 text-gray-500', 'count' => 'text-gray-700'],
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

        // 首页只保留内容生产主链路指标，详细状态放在下方健康卡片中
        $kpis = [
            ['label' => __('admin.dashboard.total_articles'), 'value' => $totalArticles, 'meta' => __('admin.dashboard.today_added', ['count' => $todayArticles]), 'icon' => 'file-text', 'href' => route('admin.articles.index')],
            ['label' => __('admin.dashboard.published'), 'value' => $publishedArticles, 'meta' => __('admin.dashboard.publish_rate', ['rate' => $publishRate]), 'icon' => 'send', 'href' => route('admin.articles.index')],
            ['label' => __('admin.dashboard.pending_review'), 'value' => $pendingReview, 'meta' => __('admin.dashboard.approved_articles').' '.$approvedArticles, 'icon' => 'badge-check', 'href' => route('admin.articles.index', ['review_status' => 'pending']), 'tone' => $pendingReview > 0 ? 'warning' : 'default'],
            ['label' => __('admin.dashboard.active_tasks'), 'value' => $activeTasks, 'meta' => __('admin.dashboard.active_tasks_detail', ['running' => $runningJobs, 'pending' => $pendingJobs]), 'icon' => 'workflow', 'href' => route('admin.tasks.index')],
        ];

        // 内容漏斗仅描述内容主链路，避免混入没有稳定来源的访问数据
        $funnelMax = max(1, $totalTitles, $draftArticles, $pendingReview, $publishedArticles);
        $funnel = [
            ['label' => __('admin.dashboard.funnel_titles'), 'value' => $totalTitles, 'color' => '#1d4ed8'],
            ['label' => __('admin.dashboard.funnel_drafts'), 'value' => $draftArticles, 'color' => '#2563eb'],
            ['label' => __('admin.dashboard.funnel_pending_review'), 'value' => $pendingReview, 'color' => '#3b82f6'],
            ['label' => __('admin.dashboard.funnel_published'), 'value' => $publishedArticles, 'color' => '#60a5fa'],
        ];

        $healthRows = [
            ['label' => __('admin.dashboard.task_active'), 'value' => $activeTasks, 'href' => route('admin.tasks.index')],
            ['label' => __('admin.dashboard.task_paused'), 'value' => $pausedTasks, 'href' => route('admin.tasks.index')],
            ['label' => __('admin.dashboard.task_running'), 'value' => $runningJobs, 'href' => route('admin.tasks.index'), 'tone' => 'working'],
            ['label' => __('admin.dashboard.url_import_failed'), 'value' => $failedJobs, 'href' => route('admin.tasks.index'), 'tone' => $failedJobs > 0 ? 'danger' : 'neutral'],
        ];

        $materialRows = [
            ['label' => __('admin.dashboard.material_knowledge'), 'value' => $knowledgeBases],
            ['label' => __('admin.dashboard.material_titles'), 'value' => $titleLibraries],
            ['label' => __('admin.dashboard.material_keywords'), 'value' => $keywordLibraries],
            ['label' => __('admin.dashboard.material_images'), 'value' => $imageLibraries],
            ['label' => __('admin.dashboard.material_authors'), 'value' => $authors],
        ];

        $aiRows = [
            ['label' => __('admin.dashboard.ai_chat_models'), 'value' => $chatModels, 'tone' => $chatModels > 0 ? 'neutral' : 'danger'],
            ['label' => __('admin.dashboard.ai_embedding_models'), 'value' => $embeddingModels],
            ['label' => __('admin.dashboard.ai_used_today'), 'value' => number_format($aiUsedToday)],
            ['label' => __('admin.dashboard.ai_total_calls'), 'value' => number_format($aiTotalUsed)],
        ];

        $urlImportRows = [
            ['label' => __('admin.dashboard.url_import_total'), 'value' => $urlImportTotal],
            ['label' => __('admin.dashboard.url_import_running'), 'value' => $urlImportRunning, 'tone' => 'working'],
            ['label' => __('admin.dashboard.url_import_completed'), 'value' => $urlImportCompleted],
            ['label' => __('admin.dashboard.url_import_failed'), 'value' => $urlImportFailed, 'tone' => $urlImportFailed > 0 ? 'danger' : 'neutral'],
        ];

        $vectorRate = $knowledgeChunks > 0 ? (int) round($vectorizedChunks / $knowledgeChunks * 100) : 0;
    @endphp

    <x-admin.page-header :title="__('admin.dashboard.heading')" :subtitle="__('admin.dashboard.subtitle', ['site' => $siteName])">
        <x-slot:actions>
            <a href="{{ route('admin.dashboard') }}" class="qdk-btn qdk-btn-ghost">
                <i data-lucide="refresh-cw" class="nav-icon"></i>
                {{ __('admin.dashboard.refresh') }}
            </a>
            <a href="{{ route('admin.tasks.create') }}" class="qdk-btn qdk-btn-primary">
                <i data-lucide="plus" class="nav-icon"></i>
                {{ __('admin.dashboard.quick_start.task_button') }}
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <section class="admin-metrics">
        @foreach ($kpis as $kpi)
            <x-admin.metric :label="$kpi['label']" :value="$kpi['value']" :meta="$kpi['meta']" :icon="$kpi['icon']" :href="$kpi['href']" :tone="$kpi['tone'] ?? 'default'" />
        @endforeach
    </section>

    <section class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
        <x-admin.card :title="__('admin.dashboard.content_funnel')" :subtitle="__('admin.dashboard.content_funnel_desc')">
            <div class="space-y-5">
                @foreach ($funnel as $stage)
                    <div>
                        <div class="mb-2 flex items-baseline justify-between gap-4">
                            <span class="text-[13px] font-medium text-gray-600">{{ $stage['label'] }}</span>
                            <span class="text-[15px] font-semibold tabular-nums text-gray-900">{{ number_format($stage['value']) }}</span>
                        </div>
                        <div class="admin-bar">
                            <div class="admin-bar__fill" style="width: {{ max(3, (int) round($stage['value'] / $funnelMax * 100)) }}%; background: {{ $stage['color'] }};"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-admin.card>

        <x-admin.card :title="__('admin.dashboard.todo_title')" flush>
            <div class="divide-y divide-gray-100">
                @forelse ($todos as $todo)
                    @php $tone = $toneStyles[$todo['tone']] ?? $toneStyles['slate']; @endphp
                    <a href="{{ $todo['href'] }}" class="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-gray-50">
                        <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-md {{ $tone['icon'] }}">
                            <i data-lucide="{{ $todo['icon'] }}" class="h-4 w-4"></i>
                        </span>
                        <span class="min-w-0 flex-1 truncate text-[13px] font-medium text-gray-700">{{ $todo['label'] }}</span>
                        @if (!is_null($todo['count']))
                            <span class="flex-shrink-0 text-[13px] font-semibold tabular-nums {{ $tone['count'] }}">{{ number_format((int) $todo['count']) }}</span>
                        @endif
                        <i data-lucide="chevron-right" class="h-4 w-4 flex-shrink-0 text-gray-300"></i>
                    </a>
                @empty
                    <div class="flex items-center gap-3 px-5 py-6 text-[13px] text-emerald-700">
                        <i data-lucide="circle-check" class="h-4 w-4 flex-shrink-0"></i>
                        {{ __('admin.dashboard.todo_empty') }}
                    </div>
                @endforelse
            </div>
        </x-admin.card>
    </section>

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
        <x-admin.card :title="__('admin.dashboard.task_health')" :subtitle="__('admin.dashboard.recent_failures')">
            <x-slot:actions>
                <a href="{{ route('admin.tasks.index') }}" class="text-[13px] font-medium text-blue-600 hover:text-blue-700">{{ __('admin.dashboard.view_all') }}</a>
            </x-slot:actions>

            <dl class="divide-y divide-gray-100">
                @foreach ($healthRows as $row)
                    <div class="flex items-center justify-between gap-4 py-2.5 first:pt-0 last:pb-0">
                        <dt class="text-[13px] text-gray-500">{{ $row['label'] }}</dt>
                        <dd @class([
                            'text-[15px] font-semibold tabular-nums',
                            'text-gray-900' => ($row['tone'] ?? 'neutral') === 'neutral',
                            'text-blue-600' => ($row['tone'] ?? '') === 'working',
                            'text-red-600' => ($row['tone'] ?? '') === 'danger',
                        ])>{{ number_format((int) $row['value']) }}</dd>
                    </div>
                @endforeach
            </dl>

            <div class="mt-4 border-t border-gray-100 pt-3">
                @forelse ($recentFailures as $failure)
                    <div class="flex items-center justify-between gap-3 py-1.5 text-[13px]">
                        <span class="min-w-0 flex-1 truncate text-gray-700">{{ $failure->task_name ?? __('admin.dashboard.unknown_task') }}</span>
                        <span class="flex-shrink-0 text-[12px] text-gray-400">{{ \Illuminate\Support\Str::limit((string) ($failure->error_message ?? ''), 24) }}</span>
                    </div>
                @empty
                    <div class="text-[13px] text-gray-400">{{ __('admin.dashboard.no_failures') }}</div>
                @endforelse
            </div>
        </x-admin.card>

        <x-admin.card :title="__('admin.dashboard.material_health')">
            <x-slot:actions>
                <a href="{{ route('admin.materials.index') }}" class="text-[13px] font-medium text-blue-600 hover:text-blue-700">{{ __('admin.dashboard.view_all') }}</a>
            </x-slot:actions>

            <dl class="divide-y divide-gray-100">
                @foreach ($materialRows as $row)
                    <div class="flex items-center justify-between gap-4 py-2.5 first:pt-0">
                        <dt class="text-[13px] text-gray-500">{{ $row['label'] }}</dt>
                        <dd class="text-[15px] font-semibold tabular-nums text-gray-900">{{ number_format((int) $row['value']) }}</dd>
                    </div>
                @endforeach
            </dl>

            <div class="mt-4 border-t border-gray-100 pt-3">
                <div class="mb-2 flex items-center justify-between text-[13px]">
                    <span class="text-gray-500">{{ __('admin.dashboard.material_vectorized') }}</span>
                    <span class="font-semibold tabular-nums text-gray-900">{{ number_format($vectorizedChunks) }} / {{ number_format($knowledgeChunks) }}</span>
                </div>
                <div class="admin-bar">
                    <div class="admin-bar__fill" style="width: {{ $vectorRate }}%;"></div>
                </div>
            </div>
        </x-admin.card>

        <x-admin.card :title="__('admin.dashboard.ai_health')">
            <x-slot:actions>
                <a href="{{ route('admin.ai-models.index') }}" class="text-[13px] font-medium text-blue-600 hover:text-blue-700">{{ __('admin.dashboard.view_all') }}</a>
            </x-slot:actions>

            <dl class="divide-y divide-gray-100">
                @foreach ($aiRows as $row)
                    <div class="flex items-center justify-between gap-4 py-2.5 first:pt-0 last:pb-0">
                        <dt class="text-[13px] text-gray-500">{{ $row['label'] }}</dt>
                        <dd @class([
                            'text-[15px] font-semibold tabular-nums',
                            'text-gray-900' => ($row['tone'] ?? 'neutral') === 'neutral',
                            'text-red-600' => ($row['tone'] ?? '') === 'danger',
                        ])>{{ is_numeric($row['value']) ? number_format((int) $row['value']) : $row['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-admin.card>

        @if ($canManageProtectedWorkflows)
            <x-admin.card :title="__('admin.dashboard.url_import_health')">
                <x-slot:actions>
                    <a href="{{ route('admin.url-import') }}" class="text-[13px] font-medium text-blue-600 hover:text-blue-700">{{ __('admin.dashboard.view_all') }}</a>
                </x-slot:actions>

                <dl class="divide-y divide-gray-100">
                    @foreach ($urlImportRows as $row)
                        <div class="flex items-center justify-between gap-4 py-2.5 first:pt-0 last:pb-0">
                            <dt class="text-[13px] text-gray-500">{{ $row['label'] }}</dt>
                            <dd @class([
                                'text-[15px] font-semibold tabular-nums',
                                'text-gray-900' => ($row['tone'] ?? 'neutral') === 'neutral',
                                'text-blue-600' => ($row['tone'] ?? '') === 'working',
                                'text-red-600' => ($row['tone'] ?? '') === 'danger',
                            ])>{{ number_format((int) $row['value']) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-admin.card>
        @endif

    </section>
@endsection
