<?php

namespace Tests\Unit;

use App\Services\GeoFlow\AiVisibility\AiVisibilityResultNormalizer;
use Laravel\Ai\Responses\Data\UrlCitation;
use PHPUnit\Framework\TestCase;

class AiVisibilityResultNormalizerTest extends TestCase
{
    public function test_it_normalizes_doubao_ark_responses_annotations(): void
    {
        $result = (new AiVisibilityResultNormalizer)->normalizeArkResponses([
            'id' => 'resp_123',
            'output' => [
                [
                    'type' => 'web_search_call',
                    'id' => 'ws_123',
                    'status' => 'completed',
                    'action' => ['query' => 'GEOFlow'],
                ],
                [
                    'type' => 'message',
                    'content' => [
                        [
                            'type' => 'output_text',
                            'text' => 'GEOFlow 是一个面向 AI 可见性的内容系统。',
                            'annotations' => [
                                [
                                    'type' => 'url_citation',
                                    'title' => 'GEOFlow Docs',
                                    'url' => 'https://www.example.com/geoflow',
                                    'text' => 'GEOFlow Docs',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'usage' => [
                'input_tokens' => 100,
                'tool_usage' => ['web_search' => 1],
            ],
        ], ['payload' => ['model' => 'doubao-seed']], 'doubao-seed', 120);

        $this->assertSame('GEOFlow 是一个面向 AI 可见性的内容系统。', $result->answerText);
        $this->assertSame('doubao-seed', $result->modelId);
        $this->assertSame(120, $result->latencyMs);
        $this->assertCount(1, $result->sources);
        $this->assertSame('native_annotation', $result->sources[0]->sourceType);
        $this->assertSame('example.com', $result->sources[0]->domain);
        $this->assertSame('ws_123', $result->metadata['web_search_calls'][0]['id']);
    }

    public function test_it_normalizes_doubao_search_custom_results(): void
    {
        $result = (new AiVisibilityResultNormalizer)->normalizeDoubaoSearchCustom([
            'LogId' => 'log_123',
            'Result' => [
                'TimeCost' => 122,
                'WebResults' => [
                    [
                        'Title' => 'AI Visibility Guide',
                        'SiteName' => 'Example',
                        'Url' => 'https://example.com/ai-visibility',
                        'Snippet' => 'AI 可见性指南',
                        'Summary' => '介绍如何优化 AI 可见性。',
                        'Content' => '正文内容',
                        'PublishTime' => '2026-07-01T10:00:00+08:00',
                        'RankScore' => 0.98,
                        'AuthInfoLevel' => 'high',
                    ],
                ],
            ],
        ], ['payload' => ['Query' => 'AI visibility']], 88);

        $this->assertSame('', $result->answerText);
        $this->assertSame('doubao_search_custom', $result->providerType);
        $this->assertSame(88, $result->latencyMs);
        $this->assertSame('log_123', $result->metadata['log_id']);
        $this->assertCount(1, $result->sources);
        $this->assertSame('web_search_result', $result->sources[0]->sourceType);
        $this->assertSame('example.com', $result->sources[0]->domain);
        $this->assertSame(0.98, $result->sources[0]->rankScore);
    }

    public function test_it_normalizes_arrayable_sdk_url_citations(): void
    {
        $citation = new UrlCitation(
            url: 'https://www.example.com/geoflow',
            title: 'GEOFlow documentation',
            startIndex: 8,
            endIndex: 24,
        );

        $sources = (new AiVisibilityResultNormalizer)->answerSources('GEOFlow has documentation.', [$citation]);

        $this->assertCount(1, $sources);
        $this->assertSame('native_annotation', $sources[0]->sourceType);
        $this->assertSame('S1', $sources[0]->citationKey);
        $this->assertSame('GEOFlow documentation', $sources[0]->title);
        $this->assertSame($citation->url, $sources[0]->url);
        $this->assertSame('example.com', $sources[0]->domain);
        $this->assertSame(1, $sources[0]->rank);
        $this->assertSame($citation->toArray(), $sources[0]->metadata);
    }

    public function test_it_normalizes_nested_url_citation_annotations(): void
    {
        $annotation = [
            'type' => 'url_citation',
            'url_citation' => [
                'url' => 'https://www.example.com/nested',
                'title' => 'Nested citation',
                'site_name' => 'Example research',
                'start_index' => 2,
                'end_index' => 16,
            ],
            'text' => 'The cited finding.',
        ];

        $sources = (new AiVisibilityResultNormalizer)->answerSources('The cited finding.', [$annotation]);

        $this->assertCount(1, $sources);
        $this->assertSame('native_annotation', $sources[0]->sourceType);
        $this->assertSame('https://www.example.com/nested', $sources[0]->url);
        $this->assertSame('Nested citation', $sources[0]->title);
        $this->assertSame('example.com', $sources[0]->domain);
        $this->assertSame('Example research', $sources[0]->siteName);
        $this->assertSame('The cited finding.', $sources[0]->snippet);
        $this->assertSame($annotation, $sources[0]->metadata);
    }

    public function test_it_extracts_actual_markdown_and_autolinks_without_sources_from_code_or_images(): void
    {
        $answer = <<<'MARKDOWN'
[GEOFlow **documentation**](https://www.example.com/docs)
[Reference guide][guide]
<https://example.com/autolink>
Visit https://example.com/plain for details.
`[Code example](https://example.com/inline-code)`
![Screenshot](https://example.com/image.png)

```text
[Code sample](https://example.com/fenced-code)
https://example.com/fenced-url
```

[guide]: http://reference.example.com/guide
MARKDOWN;

        $sources = (new AiVisibilityResultNormalizer)->answerSources($answer);

        $this->assertSame([
            'https://www.example.com/docs',
            'http://reference.example.com/guide',
            'https://example.com/autolink',
            'https://example.com/plain',
        ], array_column($sources, 'url'));
        $this->assertSame(array_fill(0, 4, 'answer_link'), array_column($sources, 'sourceType'));
        $this->assertSame(['L1', 'L2', 'L3', 'L4'], array_column($sources, 'citationKey'));
        $this->assertSame([1, 2, 3, 4], array_column($sources, 'rank'));
        $this->assertSame('GEOFlow documentation', $sources[0]->title);
        $this->assertSame('example.com', $sources[0]->domain);
        $this->assertSame('example.com', $sources[0]->siteName);
        $this->assertSame('Reference guide', $sources[1]->title);
    }

    public function test_duplicate_answer_urls_keep_the_first_native_citation_and_its_metadata(): void
    {
        $url = 'https://example.com/shared';
        $citation = new UrlCitation($url, 'Provider citation', 4, 12);
        $answer = '[Answer label]('.$url.")\n<".$url.">\n[Other](https://example.com/other)\n".$url;

        $sources = (new AiVisibilityResultNormalizer)->answerSources($answer, [
            $citation,
            ['url_citation' => ['url' => $url, 'title' => 'Duplicate provider citation']],
        ]);

        $this->assertCount(2, $sources);
        $this->assertSame([$url, 'https://example.com/other'], array_column($sources, 'url'));
        $this->assertSame(['native_annotation', 'answer_link'], array_column($sources, 'sourceType'));
        $this->assertSame('Provider citation', $sources[0]->title);
        $this->assertSame($citation->toArray(), $sources[0]->metadata);
        $this->assertSame([1, 2], array_column($sources, 'rank'));
        $this->assertSame('L2', $sources[1]->citationKey);
    }

    public function test_it_excludes_unsafe_schemes_and_relative_urls_from_citations_and_answer_links(): void
    {
        $unsafeUrls = [
            'javascript:alert%281%29',
            'vbscript:msgbox%281%29',
            'data:text/html,unsafe',
            'file:///etc/passwd',
            'ftp://example.com/file',
            'mailto:editor@example.com',
            '//example.com/relative',
            '/relative',
            'https:/missing-host',
        ];
        $answer = "[Secure source](https://example.com/secure)\n[HTTP source](http://example.com/plain)\n";
        $citations = [];
        foreach ($unsafeUrls as $url) {
            $answer .= '[Unsafe source]('.$url.")\n";
            $citations[] = new UrlCitation($url, 'Unsafe SDK citation');
            $citations[] = ['url_citation' => ['url' => $url, 'title' => 'Unsafe nested citation']];
        }

        $sources = (new AiVisibilityResultNormalizer)->answerSources($answer, $citations);

        $this->assertSame([
            'https://example.com/secure',
            'http://example.com/plain',
        ], array_column($sources, 'url'));
        $this->assertSame(['answer_link', 'answer_link'], array_column($sources, 'sourceType'));
    }

    public function test_it_does_not_invent_sources_for_plain_text_or_malformed_citations(): void
    {
        $sources = (new AiVisibilityResultNormalizer)->answerSources(
            'GEOFlow is mentioned without a link or citation.',
            [null, 'Invalid citation', new \stdClass, [], ['title' => 'A title without a URL']],
        );

        $this->assertSame([], $sources);
        $this->assertSame([], (new AiVisibilityResultNormalizer)->answerSources(''));
    }
}
