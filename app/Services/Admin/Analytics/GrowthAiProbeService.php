<?php

namespace App\Services\Admin\Analytics;

use App\Jobs\ProcessGrowthAiProbeJob;
use App\Models\Admin;
use App\Models\AiGrowthProbe;
use App\Models\AiVisibilityRun;
use App\Models\Keyword;
use App\Models\KeywordLibrary;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class GrowthAiProbeService
{
    public function __construct(
        private readonly GrowthAiAnswerCollector $collection,
        private readonly GrowthAiMentionAnalyzer $analyzer,
    ) {}

    public function configured(): bool
    {
        return $this->channels() !== [];
    }

    public function scheduleSettings(): array
    {
        $values = SiteSetting::query()->whereIn('setting_key', ['growth_ai_schedule_enabled', 'growth_ai_schedule_admin_id', 'growth_ai_schedule_question', 'growth_ai_schedule_provider', 'growth_ai_schedule_interval_minutes', 'growth_ai_schedule_next_run_at'])->pluck('setting_value', 'setting_key');

        return ['enabled' => (bool) ((int) $values->get('growth_ai_schedule_enabled', 0)), 'question' => (string) $values->get('growth_ai_schedule_question', ''), 'provider_type' => (string) $values->get('growth_ai_schedule_provider', AiGrowthProbe::CHANNEL_AUTO), 'interval_minutes' => max(1, (int) $values->get('growth_ai_schedule_interval_minutes', 60)), 'next_run_at' => $values->get('growth_ai_schedule_next_run_at')];
    }

    public function saveSchedule(array $data): void
    {
        foreach (['growth_ai_schedule_enabled' => ((int) ! empty($data['enabled'])), 'growth_ai_schedule_admin_id' => (string) ((int) ($data['admin_id'] ?? 0)), 'growth_ai_schedule_question' => trim((string) $data['question']), 'growth_ai_schedule_provider' => (string) $data['provider_type'], 'growth_ai_schedule_interval_minutes' => (string) ((int) $data['interval_minutes']), 'growth_ai_schedule_next_run_at' => now()->addMinutes((int) $data['interval_minutes'])->toDateTimeString()] as $key => $value) {
            SiteSetting::query()->updateOrCreate(['setting_key' => $key], ['setting_value' => $value]);
        }
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function channels(): array
    {
        return $this->collection->channels();
    }

    public function queue(string $question, Admin $admin, string $channel = AiGrowthProbe::CHANNEL_AUTO): AiGrowthProbe
    {
        $question = trim($question);
        if ($question === '') {
            throw new RuntimeException(__('admin.analytics.overview.probe.errors.empty_question'));
        }

        $channel = $this->collection->normalizeChannel($channel);
        $allowed = array_column($this->channels(), 'value');
        if ($allowed === []) {
            throw new RuntimeException(__('admin.analytics.overview.probe.unconfigured'));
        }
        if (! in_array($channel, $allowed, true)) {
            throw new RuntimeException(__('admin.analytics.overview.probe.errors.channel_unavailable'));
        }

        $channels = $channel === AiGrowthProbe::CHANNEL_ALL
            ? $this->collection->comparisonChannels()
            : [$channel];
        if ($channels === []) {
            throw new RuntimeException(__('admin.analytics.overview.probe.errors.no_providers'));
        }

        return DB::transaction(function () use ($question, $admin, $channels): AiGrowthProbe {
            $first = null;
            foreach ($channels as $channel) {
                $payload = [
                    'admin_id' => $admin->id,
                    'question' => $question,
                    'status' => AiGrowthProbe::STATUS_QUEUED,
                ];
                if (Schema::hasColumn('ai_growth_probes', 'provider_type')) {
                    $payload['provider_type'] = $channel;
                }

                $probe = AiGrowthProbe::query()->create($payload);
                $first ??= $probe;
                ProcessGrowthAiProbeJob::dispatch((int) $probe->id)->onQueue('geoflow')->afterCommit();
            }

            return $first;
        });
    }

    public function process(AiGrowthProbe $probe): AiGrowthProbe
    {
        if ($probe->status === AiGrowthProbe::STATUS_COMPLETED) {
            return $probe;
        }

        $probe->forceFill([
            'status' => AiGrowthProbe::STATUS_RUNNING,
            'started_at' => $probe->started_at ?? now(),
            'error_message' => null,
        ])->save();

        $runs = [];
        try {
            $runs = $this->collection->collect($probe->question, $probe->providerType());
            $run = $this->preferredRun($runs);
            if (! $run instanceof AiVisibilityRun) {
                throw new RuntimeException(__('admin.analytics.overview.probe.errors.empty_answer'));
            }

            $analysis = $this->analyzer->analyze($probe->question, $run);
            $analysis['results'] = collect($runs)->map(function (AiVisibilityRun $item) use ($probe, $run, $analysis): array {
                $itemAnalysis = $item->is($run)
                    ? $analysis
                    : ($item->status === AiVisibilityRun::STATUS_COMPLETED
                        ? $this->analyzer->analyze($probe->question, $item)
                        : []);

                return $this->presentRun($item, $itemAnalysis);
            })->values()->all();
            $probe->forceFill([
                'ai_visibility_run_id' => $run->id,
                'status' => AiGrowthProbe::STATUS_COMPLETED,
                'verdict' => (string) ($analysis['verdict'] ?? AiGrowthProbe::VERDICT_NONE),
                'brand_mentioned' => (bool) ($analysis['brand_mentioned'] ?? false),
                'owned_content_mentioned' => (bool) ($analysis['owned_content_mentioned'] ?? false),
                'answer_excerpt' => $this->analyzer->excerpt($run, $analysis),
                'analysis_json' => $analysis,
                'error_message' => null,
                'completed_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            if ($exception instanceof GrowthAiCollectionException) {
                $runs = $exception->runs;
            }
            $probe->forceFill([
                'status' => AiGrowthProbe::STATUS_FAILED,
                'analysis_json' => [
                    'results' => collect($runs)->map(fn (AiVisibilityRun $run): array => $this->presentRun($run))->values()->all(),
                ],
                'error_message' => mb_substr($exception->getMessage(), 0, 1000, 'UTF-8'),
                'completed_at' => now(),
            ])->save();

            throw $exception;
        }

        try {
            $this->syncKeywords($analysis);
        } catch (Throwable $exception) {
            report($exception);
            $analysis['warnings'][] = __('admin.analytics.overview.probe.keyword_sync_failed');
            $probe->forceFill(['analysis_json' => $analysis])->save();
        }

        return $probe->fresh() ?? $probe;
    }

    /** Persist extracted terms into reusable, separately classified keyword libraries. */
    private function syncKeywords(array $analysis): void
    {
        if (! Schema::hasTable('keywords') || ! Schema::hasTable('keyword_libraries')) {
            return;
        }

        $groups = [
            'AI分析｜自有内容关键词' => collect((array) ($analysis['owned_keywords'] ?? []))
                ->map(fn (mixed $item): string => is_array($item) ? trim((string) ($item['term'] ?? '')) : trim((string) $item)),
            'AI分析｜回答关键词' => collect((array) ($analysis['answer_keywords'] ?? []))
                ->map(fn (mixed $item): string => is_array($item) ? trim((string) ($item['term'] ?? '')) : trim((string) $item)),
        ];

        foreach ($groups as $name => $terms) {
            $terms = $terms->filter(fn (string $term): bool => mb_strlen($term) >= 2)
                ->unique(fn (string $term): string => mb_strtolower($term))->values();
            if ($terms->isEmpty()) {
                continue;
            }

            $library = KeywordLibrary::query()->firstOrCreate(
                ['name' => $name],
                ['description' => '由 AI 分析自动提取，可用于标题和内容生成', 'keyword_count' => 0],
            );
            foreach ($terms as $term) {
                Keyword::query()->firstOrCreate(
                    ['library_id' => $library->id, 'keyword' => $term],
                    ['used_count' => 0, 'usage_count' => 0],
                );
            }
            $library->forceFill(['keyword_count' => Keyword::query()->where('library_id', $library->id)->count()])->save();
        }
    }

    /**
     * @return Collection<int, AiGrowthProbe>
     */
    public function recent(int $limit = 8): Collection
    {
        if (! Schema::hasTable('ai_growth_probes')) {
            return collect();
        }

        return AiGrowthProbe::query()
            ->with(['visibilityRun.sources', 'visibilityRun.aiModel:id,name', 'visibilityRun.sourceProvider:id,name'])
            ->orderByDesc('id')
            ->limit(max(1, min(20, $limit)))
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function present(AiGrowthProbe $probe): array
    {
        $probe->loadMissing(['visibilityRun.sources', 'visibilityRun.aiModel:id,name', 'visibilityRun.sourceProvider:id,name']);
        $analysis = is_array($probe->analysis_json) ? $probe->analysis_json : [];
        $providerType = $probe->providerType();
        $analysis = $this->safeAnalysis($analysis);
        $results = array_values((array) ($analysis['results'] ?? []));
        if ($results === [] && $probe->visibilityRun instanceof AiVisibilityRun) {
            $results[] = $this->presentRun($probe->visibilityRun, $analysis);
        } elseif ($results === [] && ($probe->answer_excerpt || $analysis !== [])) {
            $results[] = [
                'uuid' => (string) $probe->uuid,
                'provider_type' => $providerType,
                'provider_label' => $probe->providerLabel(),
                'model_id' => '',
                'status' => (string) $probe->status,
                'answer_text' => (string) ($probe->answer_excerpt ?? ''),
                'sources' => (array) ($analysis['sources'] ?? []),
                'analysis' => $analysis,
                'completed_at' => $probe->completed_at?->toDateTimeString(),
            ];
        }
        foreach ($results as &$result) {
            $result['analysis'] = $this->safeAnalysis((array) ($result['analysis'] ?? []));
            $result['sources'] = $this->safeSources((array) ($result['sources'] ?? []));
        }
        unset($result);
        $preferred = collect($results)->firstWhere('uuid', $probe->visibilityRun?->uuid)
            ?? collect($results)->firstWhere('status', AiVisibilityRun::STATUS_COMPLETED)
            ?? [];
        $analysis['results'] = $results;

        return [
            'uuid' => (string) $probe->uuid,
            'question' => (string) $probe->question,
            'provider_type' => $providerType,
            'provider_label' => $probe->providerLabel(),
            'status' => (string) $probe->status,
            'verdict' => $probe->verdict,
            'brand_mentioned' => (bool) $probe->brand_mentioned,
            'owned_content_mentioned' => (bool) $probe->owned_content_mentioned,
            'answer_excerpt' => (string) ($probe->answer_excerpt ?? ''),
            'answer_text' => (string) ($preferred['answer_text'] ?? $probe->answer_excerpt ?? ''),
            'sources' => (array) ($preferred['sources'] ?? []),
            'results' => $results,
            'error_message' => (string) ($probe->error_message ?? ''),
            'analysis' => $analysis,
            'created_at' => $probe->created_at?->toDateTimeString(),
            'completed_at' => $probe->completed_at?->toDateTimeString(),
        ];
    }

    /**
     * @param  array<string, AiVisibilityRun>  $runs
     */
    private function preferredRun(array $runs): ?AiVisibilityRun
    {
        $items = collect($runs)->filter(fn (mixed $run): bool => $run instanceof AiVisibilityRun
            && $run->status === AiVisibilityRun::STATUS_COMPLETED)->values();
        $withAnswer = $items->first(fn (AiVisibilityRun $run): bool => trim((string) $run->answer_text) !== '');
        if ($withAnswer instanceof AiVisibilityRun) {
            return $withAnswer;
        }
        $withSources = $items->first(function (AiVisibilityRun $run): bool {
            $run->loadMissing('sources');

            return $run->sources->isNotEmpty();
        });
        if ($withSources instanceof AiVisibilityRun) {
            return $withSources;
        }

        return null;
    }

    private function presentRun(AiVisibilityRun $run, array $analysis = []): array
    {
        $run->loadMissing(['sources', 'aiModel:id,name', 'sourceProvider:id,name']);
        $providerLabel = trim((string) ($run->aiModel?->name ?: $run->sourceProvider?->name));

        return [
            'uuid' => (string) $run->uuid,
            'provider_type' => (string) $run->provider_type,
            'provider_label' => $providerLabel !== ''
                ? $providerLabel
                : __('admin.analytics.ai_visibility.providers.'.$run->provider_type),
            'model_id' => (string) ($run->model_id ?? ''),
            'status' => (string) $run->status,
            'answer_text' => (string) ($run->answer_text ?? ''),
            'error_message' => $run->error_message
                ? $this->collection->humanize(new RuntimeException((string) $run->error_message))
                : '',
            'latency_ms' => $run->latency_ms,
            'sources' => $this->analyzer->sourceRows($run),
            'analysis' => $analysis,
            'created_at' => $run->created_at?->toDateTimeString(),
            'started_at' => $run->started_at?->toDateTimeString(),
            'completed_at' => $run->completed_at?->toDateTimeString(),
        ];
    }

    private function safeSources(array $sources): array
    {
        return collect($sources)->filter(fn (mixed $source): bool => is_array($source))
            ->map(function (array $source): array {
                $source['url'] = $this->analyzer->webUrl((string) ($source['url'] ?? ''));

                return $source;
            })->values()->all();
    }

    private function safeAnalysis(array $analysis): array
    {
        if (array_key_exists('sources', $analysis)) {
            $analysis['sources'] = $this->safeSources((array) $analysis['sources']);
        }
        foreach ($analysis['competitors'] ?? [] as $index => $competitor) {
            $analysis['competitors'][$index]['posts'] = $this->safeSources((array) ($competitor['posts'] ?? []));
        }
        foreach ($analysis['owned_posts'] ?? [] as $index => $post) {
            $analysis['owned_posts'][$index]['remote_url'] = $this->analyzer->webUrl((string) ($post['remote_url'] ?? ''));
        }

        return $analysis;
    }
}
