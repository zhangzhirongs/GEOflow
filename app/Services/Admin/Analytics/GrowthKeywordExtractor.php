<?php

namespace App\Services\Admin\Analytics;

class GrowthKeywordExtractor
{
    public const VERSION = 3;

    private const STOP_WORDS = [
        'the', 'and', 'for', 'with', 'from', 'that', 'this', 'are', 'was', 'were', 'you', 'your', 'our',
        'have', 'has', 'will', 'can', 'not', 'but', 'its', 'into', 'their', 'they', 'these', 'those',
        'about', 'more', 'also', 'such', 'than', 'which', 'when', 'what', 'how', 'why', 'use', 'using',
        'http', 'https', 'www', 'com', 'html', 'json', 'true', 'false', 'null', '来源', '参考', '详情',
        '回答', '问题', '例如', '比如', '目前', '通常', '主要', '以下', '以上', '相关', '一些', '一个',
        '我们', '你们', '他们', '它们', '这些', '那些', '这里', '这个', '那个', '建议', '首先', '其次',
        '最后', '因此', '同时', '此外', '以及', '或者', '但是', '所以', '如果', '可以', '需要', '能够',
        '通过', '进行', '提供', '包括', '帮助', '提升', '提高', '实现', '推荐', '选择', '适合', '使用',
        '支持', '针对', '面向', '用于', '采用', '了解', '参考资料', '请问', '哪些', '什么', '如何',
        '怎么', '是否', '具有', '具备', '专注', '更好', '有效', '完成', '解决', '有关', '根据',
        '介绍', '一款', '一家', '一种', '一套', '其中', '来说', '值得', '看看', '为了', '比如说',
        '这是', '那是', '很多', '多个', '各类', '各个', '可能', '应该', '正在', '非常', '会先看', '先看',
        '是一家', '是一款', '是一个', '是一种', '是一套', '是', '在', '有', '更', '很', '也', '了',
        '致力于', '致力', '打造', '拥有', '涵盖', '结合', '基于', '借助', '围绕', '注重', '擅长',
        '强调', '聚焦', '涉及', '包含', '覆盖', '推出', '带来', '获得', '适用于', '取决于',
        '而言', '方面', '之一', '等', '及其', '其', '则', '并且', '并', '较为', '比较',
        'thanks', 'please', 'however', 'therefore', 'including', 'based', 'may', 'would', 'could',
    ];

    /** @param list<string> $vocabulary @return list<string> */
    public function extract(string $text, array $vocabulary = []): array
    {
        $text = $this->clean($text);
        $candidates = [];
        $remaining = $text;
        usort($vocabulary, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        foreach ($vocabulary as $term) {
            $term = trim($term);
            if (! $this->valid($term, 24) || $this->isNarrative($term)) {
                continue;
            }
            $pattern = '/(?<![A-Za-z0-9_])'.preg_quote($term, '/').'(?![A-Za-z0-9_])/iu';
            // Longest matching names consume the text, so aliases cannot crowd out products.
            $count = preg_match_all($pattern, $remaining);
            if ($count > 0) {
                $key = mb_strtolower($term);
                $candidates[$key] ??= ['term' => $term, 'count' => $count, 'known' => true];
                $remaining = preg_replace($pattern, ' ', $remaining) ?? $remaining;
            }
        }

        $stops = self::STOP_WORDS;
        usort($stops, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        $separators = implode('|', array_map(fn (string $word): string => preg_quote($word, '/'),
            array_filter($stops, fn (string $word): bool => mb_strlen($word) >= 2 && preg_match('/\p{Han}/u', $word) === 1)));
        // Keep mixed product names such as AI营销助手 and iPhone16 intact.
        preg_match_all('/[\p{Han}A-Za-z][\p{Han}A-Za-z0-9]*(?:[+#.-][A-Za-z0-9]+)*(?:\+{1,2}|#)?/u', $remaining, $matches);
        foreach ($matches[0] as $chunk) {
            $terms = preg_match('/\p{Han}/u', $chunk)
                ? (preg_split('/(?:'.$separators.'|(?<!目)的|(?<=\p{Han}{2})[和与及](?=[\p{Han}A-Za-z]{2}))/u', $chunk) ?: [])
                : [$chunk];
            foreach ($terms as $term) {
                if (! $this->valid($term, 12) || $this->isNarrative($term)) {
                    continue;
                }
                $key = mb_strtolower($term);
                $candidates[$key] ??= ['term' => $term, 'count' => 0, 'known' => false];
                $candidates[$key]['count']++;
            }
        }

        uasort($candidates, fn (array $a, array $b): int =>
            ($b['known'] <=> $a['known']) ?: ($b['count'] <=> $a['count']));

        return array_values(array_map(fn (array $row): string => $row['term'], array_slice($candidates, 0, 12)));
    }

    private function isNarrative(string $term): bool
    {
        return preg_match('/(?:可以|能够|需要|如何|怎么|是否|推荐|建议|提供|帮助|提升|实现|支持|具有|具备|致力|打造|拥有|涵盖|推出|值得|通常|主要|首先|其次|例如|但是|因此|不仅|而且|必须|已经|正在|适合|比较|更加)/u', $term) === 1
            || preg_match('/^(?:这里|这个|那个|我们|你们|他们|它们|这些|那些)|(?:的是|的话|来说|来看)$/u', $term) === 1;
    }

    private function valid(string $term, int $maximum): bool
    {
        return mb_strlen($term) >= 2 && mb_strlen($term) <= $maximum
            && preg_match('/^[\p{Han}A-Za-z0-9][\p{Han}A-Za-z0-9 +#._-]*$/u', $term) === 1
            && ! in_array($term, ['内容', '数据', '信息', '公司', '产品', '品牌', '功能', '优势', '分析'], true)
            && ! in_array(mb_strtolower($term), self::STOP_WORDS, true);
    }

    private function clean(string $text): string
    {
        $text = mb_substr($text, 0, 100000);
        $text = preg_replace('/```[\s\S]*?```/u', ' ', $text) ?? $text;
        $text = preg_replace('/!?\[([^\]\n]*)\]\([^\s)]*\)/u', '$1', $text) ?? $text;
        $text = preg_replace('~(?:https?://|www\.)[^\s<>，。；！？]+~iu', ' ', $text) ?? $text;
        $text = preg_replace('~\b(?:[A-Za-z0-9-]+\.)+(?:com|cn|net|org|io|ai|edu|gov)(?:/[^\s<>，。；！？]*)?\b~iu', ' ', $text) ?? $text;
        $text = preg_replace('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/u', ' ', $text) ?? $text;
        $text = preg_replace('/\[(?:S?\d+)(?:[,，\s]+\d+)*\]/u', ' ', $text) ?? $text;

        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace('/[^\p{L}\p{N}\s+#._-]/u', ' ', $text) ?? '';
    }
}
