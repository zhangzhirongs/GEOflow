<?php

namespace App\Support\GeoFlow;

/**
 * 规范化发往执行器/发布网关的文章正文：
 * - 把正文中的相对图片路径补成带主机名的绝对 URL（Markdown 的 ![](...) 与 HTML 的 <img src>）；
 * - 顺带抽取出全部图片的绝对 URL 列表，供执行器下载/上传或在交接面板中展示。
 */
final class ArticlePublishContentNormalizer
{
    /**
     * @return array{content: string, images: array<int, string>}
     */
    public static function normalize(string $content, ?string $baseUrl = null): array
    {
        $base = rtrim($baseUrl ?? (string) config('app.url'), '/');
        $images = [];

        $content = (string) preg_replace_callback(
            '/!\[([^\]]*)\]\(\s*<?([^)\s>]+)>?([^)]*)\)/u',
            static function (array $matches) use ($base, &$images): string {
                $abs = self::absolutize((string) ($matches[2] ?? ''), $base);
                if ($abs !== '') {
                    $images[] = $abs;
                }

                return '!['.($matches[1] ?? '').']('.$abs.($matches[3] ?? '').')';
            },
            $content
        );

        $content = (string) preg_replace_callback(
            '/<img\b([^>]*?)\bsrc\s*=\s*(["\'])(.*?)\2([^>]*)>/i',
            static function (array $matches) use ($base, &$images): string {
                $abs = self::absolutize((string) ($matches[3] ?? ''), $base);
                if ($abs !== '') {
                    $images[] = $abs;
                }

                return '<img'.($matches[1] ?? '').'src="'.$abs.'"'.($matches[4] ?? '').'>';
            },
            $content
        );

        return [
            'content' => $content,
            'images' => array_values(array_unique(array_filter($images))),
        ];
    }

    private static function absolutize(string $raw, string $base): string
    {
        $public = ImageUrlNormalizer::toPublicUrl($raw);
        if ($public === '') {
            return '';
        }

        if (
            str_starts_with($public, 'http://')
            || str_starts_with($public, 'https://')
            || str_starts_with($public, '//')
            || str_starts_with($public, 'data:')
        ) {
            return $public;
        }

        if ($base === '') {
            return $public;
        }

        return $base.'/'.ltrim($public, '/');
    }
}
