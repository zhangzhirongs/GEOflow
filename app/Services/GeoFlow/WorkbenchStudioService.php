<?php

namespace App\Services\GeoFlow;

use App\Models\AiModel;
use App\Models\Author;
use App\Models\Category;
use App\Models\ImageLibrary;
use App\Models\KnowledgeBase;
use App\Models\Prompt;
use App\Models\TitleLibrary;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class WorkbenchStudioService
{
    public function __construct(
        private readonly CatalogGeoFlowService $catalog,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function pageData(): array
    {
        $catalog = $this->catalog->getCatalog();
        $taskSeed = $this->taskSeed();
        $articleSeed = $this->articleSeed();

        return [
            'catalog' => $catalog,
            'skills' => $this->skills(),
            'task' => [
                'create_url' => route('admin.tasks.create'),
                'submit_url' => route('admin.tasks.store'),
                'seed' => $taskSeed,
                'options' => $this->taskOptions(),
            ],
            'article' => [
                'create_url' => route('admin.articles.create'),
                'submit_url' => route('admin.articles.store'),
                'editor_generate_url' => route('admin.articles.editor.generate'),
                'seed' => $articleSeed,
                'options' => $this->articleOptions(),
            ],
            'shortcuts' => [
                ['label' => __('admin.ai_configurator.shortcuts.tasks'), 'href' => route('admin.tasks.index'), 'icon' => 'list-checks'],
                ['label' => __('admin.ai_configurator.shortcuts.articles'), 'href' => route('admin.articles.index'), 'icon' => 'file-text'],
                ['label' => __('admin.ai_configurator.shortcuts.models'), 'href' => route('admin.ai-models.index'), 'icon' => 'cpu'],
                ['label' => __('admin.ai_configurator.shortcuts.prompts'), 'href' => route('admin.ai-prompts'), 'icon' => 'message-square-text'],
                ['label' => __('admin.ai_configurator.shortcuts.site_settings'), 'href' => route('admin.site-settings.index'), 'icon' => 'settings-2'],
                ['label' => __('admin.ai_configurator.shortcuts.providers'), 'href' => route('admin.ai-source-providers.index'), 'icon' => 'search-check'],
            ],
            'stats' => [
                'task_models' => count($catalog['models'] ?? []),
                'content_prompts' => count($catalog['prompts'] ?? []),
                'title_libraries' => count($catalog['title_libraries'] ?? []),
                'knowledge_bases' => count($catalog['knowledge_bases'] ?? []),
                'authors' => count($catalog['authors'] ?? []),
                'categories' => count($catalog['categories'] ?? []),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(string $input): array
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $input) ?? '');
        $skill = $this->matchSkill($normalized);

        if ($skill === null) {
            $skill = $this->skillDefinitions()->firstWhere('key', 'task-builder');
        }

        $prefill = match ($skill['type'] ?? 'task') {
            'article' => $this->articleSeed($this->extractContentHints($normalized)),
            'config' => [],
            default => $this->taskSeed($this->extractTaskHints($normalized)),
        };

        return [
            'input' => $normalized,
            'skill' => $skill,
            'prefill' => $prefill,
            'redirect_url' => $this->redirectUrlForSkill($skill, $prefill),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function taskSeed(array $overrides = []): array
    {
        $defaults = [
            'task_name' => __('admin.ai_configurator.defaults.task_name'),
            'title_library_id' => $this->firstId(TitleLibrary::query()->select(['id'])->orderBy('name')->get()),
            'prompt_id' => $this->firstId(Prompt::query()->select(['id'])->where('type', 'content')->orderBy('name')->get()),
            'ai_model_id' => $this->firstId(AiModel::query()
                ->select(['id'])
                ->where('status', 'active')
                ->where(function ($query): void {
                    $query->whereNull('model_type')->orWhere('model_type', '')->orWhere('model_type', 'chat');
                })
                ->orderBy('name')
                ->get()),
            'author_id' => $this->firstId(Author::query()->select(['id'])->orderBy('name')->get()),
            'image_library_id' => $this->firstId(ImageLibrary::query()->select(['id'])->orderBy('name')->get()),
            'image_count' => 1,
            'knowledge_base_id' => $this->firstId(KnowledgeBase::query()->select(['id'])->orderBy('name')->get()),
            'knowledge_base_ids' => [],
            'fixed_category_id' => $this->firstId(Category::query()->select(['id'])->orderBy('sort_order')->orderBy('name')->get()),
            'status' => 'paused',
            'article_limit' => 10,
            'draft_limit' => 10,
            'publish_interval' => 60,
            'category_mode' => 'smart',
            'model_selection_mode' => 'fixed',
            'publish_scope' => 'local_only',
            'distribution_strategy' => TaskDistributionChannelSelector::STRATEGY_BROADCAST,
            'need_review' => 1,
            'is_loop' => 0,
            'auto_keywords' => 1,
            'auto_description' => 1,
        ];

        return $this->overlayDefaults($defaults, $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    public function articleSeed(array $overrides = []): array
    {
        $defaults = [
            'title' => __('admin.ai_configurator.defaults.article_title'),
            'excerpt' => __('admin.ai_configurator.defaults.article_excerpt'),
            'content' => __('admin.ai_configurator.defaults.article_content'),
            'keywords' => __('admin.ai_configurator.defaults.article_keywords'),
            'meta_description' => __('admin.ai_configurator.defaults.article_meta_description'),
            'category_id' => $this->firstId(Category::query()->select(['id'])->orderBy('sort_order')->orderBy('name')->get()),
            'author_id' => $this->firstId(Author::query()->select(['id'])->orderBy('name')->get()),
            'status' => 'draft',
            'review_status' => 'pending',
            'task_name' => '',
            'is_hot' => '0',
            'is_featured' => '0',
            'source_title_id' => '',
            'is_ai_generated' => '1',
        ];

        return $this->overlayDefaults($defaults, $overrides);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function skills(): array
    {
        return $this->skillDefinitions()->all();
    }

    /**
     * @return array<string, array<int, array{id:int,name:string}>>
     */
    private function taskOptions(): array
    {
        return [
            'title_libraries' => $this->optionList(TitleLibrary::query()->select(['id', 'name'])->orderBy('name')->get()),
            'prompts' => $this->optionList(Prompt::query()->select(['id', 'name'])->where('type', 'content')->orderBy('name')->get()),
            'ai_models' => $this->optionList(AiModel::query()
                ->select(['id', 'name'])
                ->where('status', 'active')
                ->where(function ($query): void {
                    $query->whereNull('model_type')->orWhere('model_type', '')->orWhere('model_type', 'chat');
                })
                ->orderBy('name')
                ->get()),
            'image_libraries' => $this->optionList(ImageLibrary::query()->select(['id', 'name'])->orderBy('name')->get()),
            'knowledge_bases' => $this->optionList(KnowledgeBase::query()->select(['id', 'name'])->orderBy('name')->get()),
            'authors' => $this->optionList(Author::query()->select(['id', 'name'])->orderBy('name')->get()),
            'categories' => $this->optionList(Category::query()->select(['id', 'name'])->orderBy('sort_order')->orderBy('name')->get()),
        ];
    }

    /**
     * @return array<string, array<int, array{id:int,name:string}>>
     */
    private function articleOptions(): array
    {
        return [
            'categories' => $this->optionList(Category::query()->select(['id', 'name'])->orderBy('sort_order')->orderBy('name')->get()),
            'authors' => $this->optionList(Author::query()->select(['id', 'name'])->orderBy('name')->get()),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function skillDefinitions(): Collection
    {
        return collect([
            [
                'key' => 'task-builder',
                'title' => __('admin.ai_configurator.skills.task.title'),
                'desc' => __('admin.ai_configurator.skills.task.desc'),
                'icon' => 'workflow',
                'tone' => 'blue',
                'type' => 'task',
                'aliases' => ['任务', 'task', '定时', '批量', '自动发文', '循环'],
                'route' => route('admin.tasks.create'),
                'button' => __('admin.ai_configurator.skills.task.button'),
            ],
            [
                'key' => 'article-builder',
                'title' => __('admin.ai_configurator.skills.article.title'),
                'desc' => __('admin.ai_configurator.skills.article.desc'),
                'icon' => 'file-pen-line',
                'tone' => 'emerald',
                'type' => 'article',
                'aliases' => ['文章', '写文', '内容', '草稿', '生成文章', '稿件'],
                'route' => route('admin.articles.create'),
                'button' => __('admin.ai_configurator.skills.article.button'),
            ],
            [
                'key' => 'ai-models',
                'title' => __('admin.ai_configurator.skills.models.title'),
                'desc' => __('admin.ai_configurator.skills.models.desc'),
                'icon' => 'cpu',
                'tone' => 'violet',
                'type' => 'config',
                'aliases' => ['模型', 'model', 'deepseek', 'openai', 'gemini', 'qwen'],
                'route' => route('admin.ai-models.index'),
                'button' => __('admin.ai_configurator.skills.models.button'),
            ],
            [
                'key' => 'prompts',
                'title' => __('admin.ai_configurator.skills.prompts.title'),
                'desc' => __('admin.ai_configurator.skills.prompts.desc'),
                'icon' => 'message-square-text',
                'tone' => 'teal',
                'type' => 'config',
                'aliases' => ['提示词', 'prompt', '模板', '文案'],
                'route' => route('admin.ai-prompts'),
                'button' => __('admin.ai_configurator.skills.prompts.button'),
            ],
            [
                'key' => 'site-settings',
                'title' => __('admin.ai_configurator.skills.site.title'),
                'desc' => __('admin.ai_configurator.skills.site.desc'),
                'icon' => 'settings-2',
                'tone' => 'slate',
                'type' => 'config',
                'aliases' => ['站点', '首页', 'seo', '主题', '配置'],
                'route' => route('admin.site-settings.index'),
                'button' => __('admin.ai_configurator.skills.site.button'),
            ],
            [
                'key' => 'source-providers',
                'title' => __('admin.ai_configurator.skills.providers.title'),
                'desc' => __('admin.ai_configurator.skills.providers.desc'),
                'icon' => 'search-check',
                'tone' => 'orange',
                'type' => 'config',
                'aliases' => ['provider', '来源', '搜索源', '源配置'],
                'route' => route('admin.ai-source-providers.index'),
                'button' => __('admin.ai_configurator.skills.providers.button'),
            ],
        ]);
    }

    private function matchSkill(string $input): ?array
    {
        if ($input === '') {
            return null;
        }

        $haystack = mb_strtolower($input, 'UTF-8');
        foreach ($this->skillDefinitions() as $skill) {
            foreach ($skill['aliases'] as $alias) {
                if (Str::contains($haystack, mb_strtolower((string) $alias, 'UTF-8'))) {
                    return $skill;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function redirectUrlForSkill(array $skill, array $prefill): string
    {
        if (($skill['type'] ?? null) === 'article') {
            return route('admin.articles.create', array_merge(['workbench' => 1], $this->prefillQuery($prefill)));
        }

        if (($skill['type'] ?? null) === 'task') {
            return route('admin.tasks.create', array_merge(['workbench' => 1], $this->prefillQuery($prefill)));
        }

        return (string) ($skill['route'] ?? route('admin.dashboard'));
    }

    /**
     * @param  array<string, mixed>  $prefill
     * @return array<string, scalar|array<int, scalar>>
     */
    private function prefillQuery(array $prefill): array
    {
        return Arr::where(
            $prefill,
            static fn ($value): bool => $value !== null && $value !== '' && $value !== [] && $value !== 0 && $value !== '0'
        );
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function overlayDefaults(array $defaults, array $overrides): array
    {
        $cleanOverrides = Arr::where(
            $overrides,
            static fn ($value): bool => $value !== null && $value !== ''
        );

        return array_merge($defaults, Arr::only($cleanOverrides, array_keys($defaults)));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>  $rows
     * @return array<int, array{id:int,name:string}>
     */
    private function optionList(Collection $rows): array
    {
        return $rows
            ->map(static fn ($row): array => [
                'id' => (int) data_get($row, 'id', 0),
                'name' => (string) data_get($row, 'name', ''),
            ])
            ->values()
            ->all();
    }

    private function firstId(Collection $rows): int
    {
        $first = $rows->first();

        return (int) data_get($first, 'id', 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractTaskHints(string $input): array
    {
        return [
            'task_name' => $this->buildName($input, __('admin.ai_configurator.defaults.task_name')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractContentHints(string $input): array
    {
        $title = $this->buildName($input, __('admin.ai_configurator.defaults.article_title'));

        return [
            'title' => $title,
            'excerpt' => Str::limit($input !== '' ? $input : __('admin.ai_configurator.defaults.article_excerpt'), 140, ''),
            'content' => "# {$title}\n\n".$this->buildArticleBody($input),
            'keywords' => $this->buildKeywords($input),
            'meta_description' => Str::limit($input !== '' ? $input : __('admin.ai_configurator.defaults.article_meta_description'), 150, ''),
        ];
    }

    private function buildName(string $input, string $fallback): string
    {
        $input = trim($input);
        if ($input === '') {
            return $fallback;
        }

        $filtered = preg_replace('/[^\p{L}\p{N}\s\-_.]+/u', ' ', $input) ?? $input;
        $filtered = trim(preg_replace('/\s+/', ' ', $filtered) ?? $filtered);

        return Str::of($filtered)
            ->limit(32, '')
            ->trim()
            ->when($filtered === '', fn () => Str::of($fallback))
            ->toString();
    }

    private function buildArticleBody(string $input): string
    {
        if ($input === '') {
            return __('admin.ai_configurator.defaults.article_content');
        }

        return implode("\n", [
            __('admin.ai_configurator.defaults.article_intro'),
            '',
            $input,
            '',
            __('admin.ai_configurator.defaults.article_close'),
        ]);
    }

    private function buildKeywords(string $input): string
    {
        $parts = collect(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($input, 'UTF-8') ?: '') ?: [])
            ->filter(static fn (string $part): bool => $part !== '' && mb_strlen($part, 'UTF-8') >= 2)
            ->take(5)
            ->all();

        return implode(', ', $parts);
    }
}
