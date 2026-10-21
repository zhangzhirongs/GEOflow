<?php

namespace App\Support\Admin;

use App\Models\ManualPublicationAccount;
use App\Support\Site\ArticleHtmlPresenter;
use DOMDocument;
use DOMElement;
use DOMXPath;

final class ArticlePlatformCopyFormatter
{
    public function __construct(private readonly WeChatArticleHtmlExporter $weChatExporter)
    {
    }

    /**
     * @return array{format: 'html'|'markdown'|'plain', html: string, text: string, message: string}
     */
    public function format(string $markdown, string $platform): array
    {
        $markdown = trim(str_replace("\r\n", "\n", $markdown));
        $platform = mb_strtolower(trim($platform), 'UTF-8');

        if ($markdown === '') {
            return [
                'format' => 'plain',
                'html' => '',
                'text' => '',
                'message' => __('admin.article_editor.copy.empty'),
            ];
        }

        return match ($platform) {
            ManualPublicationAccount::PLATFORM_WECHAT_MP => $this->wechatCopy($markdown),
            ManualPublicationAccount::PLATFORM_ZHIHU => $this->htmlCopy($markdown, 'zhihu-article', $platform),
            ManualPublicationAccount::PLATFORM_BILIBILI,
            ManualPublicationAccount::PLATFORM_CSDN => $this->markdownCopy($markdown, $platform),
            default => $this->plainCopy($markdown, $platform),
        };
    }

    /**
     * @return array{format: 'html', html: string, text: string, message: string}
     */
    private function wechatCopy(string $markdown): array
    {
        $html = $this->weChatExporter->toHtml($markdown);

        return [
            'format' => 'html',
            'html' => $html,
            'text' => $html === '' ? '' : $this->weChatExporter->toPlainText($html),
            'message' => $this->successMessage(ManualPublicationAccount::PLATFORM_WECHAT_MP),
        ];
    }

    /**
     * @return array{format: 'html', html: string, text: string, message: string}
     */
    private function htmlCopy(string $markdown, string $exportName, string $platform): array
    {
        $html = $this->toSemanticHtml($markdown, $exportName);

        return [
            'format' => 'html',
            'html' => $html,
            'text' => $html === '' ? '' : $this->weChatExporter->toPlainText($html),
            'message' => $this->successMessage($platform),
        ];
    }

    /**
     * @return array{format: 'markdown', html: string, text: string, message: string}
     */
    private function markdownCopy(string $markdown, string $platform): array
    {
        return [
            'format' => 'markdown',
            'html' => '',
            'text' => $markdown,
            'message' => $this->successMessage($platform),
        ];
    }

    /**
     * @return array{format: 'plain', html: string, text: string, message: string}
     */
    private function plainCopy(string $markdown, string $platform): array
    {
        return [
            'format' => 'plain',
            'html' => '',
            'text' => $this->toPlainText($markdown),
            'message' => $this->successMessage($platform),
        ];
    }

    private function successMessage(string $platform): string
    {
        $key = 'admin.article_editor.platform_copy.success.'.$platform;
        $message = __($key);

        return $message === $key
            ? __('admin.article_editor.platform_copy.success.custom')
            : $message;
    }

    private function toSemanticHtml(string $markdown, string $exportName): string
    {
        $body = ArticleHtmlPresenter::markdownToHtml($markdown);
        if (trim($body) === '') {
            return '';
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="utf-8" ?><section data-geoflow-export="'.$exportName.'">'.$body.'</section>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        foreach ($dom->childNodes as $node) {
            if ($node->nodeType === XML_PI_NODE) {
                $dom->removeChild($node);
                break;
            }
        }

        $root = $dom->getElementsByTagName('section')->item(0);
        if (! $root instanceof DOMElement) {
            return '<section data-geoflow-export="'.$exportName.'">'.$body.'</section>';
        }

        $this->normalizeSemanticHtml($dom);
        $root->setAttribute('data-geoflow-export', $exportName);

        return trim($dom->saveHTML($root) ?: '');
    }

    private function normalizeSemanticHtml(DOMDocument $dom): void
    {
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//*');
        if ($nodes === false) {
            return;
        }

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);
            $allowed = match ($tag) {
                'a' => ['href', 'title', 'target', 'rel'],
                'img' => ['src', 'alt', 'title', 'width', 'height'],
                'td', 'th' => ['colspan', 'rowspan'],
                'section' => ['data-geoflow-export'],
                default => [],
            };

            for ($i = $node->attributes->length - 1; $i >= 0; $i--) {
                $attribute = $node->attributes->item($i);
                if ($attribute === null) {
                    continue;
                }

                if (! in_array(strtolower($attribute->name), $allowed, true)) {
                    $node->removeAttribute($attribute->name);
                }
            }

            if ($tag === 'a') {
                $node->setAttribute('target', '_blank');
                $node->setAttribute('rel', 'noopener noreferrer');
            }
        }
    }

    private function toPlainText(string $markdown): string
    {
        $prepared = $this->expandMarkdownLinks($this->replaceImagesWithPlaceholders($markdown));
        $html = ArticleHtmlPresenter::markdownToHtml($prepared);
        if (trim($html) === '') {
            return $markdown;
        }

        $html = preg_replace('/<li\b[^>]*>/iu', '- ', $html) ?? $html;
        $html = preg_replace('/<\/(h[1-6]|p|blockquote|li|tr|table|pre|ul|ol|td|th)>/iu', "</$1>\n", $html) ?? $html;
        $html = preg_replace('/<(h[1-6])\b[^>]*>/iu', "\n", $html) ?? $html;
        $html = preg_replace('/<br\s*\/?>/iu', "\n", $html) ?? $html;
        $html = preg_replace('/<hr\b[^>]*>/iu', "\n------\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/ ?\n ?/u', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function replaceImagesWithPlaceholders(string $markdown): string
    {
        return preg_replace_callback(
            '/!\[([^\]]*)\]\([^)]+\)/u',
            static function (array $matches): string {
                $alt = trim((string) ($matches[1] ?? ''));
                $placeholder = $alt !== ''
                    ? __('admin.article_editor.platform_copy.image_placeholder', ['alt' => $alt])
                    : __('admin.article_editor.platform_copy.image_placeholder_empty');

                return "\n\n".$placeholder."\n\n";
            },
            $markdown
        ) ?? $markdown;
    }

    private function expandMarkdownLinks(string $markdown): string
    {
        return preg_replace_callback(
            '/(?<!!)\[([^\]]+)\]\(([^)\s]+)\)/u',
            static function (array $matches): string {
                $label = trim((string) ($matches[1] ?? ''));
                $url = trim((string) ($matches[2] ?? ''));
                if ($label === '' || $label === $url) {
                    return $url;
                }

                return $label.' ('.$url.')';
            },
            $markdown
        ) ?? $markdown;
    }
}
