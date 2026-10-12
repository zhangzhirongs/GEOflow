<?php

namespace Tests\Feature;

use App\Support\GeoFlow\ArticlePublishContentNormalizer;
use Tests\TestCase;

class ArticlePublishContentNormalizerTest extends TestCase
{
    public function test_relative_markdown_images_become_absolute_and_are_collected(): void
    {
        config(['app.url' => 'http://host:18080']);

        $result = ArticlePublishContentNormalizer::normalize(
            "标题正文\n\n![封面](/storage/cover.png)\n\n历史素材 ![老图](uploads/old.jpg)"
        );

        $this->assertStringContainsString('http://host:18080/storage/cover.png', $result['content']);
        $this->assertStringContainsString('http://host:18080/storage/uploads/old.jpg', $result['content']);
        $this->assertContains('http://host:18080/storage/cover.png', $result['images']);
        $this->assertContains('http://host:18080/storage/uploads/old.jpg', $result['images']);
        $this->assertCount(2, $result['images']);
    }

    public function test_html_img_and_absolute_urls_are_preserved(): void
    {
        $result = ArticlePublishContentNormalizer::normalize(
            '<img src="https://cdn.test/a.png" alt="a">文本',
            'http://host:18080'
        );

        $this->assertStringContainsString('https://cdn.test/a.png', $result['content']);
        $this->assertSame(['https://cdn.test/a.png'], $result['images']);
    }

    public function test_duplicate_images_are_deduped(): void
    {
        $result = ArticlePublishContentNormalizer::normalize(
            '![a](/storage/x.png) 再来一次 ![b](/storage/x.png)',
            'http://host:18080'
        );

        $this->assertSame(['http://host:18080/storage/x.png'], $result['images']);
    }
}
