<?php

namespace App\Services\Admin\Analytics;

use App\Models\AiGrowthProbe;
use App\Models\AiModel;
use App\Models\AiSourceProvider;
use App\Models\AiVisibilityRun;
use App\Services\GeoFlow\AiVisibility\AiVisibilityConfigurationResolver;
use App\Services\GeoFlow\AiVisibility\AiVisibilityService;
use App\Services\GeoFlow\ArticleContentGenerationService;
use App\Support\GeoFlow\OpenAiRuntimeProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class GrowthAiAnswerCollector
{
    public function __construct(
        private readonly AiVisibilityService $visibility,
        private readonly AiVisibilityConfigurationResolver $configuration,
        private readonly ArticleContentGenerationService $articles,
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
        if ($allowed === [] || ! in_array($channel, $allowed, true)) {
            throw new RuntimeException(__('admin.analytics.overview.probe.errors.channel_unavailable'));
        }

        try {
            return $this->run($question, $channel);
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
        $chatModelId = AiGrowthProbe::chatModelId($channel);
        if ($chatModelId !== null) {
            return $this->collectChat($question, $this->chatModelById($chatModelId));
        }

        return match ($channel) {
            AiGrowthProbe::CHANNEL_SEARCH => $this->collectSearch($question, $search),
            AiGrowthProbe::CHANNEL_ARK => $this->collectArk($question, $ark),
            AiGrowthProbe::CHANNEL_DEEPSEEK => $this->collectDeepSeek($question, $deepSeek),
            AiGrowthProbe::CHANNEL_SEARCH_THEN_ANALYSIS => $this->collectSearchThenAnalysis($question, $search, $deepSeek),
            default => $this->collectAuto($question, $search, $ark, $deepSeek),
        };
    }

    /**
     * @return array<string, AiVisibilityRun>
     */
    private function collectAuto(string $question, ?AiSourceProvider $search, ?AiModel $ark, ?AiModel $deepSeek): array
    {
        $chat = $this->chatModels()->first();
        if ($chat instanceof AiModel) {
            return $this->collectChat($question, $chat);
        }
        if ($search instanceof AiSourceProvider && $deepSeek instanceof AiModel) {
            return $this->visibility->runDoubaoSearchThenDeepSeekAnalysis($search, $deepSeek, $question);
        }
        if ($search instanceof AiSourceProvider) {
            return $this->collectSearch($question, $search);
        }
        if ($deepSeek instanceof AiModel) {
            return $this->collectDeepSeek($question, $deepSeek);
        }
        if ($ark instanceof AiModel) {
            return $this->collectArk($question, $ark);
        }

        throw new RuntimeException(__('admin.analytics.overview.probe.errors.no_providers'));
    }

    /**
     * @return array<string, AiVisibilityRun>
     */
    private function collectChat(string $question, ?AiModel $model): array
    {
        if (! $model instanceof AiModel) {
            throw new RuntimeException(__('admin.analytics.overview.probe.errors.chat_missing'));
        }

        $prompt = __('admin.analytics.overview.probe.analysis_prompt', ['question' => $question]);
        $response = $this->articles->generate(
            $model,
            $prompt,
            __('admin.analytics.overview.probe.chat_instructions'),
        );
        $answer = OpenAiRuntimeProvider::normalizeGeneratedText((string) ($response->text ?? ''));
        if ($answer === '') {
            throw new RuntimeException(__('admin.analytics.overview.probe.errors.empty_answer'));
        }

        $run = AiVisibilityRun::query()->create([
            'keyword' => $question,
            'prompt' => $prompt,
            'provider_type' => AiVisibilityRun::PROVIDER_CHAT_COMPLETION,
            'provider_key' => 'chat',
            'ai_model_id' => (int) $model->id,
            'model_id' => (string) ($model->model_id ?? ''),
            'status' => AiVisibilityRun::STATUS_COMPLETED,
            'answer_text' => $answer,
            'locale' => 'zh_CN',
            'completed_at' => now(),
        ]);

        return ['chat_run' => $run];
    }

    /**
     * @return array<string, AiVisibilityRun>
     */
    private function collectSearch(string $question, ?AiSourceProvider $search): array
    {
        if (! $search instanceof AiSourceProvider) {
            throw new RuntimeException(__('admin.analytics.overview.probe.errors.search_missing'));
        }

        return [
            'search_run' => $this->visibility->runDoubaoSearchCustom($search, $question),
        ];
    }

    /**
     * @return array<string, AiVisibilityRun>
     */
    private function collectArk(string $question, ?AiModel $ark): array
    {
        if (! $ark instanceof AiModel) {
            throw new RuntimeException(__('admin.analytics.overview.probe.errors.ark_missing'));
        }

        return [
            'ark_run' => $this->visibility->runDoubaoArkResponses($ark, $question),
        ];
    }

    /**
     * @return array<string, AiVisibilityRun>
     */
    private function collectDeepSeek(string $question, ?AiModel $deepSeek): array
    {
        if (! $deepSeek instanceof AiModel) {
            throw new RuntimeException(__('admin.analytics.overview.probe.errors.deepseek_missing'));
        }

        return [
            'analysis_run' => $this->visibility->runDeepSeekAnalysis(
                $deepSeek,
                $question,
                __('admin.analytics.overview.probe.analysis_prompt', ['question' => $question]),
            ),
        ];
    }

    /**
     * @return array<string, AiVisibilityRun>
     */
    private function collectSearchThenAnalysis(string $question, ?AiSourceProvider $search, ?AiModel $deepSeek): array
    {
        if (! $search instanceof AiSourceProvider || ! $deepSeek instanceof AiModel) {
            throw new RuntimeException(__('admin.analytics.overview.probe.errors.search_then_analysis_missing'));
        }

        return $this->visibility->runDoubaoSearchThenDeepSeekAnalysis($search, $deepSeek, $question);
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
