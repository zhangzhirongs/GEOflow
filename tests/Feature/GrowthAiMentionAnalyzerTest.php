<?php

namespace Tests\Feature;

use App\Models\AiVisibilityRun;
use App\Models\AiVisibilitySource;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\ManualPublicationAccount;
use App\Models\ManualPublicationPersona;
use App\Models\SocialPublication;
use App\Services\Admin\Analytics\GrowthAiMentionAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrowthAiMentionAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_extracts_keywords_when_owned_articles_are_mentioned(): void
    {
        config()->set('geoflow.site_name', 'GEOFlow');
        config()->set('geoflow.site_url', 'https://geoflow.example.com');
        config()->set('app.url', 'https://geoflow.example.com');

        $article = $this->publishedArticle(
            title: 'GEOFlow 企业部署指南',
            slug: 'geoflow-enterprise-deploy',
            keywords: 'Docker,Nginx,生产环境',
        );

        $run = $this->completedRun(
            answer: '企业可以参考 GEOFlow 企业部署指南 来完成 Docker 和 Nginx 的上线。',
            sources: [[
                'title' => 'GEOFlow 企业部署指南',
                'domain' => 'geoflow.example.com',
                'url' => 'https://geoflow.example.com/article/geoflow-enterprise-deploy',
                'snippet' => '官方部署文档',
                'site_name' => 'GEOFlow',
                'rank' => 1,
            ]],
        );

        $analysis = app(GrowthAiMentionAnalyzer::class)->analyze('适合企业的 GEO 系统怎么部署？', $run);

        $this->assertTrue($analysis['brand_mentioned']);
        $this->assertTrue($analysis['owned_content_mentioned']);
        $this->assertSame('owned_content', $analysis['verdict']);
        $this->assertSame($article->id, $analysis['owned_articles'][0]['id']);
        $this->assertContains('Docker', $analysis['owned_articles'][0]['keywords']);
        $this->assertContains('Docker', $analysis['owned_articles'][0]['mentioned_keywords']);
        $this->assertContains('Nginx', $analysis['owned_articles'][0]['mentioned_keywords']);
        $this->assertTrue(collect($analysis['owned_keywords'])->contains(fn (array $keyword): bool => $keyword['term'] === 'Docker' && $keyword['in_answer'] === true));
        $this->assertSame([], $analysis['competitors']);
    }

    public function test_it_extracts_competitor_posts_when_owned_content_is_absent(): void
    {
        config()->set('geoflow.site_name', 'GEOFlow');
        config()->set('geoflow.site_url', 'https://geoflow.example.com');
        config()->set('app.url', 'https://geoflow.example.com');

        $run = $this->completedRun(
            answer: '很多团队会先看 Ahrefs 的 GEO 研究，再参考 Surfer 的内容简报。',
            sources: [
                [
                    'title' => 'Ahrefs GEO research 2026',
                    'domain' => 'ahrefs.com',
                    'url' => 'https://ahrefs.com/blog/geo-research',
                    'snippet' => 'Ahrefs 发布了生成式引擎优化研究。',
                    'site_name' => 'Ahrefs',
                    'rank' => 1,
                ],
                [
                    'title' => 'Surfer content briefing',
                    'domain' => 'surferseo.com',
                    'url' => 'https://surferseo.com/blog/briefing',
                    'snippet' => 'Surfer 给出了内容简报模板。',
                    'site_name' => 'Surfer',
                    'rank' => 2,
                ],
            ],
        );

        $analysis = app(GrowthAiMentionAnalyzer::class)->analyze('GEO 应该看哪些资料？', $run);

        $this->assertFalse($analysis['owned_content_mentioned']);
        $this->assertSame('competitor', $analysis['verdict']);
        $this->assertSame('Ahrefs', $analysis['competitors'][0]['name']);
        $this->assertSame('Ahrefs GEO research 2026', $analysis['competitors'][0]['posts'][0]['title']);
        $this->assertNotEmpty($analysis['competitors'][0]['keywords']);
    }

    public function test_it_matches_owned_social_posts_by_remote_url(): void
    {
        config()->set('geoflow.site_name', 'GEOFlow');
        config()->set('app.url', 'https://geoflow.example.com');
        $article = $this->publishedArticle('GEOFlow 周报', 'geoflow-weekly', 'GEO,可见度');

        $persona = ManualPublicationPersona::query()->create([
            'name' => '探针人设',
            'is_active' => true,
        ]);
        $account = ManualPublicationAccount::query()->create([
            'persona_id' => $persona->id,
            'platform' => 'zhihu',
            'account_name' => '探针账号',
            'is_active' => true,
        ]);
        SocialPublication::query()->create([
            'article_id' => $article->id,
            'manual_publication_account_id' => $account->id,
            'platform' => 'zhihu',
            'status' => 'synced',
            'remote_url' => 'https://zhihu.com/p/geoflow-weekly',
            'payload_snapshot' => ['title' => 'GEOFlow 周报'],
        ]);

        $run = $this->completedRun(
            answer: '可以看看 GEOFlow 在知乎发布的周报。',
            sources: [[
                'title' => 'GEOFlow 周报',
                'domain' => 'zhihu.com',
                'url' => 'https://zhihu.com/p/geoflow-weekly',
                'snippet' => '官方周报',
                'site_name' => 'Zhihu',
                'rank' => 1,
            ]],
        );

        $analysis = app(GrowthAiMentionAnalyzer::class)->analyze('最近有哪些 GEO 周报？', $run);

        $this->assertTrue($analysis['owned_content_mentioned']);
        $this->assertSame('owned_content', $analysis['verdict']);
        $this->assertSame('https://zhihu.com/p/geoflow-weekly', $analysis['owned_posts'][0]['remote_url']);
    }

    public function test_keyword_only_match_requires_a_brand_mention(): void
    {
        config()->set('geoflow.site_name', 'GEOFlow');
        config()->set('app.url', 'https://geoflow.example.com');
        $this->publishedArticle('GEOFlow 企业部署指南', 'geoflow-enterprise-deploy', '生产环境,Docker');

        $withoutBrand = $this->completedRun(
            answer: '这个方案很适合生产环境。',
            sources: [],
        );
        $analysisWithoutBrand = app(GrowthAiMentionAnalyzer::class)->analyze('生产环境怎么做？', $withoutBrand);
        $this->assertFalse($analysisWithoutBrand['owned_content_mentioned']);
        $this->assertSame([], $analysisWithoutBrand['owned_articles']);

        $withBrand = $this->completedRun(
            answer: 'GEOFlow 很适合生产环境。',
            sources: [],
        );
        $analysisWithBrand = app(GrowthAiMentionAnalyzer::class)->analyze('生产环境怎么做？', $withBrand);
        $this->assertTrue($analysisWithBrand['owned_content_mentioned']);
        $this->assertSame('keyword', $analysisWithBrand['owned_articles'][0]['matched_on']);
        $this->assertContains('生产环境', $analysisWithBrand['owned_articles'][0]['mentioned_keywords']);
    }

    public function test_it_extracts_competitor_names_from_the_answer_when_sources_are_absent(): void
    {
        config()->set('geoflow.site_name', 'GEOFlow');
        config()->set('app.url', 'https://geoflow.example.com');

        $run = $this->completedRun(
            answer: '很多团队会先看 Ahrefs 的 GEO 研究。',
            sources: [],
        );
        $analysis = app(GrowthAiMentionAnalyzer::class)->analyze('GEO 应该看哪些资料？', $run);

        $this->assertSame('competitor', $analysis['verdict']);
        $this->assertSame('Ahrefs', $analysis['competitors'][0]['name']);
        $this->assertNotEmpty($analysis['competitors'][0]['keywords']);
    }

    public function test_it_does_not_treat_generic_phrases_as_competitors(): void
    {
        config()->set('geoflow.site_name', 'GEOFlow');
        config()->set('app.url', 'https://geoflow.example.com');

        $run = $this->completedRun(
            answer: '很多团队会先看文档再做分析。',
            sources: [],
        );
        $analysis = app(GrowthAiMentionAnalyzer::class)->analyze('GEO 应该看哪些资料？', $run);

        $this->assertSame('none', $analysis['verdict']);
        $this->assertSame([], $analysis['competitors']);
    }

    public function test_brand_mention_without_articles_still_extracts_competitors(): void
    {
        config()->set('geoflow.site_name', 'GEOFlow');
        config()->set('app.url', 'https://geoflow.example.com');

        $run = $this->completedRun(
            answer: 'GEOFlow 之外，很多人也会参考 Ahrefs 的研究。',
            sources: [[
                'title' => 'Ahrefs GEO research 2026',
                'domain' => 'ahrefs.com',
                'url' => 'https://ahrefs.com/blog/geo-research',
                'snippet' => 'Ahrefs 发布了生成式引擎优化研究。',
                'site_name' => 'Ahrefs',
                'rank' => 1,
            ]],
        );
        $analysis = app(GrowthAiMentionAnalyzer::class)->analyze('还有哪些资料？', $run);

        $this->assertTrue($analysis['brand_mentioned']);
        $this->assertFalse($analysis['owned_content_mentioned']);
        $this->assertSame('owned_brand', $analysis['verdict']);
        $this->assertSame('Ahrefs', $analysis['competitors'][0]['name']);
        $this->assertSame('Ahrefs GEO research 2026', $analysis['competitors'][0]['posts'][0]['title']);
    }

    private function publishedArticle(string $title, string $slug, string $keywords): Article
    {
        $author = Author::query()->create(['name' => '分析作者', 'slug' => 'probe-author', 'status' => 'active']);
        $category = Category::query()->create(['name' => '分析分类', 'slug' => 'probe-category', 'status' => 'active']);

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

    /**
     * @param  list<array<string, mixed>>  $sources
     */
    private function completedRun(string $answer, array $sources): AiVisibilityRun
    {
        $run = AiVisibilityRun::query()->create([
            'keyword' => 'GEO 提问',
            'prompt' => 'GEO 提问',
            'provider_type' => AiVisibilityRun::PROVIDER_DOUBAO_SEARCH_CUSTOM,
            'provider_key' => 'doubao_search',
            'status' => AiVisibilityRun::STATUS_COMPLETED,
            'answer_text' => $answer,
            'completed_at' => now(),
        ]);

        foreach ($sources as $source) {
            AiVisibilitySource::query()->create([
                'ai_visibility_run_id' => (int) $run->id,
                'source_type' => 'web',
                'title' => $source['title'],
                'domain' => $source['domain'],
                'url' => $source['url'],
                'snippet' => $source['snippet'],
                'site_name' => $source['site_name'],
                'rank' => $source['rank'],
            ]);
        }

        return $run->fresh('sources') ?? $run;
    }
}
