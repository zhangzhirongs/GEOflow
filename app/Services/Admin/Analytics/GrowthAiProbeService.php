<?php

namespace App\Services\Admin\Analytics;

use App\Jobs\ProcessGrowthAiProbeJob;
use App\Models\Admin;
use App\Models\AiGrowthProbe;
use App\Models\AiVisibilityRun;
use Illuminate\Support\Collection;
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

        $payload = [
            'admin_id' => $admin->id,
            'question' => $question,
            'status' => AiGrowthProbe::STATUS_QUEUED,
        ];
        if (Schema::hasColumn('ai_growth_probes', 'provider_type')) {
            $payload['provider_type'] = $channel;
        }

        $probe = AiGrowthProbe::query()->create($payload);

        ProcessGrowthAiProbeJob::dispatch((int) $probe->id)->onQueue('geoflow');

        return $probe;
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

        try {
            $runs = $this->collection->collect($probe->question, $probe->providerType());
            $run = $this->preferredRun($runs);
            if (! $run instanceof AiVisibilityRun) {
                throw new RuntimeException(__('admin.analytics.overview.probe.errors.empty_answer'));
            }

            $run->loadMissing('sources');
            $analysis = $this->analyzer->analyze($probe->question, $run);
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
            $probe->forceFill([
                'status' => AiGrowthProbe::STATUS_FAILED,
                'error_message' => mb_substr($exception->getMessage(), 0, 1000, 'UTF-8'),
                'completed_at' => now(),
            ])->save();

            throw $exception;
        }

        return $probe->fresh() ?? $probe;
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
            ->orderByDesc('id')
            ->limit(max(1, min(20, $limit)))
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function present(AiGrowthProbe $probe): array
    {
        $analysis = is_array($probe->analysis_json) ? $probe->analysis_json : [];
        $providerType = $probe->providerType();

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
        $items = collect($runs)->filter(fn (mixed $run): bool => $run instanceof AiVisibilityRun)->values();
        $withSources = $items->first(function (AiVisibilityRun $run): bool {
            $run->loadMissing('sources');

            return $run->sources->isNotEmpty();
        });
        if ($withSources instanceof AiVisibilityRun) {
            return $withSources;
        }

        return $items->first(fn (AiVisibilityRun $run): bool => trim((string) $run->answer_text) !== '');
    }
}
