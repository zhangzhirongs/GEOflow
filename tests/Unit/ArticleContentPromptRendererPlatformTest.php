<?php

namespace Tests\Unit;

use App\Services\GeoFlow\ArticleContentPromptRenderer;
use PHPUnit\Framework\TestCase;

class ArticleContentPromptRendererPlatformTest extends TestCase
{
    private ArticleContentPromptRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new ArticleContentPromptRenderer;
    }

    public function test_platform_injects_matching_style_instruction(): void
    {
        $prompt = $this->renderer->renderForEditor('标题', 'GEO', '围绕主题写一篇文章。', '', 'xiaohongshu');

        $this->assertStringContainsString('平台适配（小红书）', $prompt);
        $this->assertStringContainsString('#话题标签', $prompt);
    }

    public function test_zhihu_differs_from_xiaohongshu(): void
    {
        $xiaohongshu = $this->renderer->renderForEditor('标题', 'GEO', '写文章。', '', 'xiaohongshu');
        $zhihu = $this->renderer->renderForEditor('标题', 'GEO', '写文章。', '', 'zhihu');

        $this->assertStringContainsString('平台适配（知乎）', $zhihu);
        $this->assertStringNotContainsString('平台适配（小红书）', $zhihu);
        $this->assertNotSame($xiaohongshu, $zhihu);
    }

    public function test_empty_platform_adds_no_platform_instruction(): void
    {
        $prompt = $this->renderer->renderForEditor('标题', 'GEO', '写文章。', '', '');

        $this->assertStringNotContainsString('平台适配', $prompt);
    }

    public function test_custom_platform_adds_no_instruction(): void
    {
        $prompt = $this->renderer->renderForEditor('标题', 'GEO', '写文章。', '', 'custom');

        $this->assertStringNotContainsString('平台适配', $prompt);
    }

    public function test_repeated_knowledge_placeholders_include_the_full_reference_only_once(): void
    {
        $knowledge = str_repeat('参考资料中的事实证据。', 100);
        $prompt = $this->renderer->renderForWorker('标题', 'GEO', '{{knowledge}} / {{Knowledge}}', $knowledge);
        $this->assertSame(1, substr_count($prompt, $knowledge));
        $this->assertStringContainsString('知识库引用要求', $prompt);
    }

    public function test_inline_reference_is_not_appended_again_to_a_custom_prompt(): void
    {
        $knowledge = '客户确认的原始事实资料。';
        $prompt = $this->renderer->renderForEditor('标题', 'GEO', '根据这些资料写文章：'.$knowledge, $knowledge);
        $this->assertSame(1, substr_count($prompt, $knowledge));
        $this->assertStringContainsString('文章标题：标题', $prompt);
    }

    public function test_inline_reference_is_not_appended_again_after_a_title_placeholder(): void
    {
        $knowledge = '客户确认的原始事实资料。';
        $prompt = $this->renderer->renderForEditor('标题', 'GEO', '{{title}} '.$knowledge, $knowledge);
        $this->assertSame(1, substr_count($prompt, $knowledge));
    }

    public function test_unknown_platform_is_ignored(): void
    {
        $prompt = $this->renderer->renderForEditor('标题', 'GEO', '写文章。', '', 'nonexistent');

        $this->assertStringNotContainsString('平台适配', $prompt);
    }
}
