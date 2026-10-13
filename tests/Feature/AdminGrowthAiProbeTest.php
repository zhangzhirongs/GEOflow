<?php

namespace Tests\Feature;

use App\Ai\Agents\MarkdownContentWriterAgent;
use App\Jobs\ProcessGrowthAiProbeJob;
use App\Models\Admin;
use App\Models\AiGrowthProbe;
use App\Models\AiModel;
use App\Models\AiSourceProvider;
use App\Models\AiVisibilityRun;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\SiteSetting;
use App\Services\Admin\Analytics\GrowthAiAnswerCollector;
use App\Services\Admin\Analytics\GrowthAiProbeService;
use App\Services\GeoFlow\AiVisibility\AiVisibilityConfigurationResolver;
use App\Support\GeoFlow\ApiKeyCrypto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class AdminGrowthAiProbeTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_shows_the_ask_ai_workbench(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.analytics'))
            ->assertOk()
            ->assertSee(__('admin.analytics.overview.probe.title'))
            ->assertSee(__('admin.analytics.overview.probe.submit'))
            ->assertSee(__('admin.analytics.overview.probe.unconfigured'))
            ->assertSee('data-growth-probe', false)
            ->assertSee('data-overview-refresh="30000"', false);
    }

    public function test_overview_shows_model_select_when_configured(): void
    {
        $this->configureAiVisibility();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.analytics'))
            ->assertOk()
            ->assertSee(__('admin.analytics.overview.probe.model_label'))
            ->assertSee(__('admin.analytics.overview.probe.channels.auto'))
            ->assertSee(__('admin.analytics.overview.probe.channels.doubao_search_custom'))
            ->assertSee('name="provider_type"', false)
            ->assertSee('data-overview-refresh="30000"', false);
    }

    public function test_guest_cannot_create_a_probe(): void
    {
        $this->post(route('admin.analytics.ai-probes.store'), [
            'question' => '适合企业的 GEO 系统有哪些？',
        ])->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_queue_a_probe_when_ai_visibility_is_configured(): void
    {
        Queue::fake();
        $this->configureAiVisibility();

        $response = $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.analytics.ai-probes.store'), [
                'question' => '适合企业的 GEO 系统有哪些？',
            ]);
        $probe = AiGrowthProbe::query()->first();
        $this->assertNotNull($probe);
        $response->assertRedirect(route('admin.analytics', ['probe' => $probe->uuid]));

        $this->assertDatabaseHas('ai_growth_probes', [
            'question' => '适合企业的 GEO 系统有哪些？',
            'provider_type' => AiGrowthProbe::CHANNEL_AUTO,
            'status' => AiGrowthProbe::STATUS_QUEUED,
        ]);
        Queue::assertPushedOn('geoflow', ProcessGrowthAiProbeJob::class);
    }

    public function test_admin_can_queue_a_probe_with_a_selected_model(): void
    {
        Queue::fake();
        $this->configureAiVisibility();

        $response = $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.analytics.ai-probes.store'), [
                'question' => '适合企业的 GEO 系统有哪些？',
                'provider_type' => AiGrowthProbe::CHANNEL_SEARCH,
            ]);
        $probe = AiGrowthProbe::query()->first();
        $this->assertNotNull($probe);
        $response->assertRedirect(route('admin.analytics', ['probe' => $probe->uuid]));

        $this->assertDatabaseHas('ai_growth_probes', [
            'question' => '适合企业的 GEO 系统有哪些？',
            'provider_type' => AiGrowthProbe::CHANNEL_SEARCH,
            'status' => AiGrowthProbe::STATUS_QUEUED,
        ]);
    }

    public function test_invalid_provider_is_rejected(): void
    {
        $this->configureAiVisibility();

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.analytics.ai-probes.store'), [
                'question' => '适合企业的 GEO 系统有哪些？',
                'provider_type' => 'gpt-4o',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('provider_type');
    }

    public function test_unconfigured_provider_is_rejected(): void
    {
        $this->configureAiVisibility();

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.analytics.ai-probes.store'), [
                'question' => '适合企业的 GEO 系统有哪些？',
                'provider_type' => AiGrowthProbe::CHANNEL_ARK,
            ])
            ->assertRedirect(route('admin.analytics'))
            ->assertSessionHasErrors('provider_type');
    }

    public function test_overview_renders_completed_competitor_analysis(): void
    {
        AiGrowthProbe::query()->create([
            'admin_id' => $this->admin()->id,
            'question' => 'GEO 应该看哪些资料？',
            'provider_type' => AiGrowthProbe::CHANNEL_SEARCH,
            'status' => AiGrowthProbe::STATUS_COMPLETED,
            'verdict' => AiGrowthProbe::VERDICT_COMPETITOR,
            'answer_excerpt' => '很多团队会先看 Ahrefs 的 GEO 研究。',
            'analysis_json' => [
                'summary' => 'AI 回答未引用当前公司内容，已提取竞品来源。',
                'competitors' => [[
                    'name' => 'Ahrefs',
                    'domain' => 'ahrefs.com',
                    'posts' => [[
                        'title' => 'Ahrefs GEO research 2026',
                        'url' => 'https://ahrefs.com/blog/geo-research',
                    ]],
                    'keywords' => ['GEO', 'research'],
                ]],
            ],
            'completed_at' => now(),
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.analytics'))
            ->assertOk()
            ->assertSee('Ahrefs')
            ->assertSee('Ahrefs GEO research 2026')
            ->assertSee('research')
            ->assertSee(__('admin.analytics.overview.probe.channels.doubao_search_custom'));
    }

    public function test_unconfigured_probe_is_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.analytics.ai-probes.store'), [
                'question' => '适合企业的 GEO 系统有哪些？',
            ])
            ->assertRedirect(route('admin.analytics'))
            ->assertSessionHasErrors('question');
    }

    public function test_guest_cannot_poll_probe_status(): void
    {
        $probe = AiGrowthProbe::query()->create([
            'admin_id' => $this->admin()->id,
            'question' => '适合企业的 GEO 系统有哪些？',
            'status' => AiGrowthProbe::STATUS_QUEUED,
        ]);

        $this->get(route('admin.analytics.ai-probes.show', $probe))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_poll_probe_status(): void
    {
        $probe = AiGrowthProbe::query()->create([
            'admin_id' => $this->admin()->id,
            'question' => '适合企业的 GEO 系统有哪些？',
            'status' => AiGrowthProbe::STATUS_QUEUED,
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->getJson(route('admin.analytics.ai-probes.show', $probe))
            ->assertOk()
            ->assertJsonPath('uuid', $probe->uuid)
            ->assertJsonPath('status', AiGrowthProbe::STATUS_QUEUED)
            ->assertJsonPath('provider_type', AiGrowthProbe::CHANNEL_AUTO);
    }

    public function test_probe_processing_records_owned_article_mentions(): void
    {
        config()->set('geoflow.site_name', 'GEOFlow');
        config()->set('app.url', 'https://geoflow.example.com');
        $this->publishedArticle('GEOFlow 企业部署指南', 'geoflow-enterprise-deploy', 'GEO,Docker,部署');

        $run = AiVisibilityRun::query()->create([
            'keyword' => '适合企业的 GEO 系统怎么部署？',
            'prompt' => '适合企业的 GEO 系统怎么部署？',
            'provider_type' => AiVisibilityRun::PROVIDER_DOUBAO_SEARCH_CUSTOM,
            'status' => AiVisibilityRun::STATUS_COMPLETED,
            'answer_text' => '可以参考 GEOFlow 企业部署指南，里面写了 Docker 方案。',
            'completed_at' => now(),
        ]);
        $run->sources()->create([
            'source_type' => 'web',
            'title' => 'GEOFlow 企业部署指南',
            'domain' => 'geoflow.example.com',
            'url' => 'https://geoflow.example.com/article/geoflow-enterprise-deploy',
            'snippet' => '官方指南',
            'site_name' => 'GEOFlow',
            'rank' => 1,
        ]);

        $this->mock(GrowthAiAnswerCollector::class, function ($mock) use ($run): void {
            $mock->shouldReceive('collect')
                ->once()
                ->with('适合企业的 GEO 系统怎么部署？', AiGrowthProbe::CHANNEL_AUTO)
                ->andReturn(['search_run' => $run]);
        });

        $probe = AiGrowthProbe::query()->create([
            'admin_id' => $this->admin()->id,
            'question' => '适合企业的 GEO 系统怎么部署？',
            'status' => AiGrowthProbe::STATUS_QUEUED,
        ]);

        app(GrowthAiProbeService::class)->process($probe->fresh());

        $probe->refresh();
        $this->assertSame(AiGrowthProbe::STATUS_COMPLETED, $probe->status);
        $this->assertSame(AiGrowthProbe::VERDICT_OWNED_CONTENT, $probe->verdict);
        $this->assertTrue($probe->owned_content_mentioned);
        $this->assertSame('GEOFlow 企业部署指南', $probe->analysis_json['owned_articles'][0]['title']);
        $this->assertContains('Docker', $probe->analysis_json['owned_articles'][0]['keywords']);
    }

    public function test_probe_processing_records_competitors_when_owned_content_is_absent(): void
    {
        config()->set('geoflow.site_name', 'GEOFlow');
        config()->set('app.url', 'https://geoflow.example.com');

        $run = AiVisibilityRun::query()->create([
            'keyword' => 'GEO 应该看哪些资料？',
            'prompt' => 'GEO 应该看哪些资料？',
            'provider_type' => AiVisibilityRun::PROVIDER_DOUBAO_SEARCH_CUSTOM,
            'status' => AiVisibilityRun::STATUS_COMPLETED,
            'answer_text' => '很多团队会先看 Ahrefs 的 GEO 研究。',
            'completed_at' => now(),
        ]);
        $run->sources()->create([
            'source_type' => 'web',
            'title' => 'Ahrefs GEO research 2026',
            'domain' => 'ahrefs.com',
            'url' => 'https://ahrefs.com/blog/geo-research',
            'snippet' => 'Ahrefs 发布了生成式引擎优化研究。',
            'site_name' => 'Ahrefs',
            'rank' => 1,
        ]);

        $this->mock(GrowthAiAnswerCollector::class, function ($mock) use ($run): void {
            $mock->shouldReceive('collect')
                ->once()
                ->with('GEO 应该看哪些资料？', AiGrowthProbe::CHANNEL_SEARCH)
                ->andReturn(['search_run' => $run]);
        });

        $probe = AiGrowthProbe::query()->create([
            'admin_id' => $this->admin()->id,
            'question' => 'GEO 应该看哪些资料？',
            'provider_type' => AiGrowthProbe::CHANNEL_SEARCH,
            'status' => AiGrowthProbe::STATUS_QUEUED,
        ]);

        app(GrowthAiProbeService::class)->process($probe->fresh());

        $probe->refresh();
        $this->assertSame(AiGrowthProbe::STATUS_COMPLETED, $probe->status);
        $this->assertSame(AiGrowthProbe::VERDICT_COMPETITOR, $probe->verdict);
        $this->assertSame('Ahrefs', $probe->analysis_json['competitors'][0]['name']);
        $this->assertSame('Ahrefs GEO research 2026', $probe->analysis_json['competitors'][0]['posts'][0]['title']);
    }

    public function test_overview_shows_chat_model_when_a_conversation_model_is_configured(): void
    {
        $model = $this->createChatModel();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.analytics'))
            ->assertOk()
            ->assertSee(__('admin.analytics.overview.probe.model_label'))
            ->assertSee(__('admin.analytics.overview.probe.channels.auto'))
            ->assertSee('本地对话模型')
            ->assertSee('value="'.AiGrowthProbe::chatChannel((int) $model->id).'"', false)
            ->assertDontSee(__('admin.analytics.overview.probe.unconfigured'))
            ->assertSee('name="provider_type"', false)
            ->assertSee('data-overview-refresh="30000"', false);
    }

    public function test_admin_can_queue_a_probe_with_a_selected_chat_model(): void
    {
        Queue::fake();
        $model = $this->createChatModel();
        $channel = AiGrowthProbe::chatChannel((int) $model->id);

        $response = $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.analytics.ai-probes.store'), [
                'question' => '现在城市更新的公司有哪些',
                'provider_type' => $channel,
            ]);
        $probe = AiGrowthProbe::query()->first();
        $this->assertNotNull($probe);
        $response->assertRedirect(route('admin.analytics', ['probe' => $probe->uuid]));

        $this->assertDatabaseHas('ai_growth_probes', [
            'question' => '现在城市更新的公司有哪些',
            'provider_type' => $channel,
            'status' => AiGrowthProbe::STATUS_QUEUED,
        ]);
        Queue::assertPushedOn('geoflow', ProcessGrowthAiProbeJob::class);
    }

    public function test_auto_probe_uses_chat_completion_like_article_generation(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://ark.cn-beijing.volces.com/api/v3/responses' => Http::response([
                'error' => [
                    'code' => 'ToolNotOpen',
                    'message' => 'Your account has not activated web search. You may activate it at https://console.volcengine.com/common-buy/CC_content_plugin',
                    'param' => '',
                    'type' => 'NotFound',
                ],
            ], 404),
        ]);
        MarkdownContentWriterAgent::fake([
            '可以参考 GEOFlow 企业部署指南，里面写了 Docker 方案。',
        ])->preventStrayPrompts();

        config()->set('geoflow.site_name', 'GEOFlow');
        config()->set('app.url', 'https://geoflow.example.com');
        $this->publishedArticle('GEOFlow 企业部署指南', 'geoflow-enterprise-deploy', 'GEO,Docker,部署');
        $this->createChatModel();
        $this->configureArkVisibility();

        $probe = AiGrowthProbe::query()->create([
            'admin_id' => $this->admin()->id,
            'question' => '现在城市更新的公司有哪些',
            'provider_type' => AiGrowthProbe::CHANNEL_AUTO,
            'status' => AiGrowthProbe::STATUS_QUEUED,
        ]);

        app(GrowthAiProbeService::class)->process($probe->fresh());

        $probe->refresh()->load('visibilityRun');
        $this->assertSame(AiGrowthProbe::STATUS_COMPLETED, $probe->status);
        $this->assertSame(AiGrowthProbe::VERDICT_OWNED_CONTENT, $probe->verdict);
        $this->assertTrue((bool) $probe->owned_content_mentioned);
        $this->assertSame(AiVisibilityRun::PROVIDER_CHAT_COMPLETION, $probe->visibilityRun?->provider_type);
        $this->assertSame('可以参考 GEOFlow 企业部署指南，里面写了 Docker 方案。', $probe->visibilityRun?->answer_text);

        MarkdownContentWriterAgent::assertPrompted(
            fn ($prompt): bool => str_contains((string) $prompt->prompt, '现在城市更新的公司有哪些')
                && $prompt->agent instanceof MarkdownContentWriterAgent
                && $prompt->agent->instructions === __('admin.analytics.overview.probe.chat_instructions'),
        );
    }

    public function test_ark_web_search_errors_are_humanized_on_failed_probes(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://ark.cn-beijing.volces.com/api/v3/responses' => Http::response([
                'error' => [
                    'code' => 'ToolNotOpen',
                    'message' => 'Your account has not activated web search. You may activate it at https://console.volcengine.com/common-buy/CC_content_plugin',
                    'param' => '',
                    'type' => 'NotFound',
                ],
            ], 404),
        ]);

        $this->configureArkVisibility();

        $probe = AiGrowthProbe::query()->create([
            'admin_id' => $this->admin()->id,
            'question' => '现在城市更新的公司有哪些',
            'provider_type' => AiGrowthProbe::CHANNEL_ARK,
            'status' => AiGrowthProbe::STATUS_QUEUED,
        ]);

        try {
            app(GrowthAiProbeService::class)->process($probe->fresh());
            $this->fail('Expected probe processing to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame(__('admin.analytics.overview.probe.errors.ark_web_search_closed'), $exception->getMessage());
        }

        $probe->refresh();
        $this->assertSame(AiGrowthProbe::STATUS_FAILED, $probe->status);
        $this->assertSame(__('admin.analytics.overview.probe.errors.ark_web_search_closed'), $probe->error_message);
        $this->assertStringNotContainsString('ToolNotOpen', (string) $probe->error_message);
        $this->assertStringNotContainsString('{', (string) $probe->error_message);
    }

    private function configureAiVisibility(): void
    {
        AiSourceProvider::query()->create([
            'name' => 'Doubao Search Custom',
            'provider_key' => AiSourceProvider::PROVIDER_DOUBAO_SEARCH_CUSTOM,
            'endpoint_url' => 'https://open.feedcoopapi.com/search_api/web_search',
            'api_key' => app(ApiKeyCrypto::class)->encrypt('search-key'),
            'status' => 'active',
            'daily_limit' => 10,
        ]);
    }

    private function createChatModel(): AiModel
    {
        return AiModel::query()->create([
            'name' => '本地对话模型',
            'version' => 'test',
            'api_key' => app(ApiKeyCrypto::class)->encrypt('chat-key'),
            'model_id' => 'doubao-seed-1-6-250615',
            'model_type' => 'chat',
            'api_url' => 'https://ark.cn-beijing.volces.com/api/v3',
            'failover_priority' => 1,
            'daily_limit' => 0,
            'used_today' => 0,
            'total_used' => 0,
            'status' => 'active',
        ]);
    }

    private function configureArkVisibility(): AiModel
    {
        $model = AiModel::query()->create([
            'name' => 'Doubao Ark',
            'version' => 'test',
            'api_key' => app(ApiKeyCrypto::class)->encrypt('ark-key'),
            'model_id' => 'doubao-seed-2-0-lite-260428',
            'model_type' => 'chat',
            'api_url' => 'https://ark.cn-beijing.volces.com/api/v3',
            'failover_priority' => 10,
            'daily_limit' => 0,
            'used_today' => 0,
            'total_used' => 0,
            'status' => 'active',
        ]);

        SiteSetting::query()->updateOrCreate(
            ['setting_key' => AiVisibilityConfigurationResolver::ARK_MODEL_SETTING_KEY],
            ['setting_value' => (string) $model->id],
        );

        return $model;
    }

    private function publishedArticle(string $title, string $slug, string $keywords): Article
    {
        $author = Author::query()->create(['name' => '探针作者', 'slug' => 'probe-admin-author', 'status' => 'active']);
        $category = Category::query()->create(['name' => '探针分类', 'slug' => 'probe-admin-category', 'status' => 'active']);

        return Article::query()->create([
            'title' => $title,
            'slug' => $slug,
            'content' => '正文',
            'keywords' => $keywords,
            'author_id' => $author->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    private function admin(): Admin
    {
        return Admin::query()->first() ?? Admin::query()->create([
            'username' => 'growth_probe_admin',
            'password' => 'secret-123',
            'email' => 'growth-probe@example.com',
            'display_name' => 'Growth Probe Admin',
            'role' => 'super_admin',
            'status' => 'active',
        ]);
    }
}
