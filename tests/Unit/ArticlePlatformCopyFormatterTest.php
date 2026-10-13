<?php

namespace Tests\Unit;

use App\Support\Admin\ArticlePlatformCopyFormatter;
use App\Support\Admin\WeChatArticleHtmlExporter;
use Tests\TestCase;

class ArticlePlatformCopyFormatterTest extends TestCase
{
    private ArticlePlatformCopyFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new ArticlePlatformCopyFormatter(new WeChatArticleHtmlExporter);
    }

    public function test_wechat_copy_returns_styled_html(): void
    {
        $result = $this->formatter->format("## 小节\n\n正文 **重点**", 'wechat');

        $this->assertSame('html', $result['format']);
        $this->assertStringContainsString('data-geoflow-export="wechat-article"', $result['html']);
        $this->assertStringContainsString('style="', $result['html']);
        $this->assertStringContainsString('<h2', $result['html']);
        $this->assertStringContainsString('<strong', $result['html']);
        $this->assertStringContainsString('小节', $result['text']);
        $this->assertSame(__('admin.article_editor.platform_copy.success.wechat'), $result['message']);
    }

    public function test_zhihu_copy_returns_semantic_html_without_wechat_styles(): void
    {
        $result = $this->formatter->format("## 小节\n\n正文 **重点**\n\n<script>alert(1)</script>", 'zhihu');

        $this->assertSame('html', $result['format']);
        $this->assertStringContainsString('data-geoflow-export="zhihu-article"', $result['html']);
        $this->assertStringContainsString('<h2', $result['html']);
        $this->assertStringContainsString('<strong', $result['html']);
        $this->assertStringNotContainsString('style="', $result['html']);
        $this->assertStringNotContainsString('<script', $result['html']);
        $this->assertSame(__('admin.article_editor.platform_copy.success.zhihu'), $result['message']);
    }

    public function test_xiaohongshu_copy_strips_markdown_and_keeps_readable_plain_text(): void
    {
        $result = $this->formatter->format(
            "## 小节\n\n正文 **重点**\n\n- 要点一\n- 要点二\n\n[官网](https://example.com)\n\n![封面](https://example.com/cover.png)",
            'xiaohongshu'
        );

        $this->assertSame('plain', $result['format']);
        $this->assertSame('', $result['html']);
        $this->assertStringContainsString('小节', $result['text']);
        $this->assertStringContainsString('重点', $result['text']);
        $this->assertStringContainsString('要点一', $result['text']);
        $this->assertStringContainsString('官网 (https://example.com)', $result['text']);
        $this->assertStringContainsString(__('admin.article_editor.platform_copy.image_placeholder', ['alt' => '封面']), $result['text']);
        $this->assertStringNotContainsString('**', $result['text']);
        $this->assertStringNotContainsString('##', $result['text']);
        $this->assertStringNotContainsString('![封面]', $result['text']);
        $this->assertSame(__('admin.article_editor.platform_copy.success.xiaohongshu'), $result['message']);
    }

    public function test_markdown_platforms_keep_source(): void
    {
        $markdown = "## 小节\n\n正文 **重点**";

        foreach (['bilibili', 'reddit', 'custom'] as $platform) {
            $result = $this->formatter->format($markdown, $platform);
            $this->assertSame('markdown', $result['format'], $platform);
            $this->assertSame('', $result['html'], $platform);
            $this->assertSame($markdown, $result['text'], $platform);
        }
    }

    public function test_plain_platforms_do_not_keep_markdown_markers(): void
    {
        $markdown = "## 标题\n\n内容 **加粗**";

        foreach (['weibo', 'douyin', 'x', 'linkedin'] as $platform) {
            $result = $this->formatter->format($markdown, $platform);
            $this->assertSame('plain', $result['format'], $platform);
            $this->assertSame('', $result['html'], $platform);
            $this->assertStringContainsString('标题', $result['text']);
            $this->assertStringContainsString('加粗', $result['text']);
            $this->assertStringNotContainsString('**', $result['text'], $platform);
            $this->assertStringNotContainsString('##', $result['text'], $platform);
        }
    }

    public function test_empty_markdown_returns_empty_copy(): void
    {
        $result = $this->formatter->format("  \n  ", 'wechat');

        $this->assertSame('', $result['html']);
        $this->assertSame('', $result['text']);
        $this->assertSame(__('admin.article_editor.copy.empty'), $result['message']);
    }
}
