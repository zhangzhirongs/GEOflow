<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\AiSourceProvider;
use App\Models\AiVisibilityRun;
use App\Models\KnowledgeBase;
use App\Models\Prompt;
use App\Services\GeoFlow\ArticleContentGenerationService;
use App\Services\GeoFlow\ArticleContentPromptRenderer;
use App\Services\GeoFlow\KnowledgeRetrievalService;
use App\Services\GeoFlow\ArticleRiskScanner;
use App\Services\GeoFlow\AiVisibility\DoubaoSearchCustomClient;
use App\Support\AdminActivityLogger;
use App\Models\Task;
use App\Services\GeoFlow\WorkbenchStudioService;
use App\Support\AdminWeb;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/**
 * bak/admin 各菜单入口的 Blade 占位页（统一布局 + 文案）；后续可拆成独立控制器。
 */
class LegacyController extends Controller
{
    private function stub(string $pageTitleKey, string $activeMenu, ?string $stubHint = null): View
    {
        return view('admin.stub', [
            'pageTitle' => __($pageTitleKey),
            'activeMenu' => $activeMenu,
            'adminSiteName' => AdminWeb::siteName(),
            'stubHint' => $stubHint,
        ]);
    }

    /**
     * 任务管理列表（最小可用版）：支持关键词、状态筛选与分页。
     */
    public function tasks(Request $request): View
    {
        $filters = $this->buildTaskFilters($request);

        return view('admin.tasks.index', [
            'pageTitle' => __('admin.tasks.page_title'),
            'activeMenu' => 'tasks',
            'adminSiteName' => AdminWeb::siteName(),
            'tasks' => $this->queryTasks($filters),
            'filters' => $filters,
            'statusOptions' => [
                'active' => __('admin.tasks.status.running'),
                'paused' => __('admin.tasks.status.paused'),
            ],
        ]);
    }

    /**
     * @return array{keyword: string, status: string}
     */
    private function buildTaskFilters(Request $request): array
    {
        $keyword = trim((string) $request->query('keyword', ''));
        $status = (string) $request->query('status', '');

        if (! in_array($status, ['active', 'paused'], true)) {
            $status = '';
        }

        return [
            'keyword' => $keyword,
            'status' => $status,
        ];
    }

    /**
     * @param  array{keyword: string, status: string}  $filters
     */
    private function queryTasks(array $filters): LengthAwarePaginator
    {
        try {
            $query = Task::query()
                ->select([
                    'id',
                    'name',
                    'status',
                    'title_library_id',
                    'ai_model_id',
                    'created_count',
                    'published_count',
                    'loop_count',
                    'updated_at',
                    'created_at',
                ])
                ->with([
                    'titleLibrary:id,name',
                    'aiModel:id,name',
                ])
                ->orderByDesc('id');

            if ($filters['keyword'] !== '') {
                $query->where('name', 'like', '%'.$filters['keyword'].'%');
            }

            if ($filters['status'] !== '') {
                $query->where('status', $filters['status']);
            }

            return $query->paginate(15)->withQueryString();
        } catch (QueryException) {
            return $this->emptyTasksPaginator();
        }
    }

    /**
     * 兜底空分页：测试环境缺少 tasks 表时保持页面可渲染。
     */
    private function emptyTasksPaginator(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: collect(),
            total: 0,
            perPage: 15,
            currentPage: max(1, (int) request()->query('page', 1)),
            options: [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }

    public function articles(): View
    {
        return $this->stub('admin.articles.page_title', 'articles');
    }

    public function materials(): View
    {
        return $this->stub('admin.materials.page_title', 'materials');
    }

    public function aiConfigurator(WorkbenchStudioService $workbench): View
    {
        return view('admin.ai-configurator.index', [
            'pageTitle' => __('admin.ai_configurator.page_title'),
            'activeMenu' => 'ai_config',
            'adminSiteName' => AdminWeb::siteName(),
            'stats' => $this->loadAiConfiguratorStats(),
            'workbench' => $workbench->pageData(),
        ]);
    }

    public function resolveAiConfiguratorIntent(Request $request, WorkbenchStudioService $workbench): JsonResponse
    {
        $validated = $request->validate([
            'input' => ['required', 'string', 'max:2000'],
        ]);

        return response()->json($workbench->resolve((string) $validated['input']));
    }

    public function generateAiConfiguratorArticle(
        Request $request,
        ArticleContentPromptRenderer $promptRenderer,
        ArticleContentGenerationService $generationService,
        KnowledgeRetrievalService $knowledgeRetrievalService,
    ): \Symfony\Component\HttpFoundation\Response {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'keyword' => ['nullable', 'string', 'max:200'],
            'prompt' => ['nullable', 'string', 'max:4000'],
            'knowledge_base_id' => ['nullable', 'integer', 'min:1', 'exists:knowledge_bases,id'],
            'prompt_id' => ['nullable', 'integer', 'min:1', 'exists:prompts,id'],
            'ai_model_id' => ['required', 'integer', 'min:1', 'exists:ai_models,id'],
        ]);

        $model = AiModel::query()
            ->whereKey((int) $validated['ai_model_id'])
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereNull('model_type')->orWhere('model_type', '')->orWhere('model_type', 'chat');
            })
            ->firstOrFail();
        $knowledge = ! empty($validated['knowledge_base_id']) ? KnowledgeBase::query()->findOrFail((int) $validated['knowledge_base_id']) : null;
        $prompt = trim((string) ($validated['prompt'] ?? ''));
        if ($prompt === '' && ! empty($validated['prompt_id'])) {
            $prompt = (string) Prompt::query()->whereKey((int) $validated['prompt_id'])->where('type', 'content')->value('content');
        }
        $context = $knowledge ? $knowledgeRetrievalService->retrieveContext((int) $knowledge->id, trim($validated['title'].' '.($validated['keyword'] ?? '')), 5, 3200) : '';
        $contentPrompt = $promptRenderer->renderForEditor(trim($validated['title']), trim((string) ($validated['keyword'] ?? '')), $prompt, $context);

        try {
            $stream = $generationService->stream($model, $contentPrompt);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['message' => __('admin.article_assistant.generate.failed')], 500);
        }

        $response = $stream->toResponse($request);
        $response->headers->set('Cache-Control', 'no-cache, no-transform');
        $response->headers->set('X-Accel-Buffering', 'no');
        return $response;
    }

    public function checkAiConfiguratorArticle(Request $request, ArticleRiskScanner $riskScanner): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string', 'max:200000'],
            'excerpt' => ['nullable', 'string', 'max:10000'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        return response()->json($riskScanner->scan([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'excerpt' => (string) ($validated['excerpt'] ?? ''),
            'keywords' => (string) ($validated['keywords'] ?? ''),
            'meta_description' => (string) ($validated['meta_description'] ?? ''),
        ]));
    }

    public function runAiConfiguratorSearch(Request $request, DoubaoSearchCustomClient $searchClient): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:500'],
            'confirmed' => ['accepted'],
        ]);
        $provider = AiSourceProvider::query()
            ->where('provider_key', AiSourceProvider::PROVIDER_DOUBAO_SEARCH_CUSTOM)
            ->where('status', 'active')
            ->latest('id')
            ->first();
        if (! $provider) {
            return response()->json(['message' => __('admin.ai_configurator.tool_search_unavailable')], 422);
        }

        try {
            $result = $searchClient->search($provider, trim($validated['query']), ['count' => 5]);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['message' => __('admin.ai_configurator.tool_search_failed')], 422);
        }

        $admin = $request->user('admin');
        if ($admin) {
            AdminActivityLogger::logFromRequest($request, $admin, 'ai:tool-search', [
                'query' => $validated['query'],
                'provider' => $provider->provider_key,
                'source_count' => count($result->sources),
            ]);
        }

        return response()->json([
            'answer' => $result->answerText,
            'sources' => collect($result->sources)->map(static fn ($source): array => [
                'title' => $source->title,
                'url' => $source->url,
                'snippet' => $source->snippet ?: $source->summary,
            ])->values()->all(),
        ]);
    }

    /**
     * AI 模型配置页（迁移占位）。
     */
    public function aiModels(): View
    {
        return $this->stub('admin.ai_models.page_title', 'ai_config');
    }

    /**
     * 正文提示词配置页（迁移占位）。
     */
    public function aiPrompts(): View
    {
        return $this->stub('admin.ai_prompts.page_title', 'ai_config');
    }

    /**
     * 特殊提示词配置页（迁移占位）。
     */
    public function aiSpecialPrompts(): View
    {
        return $this->stub('admin.ai_special.page_title', 'ai_config');
    }

    public function siteSettings(): View
    {
        return $this->stub('admin.site_settings.page_title', 'site_settings');
    }

    public function securitySettings(): View
    {
        return $this->stub('admin.security.page_title', 'security');
    }

    public function adminUsers(): View
    {
        return $this->stub('admin.admin_users.page_title', 'admin_users');
    }

    public function apiTokens(): View
    {
        return $this->stub('admin.api_tokens.page_title', 'admin_users');
    }

    public function adminActivityLogs(): View
    {
        return $this->stub('admin.activity_logs.page_title', 'admin_users');
    }

    /**
     * 加载 AI 配置器概览统计。
     *
     * @return array{model_count:int,prompt_count:int,total_usage:int,today_usage:int,search_provider_count:int,search_provider_today_usage:int,visibility_failed_runs:int}
     */
    private function loadAiConfiguratorStats(): array
    {
        $hasSourceProviderTable = Schema::hasTable('ai_source_providers');
        $hasSourceProviderUsageDate = $hasSourceProviderTable
            && Schema::hasColumn('ai_source_providers', 'usage_date');

        return [
            'model_count' => AiModel::query()->where('status', 'active')->count(),
            'prompt_count' => Prompt::query()->count(),
            'total_usage' => (int) (AiModel::query()->sum('total_used') ?? 0),
            'today_usage' => (int) (AiModel::query()
                ->forCurrentUsageDay()
                ->sum('used_today') ?? 0),
            'search_provider_count' => $hasSourceProviderTable
                ? AiSourceProvider::query()->where('status', 'active')->count()
                : 0,
            'search_provider_today_usage' => $hasSourceProviderUsageDate
                ? (int) (AiSourceProvider::query()
                    ->whereDate('usage_date', now()->toDateString())
                    ->sum('used_today') ?? 0)
                : 0,
            'visibility_failed_runs' => Schema::hasTable('ai_visibility_runs')
                ? AiVisibilityRun::query()->where('status', AiVisibilityRun::STATUS_FAILED)->count()
                : 0,
        ];
    }
}
