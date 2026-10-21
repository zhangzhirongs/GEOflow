<?php

namespace App\Services\GeoFlow;

final class ArticleContentPromptRenderer
{
    /**
     * 单篇编辑器使用自然来源表述，避免把内部证据编号写入成稿。
     */
    public function renderForEditor(string $title, string $keyword, ?string $promptContent, string $knowledgeContext = '', string $platform = ''): string
    {
        return $this->render($title, $keyword, $promptContent, $knowledgeContext, false, $platform);
    }

    /**
     * Worker 保留证据编号，供后续自动化审核和来源追踪。
     */
    public function renderForWorker(string $title, string $keyword, ?string $promptContent, string $knowledgeContext = '', string $platform = ''): string
    {
        return $this->render($title, $keyword, $promptContent, $knowledgeContext, true, $platform);
    }

    /**
     * 构造正文提示词：优先精确替换变量；无变量的自定义提示词自动补齐文章上下文。
     */
    private function render(
        string $title,
        string $keyword,
        ?string $promptContent,
        string $knowledgeContext,
        bool $includeEvidenceIds,
        string $platform = '',
    ): string {
        $prompt = trim((string) $promptContent);
        $isFallbackPrompt = false;
        if ($prompt === '') {
            $prompt = "请围绕标题“{$title}”和关键词“{$keyword}”生成一篇结构清晰、语言自然的中文文章。";
            $isFallbackPrompt = true;
        }

        $hasExplicitContextVariables = $isFallbackPrompt || $this->promptHasKnownContextVariables($prompt);
        $hasExplicitKnowledgeVariable = $this->promptHasContextVariable($prompt, 'knowledge');
        $renderedPrompt = $this->renderPromptTemplate($prompt, [
            'title' => $title,
            'keyword' => $keyword,
            'knowledge' => $knowledgeContext,
        ]);

        if (! $hasExplicitContextVariables) {
            $renderedPrompt = $this->appendSmartPromptContext($renderedPrompt, $title, $keyword, $knowledgeContext);
        } elseif (! $hasExplicitKnowledgeVariable) {
            $renderedPrompt = $this->appendKnowledgeContext($renderedPrompt, $knowledgeContext);
        }

        $finalInstructions = array_values(array_filter([
            $this->knowledgeAttributionInstruction($renderedPrompt, $knowledgeContext, $includeEvidenceIds),
            $this->platformStyleInstruction($platform, $this->isLikelyEnglishPrompt($renderedPrompt)),
            $this->finalPromptInstruction($renderedPrompt),
        ], static fn (string $instruction): bool => trim($instruction) !== ''));

        return trim($renderedPrompt)."\n\n".implode("\n", $finalInstructions);
    }

    private function promptHasKnownContextVariables(string $prompt): bool
    {
        return preg_match('/\{\{\s*(title|keyword|knowledge)\s*\}\}/iu', $prompt) === 1
            || preg_match('/\{\{#if\s+(title|keyword|knowledge)\s*\}\}/iu', $prompt) === 1;
    }

    private function promptHasContextVariable(string $prompt, string $name): bool
    {
        $variable = preg_quote($name, '/');

        return preg_match('/\{\{\s*'.$variable.'\s*\}\}/iu', $prompt) === 1
            || preg_match('/\{\{#if\s+'.$variable.'\s*\}\}/iu', $prompt) === 1;
    }

    /**
     * @param  array{title:string, keyword:string, knowledge:string}  $context
     */
    private function renderPromptTemplate(string $prompt, array $context): string
    {
        $renderedPrompt = preg_replace_callback('/\{\{#if\s+([A-Za-z_][A-Za-z0-9_]*)\s*\}\}(.*?)\{\{\/if\}\}/su', function (array $matches) use ($context): string {
            $name = (string) ($matches[1] ?? '');
            if (! $this->isKnownPromptContextName($name)) {
                return (string) ($matches[0] ?? '');
            }

            $value = $this->promptContextValue($name, $context);

            return trim($value) !== '' ? (string) ($matches[2] ?? '') : '';
        }, $prompt) ?? $prompt;

        return preg_replace_callback('/\{\{\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}/u', function (array $matches) use ($context): string {
            $name = (string) ($matches[1] ?? '');
            $value = $this->promptContextValue($name, $context);

            return $value !== '' || $this->isKnownPromptContextName($name) ? $value : (string) ($matches[0] ?? '');
        }, $renderedPrompt) ?? $renderedPrompt;
    }

    /**
     * @param  array{title:string, keyword:string, knowledge:string}  $context
     */
    private function promptContextValue(string $name, array $context): string
    {
        return match (mb_strtolower($name, 'UTF-8')) {
            'title' => $context['title'],
            'keyword' => $context['keyword'],
            'knowledge' => $context['knowledge'],
            default => '',
        };
    }

    private function isKnownPromptContextName(string $name): bool
    {
        return in_array(mb_strtolower($name, 'UTF-8'), ['title', 'keyword', 'knowledge'], true);
    }

    private function appendSmartPromptContext(string $prompt, string $title, string $keyword, string $knowledgeContext): string
    {
        if ($this->isLikelyEnglishPrompt($prompt)) {
            $lines = [
                'Task context:',
                '- Article title: '.$title,
            ];
            if (trim($keyword) !== '') {
                $lines[] = '- Core keyword: '.$keyword;
            }
            if (trim($knowledgeContext) !== '') {
                $lines[] = '- Reference knowledge:';
                $lines[] = $knowledgeContext;
            }

            return trim($prompt)."\n\n".implode("\n", $lines);
        }

        $lines = [
            '【任务上下文】',
            '- 文章标题：'.$title,
        ];
        if (trim($keyword) !== '') {
            $lines[] = '- 核心关键词：'.$keyword;
        }
        if (trim($knowledgeContext) !== '') {
            $lines[] = '- 参考知识：';
            $lines[] = $knowledgeContext;
        }

        return trim($prompt)."\n\n".implode("\n", $lines);
    }

    private function appendKnowledgeContext(string $prompt, string $knowledgeContext): string
    {
        if (trim($knowledgeContext) === '') {
            return trim($prompt);
        }

        if ($this->isLikelyEnglishPrompt($prompt)) {
            return trim($prompt)."\n\nReference knowledge:\n".$knowledgeContext;
        }

        return trim($prompt)."\n\n【参考知识】\n".$knowledgeContext;
    }

    private function finalPromptInstruction(string $prompt): string
    {
        if ($this->isLikelyEnglishPrompt($prompt)) {
            return 'Please output only the final article body in Markdown. Do not repeat the prompt or output placeholders.';
        }

        return '请直接输出最终文章正文（Markdown），不要重复提示词、不要输出占位符。';
    }

    private function knowledgeAttributionInstruction(string $prompt, string $knowledgeContext, bool $includeEvidenceIds): string
    {
        if (trim($knowledgeContext) === '') {
            return '';
        }

        if ($includeEvidenceIds) {
            if ($this->isLikelyEnglishPrompt($prompt)) {
                return 'Knowledge citation rule: when using facts, data, or business judgments from the reference knowledge, cite the evidence ID such as [K1] in the relevant sentence. If the evidence is insufficient, use cautious wording and do not invent sources or conclusions.';
            }

            return '知识库引用要求：涉及事实、数据或业务判断时，优先依据参考知识中的 [K1] 等证据编号，并在相关句子后标注证据编号；证据不足时不要编造来源或结论。';
        }

        if ($this->isLikelyEnglishPrompt($prompt)) {
            return 'Knowledge attribution rule: never output citation placeholders, including but not limited to [K1], [K2], [K3], 【K1】, or （K1）. When attribution is needed, write natural phrases such as “the materials show,” “the client confirmed,” or “according to the store materials,” without numbered citations. If the evidence is insufficient, use cautious wording and do not invent sources or conclusions.';
        }

        return '知识库依据表达要求：禁止输出任何引用占位符，包括但不限于 [K1]、[K2]、[K3]、【K1】、（K1）等。文章中如需表达依据，直接写“资料显示”“客户确认”“根据门店资料”，不要添加编号引用。证据不足时不要编造来源或结论。';
    }

    /**
     * 平台适配指令：按目标社媒平台追加格式/文风要求。空平台返回空串（被 array_filter 过滤）。
     */
    private function platformStyleInstruction(string $platform, bool $isEnglish): string
    {
        $platform = mb_strtolower(trim($platform), 'UTF-8');
        $platform = $platform === 'wechat_mp' ? 'wechat' : $platform;
        if ($platform === '') {
            return '';
        }

        $zh = [
            'xiaohongshu' => '平台适配（小红书）：口语化种草风；开头一句强钩子，标题/首句可点缀少量 emoji；正文用短段落和分点，多用“姐妹们/亲测/踩雷”等真实口吻；结尾给 3-5 个 #话题标签。整体控制在 600-1000 字。',
            'zhihu' => '平台适配（知乎）：专业、理性、有深度；按“背景→分析→结论/建议”组织，使用小标题分节；观点严谨、可引用数据但不得编造；避免营销腔和过多 emoji。',
            'weibo' => '平台适配（微博）：短平快，正文 140-300 字；口语化、信息密度高；可带 1-2 个 #话题#；结尾用一句话引导转发或评论。',
            'wechat' => '平台适配（微信公众号）：长图文排版友好；用小标题分节、段落简短、重点句可独立成段；语气正式而亲和，开头点题、结尾收束并引导关注。',
            'douyin' => '平台适配（抖音）：输出短视频口播脚本；开头 3 秒强钩子，按口播节奏分句/分镜（可用“镜头/口播：”标注），语言极口语化，结尾引导点赞关注。',
            'bilibili' => '平台适配（B站）：年轻化、有网感；可用脚本或图文分段结构，层次清晰、适度玩梗；结尾引导三连（点赞/投币/收藏）与评论互动。',
            'reddit' => '平台适配（Reddit）：真诚、非营销的社区口吻；标题直给信息，正文结构清晰、可用要点列表；避免硬广与夸张措辞。',
            'x' => '平台适配（X/Twitter）：精炼有力；可写成单条或分条串（thread），每条独立成义、短句优先；可用 1-2 个话题标签。',
            'linkedin' => '平台适配（LinkedIn）：专业职场口吻；开头抛出洞见或问题，正文分点阐述并给出可落地结论；克制使用 emoji，结尾引导专业讨论。',
        ];

        $en = [
            'xiaohongshu' => 'Platform (Xiaohongshu/RED): casual, personal "sharing" tone; strong hook in the opening, a few emojis allowed in title/first line; short paragraphs and bullet points; end with 3-5 #hashtags; keep it concise.',
            'zhihu' => 'Platform (Zhihu): professional, rational, in-depth; structure as background → analysis → conclusion with subheadings; rigorous, data-aware but never fabricated; avoid marketing tone and excessive emojis.',
            'weibo' => 'Platform (Weibo): short and punchy, 140-300 characters; conversational, high information density; 1-2 #topic# tags; end with a line inviting reposts or comments.',
            'wechat_mp' => 'Platform (WeChat Official Account): reader-friendly long-form layout; subheadings, short paragraphs, standalone key sentences; formal yet warm; hook up front, wrap up with a follow prompt.',
            'douyin' => 'Platform (Douyin/TikTok): produce a short-video voiceover script; 3-second hook up front, split by spoken rhythm/shots, highly colloquial, end with a like/follow call-to-action.',
            'bilibili' => 'Platform (Bilibili): youthful, community-savvy tone; script or sectioned long-form, clear structure, light humor; end by inviting likes/coins/favorites and comments.',
            'reddit' => 'Platform (Reddit): sincere, non-promotional community voice; informative title, clear structure with bullet lists; no hard selling or hype.',
            'x' => 'Platform (X/Twitter): concise and sharp; a single post or a thread, each line self-contained, short sentences first; 1-2 hashtags.',
            'linkedin' => 'Platform (LinkedIn): professional voice; open with an insight or question, make the body point-by-point with actionable takeaways; sparing emojis; end inviting discussion.',
        ];

        $map = $isEnglish ? $en : $zh;

        return $map[$platform] ?? '';
    }

    private function isLikelyEnglishPrompt(string $prompt): bool
    {
        preg_match_all('/\p{Han}/u', $prompt, $cjkMatches);
        preg_match_all('/[A-Za-z]/', $prompt, $latinMatches);

        return count($latinMatches[0] ?? []) > 20 && count($cjkMatches[0] ?? []) <= 3;
    }
}
