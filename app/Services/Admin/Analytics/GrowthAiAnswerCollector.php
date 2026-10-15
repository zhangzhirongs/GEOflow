<?php

namespace App\Services\Admin\Analytics;

use App\Models\AiGrowthProbe;
use App\Models\AiModel;
use App\Models\AiSourceProvider;
use App\Models\AiVisibilityRun;
use App\Services\GeoFlow\AiVisibility\AiVisibilityConfigurationResolver;
use App\Services\GeoFlow\AiVisibility\AiVisibilityResultNormalizer;
use App\Services\GeoFlow\AiVisibility\AiVisibilityRunException;
use App\Services\GeoFlow\AiVisibility\AiVisibilityService;
use App\Services\GeoFlow\ArticleContentGenerationService;
use App\Support\GeoFlow\OpenAiRuntimeProvider;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class GrowthAiAnswerCollector
{
    public function __construct(
        private readonly AiVisibilityService $visibility,
        private readonly AiVisibilityConfigurationResolver $configuration,
        private readonly ArticleContentGenerationService $articles,
        private readonly AiVisibilityResultNormalizer $normalizer,
    ) {}

    /**
     * @return list<array{value: string, label: string}>
     */
    public function channels(): array
    {
        $status = $this->configuration->status();
        $models = $this->chatModels();
        $channels = [];

        if ($models->isNotEmpty() || ($status['configured'] ?? false)) {
            $channels[] = $this->channel(AiGrowthProbe::CHANNEL_AUTO);
            $channels[] = $this->channel(AiGrowthProbe::CHANNEL_ALL);
        }

        foreach ($models as $model) {
            $label = trim((string) $model->name);
            $channels[] = [
                'value' => AiGrowthProbe::chatChannel((int) $model->id),
                'label' => $label !== '' ? $label : (string) $model->model_id,
            ];
        }

        if (($status['doubao_search_configured'] ?? false) && ($status['deepseek_configured'] ?? false)) {
            $channels[] = $this->channel(AiGrowthProbe::CHANNEL_SEARCH_THEN_ANALYSIS);
        }
        if ($status['doubao_search_configured'] ?? false) {
            $channels[] = $this->channel(AiGrowthProbe::CHANNEL_SEARCH);
        }
        if ($status['deepseek_configured'] ?? false) {
            $channels[] = $this->channel(AiGrowthProbe::CHANNEL_DEEPSEEK);
        }
        if ($status['ark_configured'] ?? false) {
            $channels[] = $this->channel(AiGrowthProbe::CHANNEL_ARK);
        }

        return $channels;
    }

    /**
     * @return list<string>
     */
    public function comparisonChannels(): array
    {
        $ark = $this->configuration->arkModel();
        $deepSeek = $this->configuration->deepSeekModel();
        $channels = $this->chatModels()->map(function (AiModel $model) use ($ark, $deepSeek): string {
            return match ((int) $model->id) {
                $ark?->id => AiGrowthProbe::CHANNEL_ARK,
                $deepSeek?->id => AiGrowthProbe::CHANNEL_DEEPSEEK,
                default => AiGrowthProbe::chatChannel((int) $model->id),
            };
        })->all();

        if ($ark instanceof AiModel) {
            $channels[] = AiGrowthProbe::CHANNEL_ARK;
        }
        if ($deepSeek instanceof AiModel) {
            $channels[] = AiGrowthProbe::CHANNEL_DEEPSEEK;
        }
        if ($this->configuration->searchProvider() instanceof AiSourceProvider) {
            $channels[] = AiGrowthProbe::CHANNEL_SEARCH;
        }

        return array_values(array_unique($channels));
    }

    public function channelLabel(string $channel): string
    {
        $channel = $this->normalizeChannel($channel);
        foreach ($this->channels() as $item) {
            if ($item['value'] === $channel) {
                return $item['label'];
            }
        }

        if (AiGrowthProbe::isChatChannel($channel)) {
            return __('admin.analytics.overview.probe.channels.chat');
        }

        return __('admin.analytics.overview.probe.channels.'.$channel);
    }

    /**
     * @return array<string, AiVisibilityRun>
     */
    public function collect(string $question, string $channel = AiGrowthProbe::CHANNEL_AUTO): array
    {
        $question = trim($question);
        if ($question === '') {
            throw new RuntimeException(__('admin.analytics.overview.probe.errors.empty_question'));
        }

        $channel = $this->normalizeChannel($channel);
        $allowed = array_column($this->channels(), 'value');
        if ($allowed === [] || ! in_array($channel, $allowed, true) || $channel === AiGrowthProbe::CHANNEL_ALL) {
            throw new RuntimeException(__('admin.analytics.overview.probe.errors.channel_unavailable'));
        }

        try {
            return $this->run($question, $channel);
        } catch (QueryException|GrowthAiCollectionException $exception) {
            throw $exception;
        } catch (AiVisibilityRunException $exception) {
            throw new GrowthAiCollectionException(
                $this->humanize($exception),
                array_merge($exception->completedRuns, ['failed_'.$exception->run->uuid => $exception->run]),
                $exception,
            );
        } catch (Throwable $exception) {
            throw new RuntimeException($this->humanize($exception), previous: $exception);
        }
    }

    public function normalizeChannel(string $channel): string
    {
        $channel = trim($channel);

        return $channel !== '' ? $channel : AiGrowthProbe::CHANNEL_AUTO;
    }

    public function humanize(Throwable $exception): string
    {
        $message = trim($exception->getMessage());
        $blob = $message;
        $previous = $exception->getPrevious();
        if ($previous instanceof Throwable) {
            $blob .= ' '.$previous->getMessage();
        }

        if (str_contains($blob, 'ToolNotOpen') || str_contains(strtolower($blob), 'has not activated web search')) {
            return __('admin.analytics.overview.probe.errors.ark_web_search_closed');
        }

        if (preg_match('/HTTP\s+\d{3}/', $message) === 1 && str_contains($message, '{"error"')) {
            $jsonStart = strpos($message, '{');
            $decoded = is_int($jsonStart) ? json_decode(substr($message, $jsonStart), true) : null;
            $code = is_array($decoded) ? (string) data_get($decoded, 'error.code', '') : '';
            $detail = is_array($decoded) ? trim((string) data_get($decoded, 'error.message', '')) : '';
            $parts = array_values(array_filter([$code, $detail], fn (string $part): bool => $part !== ''));
            if ($parts !== []) {
                return __('admin.analytics.overview.probe.errors.model_request_failed', [
                    'detail' => implode('：', $parts),
                ]);
            }
        }

        return $message !== '' ? $message : __('admin.analytics.overview.probe.errors.empty_answer');
    }

    /**
     * @return array<string, AiVisibilityRun>
     */
    private function run(string $question, string $channel): array
    {
        $search = $this->configuration->searchProvider();
        $ark = $this->configuration->arkModel();
        $deepSeek = $this->configuration->deepSeekModel();
        $timeout = max(1, min(60, (int) config('geoflow.ai_visibility.http_timeout_seconds', 60)));
        $chatModelId = AiGrowthProbe::chatModelId($channel);
        if ($chatModelId !== null) {
            return $this->collectChat($question, $this->chatModelById($chatModelId), $timeout);
        }

        return match ($channel) {
            AiGrowthProbe::CHANNEL_SEARCH => $this->collectSearch($question, $search, $timeout),
            AiGrowthProbe::CHANNEL_ARK => $this->collectArk($question, $ark, $timeout),
            AiGrowthProbe::CHANNEL_DEEPSEEK => $this->collectDeepSeek($question, $deepSeek, $timeout),
            AiGrowthProbe::CHANNEL_SEARCH_THEN_ANALYSIS => $this->collectSearchThenAnalysis($question, $search, $deepSeek, $timeout),
            default => $this->collectAuto($question, $search, $ark, $deepSeek),
        };
    }

    /**
     * @return array<string, AiVisibilityRun>
     */
    private function collectAuto(string $question, ?AiSourceProvider $search, ?AiModel $ark, ?AiModel $deepSeek): array
    {
        $attempts = [];
        foreach ($this->chatModels() as $chat) {
            $attempts[] = [
                trim((string) $chat->name) ?: (string) $chat->model_id,
                fn (int $timeout): array => $this->collectChat($question, $chat, $timeout),
            ];
        }
        if ($search instanceof AiSourceProvider && $deepSeek instanceof AiModel) {
            $attempts[] = [
                $this->channel(AiGrowthProbe::CHANNEL_SEARCH_THEN_ANALYSIS)['label'],
                fn (int $timeout): array => $this->collectSearchThenAnalysis($question, $search, $deepSeek, $timeout),
            ];
        }
        if ($search instanceof AiSourceProvider) {
            $attempts[] = [
                $this->channel(AiGrowthProbe::CHANNEL_SEARCH)['label'],
                fn (int $timeout): array => $this->collectSearch($question, $search, $timeout),
            ];
        }
        if ($deepSeek instanceof AiModel) {
            $attempts[] = [
                $this->channel(AiGrowthProbe::CHANNEL_DEEPSEEK)['label'],
                fn (int $timeout): array => $this->collectDeepSeek($question, $deepSeek, $timeout),
            ];
        }
        if ($ark instanceof AiModel) {
            $attempts[] = [
                $this->channel(AiGrowthProbe::CHANNEL_ARK)['label'],
                fn (int $timeout): array => $this->collectArk($question, $ark, $timeout),
            ];
        }

        $deadline = hrtime(true) + 150_000_000_000;
        $errors = [];
        $failedRuns = [];
        foreach ($attempts as [$label, $attempt]) {
            $remaining = (int) floor(($deadline - hrtime(true)) / 1_000_000_000);
            if ($remaining < 2) {
                $errors[] = __('admin.analytics.overview.probe.errors.time_budget');
                break;
            }

            try {
                $runs = $attempt(max(1, min(
                    60,
                    $remaining,
                    (int) config('geoflow.ai_visibility.http_timeout_seconds', 60),
                )));
                $usable = collect($runs)->contains(function (AiVisibilityRun $run): bool {
                    $run->loadMissing('sources');

                    return $run->status === AiVisibilityRun::STATUS_COMPLETED
                        && (trim((string) $run->answer_text) !== '' || $run->sources->isNotEmpty());
                });
                if (! $usable) {
                    $errors[] = $label.': '.__('admin.analytics.overview.probe.errors.empty_answer');
                    foreach ($runs as $run) {
                        $failedRuns['empty_'.$run->uuid] = $run;
                    }

                    continue;
                }

                return array_merge($failedRuns, $runs);
            } catch (QueryException $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                $errors[] = $label.': '.$this->humanize($exception);
                if ($exception instanceof AiVisibilityRunException) {
                    $failedRuns['failed_'.$exception->run->uuid] = $exception->run;
                }
            }
        }

        throw new GrowthAiCollectionException(
            $errors === []
                ? __('admin.analytics.overview.probe.errors.no_providers')
                : __('admin.analytics.overview.probe.errors.all_failed', ['detail' => implode(' | ', $errors)]),
            $failedRuns,
        );
    }

    /**
     * @return array<string, AiVisibilityRun>
     */
    private function collectChat(string $question, ?AiModel $model, int $timeout): array
    {
        if (! $model instanceof AiModel) {
            throw new RuntimeException(__('admin.analytics.overview.probe.errors.chat_missing'));
        }

        $prompt = __('admin.analytics.overview.probe.analysis_prompt', ['question' => $question]);
        $run = AiVisibilityRun::query()->create([
            'keyword' => mb_substr($question, 0, 255, 'UTF-8'),
            'prompt' => $prompt,
            'provider_type' => AiVisibilityRun::PROVIDER_CHAT_COMPLETION,
            'provider_key' => 'chat',
            'ai_model_id' => (int) $model->id,
            'model_id' => (string) ($model->model_id ?? ''),
            'status' => AiVisibilityRun::STATUS_RUNNING,
            'locale' => 'zh_CN',
            'started_at' => now(),
        ]);

        $startedAt = hrtime(true);
        try {
            $response = $this->articles->generate(
                $model,
                $prompt,
                __('admin.analytics.overview.probe.chat_instructions'),
                $timeout,
            );
            $answer = OpenAiRuntimeProvider::normalizeGeneratedText($response->text);
            if ($answer === '') {
                throw new RuntimeException(__('admin.analytics.overview.probe.errors.empty_answer'));
            }

            $sources = $this->normalizer->answerSources($answer, $response->meta->citations->all());
            DB::transaction(function () use ($run, $response, $answer, $sources, $startedAt): void {
                $run->update([
                    'status' => AiVisibilityRun::STATUS_COMPLETED,
                    'answer_text' => $answer,
                    'usage_json' => $response->usage->toArray(),
                    'latency_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
                    'completed_at' => now(),
                ]);
                foreach ($sources as $source) {
                    $run->sources()->create($source->toDatabaseAttributes());
                }
            });
        } catch (Throwable $exception) {
            $run->update([
                'status' => AiVisibilityRun::STATUS_FAILED,
                'error_message' => mb_substr($this->humanize($exception), 0, 4000, 'UTF-8'),
                'completed_at' => now(),
            ]);

            throw $exception instanceof QueryException ? $exception : new AiVisibilityRunException($run, $exception);
        }

        return ['chat_run' => $run];
    }

    /**
     * @return array<string, AiVisibilityRun>
     */
    private function collectSearch(string $question, ?AiSourceProvider $search, int $timeout): array
    {
        if (! $search instanceof AiSourceProvider) {
            throw new RuntimeException(__('admin.analytics.overview.probe.errors.search_missing'));
        }

        return [
            'search_run' => $this->visibility->runDoubaoSearchCustom($search, $question, ['timeout' => $timeout]),
        ];
    }

    /**
     * @return array<string, AiVisibilityRun>
     */
    private function collectArk(string $question, ?AiModel $ark, int $timeout): array
    {
        if (! $ark instanceof AiModel) {
            throw new RuntimeException(__('admin.analytics.overview.probe.errors.ark_missing'));
        }

        return [
            'ark_run' => $this->visibility->runDoubaoArkResponses($ark, $question, options: ['timeout' => $timeout]),
        ];
    }

    /**
     * @return array<string, AiVisibilityRun>
     */
    private function collectDeepSeek(string $question, ?AiModel $deepSeek, int $timeout): array
    {
        if (! $deepSeek instanceof AiModel) {
            throw new RuntimeException(__('admin.analytics.overview.probe.errors.deepseek_missing'));
        }

        return [
            'analysis_run' => $this->visibility->runDeepSeekAnalysis(
                $deepSeek,
                $question,
                __('admin.analytics.overview.probe.analysis_prompt', ['question' => $question]),
                options: ['timeout' => $timeout],
            ),
        ];
    }

    /**
     * @return array<string, AiVisibilityRun>
     */
    private function collectSearchThenAnalysis(string $question, ?AiSourceProvider $search, ?AiModel $deepSeek, int $timeout): array
    {
        if (! $search instanceof AiSourceProvider || ! $deepSeek instanceof AiModel) {
            throw new RuntimeException(__('admin.analytics.overview.probe.errors.search_then_analysis_missing'));
        }

        try {
            $options = ['timeout' => max(1, intdiv($timeout, 2))];

            return $this->visibility->runDoubaoSearchThenDeepSeekAnalysis(
                $search,
                $deepSeek,
                $question,
                __('admin.analytics.overview.probe.analysis_prompt', ['question' => $question]),
                $options,
                $options,
            );
        } catch (AiVisibilityRunException $exception) {
            if ($exception->completedRuns === []) {
                throw $exception;
            }

            return array_merge($exception->completedRuns, ['analysis_run' => $exception->run]);
        }
    }

    /**
     * @return Collection<int, AiModel>
     */
    private function chatModels(): Collection
    {
        if (! Schema::hasTable('ai_models')) {
            return collect();
        }

        return AiModel::query()
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereNull('model_type')
                    ->orWhere('model_type', '')
                    ->orWhere('model_type', 'chat');
            })
            ->orderBy('failover_priority')
            ->orderBy('id')
            ->get()
            ->filter(function (AiModel $model): bool {
                return trim((string) ($model->getRawOriginal('api_key') ?? '')) !== ''
                    && trim((string) ($model->model_id ?? '')) !== ''
                    && OpenAiRuntimeProvider::resolveChatBaseUrl((string) ($model->api_url ?? '')) !== '';
            })
            ->values();
    }

    private function chatModelById(int $modelId): ?AiModel
    {
        return $this->chatModels()->first(
            fn (AiModel $model): bool => (int) $model->id === $modelId
        );
    }

    /**
     * @return array{value: string, label: string}
     */
    private function channel(string $value): array
    {
        return [
            'value' => $value,
            'label' => __('admin.analytics.overview.probe.channels.'.$value),
        ];
    }
}
