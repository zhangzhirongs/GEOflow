<?php

namespace Tests\Unit;

use App\Services\GeoFlow\AiVisibility\AiVisibilitySourceData;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AiVisibilitySourceDataTest extends TestCase
{
    #[DataProvider('boundedFields')]
    public function test_it_bounds_unicode_fields_and_preserves_original_metadata(string $property, string $column, int $limit): void
    {
        $value = str_repeat("\u{6765}", $limit)."\u{1F680}";
        $metadata = [
            'provider_trace' => 'trace-123',
            'original_fields' => ['upstream_id' => 'source-456'],
        ];
        $arguments = array_replace([
            'sourceType' => 'native_annotation',
            'metadata' => $metadata,
        ], [$property => $value]);
        $source = new AiVisibilitySourceData(...$arguments);

        $attributes = $source->toDatabaseAttributes();

        $this->assertSame($limit, mb_strlen($attributes[$column], 'UTF-8'));
        $this->assertTrue(mb_check_encoding($attributes[$column], 'UTF-8'));
        $this->assertSame(str_repeat("\u{6765}", $limit), $attributes[$column]);
        $this->assertSame([
            'provider_trace' => 'trace-123',
            'original_fields' => ['upstream_id' => 'source-456', $column => $value],
        ], $attributes['metadata_json']);
        $this->assertSame($value, $source->{$property});
        $this->assertSame($metadata, $source->metadata);
    }

    public static function boundedFields(): array
    {
        return [
            'source type' => ['sourceType', 'source_type', 80],
            'citation key' => ['citationKey', 'citation_key', 40],
            'title' => ['title', 'title', 500],
            'domain' => ['domain', 'domain', 255],
            'site name' => ['siteName', 'site_name', 255],
            'authority level' => ['authorityLevel', 'authority_level', 80],
        ];
    }

    public function test_fields_at_the_unicode_limits_are_preserved_without_extra_metadata(): void
    {
        $source = new AiVisibilitySourceData(
            sourceType: str_repeat("\u{6765}", 80),
            citationKey: str_repeat("\u{6765}", 40),
            title: str_repeat("\u{6765}", 500),
            domain: str_repeat("\u{6765}", 255),
            siteName: str_repeat("\u{6765}", 255),
            authorityLevel: str_repeat("\u{6765}", 80),
            metadata: ['provider_trace' => 'trace-123'],
        );

        $attributes = $source->toDatabaseAttributes();

        $this->assertSame($source->sourceType, $attributes['source_type']);
        $this->assertSame($source->citationKey, $attributes['citation_key']);
        $this->assertSame($source->title, $attributes['title']);
        $this->assertSame($source->domain, $attributes['domain']);
        $this->assertSame($source->siteName, $attributes['site_name']);
        $this->assertSame($source->authorityLevel, $attributes['authority_level']);
        $this->assertSame($source->metadata, $attributes['metadata_json']);
    }

    public function test_truncation_preserves_original_values_when_metadata_was_empty(): void
    {
        $title = str_repeat("\u{6765}", 501);
        $siteName = str_repeat("\u{7ad9}", 256);

        $attributes = (new AiVisibilitySourceData(
            sourceType: 'answer_link',
            title: $title,
            siteName: $siteName,
        ))->toDatabaseAttributes();

        $this->assertSame([
            'original_fields' => ['title' => $title, 'site_name' => $siteName],
        ], $attributes['metadata_json']);
        $this->assertSame(str_repeat("\u{6765}", 500), $attributes['title']);
        $this->assertSame(str_repeat("\u{7ad9}", 255), $attributes['site_name']);
    }

    public function test_optional_null_fields_and_unbounded_text_are_preserved(): void
    {
        $text = str_repeat("\u{6765}", 2000);
        $url = 'https://example.com/'.str_repeat('a', 2000);
        $attributes = (new AiVisibilitySourceData(
            sourceType: 'web_search_result',
            url: $url,
            snippet: $text,
            summary: $text,
            contentExcerpt: $text,
        ))->toDatabaseAttributes();

        $this->assertNull($attributes['citation_key']);
        $this->assertNull($attributes['title']);
        $this->assertNull($attributes['domain']);
        $this->assertNull($attributes['site_name']);
        $this->assertNull($attributes['authority_level']);
        $this->assertNull($attributes['metadata_json']);
        $this->assertSame($url, $attributes['url']);
        $this->assertSame($text, $attributes['snippet']);
        $this->assertSame($text, $attributes['summary']);
        $this->assertSame($text, $attributes['content_excerpt']);
    }
}
