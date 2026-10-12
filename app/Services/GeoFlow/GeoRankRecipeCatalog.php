<?php

namespace App\Services\GeoFlow;

class GeoRankRecipeCatalog
{
    /**
     * @return array<string,mixed>
     */
    public function forPlatform(string $platform): array
    {
        return match ($platform) {
            'xiaohongshu' => $this->xiaohongshu(),
            'zhihu' => $this->zhihu(),
            'bilibili' => $this->bilibili(),
            default => $this->xiaohongshu(),
        };
    }

    /**
     * @return array<string,mixed>
     */
    private function xiaohongshu(): array
    {
        return [
            'platform' => 'xiaohongshu',
            'version' => 3,
            'displayName' => 'xiaohongshu',
            'login' => [
                'url' => 'https://creator.xiaohongshu.com/login?source=official',
                'successSelectors' => [
                    'a[href*="/user/profile"]',
                    '[class*="user-name"]',
                    '[class*="account-name"]',
                    '.reds-avatar',
                    'img[class*="avatar"]',
                ],
                'successCookieNames' => [
                    'web_session',
                    'access-token-creator.xiaohongshu.com',
                    'galaxy_creator_session_id',
                    'x-user-id-creator.xiaohongshu.com',
                ],
                'successUrlIncludes' => ['/new/home', '/publish', '/statistics', '/notebook', '/creator-micro'],
                'timeoutMs' => 300000,
                'keepOpenMs' => 4000,
                'hint' => 'Scan QR or sign in on this PC. Cookies stay local.',
            ],
            'check' => [
                'url' => 'https://creator.xiaohongshu.com/new/home',
                'loggedInSelectors' => ['[class*="user-name"]', '[class*="account-name"]', 'img[class*="avatar"]', '.reds-avatar'],
                'loggedOutSelectors' => ['button:has-text("login")'],
                'loggedInCookieNames' => [
                    'web_session',
                    'access-token-creator.xiaohongshu.com',
                    'galaxy_creator_session_id',
                    'x-user-id-creator.xiaohongshu.com',
                ],
                'loggedOutUrlIncludes' => ['/login'],
                'timeoutMs' => 30000,
            ],
            'publish' => [
                'url' => 'https://creator.xiaohongshu.com/publish/publish',
                'timeoutMs' => 900000,
                'draftOnlyDefault' => true,
                'mode' => 'handoff',
                'handoffHint' => 'Copy title then body on the creator publish page, then click publish yourself.',
                'steps' => [
                    ['action' => 'goto', 'url' => 'https://creator.xiaohongshu.com/publish/publish', 'waitUntil' => 'domcontentloaded'],
                    ['action' => 'wait', 'ms' => 1500],
                ],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function zhihu(): array
    {
        return [
            'platform' => 'zhihu',
            'version' => 1,
            'displayName' => 'zhihu',
            'login' => [
                'url' => 'https://www.zhihu.com/signin',
                'successSelectors' => ['a[href*="/people/"]', '.AppHeader-profile'],
                'successCookieNames' => ['z_c0'],
                'successUrlIncludes' => ['/people/'],
                'timeoutMs' => 300000,
                'keepOpenMs' => 3000,
                'hint' => 'Complete Zhihu login in the popup browser.',
            ],
            'check' => [
                'url' => 'https://www.zhihu.com',
                'loggedInSelectors' => ['.AppHeader-profile', 'a[href*="/people/"]'],
                'loggedOutSelectors' => ['a[href*="/signin"]'],
                'loggedInCookieNames' => ['z_c0'],
                'loggedOutUrlIncludes' => ['/signin'],
                'timeoutMs' => 30000,
            ],
            'publish' => [
                'url' => 'https://zhuanlan.zhihu.com/write',
                'timeoutMs' => 900000,
                'draftOnlyDefault' => true,
                'mode' => 'handoff',
                'handoffHint' => 'Copy title and body into Zhihu editor, then publish yourself.',
                'steps' => [
                    ['action' => 'goto', 'url' => 'https://zhuanlan.zhihu.com/write', 'waitUntil' => 'domcontentloaded'],
                    ['action' => 'wait', 'ms' => 1500],
                ],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function bilibili(): array
    {
        return [
            'platform' => 'bilibili',
            'version' => 1,
            'displayName' => 'bilibili',
            'login' => [
                'url' => 'https://passport.bilibili.com/login',
                'successSelectors' => ['.header-avatar-wrap', '.h-basic-info'],
                'successCookieNames' => ['SESSDATA', 'DedeUserID'],
                'successUrlIncludes' => ['space.bilibili.com'],
                'timeoutMs' => 300000,
                'keepOpenMs' => 3000,
                'hint' => 'Complete Bilibili login in the popup browser.',
            ],
            'check' => [
                'url' => 'https://member.bilibili.com/platform/home',
                'loggedInSelectors' => ['.home-right', '.user-container'],
                'loggedOutSelectors' => ['input[name="username"]'],
                'loggedInCookieNames' => ['SESSDATA', 'DedeUserID'],
                'loggedOutUrlIncludes' => ['/login'],
                'timeoutMs' => 30000,
            ],
            'publish' => [
                'url' => 'https://member.bilibili.com/article-text/home',
                'timeoutMs' => 900000,
                'draftOnlyDefault' => true,
                'mode' => 'handoff',
                'handoffHint' => 'Copy title and body into Bilibili editor, then publish yourself.',
                'steps' => [
                    ['action' => 'goto', 'url' => 'https://member.bilibili.com/article-text/home', 'waitUntil' => 'domcontentloaded'],
                    ['action' => 'wait', 'ms' => 1500],
                ],
            ],
        ];
    }
}
