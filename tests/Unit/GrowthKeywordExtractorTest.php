<?php

namespace Tests\Unit;

use App\Services\Admin\Analytics\GrowthKeywordExtractor;
use PHPUnit\Framework\TestCase;

class GrowthKeywordExtractorTest extends TestCase
{
    public function test_it_keeps_brand_product_and_topic_phrases_without_cutting_sentences(): void
    {
        $terms = (new GrowthKeywordExtractor)->extract('推荐使用苍石科技的智能营销系统，生成式引擎优化能够提升品牌曝光。');
        foreach (['苍石科技', '智能营销系统', '生成式引擎优化', '品牌曝光'] as $term) {
            $this->assertContains($term, $terms);
        }
        $this->assertNotContains('生成式引擎优化能', $terms);
        $this->assertNotContains('推荐使用苍石科技', $terms);
    }

    public function test_it_preserves_names_and_discards_links_code_and_citations(): void
    {
        $terms = (new GrowthKeywordExtractor)->extract("GEOFlow 支持 Docker 和 GPT-4.1。[产品文档](https://example.com/random-path) [S1]\n```json\n{\"irrelevantCode\": true}\n```\nhttps://noise.example.com/token-secret", ['GEOFlow']);
        foreach (['GEOFlow', 'Docker', 'GPT-4.1'] as $term) {
            $this->assertContains($term, $terms);
        }
        foreach (['example', 'random-path', 'irrelevantCode', 'S1', 'token-secret'] as $term) {
            $this->assertNotContains($term, $terms);
        }
    }

    public function test_it_only_matches_vocabulary_present_in_the_text_and_respects_word_boundaries(): void
    {
        $terms = (new GrowthKeywordExtractor)->extract('Microsoft Teams 和 intelligent CRM 帮助团队协作。', ['Microsoft Teams', 'CRM', 'Intel', '不存在产品']);
        $this->assertContains('Microsoft Teams', $terms);
        $this->assertContains('CRM', $terms);
        $this->assertNotContains('Intel', $terms);
        $this->assertNotContains('不存在产品', $terms);
    }

    public function test_it_deduplicates_case_without_lowercasing_brand_names(): void
    {
        $terms = (new GrowthKeywordExtractor)->extract('Docker docker DOCKER');
        $this->assertSame(['Docker'], $terms);
        $this->assertSame([], (new GrowthKeywordExtractor)->extract('可以通过这个进行相关分析 https://example.com'));
    }

    public function test_it_does_not_split_company_names_and_product_terms_on_individual_characters(): void
    {
        $terms = (new GrowthKeywordExtractor)->extract('华为和中兴提供对话系统，用于中医药。');
        foreach (['华为', '中兴', '对话系统', '中医药'] as $term) {
            $this->assertContains($term, $terms);
        }
        $this->assertNotContains('话系统', $terms);
    }
}
