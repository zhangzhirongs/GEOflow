<?php

namespace App\Services\GeoFlow\AiVisibility;

use Carbon\CarbonInterface;

final class AiVisibilitySourceData
{
    /**
     * @param  array<string,mixed>  $metadata
     */
    public function __construct(
        public readonly string $sourceType,
        public readonly ?string $citationKey = null,
        public readonly ?string $title = null,
        public readonly ?string $url = null,
        public readonly ?string $domain = null,
        public readonly ?string $siteName = null,
        public readonly ?string $snippet = null,
        public readonly ?string $summary = null,
        public readonly ?string $contentExcerpt = null,
        public readonly ?CarbonInterface $publishedAt = null,
        public readonly ?int $rank = null,
        public readonly ?float $rankScore = null,
        public readonly ?string $authorityLevel = null,
        public readonly array $metadata = [],
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function toDatabaseAttributes(): array
    {
        $attributes = [
            'source_type' => $this->sourceType,
            'citation_key' => $this->citationKey,
            'title' => $this->title,
            'url' => $this->url,
            'domain' => $this->domain,
            'site_name' => $this->siteName,
            'snippet' => $this->snippet,
            'summary' => $this->summary,
            'content_excerpt' => $this->contentExcerpt,
            'published_at' => $this->publishedAt?->toDateTimeString(),
            'rank' => $this->rank,
            'rank_score' => $this->rankScore,
            'authority_level' => $this->authorityLevel,
            'metadata_json' => $this->metadata !== [] ? $this->metadata : null,
        ];

        foreach (['source_type' => 80, 'citation_key' => 40, 'title' => 500, 'domain' => 255, 'site_name' => 255, 'authority_level' => 80] as $field => $limit) {
            if (is_string($attributes[$field]) && mb_strlen($attributes[$field], 'UTF-8') > $limit) {
                $attributes['metadata_json']['original_fields'][$field] = $attributes[$field];
                $attributes[$field] = mb_substr($attributes[$field], 0, $limit, 'UTF-8');
            }
        }

        return $attributes;
    }
}
