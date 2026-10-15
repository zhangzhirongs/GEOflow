<?php

namespace Tests\Feature;

use App\Ai\Agents\MarkdownContentWriterAgent;
use App\Models\AiModel;
use App\Models\AiSourceProvider;
use App\Models\AiVisibilityRun;
use App\Services\GeoFlow\AiVisibility\AiVisibilityRunException;
use App\Services\GeoFlow\AiVisibility\AiVisibilityService;
use App\Services\GeoFlow\AiVisibility\AiVisibilitySourceData;
use App\Support\GeoFlow\ApiKeyCrypto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\UrlCitation;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\TextResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class AiVisibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        MarkdownContentWriterAgent::fake([])->preventStrayPrompts();
    }

    public function test_it_persists_doubao_search_custom_sources(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://open.feedcoopapi.com/search_api/web_search' => Http::response([
                'LogId' => 'log_search_1',
                'Result' => [
                    'WebResults' => [
                        [
                            'Title' => 'GEOFlow 介绍',
                            'SiteName' => 'GEOFlow',
                            'Url' => 'https://example.com/geoflow',
                            'Snippet' => 'GEOFlow 帮助内容进入 AI 回答。',
                            'Summary' => 'GEOFlow 的 AI 可见性能力。',
                            'Content' => '完整搜索结果内容',
                            'RankScore' => 0.91,
                        ],
                    ],
                ],
            ]),
        ]);

        $provider = $this->createSearchProvider();

        $run = app(AiVisibilityService::class)->runDoubaoSearchCustom($provider, 'GEOFlow AI 可见性', [
            'count' => 2,
        ]);

        $this->assertSame(AiVisibilityRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(AiVisibilityRun::PROVIDER_DOUBAO_SEARCH_CUSTOM, $run->provider_type);
        $this->assertSame('GEOFlow AI 可见性', $run->keyword);
        $this->assertSame(1, $run->sources()->count());
        $this->assertSame(1, (int) $provider->fresh()->used_today);
        $this->assertDatabaseHas('ai_visibility_sources', [
            'ai_visibility_run_id' => (int) $run->id,
            'title' => 'GEOFlow 介绍',
            'domain' => 'example.com',
        ]);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://open.feedcoopapi.com/search_api/web_search'
            && $request->hasHeader('Authorization', 'Bearer test-search-key')
            && $request['Query'] === 'GEOFlow AI 可见性'
            && $request['Count'] === 2
            && ($request['Filter']['NeedContent'] ?? null) === true);
    }

    public function test_it_uses_source_provider_metadata_as_default_search_options(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://open.feedcoopapi.com/search_api/web_search' => Http::response([
                'LogId' => 'log_search_metadata',
                'Result' => [
                    'WebResults' => [],
                ],
            ]),
        ]);

        $provider = $this->createSearchProvider([
            'metadata_json' => [
                'count' => 4,
                'search_type' => 'web',
                'need_summary' => false,
                'need_content' => false,
                'need_url' => true,
                'content_formats' => 'Text',
                'auth_info_level' => 'high',
                'sites' => ['geoflow.example'],
                'block_hosts' => ['blocked.example'],
            ],
        ]);

        $run = app(AiVisibilityService::class)->runDoubaoSearchCustom($provider, 'GEOFlow');

        $this->assertSame(AiVisibilityRun::STATUS_COMPLETED, $run->status);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://open.feedcoopapi.com/search_api/web_search'
            && $request['Query'] === 'GEOFlow'
            && $request['Count'] === 4
            && ($request['NeedSummary'] ?? null) === false
            && ($request['AuthInfoLevel'] ?? null) === 'high'
            && ($request['Filter']['NeedContent'] ?? null) === false
            && ($request['Filter']['NeedUrl'] ?? null) === true
            && ($request['Filter']['ContentFormats'] ?? null) === 'Text'
            && ($request['Filter']['Sites'] ?? null) === ['geoflow.example']
            && ($request['Filter']['BlockHosts'] ?? null) === ['blocked.example']);
    }

    public function test_it_persists_doubao_ark_responses_answer_and_sources(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://ark.cn-beijing.volces.com/api/v3/responses' => Http::response([
                'id' => 'resp_ark_1',
                'output' => [
                    [
                        'type' => 'web_search_call',
                        'id' => 'ws_ark_1',
                        'status' => 'completed',
                    ],
                    [
                        'type' => 'message',
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => '豆包返回的可见性回答。',
                                'annotations' => [
                                    [
                                        'title' => 'GEOFlow source',
                                        'url' => 'https://example.com/source',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'usage' => [
                    'input_tokens' => 21,
                    'output_tokens' => 34,
                ],
            ]),
        ]);

        $model = $this->createAiModel([
            'name' => '火山方舟测试模型',
            'model_id' => 'doubao-seed-2-0-lite-260428',
            'api_url' => 'https://ark.cn-beijing.volces.com/api/v3',
            'max_tokens' => 2048,
        ]);

        $run = app(AiVisibilityService::class)->runDoubaoArkResponses($model, 'GEOFlow', '请搜索 GEOFlow');

        $this->assertSame(AiVisibilityRun::STATUS_COMPLETED, $run->status);
        $this->assertSame('豆包返回的可见性回答。', $run->answer_text);
        $this->assertSame(1, $run->sources()->count());
        $this->assertSame(1, (int) $model->fresh()->used_today);
        $this->assertDatabaseHas('ai_visibility_sources', [
            'ai_visibility_run_id' => (int) $run->id,
            'title' => 'GEOFlow source',
            'domain' => 'example.com',
        ]);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://ark.cn-beijing.volces.com/api/v3/responses'
            && $request->hasHeader('Authorization', 'Bearer test-model-key')
            && $request['model'] === 'doubao-seed-2-0-lite-260428'
            && $request['max_output_tokens'] === 2048
            && ($request['tools'][0]['type'] ?? null) === 'web_search'
            && ($request['input'][0]['content'][0]['text'] ?? null) === '请搜索 GEOFlow');
    }

    public function test_it_marks_doubao_search_custom_run_failed_with_provider_body(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://open.feedcoopapi.com/search_api/web_search' => Http::response([
                'error' => ['message' => 'bad api key'],
            ], 401),
        ]);

        $provider = $this->createSearchProvider();

        try {
            app(AiVisibilityService::class)->runDoubaoSearchCustom($provider, 'GEOFlow');
            $this->fail('Expected Doubao Search Custom failure to throw.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('HTTP 401', $exception->getMessage());
            $this->assertStringContainsString('bad api key', $exception->getMessage());
        }

        $run = AiVisibilityRun::query()->firstOrFail();
        $this->assertSame(AiVisibilityRun::STATUS_FAILED, $run->status);
        $this->assertStringContainsString('HTTP 401', (string) $run->error_message);
        $this->assertStringContainsString('bad api key', (string) $run->error_message);
        $this->assertSame(0, (int) $provider->fresh()->used_today);
    }

    public function test_it_does_not_call_doubao_search_custom_when_provider_limit_is_exhausted(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $provider = $this->createSearchProvider([
            'daily_limit' => 1,
            'used_today' => 1,
            'usage_date' => now()->toDateString(),
        ]);

        try {
            app(AiVisibilityService::class)->runDoubaoSearchCustom($provider, 'GEOFlow');
            $this->fail('Expected exhausted source provider to throw.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('已达到每日调用上限', $exception->getMessage());
        }

        $run = AiVisibilityRun::query()->firstOrFail();
        $this->assertSame(AiVisibilityRun::STATUS_FAILED, $run->status);
        $this->assertStringContainsString('已达到每日调用上限', (string) $run->error_message);
        Http::assertNothingSent();
    }

    public function test_it_resets_yesterdays_provider_usage_before_calling_search(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://open.feedcoopapi.com/search_api/web_search' => Http::response([
                'LogId' => 'log_new_day',
                'Result' => ['WebResults' => []],
            ]),
        ]);

        $provider = $this->createSearchProvider([
            'daily_limit' => 1,
            'used_today' => 1,
            'usage_date' => now()->subDay()->toDateString(),
        ]);

        $run = app(AiVisibilityService::class)->runDoubaoSearchCustom($provider, 'GEOFlow');

        $this->assertSame(AiVisibilityRun::STATUS_COMPLETED, $run->status);
        $provider->refresh();
        $this->assertSame(now()->toDateString(), $provider->usage_date?->toDateString());
        $this->assertSame(1, (int) $provider->used_today);
    }

    public function test_it_does_not_call_ark_responses_when_model_is_inactive(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $model = $this->createAiModel([
            'status' => 'inactive',
            'api_url' => 'https://ark.cn-beijing.volces.com/api/v3',
            'model_id' => 'doubao-seed-2-0-lite-260428',
        ]);

        try {
            app(AiVisibilityService::class)->runDoubaoArkResponses($model, 'GEOFlow');
            $this->fail('Expected inactive model to throw.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('不可用或已停用', $exception->getMessage());
        }

        $run = AiVisibilityRun::query()->firstOrFail();
        $this->assertSame(AiVisibilityRun::STATUS_FAILED, $run->status);
        $this->assertStringContainsString('不可用或已停用', (string) $run->error_message);
        Http::assertNothingSent();
    }

    public function test_it_persists_deepseek_analysis_usage_from_ai_sdk_response(): void
    {
        MarkdownContentWriterAgent::fake(['分析完成'])->preventStrayPrompts();

        $model = $this->createAiModel();

        $run = app(AiVisibilityService::class)->runDeepSeekAnalysis($model, 'GEOFlow', '请分析 GEOFlow 的 AI 可见性');

        $this->assertSame(AiVisibilityRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(AiVisibilityRun::PROVIDER_DEEPSEEK_ANALYSIS, $run->provider_type);
        $this->assertSame('分析完成', $run->answer_text);
        $this->assertSame([
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'cache_write_input_tokens' => 0,
            'cache_read_input_tokens' => 0,
            'reasoning_tokens' => 0,
        ], $run->usage_json);
        $this->assertSame(1, (int) $model->fresh()->used_today);
    }

    public function test_it_uses_the_models_max_tokens_for_deepseek_analysis_by_default(): void
    {
        MarkdownContentWriterAgent::fake(['分析完成'])->preventStrayPrompts();

        $model = $this->createAiModel(['max_tokens' => 2048]);

        app(AiVisibilityService::class)->runDeepSeekAnalysis($model, 'GEOFlow', '请分析 GEOFlow 的 AI 可见性');

        MarkdownContentWriterAgent::assertPrompted(
            fn ($prompt): bool => $prompt->agent instanceof MarkdownContentWriterAgent
                && $prompt->agent->maxTokens === 2048,
        );
    }

    #[DataProvider('questionLengths')]
    public function test_long_search_questions_keep_the_full_outbound_query_and_bound_the_stored_keyword(int $length): void
    {
        Http::fake([
            'https://open.feedcoopapi.com/search_api/web_search' => Http::response([
                'Result' => ['WebResults' => []],
            ]),
        ]);
        $question = str_repeat("\u{95ee}", $length - 1)."\u{7ec8}";

        $run = app(AiVisibilityService::class)->runDoubaoSearchCustom($this->createSearchProvider(), $question);
        $run->refresh();

        $this->assertSame(AiVisibilityRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(min(255, $length), mb_strlen($run->keyword, 'UTF-8'));
        $this->assertSame(mb_substr($question, 0, 255, 'UTF-8'), $run->keyword);
        $this->assertTrue(mb_check_encoding($run->keyword, 'UTF-8'));
        $this->assertSame($question, $run->prompt);
        $this->assertSame($question, $run->raw_request_json['payload']['Query']);
        Http::assertSent(fn (Request $request): bool => $request['Query'] === $question);
        Http::assertSentCount(1);
    }

    #[DataProvider('questionLengths')]
    public function test_long_ark_questions_keep_the_full_default_outbound_prompt_and_bound_the_stored_keyword(int $length): void
    {
        Http::fake([
            'https://ark.cn-beijing.volces.com/api/v3/responses' => Http::response([
                'output_text' => 'Visibility answer.',
            ]),
        ]);
        $question = str_repeat("\u{95ee}", $length - 1)."\u{7ec8}";
        $model = $this->createAiModel([
            'model_id' => 'doubao-seed-2-0-lite-260428',
            'api_url' => 'https://ark.cn-beijing.volces.com/api/v3',
        ]);

        $run = app(AiVisibilityService::class)->runDoubaoArkResponses($model, $question);
        $run->refresh();

        $this->assertSame(AiVisibilityRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(min(255, $length), mb_strlen($run->keyword, 'UTF-8'));
        $this->assertSame(mb_substr($question, 0, 255, 'UTF-8'), $run->keyword);
        $this->assertStringContainsString($question, $run->prompt);
        $this->assertSame($run->prompt, $run->raw_request_json['payload']['input'][0]['content'][0]['text']);
        Http::assertSent(fn (Request $request): bool => $request['input'][0]['content'][0]['text'] === $run->prompt
            && str_contains($request['input'][0]['content'][0]['text'], $question));
        Http::assertSentCount(1);
    }

    #[DataProvider('questionLengths')]
    public function test_long_deepseek_questions_keep_the_full_sdk_prompt_and_bound_the_stored_keyword(int $length): void
    {
        MarkdownContentWriterAgent::fake(['Visibility analysis.'])->preventStrayPrompts();
        $question = str_repeat("\u{95ee}", $length - 1)."\u{7ec8}";

        $run = app(AiVisibilityService::class)->runDeepSeekAnalysis($this->createAiModel(), $question, $question);
        $run->refresh();

        $this->assertSame(AiVisibilityRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(min(255, $length), mb_strlen($run->keyword, 'UTF-8'));
        $this->assertSame(mb_substr($question, 0, 255, 'UTF-8'), $run->keyword);
        $this->assertSame($question, $run->prompt);
        $this->assertSame($question, $run->raw_request_json['prompt']);
        MarkdownContentWriterAgent::assertPrompted(
            fn (AgentPrompt $prompt): bool => $prompt->prompt === $question,
        );
        Http::assertNothingSent();
    }

    public static function questionLengths(): array
    {
        return [
            '255 characters' => [255],
            '256 characters' => [256],
            '2000 characters' => [2000],
        ];
    }

    public function test_combined_analysis_failure_carries_the_completed_search_and_releases_only_the_failed_model_quota(): void
    {
        Http::fake([
            'https://open.feedcoopapi.com/search_api/web_search' => Http::response([
                'LogId' => 'completed-search',
                'Result' => [
                    'WebResults' => [[
                        'Title' => 'Search evidence',
                        'Url' => 'https://example.com/evidence',
                        'SiteName' => 'Example',
                        'Summary' => 'Evidence available for analysis.',
                    ]],
                ],
            ]),
        ]);
        $provider = $this->createSearchProvider([
            'daily_limit' => 10,
            'used_today' => 2,
            'total_used' => 5,
            'usage_date' => now()->toDateString(),
        ]);
        $model = $this->createAiModel([
            'daily_limit' => 10,
            'used_today' => 3,
            'total_used' => 7,
            'usage_date' => now()->toDateString(),
        ]);
        MarkdownContentWriterAgent::fake([function () use ($model): string {
            $this->assertSame(4, (int) $model->fresh()->used_today);

            return " \n\t ";
        }])->preventStrayPrompts();

        try {
            app(AiVisibilityService::class)->runDoubaoSearchThenDeepSeekAnalysis($provider, $model, 'GEOFlow');
            $this->fail('Expected the empty analysis to throw.');
        } catch (AiVisibilityRunException $exception) {
            $this->assertStringContainsString('返回空内容', $exception->getMessage());
            $this->assertSame(AiVisibilityRun::STATUS_FAILED, $exception->run->status);
            $this->assertSame(AiVisibilityRun::PROVIDER_DEEPSEEK_ANALYSIS, $exception->run->provider_type);
            $this->assertSame($exception->getMessage(), $exception->run->error_message);
            $this->assertNotNull($exception->run->completed_at);
            $this->assertNull($exception->run->answer_text);
            $this->assertSame(0, $exception->run->sources()->count());
            $this->assertInstanceOf(AiVisibilityRunException::class, $exception->getPrevious());
            $this->assertSame(['search_run'], array_keys($exception->completedRuns));

            $searchRun = $exception->completedRuns['search_run'];
            $this->assertSame(AiVisibilityRun::STATUS_COMPLETED, $searchRun->status);
            $this->assertSame(AiVisibilityRun::PROVIDER_DOUBAO_SEARCH_CUSTOM, $searchRun->provider_type);
            $this->assertTrue($searchRun->relationLoaded('sources'));
            $this->assertCount(1, $searchRun->sources);
            $this->assertSame('https://example.com/evidence', $searchRun->sources[0]->url);
            $this->assertSame('web_search_result', $searchRun->sources[0]->source_type);
            $this->assertDatabaseHas('ai_visibility_runs', [
                'id' => $searchRun->id,
                'status' => AiVisibilityRun::STATUS_COMPLETED,
            ]);
            $this->assertDatabaseHas('ai_visibility_runs', [
                'id' => $exception->run->id,
                'status' => AiVisibilityRun::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
            ]);
        }

        $this->assertDatabaseCount('ai_visibility_runs', 2);
        $this->assertSame(3, (int) $provider->fresh()->used_today);
        $this->assertSame(6, (int) $provider->fresh()->total_used);
        $this->assertSame(3, (int) $model->fresh()->used_today);
        $this->assertSame(7, (int) $model->fresh()->total_used);
        MarkdownContentWriterAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('https://example.com/evidence'));
        Http::assertSentCount(1);
    }

    public function test_deepseek_sdk_failure_carries_the_failed_run_and_releases_its_reservation(): void
    {
        $model = $this->createAiModel([
            'used_today' => 2,
            'total_used' => 5,
            'usage_date' => now()->toDateString(),
        ]);
        MarkdownContentWriterAgent::fake([function () use ($model): never {
            $this->assertSame(3, (int) $model->fresh()->used_today);

            throw new RuntimeException('SDK transport failed');
        }])->preventStrayPrompts();

        try {
            app(AiVisibilityService::class)->runDeepSeekAnalysis($model, 'GEOFlow', 'Analyze GEOFlow.');
            $this->fail('Expected the SDK failure to throw.');
        } catch (AiVisibilityRunException $exception) {
            $this->assertSame([], $exception->completedRuns);
            $this->assertSame(AiVisibilityRun::STATUS_FAILED, $exception->run->fresh()->status);
            $this->assertStringContainsString('SDK transport failed', $exception->getMessage());
            $this->assertSame($exception->getMessage(), $exception->run->error_message);
            $this->assertNotNull($exception->run->completed_at);
        }

        $this->assertSame(2, (int) $model->fresh()->used_today);
        $this->assertSame(5, (int) $model->fresh()->total_used);
        $this->assertDatabaseCount('ai_visibility_runs', 1);
        Http::assertNothingSent();
    }

    public function test_deepseek_persists_native_citations_and_answer_links_alongside_search_context(): void
    {
        $citation = new UrlCitation('https://www.example.com/native', 'Native evidence', 7, 18);
        $answer = <<<'MARKDOWN'
See [answer label](https://www.example.com/native) and [Guide](https://guide.example.com/details).
<https://example.com/autolink>
[Repeated guide](https://guide.example.com/details)
[Search context](https://search.example.com/context)
[Unsafe](javascript:alert%281%29)
MARKDOWN;
        $usage = new Usage(promptTokens: 42, completionTokens: 14, cacheWriteInputTokens: 3, cacheReadInputTokens: 2, reasoningTokens: 1);
        MarkdownContentWriterAgent::fake([
            new TextResponse($answer, $usage, new Meta(
                provider: 'deepseek',
                model: 'deepseek-v4-flash',
                citations: collect([$citation]),
            )),
        ])->preventStrayPrompts();
        $context = new AiVisibilitySourceData(
            sourceType: 'web_search_result',
            citationKey: 'S1',
            title: 'External search context',
            url: 'https://search.example.com/context',
            domain: 'search.example.com',
            siteName: 'Search evidence',
            metadata: ['provider_trace' => 'search-123'],
        );
        $model = $this->createAiModel();

        $run = app(AiVisibilityService::class)->runDeepSeekAnalysis($model, 'GEOFlow', 'Analyze GEOFlow.', [$context]);
        $run->refresh()->load('sources');
        $sources = $run->sources->keyBy('url');

        $this->assertSame(AiVisibilityRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(trim($answer), $run->answer_text);
        $this->assertSame($usage->toArray(), $run->usage_json);
        $this->assertSame(4, $run->analysis_json['source_count']);
        $this->assertCount(4, $sources);
        $this->assertSame('native_annotation', $sources[$citation->url]->source_type);
        $this->assertSame('Native evidence', $sources[$citation->url]->title);
        $this->assertSame('example.com', $sources[$citation->url]->domain);
        $this->assertSame($citation->toArray(), $sources[$citation->url]->metadata_json);
        $this->assertSame('answer_link', $sources['https://guide.example.com/details']->source_type);
        $this->assertSame('Guide', $sources['https://guide.example.com/details']->title);
        $this->assertSame('guide.example.com', $sources['https://guide.example.com/details']->site_name);
        $this->assertSame('answer_link', $sources['https://example.com/autolink']->source_type);
        $this->assertSame('web_search_result', $sources[$context->url]->source_type);
        $this->assertSame($context->title, $sources[$context->url]->title);
        $this->assertSame($context->metadata, $sources[$context->url]->metadata_json);
        $this->assertSame(1, (int) $model->fresh()->used_today);
        $this->assertSame(1, (int) $model->fresh()->total_used);
        Http::assertNothingSent();
    }

    public function test_search_persists_bounded_unicode_source_fields_with_original_metadata(): void
    {
        $title = str_repeat("\u{6765}", 501);
        $siteName = str_repeat("\u{7ad9}", 256);
        $authority = str_repeat("\u{6743}", 81);
        Http::fake([
            'https://open.feedcoopapi.com/search_api/web_search' => Http::response([
                'Result' => [
                    'WebResults' => [[
                        'Title' => $title,
                        'SiteName' => $siteName,
                        'AuthInfoLevel' => $authority,
                        'Url' => 'https://example.com/long-source',
                        'SourceType' => 'article',
                        'Host' => 'example.com',
                        'RankScore' => 0.9,
                    ]],
                ],
            ]),
        ]);

        $run = app(AiVisibilityService::class)->runDoubaoSearchCustom($this->createSearchProvider(), 'GEOFlow');
        $source = $run->sources()->sole();

        $this->assertSame(AiVisibilityRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(str_repeat("\u{6765}", 500), $source->title);
        $this->assertSame(str_repeat("\u{7ad9}", 255), $source->site_name);
        $this->assertSame(str_repeat("\u{6743}", 80), $source->authority_level);
        $this->assertSame([
            'raw_rank_score' => 0.9,
            'source_type' => 'article',
            'host' => 'example.com',
            'original_fields' => [
                'title' => $title,
                'site_name' => $siteName,
                'authority_level' => $authority,
            ],
        ], $source->metadata_json);
        $this->assertSame($title, $run->raw_response_json['Result']['WebResults'][0]['Title']);
    }

    #[DataProvider('analysisTimeouts')]
    public function test_deepseek_forwards_the_explicit_timeout_to_the_sdk(int $timeout, int $expected): void
    {
        MarkdownContentWriterAgent::fake(['Visibility analysis.'])->preventStrayPrompts();

        app(AiVisibilityService::class)->runDeepSeekAnalysis(
            $this->createAiModel(),
            'GEOFlow',
            'Analyze GEOFlow.',
            options: ['timeout' => $timeout],
        );

        MarkdownContentWriterAgent::assertPrompted(
            fn (AgentPrompt $prompt): bool => $prompt->prompt === 'Analyze GEOFlow.'
                && $prompt->timeout === $expected,
        );
        Http::assertNothingSent();
    }

    public static function analysisTimeouts(): array
    {
        return [
            'explicit timeout' => [37, 37],
            'minimum one second' => [0, 1],
        ];
    }

    public function test_combined_analysis_forwards_independent_search_and_sdk_timeouts(): void
    {
        $httpOptions = null;
        Http::fake([
            'https://open.feedcoopapi.com/search_api/web_search' => function (Request $request, array $options) use (&$httpOptions) {
                $httpOptions = $options;

                return Http::response(['Result' => ['WebResults' => []]]);
            },
        ]);
        MarkdownContentWriterAgent::fake(['Visibility analysis.'])->preventStrayPrompts();

        $runs = app(AiVisibilityService::class)->runDoubaoSearchThenDeepSeekAnalysis(
            $this->createSearchProvider(),
            $this->createAiModel(),
            'GEOFlow',
            analysisPrompt: 'Analyze GEOFlow.',
            searchOptions: ['timeout' => 2],
            analysisOptions: ['timeout' => 23],
        );

        $this->assertSame(AiVisibilityRun::STATUS_COMPLETED, $runs['search_run']->status);
        $this->assertSame(AiVisibilityRun::STATUS_COMPLETED, $runs['analysis_run']->status);
        $this->assertIsArray($httpOptions);
        $this->assertEquals(2, $httpOptions['timeout']);
        $this->assertEquals(2, $httpOptions['connect_timeout']);
        MarkdownContentWriterAgent::assertPrompted(
            fn (AgentPrompt $prompt): bool => $prompt->prompt === 'Analyze GEOFlow.'
                && $prompt->timeout === 23,
        );
        Http::assertSent(fn (Request $request): bool => ! array_key_exists('timeout', $request->data()));
        Http::assertSentCount(1);
    }

    public function test_ark_forwards_the_explicit_http_timeout_without_retrying_a_failed_request(): void
    {
        $httpOptions = null;
        Http::fake([
            'https://ark.cn-beijing.volces.com/api/v3/responses' => function (Request $request, array $options) use (&$httpOptions) {
                $httpOptions = $options;

                return Http::response(['error' => ['message' => 'temporarily unavailable']], 503);
            },
        ]);
        $model = $this->createAiModel([
            'model_id' => 'doubao-seed-2-0-lite-260428',
            'api_url' => 'https://ark.cn-beijing.volces.com/api/v3',
        ]);

        try {
            app(AiVisibilityService::class)->runDoubaoArkResponses($model, 'GEOFlow', options: ['timeout' => 3]);
            $this->fail('Expected the failed Ark response to throw.');
        } catch (AiVisibilityRunException $exception) {
            $this->assertSame(AiVisibilityRun::STATUS_FAILED, $exception->run->status);
            $this->assertStringContainsString('HTTP 503', $exception->getMessage());
        }

        $this->assertIsArray($httpOptions);
        $this->assertEquals(3, $httpOptions['timeout']);
        $this->assertEquals(3, $httpOptions['connect_timeout']);
        $this->assertSame(0, (int) $model->fresh()->used_today);
        $this->assertSame(0, (int) $model->fresh()->total_used);
        Http::assertSent(fn (Request $request): bool => ! array_key_exists('timeout', $request->data()));
        Http::assertSentCount(1);
    }

    private function createSearchProvider(array $overrides = []): AiSourceProvider
    {
        return AiSourceProvider::query()->create(array_merge([
            'name' => 'Doubao Search Custom',
            'provider_key' => AiSourceProvider::PROVIDER_DOUBAO_SEARCH_CUSTOM,
            'endpoint_url' => 'https://open.feedcoopapi.com/search_api/web_search',
            'api_key' => app(ApiKeyCrypto::class)->encrypt('test-search-key'),
            'status' => 'active',
            'daily_limit' => 0,
            'used_today' => 0,
            'total_used' => 0,
        ], $overrides));
    }

    private function createAiModel(array $overrides = []): AiModel
    {
        return AiModel::query()->create(array_merge([
            'name' => 'DeepSeek V4 Flash',
            'version' => 'v4',
            'api_key' => app(ApiKeyCrypto::class)->encrypt('test-model-key'),
            'model_id' => 'deepseek-v4-flash',
            'model_type' => 'chat',
            'api_url' => 'https://api.deepseek.com',
            'failover_priority' => 100,
            'daily_limit' => 0,
            'used_today' => 0,
            'total_used' => 0,
            'status' => 'active',
        ], $overrides));
    }
}
