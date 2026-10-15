<?php

namespace App\Services\Admin\Analytics;

use App\Models\AiGrowthProbe;
use App\Models\AiVisibilityRun;
use App\Models\AiVisibilitySource;
use App\Models\Article;
use App\Models\SocialPublication;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GrowthAiMentionAnalyzer
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(string $question, AiVisibilityRun $run): array
    {
        $brandAliases = $this->brandAliases();
        $ownedHosts = $this->ownedHosts();
        $answerText = trim((string) $run->answer_text);
        $sources = $this->sourceRows($run);
        $haystack = $this->haystack($answerText, $sources);
        $brandMentioned = $this->containsAny($haystack, $brandAliases);

        $ownedArticles = $this->matchOwnedArticles($haystack, $brandMentioned);
        $ownedPosts = $this->matchOwnedPosts($haystack);
        $ownedContentMentioned = $ownedArticles !== [] || $ownedPosts !== [];

        $ownedKeywords = $this->collectOwnedKeywords($ownedArticles, $ownedPosts, $answerText);
        $competitors = $ownedContentMentioned
            ? []
            : $this->extractCompetitors($sources, $answerText, $ownedHosts, $brandAliases);

        $verdict = $this->verdict($ownedContentMentioned, $brandMentioned, $competitors !== []);

        return [
            'question' => trim($question),
            'verdict' => $verdict,
            'brand_mentioned' => $brandMentioned,
            'owned_content_mentioned' => $ownedContentMentioned,
            'owned_articles' => $ownedArticles,
            'owned_posts' => $ownedPosts,
            'owned_keywords' => $ownedKeywords,
            'competitors' => $competitors,
            'answer_keywords' => $this->extractTerms($answerText, $brandAliases),
            'source_count' => count($sources),
            'sources' => $sources,
            'provider_type' => (string) $run->provider_type,
            'summary' => $this->summary($verdict, $ownedArticles, $ownedPosts, $competitors),
        ];
    }

    /**
     * @param  array<string, mixed>  $analysis
     */
    public function excerpt(AiVisibilityRun $run, array $analysis): string
    {
        $answer = trim((string) $run->answer_text);
        if ($answer === '') {
            $summary = trim((string) ($analysis['summary'] ?? ''));

            return $summary;
        }

        return mb_substr($answer, 0, 800, 'UTF-8');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function sourceRows(AiVisibilityRun $run): array
    {
        $run->loadMissing('sources');

        return $run->sources
            ->values()
            ->map(function (AiVisibilitySource $source, int $index): array {
                $title = trim((string) $source->title);
                $url = $this->webUrl((string) $source->url);
                $domain = $this->normalizeHost((string) ($source->domain ?: parse_url($url, PHP_URL_HOST) ?: $source->site_name));

                return [
                    'source_type' => (string) $source->source_type,
                    'citation_key' => (string) ($source->citation_key ?: 'S'.($index + 1)),
                    'title' => $title,
                    'url' => $url,
                    'domain' => $domain,
                    'site_name' => trim((string) $source->site_name),
                    'snippet' => trim((string) ($source->snippet ?: $source->summary ?: $source->content_excerpt)),
                    'rank' => (int) ($source->rank ?: $index + 1),
                    'published_at' => $source->published_at?->toDateTimeString(),
                ];
            })
            ->all();
    }

    public function webUrl(string $url): string
    {
        $url = trim($url);

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
            && (string) parse_url($url, PHP_URL_HOST) !== ''
            ? $url
            : '';
    }

    /**
     * @param  list<array<string, mixed>>  $sources
     */
    private function haystack(string $answerText, array $sources): string
    {
        $parts = [$answerText];
        foreach ($sources as $source) {
            $parts[] = (string) ($source['title'] ?? '');
            $parts[] = (string) ($source['url'] ?? '');
            $parts[] = (string) ($source['domain'] ?? '');
            $parts[] = (string) ($source['site_name'] ?? '');
            $parts[] = (string) ($source['snippet'] ?? '');
        }

        return Str::lower(implode("\n", array_filter($parts, static fn (string $part): bool => trim($part) !== '')));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function matchOwnedArticles(string $haystack, bool $brandMentioned): array
    {
        if (! Schema::hasTable('articles') || $haystack === '') {
            return [];
        }

        $articles = Article::query()
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'title', 'slug', 'keywords', 'original_keyword', 'excerpt']);

        $matched = [];
        foreach ($articles as $article) {
            $match = $this->articleMatch($article, $haystack, $brandMentioned);
            if ($match === null) {
                continue;
            }

            $matched[] = [
                'id' => (int) $article->id,
                'title' => (string) $article->title,
                'slug' => (string) $article->slug,
                'url' => route('site.article', ['slug' => (string) $article->slug]),
                'matched_on' => $match['matched_on'],
                'keywords' => $match['keywords'],
                'mentioned_keywords' => $match['mentioned_keywords'],
            ];
        }

        return $matched;
    }

    /**
     * @return array{matched_on: string, keywords: list<string>, mentioned_keywords: list<string>}|null
     */
    private function articleMatch(Article $article, string $haystack, bool $brandMentioned): ?array
    {
        $title = trim((string) $article->title);
        $slug = trim((string) $article->slug);
        $keywords = $this->articleKeywords($article);
        $mentionedKeywords = [];
        foreach ($keywords as $keyword) {
            if (mb_strlen($keyword) >= 2 && str_contains($haystack, Str::lower($keyword))) {
                $mentionedKeywords[] = $keyword;
            }
        }

        $matchedOn = null;
        if ($slug !== '' && str_contains($haystack, Str::lower('/article/'.$slug))) {
            $matchedOn = 'url';
        } elseif ($title !== '' && mb_strlen($title) >= 4 && str_contains($haystack, Str::lower($title))) {
            $matchedOn = 'title';
        } elseif ($brandMentioned) {
            foreach ($mentionedKeywords as $keyword) {
                if (mb_strlen($keyword) >= 4) {
                    $matchedOn = 'keyword';
                    break;
                }
            }
        }

        if ($matchedOn === null) {
            return null;
        }

        return [
            'matched_on' => $matchedOn,
            'keywords' => $keywords,
            'mentioned_keywords' => $mentionedKeywords,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function matchOwnedPosts(string $haystack): array
    {
        if (! Schema::hasTable('social_publications') || $haystack === '') {
            return [];
        }

        $posts = SocialPublication::query()
            ->with('article:id,title,slug,keywords,original_keyword')
            ->whereNotNull('remote_url')
            ->where('remote_url', '!=', '')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $matched = [];
        foreach ($posts as $post) {
            $remoteUrl = trim((string) $post->remote_url);
            $payload = is_array($post->payload_snapshot) ? $post->payload_snapshot : [];
            $payloadTitle = trim((string) ($payload['title'] ?? $payload['content'] ?? ''));
            $urlHit = $remoteUrl !== '' && str_contains($haystack, Str::lower($remoteUrl));
            $titleHit = mb_strlen($payloadTitle) >= 4 && str_contains($haystack, Str::lower($payloadTitle));

            if (! $urlHit && ! $titleHit) {
                continue;
            }

            $article = $post->article;
            $keywords = $article instanceof Article ? $this->articleKeywords($article) : [];
            $mentionedKeywords = [];
            foreach ($keywords as $keyword) {
                if (mb_strlen($keyword) >= 2 && str_contains($haystack, Str::lower($keyword))) {
                    $mentionedKeywords[] = $keyword;
                }
            }

            $matched[] = [
                'id' => (int) $post->id,
                'platform' => (string) $post->platform,
                'remote_url' => $remoteUrl,
                'article_id' => (int) $post->article_id,
                'title' => $payloadTitle !== '' ? $payloadTitle : (string) ($article?->title ?? ''),
                'matched_on' => $urlHit ? 'url' : 'title',
                'keywords' => $keywords,
                'mentioned_keywords' => $mentionedKeywords,
            ];
        }

        return $matched;
    }

    /**
     * @param  list<array<string, mixed>>  $ownedArticles
     * @param  list<array<string, mixed>>  $ownedPosts
     * @return list<array{term: string, count: int, source: string, in_answer: bool}>
     */
    private function collectOwnedKeywords(array $ownedArticles, array $ownedPosts, string $answerText): array
    {
        $counts = [];
        foreach ([$ownedArticles, $ownedPosts] as $rows) {
            foreach ($rows as $row) {
                foreach ((array) ($row['keywords'] ?? []) as $keyword) {
                    $keyword = trim((string) $keyword);
                    if ($keyword === '') {
                        continue;
                    }
                    $key = Str::lower($keyword);
                    $inAnswer = str_contains(Str::lower($answerText), Str::lower($keyword));
                    $counts[$key] = [
                        'term' => $keyword,
                        'count' => (int) (($counts[$key]['count'] ?? 0) + 1),
                        'source' => 'content',
                        'in_answer' => (bool) (($counts[$key]['in_answer'] ?? false) || $inAnswer),
                    ];
                }
            }
        }

        return collect($counts)
            ->sortBy([
                ['in_answer', 'desc'],
                ['count', 'desc'],
            ])
            ->take(20)
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $sources
     * @param  list<string>  $ownedHosts
     * @param  list<string>  $brandAliases
     * @return list<array<string, mixed>>
     */
    private function extractCompetitors(array $sources, string $answerText, array $ownedHosts, array $brandAliases): array
    {
        $groups = [];
        foreach ($sources as $source) {
            $domain = (string) ($source['domain'] ?? '');
            if ($domain === '' || $this->domainMatches($domain, $ownedHosts)) {
                continue;
            }

            $name = trim((string) ($source['site_name'] ?? ''));
            if ($name === '' || $this->containsAny($name, $brandAliases)) {
                $name = $this->companyNameFromDomain($domain);
            }
            if ($name === '' || $this->containsAny($name, $brandAliases)) {
                continue;
            }

            $key = Str::lower($domain !== '' ? $domain : $name);
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'name' => $name,
                    'domain' => $domain,
                    'posts' => [],
                    'keywords' => [],
                ];
            }

            $title = trim((string) ($source['title'] ?? ''));
            if ($title !== '') {
                $groups[$key]['posts'][] = [
                    'title' => $title,
                    'url' => (string) ($source['url'] ?? ''),
                    'rank' => (int) ($source['rank'] ?? 0),
                ];
            }

            $text = trim($title.' '.(string) ($source['snippet'] ?? ''));
            $groups[$key]['keywords'] = array_values(array_unique(array_merge(
                $groups[$key]['keywords'],
                $this->extractTerms($text, $brandAliases),
            )));
        }

        foreach ($this->extractAnswerCompanies($answerText, $brandAliases) as $company) {
            $alreadyGrouped = collect($groups)->contains(function (array $group) use ($company): bool {
                return Str::lower((string) $group['name']) === Str::lower($company)
                    || str_contains(Str::lower((string) $group['domain']), Str::lower($company));
            });
            if ($alreadyGrouped) {
                continue;
            }

            $groups[Str::lower($company)] = [
                'name' => $company,
                'domain' => '',
                'posts' => [],
                'keywords' => $this->extractTerms($answerText, array_merge($brandAliases, [$company])),
            ];
        }

        return collect($groups)
            ->map(function (array $group): array {
                $group['posts'] = array_slice($group['posts'], 0, 5);
                $group['keywords'] = array_slice($group['keywords'], 0, 8);

                return $group;
            })
            ->sortByDesc(fn (array $group): int => count($group['posts']))
            ->take(8)
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $ownedArticles
     * @param  list<array<string, mixed>>  $ownedPosts
     * @param  list<array<string, mixed>>  $competitors
     */
    private function summary(string $verdict, array $ownedArticles, array $ownedPosts, array $competitors): string
    {
        return match ($verdict) {
            AiGrowthProbe::VERDICT_OWNED_CONTENT => sprintf(
                'AI 回答提到了当前公司的 %d 篇文章、%d 条推文，已提取相关关键词。',
                count($ownedArticles),
                count($ownedPosts),
            ),
            AiGrowthProbe::VERDICT_OWNED_BRAND => 'AI 回答提到了当前品牌，但没有明确引用已发布文章或推文。',
            AiGrowthProbe::VERDICT_COMPETITOR => sprintf(
                'AI 回答未引用当前公司内容，已提取 %d 个竞品来源及其推文、关键词。',
                count($competitors),
            ),
            default => 'AI 回答没有识别到当前品牌或可分析的竞品引用。',
        };
    }

    private function verdict(bool $ownedContent, bool $brandMentioned, bool $hasCompetitors): string
    {
        if ($ownedContent) {
            return AiGrowthProbe::VERDICT_OWNED_CONTENT;
        }
        if ($brandMentioned) {
            return AiGrowthProbe::VERDICT_OWNED_BRAND;
        }
        if ($hasCompetitors) {
            return AiGrowthProbe::VERDICT_COMPETITOR;
        }

        return AiGrowthProbe::VERDICT_NONE;
    }

    /**
     * @return list<string>
     */
    private function articleKeywords(Article $article): array
    {
        $raw = trim((string) $article->keywords.' '.(string) $article->original_keyword);
        $parts = preg_split('/[,，、;；|\\s]+/u', $raw) ?: [];

        return collect($parts)
            ->map(fn (string $keyword): string => trim($keyword))
            ->filter(fn (string $keyword): bool => mb_strlen($keyword) >= 2)
            ->unique(fn (string $keyword): string => Str::lower($keyword))
            ->take(12)
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $aliases
     */
    private function containsAny(string $text, array $aliases): bool
    {
        $text = Str::lower($text);
        foreach ($aliases as $alias) {
            $alias = Str::lower(trim($alias));
            if ($alias !== '' && mb_strlen($alias) >= 2 && str_contains($text, $alias)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $ownedHosts
     */
    private function domainMatches(string $domain, array $ownedHosts): bool
    {
        $domain = $this->normalizeHost($domain);
        foreach ($ownedHosts as $host) {
            if ($domain === $host || str_ends_with($domain, '.'.$host)) {
                return true;
            }
        }

        return false;
    }

    private function companyNameFromDomain(string $domain): string
    {
        $domain = $this->normalizeHost($domain);
        $parts = explode('.', $domain);
        $name = $parts[0] ?? $domain;

        return $name === 'www' ? (string) ($parts[1] ?? $domain) : $name;
    }

    private function normalizeHost(string $host): string
    {
        $host = Str::lower(trim($host));
        $host = preg_replace('/^https?:\/\//', '', $host) ?? $host;
        $host = preg_replace('/\/.*$/', '', $host) ?? $host;
        $host = preg_replace('/^www\./', '', $host) ?? $host;

        return trim($host);
    }

    /**
     * @return list<string>
     */
    private function brandAliases(): array
    {
        $aliases = collect([
            config('geoflow.site_name'),
            config('geoflow.site_full_name'),
            config('app.name'),
            'GEOFlow',
        ])
            ->map(fn (mixed $alias): string => trim((string) $alias))
            ->filter(fn (string $alias): bool => $alias !== '' && mb_strlen($alias) >= 2 && Str::lower($alias) !== 'laravel');

        return $aliases
            ->unique(fn (string $alias): string => Str::lower($alias))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function ownedHosts(): array
    {
        return collect([
            config('geoflow.site_url'),
            config('app.url'),
        ])
            ->map(fn (mixed $url): string => $this->normalizeHost((string) (parse_url((string) $url, PHP_URL_HOST) ?: $url)))
            ->filter(fn (string $host): bool => $host !== '' && ! in_array($host, ['localhost', '127.0.0.1', '::1'], true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $brandAliases
     * @return list<string>
     */
    private function extractAnswerCompanies(string $text, array $brandAliases): array
    {
        $skip = [
            'GEO', 'SEO', 'AEO', 'AIO', 'AI', 'LLM', 'GPT', 'API', 'HTTP', 'HTTPS', 'JSON', 'CMS', 'SaaS', 'FAQ', 'URL', 'PDF',
            'Docker', 'Nginx', 'Linux', 'Windows',
        ];
        $names = [];

        preg_match_all('/\b[A-Z][A-Za-z0-9]{2,}\b/u', $text, $latinMatches);
        foreach ($latinMatches[0] ?? [] as $name) {
            $name = trim($name);
            if ($name === '' || in_array($name, $skip, true) || $this->containsAny($name, $brandAliases)) {
                continue;
            }
            $names[Str::lower($name)] = $name;
        }

        preg_match_all('/[\x{4e00}-\x{9fff}]{2,12}(?:公司|科技|集团|研究院)/u', $text, $chineseMatches);
        foreach ($chineseMatches[0] ?? [] as $name) {
            $name = trim($name);
            if ($name === '' || $this->containsAny($name, $brandAliases)) {
                continue;
            }
            $names[Str::lower($name)] = $name;
        }

        return array_values($names);
    }

    /**
     * @param  list<string>  $brandAliases
     * @return list<string>
     */
    private function extractTerms(string $text, array $brandAliases): array
    {
        $stopWords = [
            'the', 'and', 'for', 'with', 'from', 'that', 'this', 'are', 'was', 'http', 'https', 'www', 'com', 'html',
            '以及', '对应', '这个', '相关', '可以', '进行', '通过', '一个', '一些', '包括', '结果', '信息', '数据', '内容', '分析', '回答',
        ];
        $brandAliasSet = collect($brandAliases)->map(fn (string $alias): string => Str::lower($alias))->all();

        preg_match_all('/[A-Za-z][A-Za-z0-9_+-]{1,}|[\x{4e00}-\x{9fff}]{2,8}/u', $text, $matches);
        $terms = $matches[0] ?? [];

        return collect($terms)
            ->map(fn (string $term): string => trim($term))
            ->filter(function (string $term) use ($stopWords, $brandAliasSet): bool {
                if ($term === '' || in_array(Str::lower($term), $stopWords, true) || in_array($term, $stopWords, true)) {
                    return false;
                }
                if (mb_strlen($term) > 12) {
                    return false;
                }
                foreach ($brandAliasSet as $alias) {
                    if ($alias !== '' && Str::lower($term) === $alias) {
                        return false;
                    }
                }

                return true;
            })
            ->countBy(fn (string $term): string => Str::lower($term))
            ->sortDesc()
            ->keys()
            ->take(12)
            ->values()
            ->all();
    }
}
