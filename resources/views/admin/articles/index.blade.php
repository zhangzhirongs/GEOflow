@extends('admin.layouts.app')

@php
    $isTrashView = (bool) ($isTrashView ?? false);
    $selectedTaskId = (int) ($filters['task_id'] ?? 0);
    $selectedStatus = (string) ($filters['status'] ?? '');
    $selectedReviewStatus = (string) ($filters['review_status'] ?? '');
    $selectedAuthorId = (int) ($filters['author_id'] ?? 0);
    $selectedDistributionChannelIds = collect($filters['distribution_channel_ids'] ?? [])
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->values()
        ->all();
    $selectedDateFrom = (string) ($filters['date_from'] ?? '');
    $selectedDateTo = (string) ($filters['date_to'] ?? '');
    $selectedSearch = (string) ($filters['search'] ?? '');
    $selectedPerPage = (int) ($filters['per_page'] ?? 20);
    $selectedTaskName = '';
    foreach ($tasks as $taskOption) {
        if ((int) ($taskOption['id'] ?? 0) === $selectedTaskId) {
            $selectedTaskName = (string) ($taskOption['name'] ?? '');
            break;
        }
    }
    $articleListAnchor = '#article-list';
    $categoryManageUrl = route('admin.categories.index');
    $reviewCenterUrl = route('admin.articles.index', ['review_status' => 'pending']).$articleListAnchor;
    $trashUrl = route('admin.articles.index', ['trashed' => 1]);
    $articlesIndexUrl = route('admin.articles.index');
    $clearTaskFilterUrl = route('admin.articles.index', request()->except(['task_id', 'page']));
    /* $contentWorkbenchItems = [
        [
            'icon' => 'shield-check',
            'title' => __('admin.articles.workbench.review_title'),
            'desc' => __('admin.articles.workbench.review_desc'),
            'count' => (int) ($stats['pending_review'] ?? 0),
            'href' => $reviewCenterUrl,
            'iconClass' => 'bg-amber-50 text-amber-600 ring-amber-100',
            'countClass' => 'text-amber-700',
            'linkClass' => 'text-amber-700 group-hover:text-amber-800',
        ],
        [
            'icon' => 'edit',
            'title' => __('admin.articles.workbench.optimize_title'),
            'desc' => __('admin.articles.workbench.optimize_desc'),
            'count' => (int) ($stats['draft'] ?? 0),
            'href' => route('admin.articles.index', ['status' => 'draft']).$articleListAnchor,
            'iconClass' => 'bg-blue-50 text-blue-600 ring-blue-100',
            'countClass' => 'text-blue-700',
            'linkClass' => 'text-blue-700 group-hover:text-blue-800',
        ],
        [
            'icon' => 'send',
            'title' => __('admin.articles.workbench.distribution_title'),
            'desc' => __('admin.articles.workbench.distribution_desc'),
            'count' => (int) ($stats['published'] ?? 0),
            'href' => route('admin.articles.index', ['status' => 'published']).$articleListAnchor,
            'iconClass' => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
            'countClass' => 'text-emerald-700',
            'linkClass' => 'text-emerald-700 group-hover:text-emerald-800',
        ],
        [
            'icon' => 'chart-no-axes-combined',
            'title' => __('admin.articles.workbench.observation_title'),
            'desc' => __('admin.articles.workbench.observation_desc'),
            'count' => (int) ($stats['observed'] ?? 0),
            'href' => route('admin.analytics'),
            'iconClass' => 'bg-purple-50 text-purple-600 ring-purple-100',
            'countClass' => 'text-purple-700',
            'linkClass' => 'text-purple-700 group-hover:text-purple-800',
        ],
    ];
    $workbenchPriority = collect($contentWorkbenchItems)
        ->sortByDesc(fn ($item) => (int) ($item['count'] ?? 0))
        ->first();
    $workbenchHasPriority = (int) ($workbenchPriority['count'] ?? 0) > 0; */
@endphp

@section('content')
    <x-admin.page-header
        :title="$pageTitle"
        :subtitle="$isTrashView ? __('admin.articles.trash.subtitle') : __('admin.articles.page_subtitle')">
        <x-slot:actions>
            @if($isTrashView)
                <a href="{{ $articlesIndexUrl }}" class="qdk-btn qdk-btn-ghost">
                    <i data-lucide="arrow-left" class="nav-icon"></i>
                    {{ __('admin.articles.trash.back') }}
                </a>
                <button type="button" onclick="submitEmptyTrash()" class="qdk-btn qdk-btn-danger">
                    <i data-lucide="trash-2" class="nav-icon"></i>
                    {{ __('admin.articles.trash.empty') }}
                </button>
            @else
                <a href="{{ $categoryManageUrl }}" class="qdk-btn qdk-btn-ghost">
                    <i data-lucide="folder" class="nav-icon"></i>
                    {{ __('admin.button.category_manage') }}
                </a>
                <a href="{{ $reviewCenterUrl }}" class="qdk-btn qdk-btn-ghost">
                    <i data-lucide="badge-check" class="nav-icon"></i>
                    {{ __('admin.button.review_center') }}
                </a>
                <a href="{{ $trashUrl }}" class="qdk-btn qdk-btn-ghost" title="{{ __('admin.button.trash') }}">
                    <i data-lucide="trash-2" class="nav-icon"></i>
                    {{ __('admin.button.trash') }}
                </a>
                <a href="{{ route('admin.articles.create') }}" class="qdk-btn qdk-btn-primary">
                    <i data-lucide="plus" class="nav-icon"></i>
                    {{ __('admin.button.create_article') }}
                </a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    @if($isTrashView)
        <div class="admin-metrics admin-metrics--narrow">
            <x-admin.metric
                :label="__('admin.articles.trash.stats_total')"
                :value="(int) ($stats['trashed_total'] ?? 0)"
                icon="archive"
                tone="neutral" />
        </div>
    @else
        @if (false)
        <section class="admin-card mb-6">
            <div class="admin-card__head">
                <div class="min-w-0">
                    <p class="mb-1 text-[12px] font-semibold uppercase tracking-[0.14em] text-blue-600">{{ __('admin.articles.workbench.eyebrow') }}</p>
                    <h2 class="qdk-card-title">{{ __('admin.articles.workbench.title') }}</h2>
                    <p class="qdk-card-sub max-w-3xl">{{ __('admin.articles.workbench.desc') }}</p>
                </div>
                <a href="{{ route('admin.tasks.create') }}" class="qdk-btn qdk-btn-sm qdk-btn-ghost shrink-0">
                    <i data-lucide="workflow" class="nav-icon"></i>
                    {{ __('admin.articles.workbench.create_task') }}
                </a>
            </div>

            {{-- 当前建议动作：优先引导到最需要处理的环节 --}}
            <div class="flex flex-col gap-4 border-b border-blue-100 bg-blue-50/60 px-6 py-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-white text-blue-600 ring-1 ring-blue-100">
                        <i data-lucide="{{ $workbenchHasPriority ? $workbenchPriority['icon'] : 'circle-check' }}" class="h-4 w-4"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[13px] font-semibold text-blue-700">{{ __('admin.articles.workbench.current_action_title') }}</p>
                        <p class="mt-1 text-[14px] font-semibold text-gray-900">
                            @if($workbenchHasPriority)
                                {{ __('admin.articles.workbench.current_action_desc', ['count' => (int) $workbenchPriority['count'], 'stage' => $workbenchPriority['title']]) }}
                            @else
                                {{ __('admin.articles.workbench.current_action_empty_title') }}
                            @endif
                        </p>
                        <p class="mt-0.5 text-[13px] leading-5 text-gray-500">
                            {{ $workbenchHasPriority ? __('admin.articles.workbench.current_action_help') : __('admin.articles.workbench.current_action_empty_desc') }}
                        </p>
                    </div>
                </div>
                <a href="{{ $workbenchHasPriority ? $workbenchPriority['href'] : route('admin.tasks.create') }}" class="qdk-btn qdk-btn-sm qdk-btn-primary shrink-0">
                    {{ $workbenchHasPriority ? __('admin.articles.workbench.current_action_button') : __('admin.articles.workbench.current_action_empty_button') }}
                    <i data-lucide="arrow-right" class="nav-icon"></i>
                </a>
            </div>

            {{-- 四个阶段：同一行等宽呈现，去掉卡片套卡片 --}}
            <div class="grid grid-cols-1 divide-y divide-gray-100 md:grid-cols-2 md:divide-x md:divide-y-0 xl:grid-cols-4">
                @foreach ($contentWorkbenchItems as $item)
                    <a href="{{ $item['href'] }}" class="group flex flex-col px-6 py-5 transition-colors hover:bg-gray-50">
                        <span class="flex items-center gap-2 text-[13px] font-medium text-gray-500">
                            <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4 text-gray-400"></i>
                            {{ $item['title'] }}
                        </span>
                        <span class="mt-3 text-[28px] font-bold leading-none tabular-nums text-gray-900">{{ (int) $item['count'] }}</span>
                        <span class="mt-2 line-clamp-2 text-[12px] leading-5 text-gray-400">{{ $item['desc'] }}</span>
                        <span class="mt-3 inline-flex items-center gap-1 text-[13px] font-medium text-blue-600 group-hover:text-blue-700">
                            {{ __('admin.articles.workbench.open') }}
                            <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
        @endif

        <div class="admin-metrics admin-metrics--5">
            @foreach ([
                ['icon' => 'file-text', 'label' => __('admin.articles.stats.total'), 'value' => (int) ($stats['total'] ?? 0), 'tone' => 'default'],
                ['icon' => 'globe', 'label' => __('admin.articles.stats.published'), 'value' => (int) ($stats['published'] ?? 0), 'tone' => 'success'],
                ['icon' => 'edit', 'label' => __('admin.articles.stats.draft'), 'value' => (int) ($stats['draft'] ?? 0), 'tone' => 'neutral'],
                ['icon' => 'badge-check', 'label' => __('admin.articles.stats.pending_review'), 'value' => (int) ($stats['pending_review'] ?? 0), 'tone' => 'warning'],
                ['icon' => 'calendar', 'label' => __('admin.articles.stats.today'), 'value' => (int) ($stats['today'] ?? 0), 'tone' => 'neutral'],
            ] as $statCard)
                <x-admin.metric :label="$statCard['label']" :value="$statCard['value']" :icon="$statCard['icon']" :tone="$statCard['tone']" />
            @endforeach
        </div>
    @endif

    @if($selectedTaskId > 0)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-[13px] text-blue-800">
            <div class="inline-flex items-center gap-2">
                <i data-lucide="filter" class="h-4 w-4"></i>
                <span>{{ __('admin.articles.filters.current_task', ['task' => $selectedTaskName !== '' ? $selectedTaskName : '#'.$selectedTaskId]) }}</span>
            </div>
            <a href="{{ $clearTaskFilterUrl }}" class="inline-flex items-center gap-1 font-medium text-blue-700 hover:text-blue-900">
                <i data-lucide="x" class="h-4 w-4"></i>
                {{ __('admin.articles.filters.clear_task') }}
            </a>
        </div>
    @endif
        <x-admin.filter-bar :reset-url="$isTrashView ? route('admin.articles.index', ['trashed' => 1]) : route('admin.articles.index')">
            <x-slot:hidden>
                @if($isTrashView)
                    <input type="hidden" name="trashed" value="1">
                @endif
            </x-slot:hidden>
            <x-admin.filter-field :label="__('admin.articles.filters.task')" width="180">
                <select name="task_id" class="admin-filter-control">
                    <option value="">{{ __('admin.articles.filters.all_tasks') }}</option>
                    @foreach($tasks as $task)
                        <option value="{{ (int) $task['id'] }}" @selected($selectedTaskId === (int) $task['id'])>{{ $task['name'] }}</option>
                    @endforeach
                </select>
            </x-admin.filter-field>
            @if(!$isTrashView)
            <x-admin.filter-field :label="__('admin.articles.filters.status')" width="140">
                <select name="status" class="admin-filter-control">
                    <option value="">{{ __('admin.articles.filters.all_status') }}</option>
                    <option value="draft" @selected($selectedStatus === 'draft')>{{ __('admin.articles.status.draft') }}</option>
                    <option value="published" @selected($selectedStatus === 'published')>{{ __('admin.articles.status.published') }}</option>
                    <option value="private" @selected($selectedStatus === 'private')>{{ __('admin.articles.status.private') }}</option>
                </select>
            </x-admin.filter-field>
            @endif
            <x-admin.filter-field :label="__('admin.articles.filters.search')" width="220">
                <input type="text" name="search" value="{{ $selectedSearch }}" placeholder="{{ __('admin.articles.filters.search_placeholder') }}" class="admin-filter-control">
            </x-admin.filter-field>
            <x-slot:advanced>
            @if(!$isTrashView)
            <x-admin.filter-field :label="__('admin.articles.filters.review_status')" width="150">
                <select name="review_status" class="admin-filter-control">
                    <option value="">{{ __('admin.articles.filters.all_review') }}</option>
                    <option value="pending" @selected($selectedReviewStatus === 'pending')>{{ __('admin.articles.review.pending') }}</option>
                    <option value="approved" @selected($selectedReviewStatus === 'approved')>{{ __('admin.articles.review.approved') }}</option>
                    <option value="rejected" @selected($selectedReviewStatus === 'rejected')>{{ __('admin.articles.review.rejected') }}</option>
                    <option value="auto_approved" @selected($selectedReviewStatus === 'auto_approved')>{{ __('admin.articles.review.auto_approved') }}</option>
                </select>
            </x-admin.filter-field>
            @endif
            <x-admin.filter-field :label="__('admin.articles.filters.author')" width="150">
                <select name="author_id" class="admin-filter-control">
                    <option value="">{{ __('admin.articles.filters.all_authors') }}</option>
                    @foreach($authors as $author)
                        <option value="{{ (int) $author['id'] }}" @selected($selectedAuthorId === (int) $author['id'])>{{ $author['name'] }}</option>
                    @endforeach
                </select>
            </x-admin.filter-field>
            <x-admin.filter-field :label="__('admin.articles.filters.date_from')" width="150">
                <input type="date" name="date_from" value="{{ $selectedDateFrom }}" class="admin-filter-control">
            </x-admin.filter-field>
            <x-admin.filter-field :label="__('admin.articles.filters.date_to')" width="150">
                <input type="date" name="date_to" value="{{ $selectedDateTo }}" class="admin-filter-control">
            </x-admin.filter-field>
            @if(!empty($distributionChannels))
                <div class="w-full">
                    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <label class="block text-sm font-medium text-gray-700">{{ __('admin.articles.filters.distribution_channel') }}</label>
                        <div class="flex items-center gap-2">
                            <span data-distribution-channel-filter-count class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                                {{ __('admin.articles.filters.distribution_channel_selected_count', ['count' => count($selectedDistributionChannelIds)]) }}
                            </span>
                            <button type="button"
                                    data-distribution-channel-filter-toggle
                                    aria-expanded="false"
                                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                                <span data-distribution-channel-filter-toggle-label>{{ __('admin.articles.filters.distribution_channel_expand') }}</span>
                                <i data-lucide="chevron-down" data-distribution-channel-filter-toggle-icon class="ml-1 h-3.5 w-3.5 transition-transform"></i>
                            </button>
                        </div>
                    </div>
                    <div data-distribution-channel-filter-panel class="hidden grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($distributionChannels as $channel)
                            <label data-distribution-channel-filter-card @class([
                                'flex items-start gap-3 rounded-md border px-4 py-3 text-sm transition',
                                'border-blue-200 bg-blue-50' => in_array((int) ($channel['id'] ?? 0), $selectedDistributionChannelIds, true),
                                'border-gray-200 bg-white hover:border-blue-300 hover:bg-blue-50' => ! in_array((int) ($channel['id'] ?? 0), $selectedDistributionChannelIds, true),
                            ])>
                                <input type="checkbox"
                                       name="distribution_channel_ids[]"
                                       value="{{ (int) ($channel['id'] ?? 0) }}"
                                       @checked(in_array((int) ($channel['id'] ?? 0), $selectedDistributionChannelIds, true))
                                       data-distribution-channel-filter-input
                                       class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="min-w-0">
                                    <span class="block font-medium text-gray-900">{{ $channel['name'] }}</span>
                                    @if((string) ($channel['domain'] ?? '') !== '')
                                        <span class="block break-all text-gray-500">{{ (string) ($channel['domain'] ?? '') }}</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-gray-500">{{ __('admin.articles.filters.distribution_channel_help') }}</p>
                </div>
            @endif
            </x-slot:advanced>
        </x-admin.filter-bar>


        <div id="article-list" class="admin-card scroll-mt-24">
            <div class="admin-card__head">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="qdk-card-title">
                        {{ $isTrashView ? __('admin.articles.trash.list_title') : __('admin.articles.list_title') }}
                    </h2>
                    <span class="text-[13px] text-gray-400">{{ __('admin.articles.list_total', ['count' => $articles->total()]) }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" onclick="toggleBatchActions()" class="qdk-btn qdk-btn-sm qdk-btn-ghost">
                        <i data-lucide="check-square" class="nav-icon"></i>
                        {{ __('admin.button.bulk_actions') }}
                    </button>
                </div>
            </div>

            @if($articles->isEmpty())
                <x-admin.empty-state
                    icon="inbox"
                    :title="$isTrashView ? __('admin.articles.trash.empty_title') : __('admin.articles.empty_title')"
                    :description="$isTrashView ? __('admin.articles.trash.empty_desc') : __('admin.articles.empty_desc')">
                    <x-slot:actions>
                        @if($isTrashView)
                            <a href="{{ $articlesIndexUrl }}" class="qdk-btn qdk-btn-sm qdk-btn-ghost">
                                <i data-lucide="arrow-left" class="nav-icon"></i>
                                {{ __('admin.articles.trash.back') }}
                            </a>
                        @else
                            <a href="{{ route('admin.articles.create') }}" class="qdk-btn qdk-btn-sm qdk-btn-primary">
                                <i data-lucide="plus" class="nav-icon"></i>
                                {{ __('admin.button.create_article') }}
                            </a>
                        @endif
                    </x-slot:actions>
                </x-admin.empty-state>
            @else
                <div id="batch-actions" class="hidden border-b border-gray-100 bg-gray-50 px-6 py-3">
                    <form method="POST" action="{{ \App\Support\AdminWeb::routePath('admin.articles.batch.update-status') }}" id="batch-form">
                        @csrf
                        <div id="batch-selected-ids"></div>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600">
                                @if(__('admin.articles.bulk.selected_prefix') !== '')
                                    <span>{{ __('admin.articles.bulk.selected_prefix') }}</span>
                                @endif
                                <span id="selected-count">0</span>
                                <span>{{ __('admin.articles.bulk.selected_suffix') }}</span>
                            </span>
                            <select name="action" id="batch-action" class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">{{ __('admin.articles.bulk.select_action') }}</option>
                                @if($isTrashView)
                                    <option value="batch_restore">{{ __('admin.articles.trash.action_restore') }}</option>
                                    <option value="batch_force_delete">{{ __('admin.articles.trash.action_force_delete') }}</option>
                                @else
                                    <option value="batch_update_status">{{ __('admin.articles.bulk.status_to') }}</option>
                                    <option value="batch_update_review">{{ __('admin.articles.bulk.review_to') }}</option>
                                    <option value="delete_articles">{{ __('admin.articles.bulk.delete') }}</option>
                                @endif
                            </select>
                            @if(!$isTrashView)
                            <select name="new_status" id="status-select" class="hidden border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="draft">{{ __('admin.articles.status.draft') }}</option>
                                <option value="published">{{ __('admin.articles.status.published') }}</option>
                                <option value="private">{{ __('admin.articles.status.private') }}</option>
                            </select>
                            <select name="review_status" id="review-select" class="hidden border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="pending">{{ __('admin.articles.review.pending') }}</option>
                                <option value="approved">{{ __('admin.articles.review.approved') }}</option>
                                <option value="rejected">{{ __('admin.articles.review.rejected') }}</option>
                                <option value="auto_approved">{{ __('admin.articles.review.auto_approved') }}</option>
                            </select>
                            @endif
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700">
                                {{ __('admin.button.execute') }}
                            </button>
                            <button type="button" onclick="toggleBatchActions()" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                {{ __('admin.button.cancel') }}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="admin-table-wrap">
                    <table class="admin-table min-w-full">
                        <thead>
                        <tr>
                            <th class="batch-checkbox hidden px-6 py-3 text-left">
                                <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 shadow-sm">
                            </th>
                            <th class="px-5 py-3 text-left text-[12px] font-semibold text-gray-500">{{ __('admin.articles.column.id') }}</th>
                            <th class="px-5 py-3 text-left text-[12px] font-semibold text-gray-500">{{ __('admin.articles.column.info') }}</th>
                            <th class="px-5 py-3 text-left text-[12px] font-semibold text-gray-500">{{ __('admin.articles.column.task_author') }}</th>
                            @if(!$isTrashView)
                            <th class="px-5 py-3 text-left text-[12px] font-semibold text-gray-500">{{ __('admin.articles.column.workflow') }}</th>
                            @endif
                            <th class="px-5 py-3 text-left text-[12px] font-semibold text-gray-500">{{ $isTrashView ? __('admin.articles.trash.column.deleted_at') : __('admin.articles.column.created_at') }}</th>
                            <th class="sticky right-0 z-20 bg-gray-50 px-5 py-3 text-left text-[12px] font-semibold text-gray-500 shadow-[-6px_0_8px_-6px_rgba(0,0,0,0.2)]">{{ __('admin.articles.column.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($articles as $article)
                            @php
                                $statusClass = match((string) $article->status) {
                                    'published' => 'qdk-badge-green',
                                    'draft' => 'qdk-badge-amber',
                                    'private' => 'qdk-badge-violet',
                                    default => 'qdk-badge-gray'
                                };
                                $reviewClass = match((string) $article->review_status) {
                                    'approved' => 'qdk-badge-green',
                                    'auto_approved' => 'qdk-badge-blue',
                                    'rejected' => 'qdk-badge-red',
                                    default => 'qdk-badge-amber'
                                };
                                $distributionTotal = (int) ($article->distribution_total_count ?? 0);
                                $distributionSynced = (int) ($article->distribution_synced_count ?? 0);
                                $distributionFailed = (int) ($article->distribution_failed_count ?? 0);
                                $distributionPending = max(0, $distributionTotal - $distributionSynced - $distributionFailed);
                                $articleDistributionChannels = collect($article->distributions ?? []);
                                if (count($selectedDistributionChannelIds) > 0) {
                                    $articleDistributionChannels = $articleDistributionChannels->filter(
                                        fn ($distribution): bool => in_array((int) ($distribution->distribution_channel_id ?? 0), $selectedDistributionChannelIds, true)
                                    );
                                }
                                $articleDistributionChannelLabels = $articleDistributionChannels
                                    ->map(function ($distribution): string {
                                        $channelName = (string) ($distribution->channel->name ?? '');
                                        $channelDomain = (string) ($distribution->channel->domain ?? '');
                                        if ($channelName !== '' && $channelDomain !== '') {
                                            return $channelName.' · '.$channelDomain;
                                        }

                                        return $channelName !== '' ? $channelName : $channelDomain;
                                    })
                                    ->filter(fn (string $label): bool => $label !== '')
                                    ->unique()
                                    ->values();
                                $remoteDistributions = collect($article->syncedRemoteDistributions ?? []);
                                if (count($selectedDistributionChannelIds) > 0) {
                                    $remoteDistributions = $remoteDistributions->filter(
                                        fn ($distribution): bool => in_array((int) ($distribution->distribution_channel_id ?? 0), $selectedDistributionChannelIds, true)
                                    );
                                }
                                $remoteViewLinks = $remoteDistributions
                                    ->map(function ($distribution): array {
                                        $remoteUrl = trim((string) ($distribution->remote_url ?? ''));
                                        $channelName = (string) ($distribution->channel->name ?? '');
                                        $channelDomain = (string) ($distribution->channel->domain ?? '');

                                        return [
                                            'url' => $remoteUrl,
                                            'channel' => $channelName !== '' ? $channelName : ($channelDomain !== '' ? $channelDomain : __('admin.articles.action.remote_channel_unknown')),
                                            'host' => (string) (parse_url($remoteUrl, PHP_URL_HOST) ?: $channelDomain),
                                        ];
                                    })
                                    ->filter(function (array $link): bool {
                                        $url = (string) ($link['url'] ?? '');
                                        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

                                        return filter_var($url, FILTER_VALIDATE_URL) !== false
                                            && in_array($scheme, ['http', 'https'], true);
                                    })
                                    ->values();
                                $primaryRemoteLink = $remoteViewLinks->first();
                                $localArticleUrl = null;
                                if ((string) $article->status === 'published' && trim((string) $article->slug) !== '') {
                                    $localArticleUrl = route('site.article', ['slug' => (string) $article->slug]);
                                }
                                $primaryPublishedLink = $primaryRemoteLink;
                                if ($primaryPublishedLink === null && $localArticleUrl !== null) {
                                    $primaryPublishedLink = [
                                        'url' => $localArticleUrl,
                                        'channel' => __('admin.articles.action.local_site'),
                                        'title' => __('admin.articles.action.view_local'),
                                    ];
                                } elseif ($primaryPublishedLink !== null) {
                                    $primaryPublishedLink['title'] = __('admin.articles.action.view_remote_for_channel', ['channel' => $primaryPublishedLink['channel']]);
                                }
                                $distributionBadge = null;
                                if (!$isTrashView && $distributionTotal > 0) {
                                    if ($distributionFailed > 0) {
                                        $distributionBadge = [
                                            'label' => __('admin.distribution.article_status.failed'),
                                            'detail' => $distributionFailed.'/'.$distributionTotal,
                                            'class' => 'bg-red-50 text-red-700 ring-red-100',
                                        ];
                                    } elseif ($distributionSynced >= $distributionTotal) {
                                        $distributionBadge = [
                                            'label' => __('admin.distribution.article_status.synced'),
                                            'detail' => $distributionSynced.'/'.$distributionTotal,
                                            'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                                        ];
                                    } else {
                                        $distributionBadge = [
                                            'label' => __('admin.distribution.article_status.queued'),
                                            'detail' => $distributionPending.'/'.$distributionTotal,
                                            'class' => 'bg-sky-50 text-sky-700 ring-sky-100',
                                        ];
                                    }
                                }
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="batch-checkbox hidden px-6 py-4">
                                    <input type="checkbox" value="{{ (int) $article->id }}" class="article-checkbox rounded border-gray-300 text-blue-600 shadow-sm">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">#{{ (int) $article->id }}</td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 truncate">
                                        @if($isTrashView)
                                            <span>{{ $article->title }}</span>
                                        @else
                                            <a href="{{ route('admin.articles.edit', ['articleId' => (int) $article->id]) }}" class="hover:text-blue-600">{{ $article->title }}</a>
                                        @endif
                                    </div>
                                    @if((string) ($article->excerpt ?? '') !== '')
                                        <p class="text-xs text-gray-500 mt-1">{{ \Illuminate\Support\Str::limit((string) $article->excerpt, 100) }}</p>
                                    @endif
                                    @if((string) ($article->keywords ?? '') !== '')
                                        <div class="text-xs text-blue-600 mt-1">{{ __('admin.articles.keywords') }}: {{ $article->keywords }}</div>
                                    @endif
                                    @if(!$isTrashView && (!empty($article->is_hot) || !empty($article->is_featured)))
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @if(!empty($article->is_hot))
                                                <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-100">{{ __('admin.articles.badge.hot') }}</span>
                                            @endif
                                            @if(!empty($article->is_featured))
                                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-blue-100">{{ __('admin.articles.badge.featured') }}</span>
                                            @endif
                                        </div>
                                    @endif
                                    @if($distributionBadge !== null)
                                        <div class="mt-2">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 {{ $distributionBadge['class'] }}">
                                                <i data-lucide="send" class="mr-1 h-3 w-3"></i>
                                                {{ $distributionBadge['label'] }}
                                                <span class="ml-1 font-mono text-[11px] opacity-80">{{ $distributionBadge['detail'] }}</span>
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <div class="space-y-1.5">
                                        @if((string) ($article->task->name ?? '') !== '')
                                            <div class="max-w-[220px] truncate font-medium text-blue-600" title="{{ $article->task->name }}">{{ $article->task->name }}</div>
                                        @endif
                                        <div class="text-gray-600">{{ $article->author->name ?? '' }}</div>
                                        @if($articleDistributionChannelLabels->isNotEmpty())
                                            <div class="flex max-w-[240px] flex-wrap gap-1.5">
                                                @foreach($articleDistributionChannelLabels->take(3) as $channelLabel)
                                                    <span class="inline-flex max-w-full items-center rounded-full bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-slate-200" title="{{ $channelLabel }}">
                                                        <i data-lucide="radio-tower" class="mr-1 h-3 w-3 shrink-0"></i>
                                                        <span class="max-w-[170px] truncate">{{ $channelLabel }}</span>
                                                    </span>
                                                @endforeach
                                                @if($articleDistributionChannelLabels->count() > 3)
                                                    <span class="inline-flex items-center rounded-full bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-500 ring-1 ring-gray-200">
                                                        +{{ $articleDistributionChannelLabels->count() - 3 }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                        @if((int) ($article->is_ai_generated ?? 0) === 1)
                                            <span class="qdk-badge qdk-badge-violet">{{ __('admin.articles.ai_generated') }}</span>
                                        @endif
                                    </div>
                                </td>
                                @if(!$isTrashView)
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1">
                                        <span class="qdk-badge {{ $statusClass }}">
                                            {{ __('admin.articles.publish_prefix') }}: {{ __('admin.articles.status.'.(string) $article->status) }}
                                        </span>
                                        <span class="qdk-badge {{ $reviewClass }}">
                                            {{ __('admin.articles.review_prefix') }}: {{ __('admin.articles.review.'.(string) $article->review_status) }}
                                        </span>
                                    </div>
                                </td>
                                @endif
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($isTrashView)
                                        <div>{{ optional($article->deleted_at)->format('Y-m-d H:i') }}</div>
                                        <div class="text-xs text-gray-400">{{ __('admin.articles.trash.created_prefix') }} {{ optional($article->created_at)->format('m-d H:i') }}</div>
                                    @else
                                        <div>{{ optional($article->created_at)->format('m-d H:i') }}</div>
                                        @if($article->published_at)
                                            <div class="text-xs text-green-600">{{ __('admin.articles.published_at', ['time' => $article->published_at->format('m-d H:i')]) }}</div>
                                        @endif
                                    @endif
                                </td>
                                <td class="sticky right-0 z-10 whitespace-nowrap bg-white px-6 py-4 text-sm font-medium shadow-[-6px_0_8px_-6px_rgba(0,0,0,0.12)]">
                                    @if($isTrashView)
                                        <div class="flex items-center space-x-2">
                                            <form method="POST" action="{{ route('admin.articles.restore', ['articleId' => (int) $article->id]) }}" class="inline" onsubmit="return confirm(@json(__('admin.articles.trash.confirm_restore')))">
                                                @csrf
                                                <button type="submit" class="text-gray-400 hover:text-emerald-600" title="{{ __('admin.articles.trash.action_restore') }}">
                                                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.articles.force-delete', ['articleId' => (int) $article->id]) }}" class="inline" onsubmit="return confirm(@json(__('admin.articles.trash.confirm_delete')))">
                                                @csrf
                                                <button type="submit" class="text-gray-400 hover:text-red-600" title="{{ __('admin.articles.trash.action_force_delete') }}">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <div class="flex items-center space-x-2">
                                            @if($primaryPublishedLink !== null)
                                                <a href="{{ $primaryPublishedLink['url'] }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-blue-600" title="{{ $primaryPublishedLink['title'] }}">
                                                    <i data-lucide="external-link" class="w-4 h-4"></i>
                                                </a>
                                            @else
                                                <span class="inline-flex cursor-not-allowed text-gray-300" title="{{ __('admin.articles.action.view_remote_unavailable') }}">
                                                    <i data-lucide="eye-off" class="w-4 h-4"></i>
                                                </span>
                                            @endif
                                            <a href="{{ route('admin.articles.edit', ['articleId' => (int) $article->id]) }}" class="text-gray-400 hover:text-blue-600" title="{{ __('admin.button.edit') }}">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </a>
                                            @if($canCreateManualPublication && in_array((string) $article->review_status, ['approved', 'auto_approved'], true))
                                                <a href="{{ route('admin.manual-publications.create', ['article_id' => (int) $article->id]) }}" class="text-gray-400 hover:text-blue-600" title="{{ __('admin.manual_publications.article_action') }}">
                                                    <i data-lucide="send" class="w-4 h-4"></i>
                                                </a>
                                            @endif
                                            @if((string) $article->review_status === 'pending')
                                                <button type="button" onclick="quickReview({{ (int) $article->id }}, 'approved')" class="text-gray-400 hover:text-emerald-600" title="{{ __('admin.articles.action.approve') }}">
                                                    <i data-lucide="check" class="w-4 h-4"></i>
                                                </button>
                                                <button type="button" onclick="quickReview({{ (int) $article->id }}, 'rejected')" class="text-gray-400 hover:text-red-600" title="{{ __('admin.articles.action.reject') }}">
                                                    <i data-lucide="x" class="w-4 h-4"></i>
                                                </button>
                                            @endif
                                            <button type="button" onclick="deleteArticle({{ (int) $article->id }})" class="text-gray-400 hover:text-red-600" title="{{ __('admin.button.delete') }}">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="admin-card__foot flex-col gap-3">
                    <div class="flex w-full flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <div class="shrink-0 text-sm text-gray-700">
                            {{ __('admin.articles.pagination.summary', ['from' => $articles->firstItem() ?? 0, 'to' => $articles->lastItem() ?? 0, 'total' => $articles->total()]) }}
                            @if($articles->lastPage() > 1)
                                <span class="ml-2 text-gray-500">{{ __('admin.articles.pagination.pages', ['page' => $articles->currentPage(), 'total_pages' => $articles->lastPage()]) }}</span>
                            @endif
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <form method="GET" class="flex items-center gap-2">
                                @foreach(request()->except(['per_page', 'page']) as $key => $value)
                                    @if(is_array($value))
                                        @foreach($value as $arrayValue)
                                            <input type="hidden" name="{{ $key }}[]" value="{{ $arrayValue }}">
                                        @endforeach
                                    @else
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endif
                                @endforeach
                                <input type="hidden" name="page" value="1">
                                <label for="per-page-input" class="text-sm text-gray-600">{{ __('admin.articles.pagination.per_page') }}</label>
                                <input id="per-page-input" type="number" name="per_page" min="10" max="100" step="1" value="{{ $selectedPerPage }}" class="w-20 rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 shadow-sm">
                                <button type="submit" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('admin.button.apply') }}</button>
                            </form>
                        </div>
                    </div>
                    <div class="w-full min-w-0 overflow-x-auto xl:flex xl:justify-end">
                        <div class="min-w-max">
                            {{ $articles->onEachSide(1)->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
@endsection

@push('scripts')
    <script>
        const ARTICLES_I18N = @json($articlesI18n);
        const TRASH_I18N = @json($trashI18n);
        const IS_TRASH_VIEW = @json($isTrashView);
        const EMPTY_TRASH_URL = @json(\App\Support\AdminWeb::routePath('admin.articles.trash.empty'));
        const DISTRIBUTION_CHANNEL_FILTER_COUNT_LABEL = @json(__('admin.articles.filters.distribution_channel_selected_count', ['count' => '__COUNT__']));
        const DISTRIBUTION_CHANNEL_FILTER_EXPAND_LABEL = @json(__('admin.articles.filters.distribution_channel_expand'));
        const DISTRIBUTION_CHANNEL_FILTER_COLLAPSE_LABEL = @json(__('admin.articles.filters.distribution_channel_collapse'));

        function toggleBatchActions() {
            const batchActions = document.getElementById('batch-actions');
            const checkboxes = document.querySelectorAll('.batch-checkbox');
            if (!batchActions) {
                return;
            }

            const isHidden = batchActions.classList.contains('hidden');
            if (isHidden) {
                batchActions.classList.remove('hidden');
                checkboxes.forEach((node) => node.classList.remove('hidden'));
                return;
            }

            batchActions.classList.add('hidden');
            checkboxes.forEach((node) => node.classList.add('hidden'));
            document.querySelectorAll('.article-checkbox').forEach((node) => node.checked = false);
            const selectAll = document.getElementById('select-all');
            if (selectAll) {
                selectAll.checked = false;
            }
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const countElement = document.getElementById('selected-count');
            if (!countElement) {
                return;
            }
            countElement.textContent = String(document.querySelectorAll('.article-checkbox:checked').length);
        }

        const ARTICLE_BATCH_ROUTES = @json($articleBatchRoutes);

        function submitEmptyTrash() {
            if (!confirm(TRASH_I18N.confirmEmpty)) {
                return;
            }
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = EMPTY_TRASH_URL;
            form.style.display = 'none';
            form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}">`;
            document.body.appendChild(form);
            form.submit();
        }

        function submitAction(action, articleId, extra = {}) {
            const targetAction = ARTICLE_BATCH_ROUTES[action] ?? '';
            if (targetAction === '') {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = targetAction;
            form.style.display = 'none';
            form.innerHTML = `
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="article_ids[]" value="${articleId}">
            `;
            Object.entries(extra).forEach(([key, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = String(value);
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
        }

        function deleteArticle(articleId) {
            if (!confirm(ARTICLES_I18N.confirmDelete)) {
                return;
            }
            submitAction('delete_articles', articleId);
        }

        function quickReview(articleId, status) {
            const actionText = status === 'approved' ? ARTICLES_I18N.reviewApproved : ARTICLES_I18N.reviewRejected;
            if (!confirm(ARTICLES_I18N.confirmQuickReview.replace('__ACTION__', actionText))) {
                return;
            }
            submitAction('batch_update_review', articleId, { review_status: status });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const distributionChannelFilterInputs = document.querySelectorAll('[data-distribution-channel-filter-input]');
            const distributionChannelFilterCount = document.querySelector('[data-distribution-channel-filter-count]');
            const distributionChannelFilterPanel = document.querySelector('[data-distribution-channel-filter-panel]');
            const distributionChannelFilterToggle = document.querySelector('[data-distribution-channel-filter-toggle]');
            const distributionChannelFilterToggleLabel = document.querySelector('[data-distribution-channel-filter-toggle-label]');
            const distributionChannelFilterToggleIcon = document.querySelector('[data-distribution-channel-filter-toggle-icon]');

            function setDistributionChannelFilterExpanded(isExpanded) {
                if (!distributionChannelFilterPanel || !distributionChannelFilterToggle) {
                    return;
                }

                distributionChannelFilterPanel.classList.toggle('hidden', !isExpanded);
                distributionChannelFilterToggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
                if (distributionChannelFilterToggleLabel) {
                    distributionChannelFilterToggleLabel.textContent = isExpanded
                        ? DISTRIBUTION_CHANNEL_FILTER_COLLAPSE_LABEL
                        : DISTRIBUTION_CHANNEL_FILTER_EXPAND_LABEL;
                }
                distributionChannelFilterToggleIcon?.classList.toggle('rotate-180', isExpanded);
            }

            function syncDistributionChannelFilterState() {
                const selectedCount = Array.from(distributionChannelFilterInputs).filter((input) => input.checked).length;
                if (distributionChannelFilterCount) {
                    distributionChannelFilterCount.textContent = DISTRIBUTION_CHANNEL_FILTER_COUNT_LABEL.replace('__COUNT__', String(selectedCount));
                }

                distributionChannelFilterInputs.forEach((input) => {
                    const card = input.closest('[data-distribution-channel-filter-card]');
                    if (!card) {
                        return;
                    }

                    const isSelected = input.checked;
                    card.classList.toggle('border-blue-200', isSelected);
                    card.classList.toggle('bg-blue-50', isSelected);
                    card.classList.toggle('border-gray-200', !isSelected);
                    card.classList.toggle('bg-white', !isSelected);
                    card.classList.toggle('hover:border-blue-300', !isSelected);
                    card.classList.toggle('hover:bg-blue-50', !isSelected);
                });
            }

            distributionChannelFilterInputs.forEach((input) => {
                input.addEventListener('change', syncDistributionChannelFilterState);
            });
            distributionChannelFilterToggle?.addEventListener('click', function() {
                setDistributionChannelFilterExpanded(this.getAttribute('aria-expanded') !== 'true');
            });
            setDistributionChannelFilterExpanded(false);
            syncDistributionChannelFilterState();

            const selectAll = document.getElementById('select-all');
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    document.querySelectorAll('.article-checkbox').forEach((node) => node.checked = this.checked);
                    updateSelectedCount();
                });
            }

            document.querySelectorAll('.article-checkbox').forEach((node) => {
                node.addEventListener('change', updateSelectedCount);
            });

            const batchAction = document.getElementById('batch-action');
            if (batchAction && !IS_TRASH_VIEW) {
                batchAction.addEventListener('change', function() {
                    const statusSelect = document.getElementById('status-select');
                    const reviewSelect = document.getElementById('review-select');
                    statusSelect?.classList.add('hidden');
                    reviewSelect?.classList.add('hidden');
                    if (this.value === 'batch_update_status') {
                        statusSelect?.classList.remove('hidden');
                    } else if (this.value === 'batch_update_review') {
                        reviewSelect?.classList.remove('hidden');
                    }
                });
            }

            const batchForm = document.getElementById('batch-form');
            if (batchForm) {
                batchForm.addEventListener('submit', function(event) {
                    const selected = document.querySelectorAll('.article-checkbox:checked');
                    if (selected.length === 0) {
                        event.preventDefault();
                        alert(IS_TRASH_VIEW ? TRASH_I18N.alertSelect : ARTICLES_I18N.selectArticles);
                        return;
                    }

                    const action = document.getElementById('batch-action')?.value ?? '';
                    if (action === '') {
                        event.preventDefault();
                        alert(ARTICLES_I18N.selectAction);
                        return;
                    }

                    const targetAction = ARTICLE_BATCH_ROUTES[action] ?? '';
                    if (targetAction === '') {
                        event.preventDefault();
                        alert(ARTICLES_I18N.selectAction);
                        return;
                    }
                    batchForm.action = targetAction;

                    if (IS_TRASH_VIEW) {
                        if (action === 'batch_restore' && !confirm(TRASH_I18N.confirmBatchRestore.replace('__COUNT__', String(selected.length)))) {
                            event.preventDefault();
                            return;
                        }
                        if (action === 'batch_force_delete' && !confirm(TRASH_I18N.confirmBatchForceDelete.replace('__COUNT__', String(selected.length)))) {
                            event.preventDefault();
                            return;
                        }
                    } else {
                    if (action === 'batch_update_status' && !(document.getElementById('status-select')?.value ?? '')) {
                        event.preventDefault();
                        alert(ARTICLES_I18N.selectStatus);
                        return;
                    }

                    if (action === 'batch_update_review' && !(document.getElementById('review-select')?.value ?? '')) {
                        event.preventDefault();
                        alert(ARTICLES_I18N.selectReview);
                        return;
                    }

                    if (action === 'delete_articles' && !confirm(ARTICLES_I18N.confirmDeleteSelected.replace('__COUNT__', selected.length))) {
                        event.preventDefault();
                        return;
                    }
                    }

                    const selectedIdsContainer = document.getElementById('batch-selected-ids');
                    if (!selectedIdsContainer) {
                        return;
                    }
                    selectedIdsContainer.innerHTML = '';
                    selected.forEach((checkbox) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'article_ids[]';
                        input.value = checkbox.value;
                        selectedIdsContainer.appendChild(input);
                    });
                });
            }
        });
    </script>
@endpush
