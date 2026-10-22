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
            'toutiao' => $this->toutiao(),
            'baijiahao', 'weibo', 'douyin', 'wechat_mp', 'csdn', 'local_demo' => $this->platformSession($platform),
            default => $this->generic($platform),
        };
    }

    /** @return array<string,mixed> */
    private function toutiao(): array
    {
        $recipe = $this->generic('toutiao');
        $recipe['version'] = 3;
        $recipe['login'] = [
            'url' => 'https://mp.toutiao.com',
            'successSelectors' => ['.auth-avator', '.user-info'],
            'failureSelectors' => ['button:has-text("登录")', 'a:has-text("登录")'],
            'successCookieNames' => ['sessionid', 'sessionid_ss', 'sid_tt'],
            'successUrlIncludes' => ['mp.toutiao.com/profile_v4/index', 'mp.toutiao.com/profile_v4/graphic/'],
            'timeoutMs' => 300000,
            'keepOpenMs' => 3000,
            'hint' => '在弹出的浏览器中登录头条号后台。Cookie 只保存在本机。',
        ];
        $recipe['check'] = [
            'url' => 'https://mp.toutiao.com',
            'loggedInSelectors' => ['.auth-avator', '.user-info'],
            'loggedOutSelectors' => ['button:has-text("登录")', 'a:has-text("登录")'],
            'loggedInCookieNames' => ['sessionid', 'sessionid_ss', 'sid_tt'],
            'loggedInUrlIncludes' => ['mp.toutiao.com/profile_v4/index', 'mp.toutiao.com/profile_v4/graphic/'],
            'loggedOutUrlIncludes' => ['/auth/page/login'],
            'timeoutMs' => 30000,
        ];
        $recipe['publish']['url'] = 'https://mp.toutiao.com/profile_v4/graphic/publish';
        $recipe['publish']['steps'][0]['url'] = $recipe['publish']['url'];

        return $recipe;
    }

    /** @return array<string,mixed> */
    private function platformSession(string $platform): array
    {
        $sessions = [
            'baijiahao' => [
                'version' => 3,
                'login' => [
                    'url' => 'https://baijiahao.baidu.com',
                    'successSelectors' => ['.user-name'],
                    'failureSelectors' => ['button:has-text("登录")', 'a:has-text("登录")'],
                    'successCookieNames' => ['BDUSS', 'STOKEN'],
                    'timeoutMs' => 300000,
                    'keepOpenMs' => 3000,
                    'hint' => '在弹出的浏览器中登录百家号创作者后台。Cookie 只保存在本机。',
                ],
                'check' => [
                    'url' => 'https://baijiahao.baidu.com',
                    'loggedInSelectors' => ['.user-name'],
                    'loggedOutSelectors' => ['button:has-text("登录")', 'a:has-text("登录")'],
                    'loggedInCookieNames' => ['BDUSS', 'STOKEN'],
                    'timeoutMs' => 30000,
                ],
            ],
            'weibo' => [
                'version' => 2,
                'login' => [
                    'url' => 'https://weibo.com/login.php',
                    'successSelectors' => ['[class*="Ctrls_avatar"]'],
                    'failureSelectors' => ['a:has-text("登录")', 'button:has-text("登录")'],
                    'successCookieNames' => ['SUB'],
                    'timeoutMs' => 300000,
                    'keepOpenMs' => 3000,
                    'hint' => '在弹出的浏览器中登录微博。Cookie 只保存在本机。',
                ],
                'check' => [
                    'url' => 'https://weibo.com',
                    'loggedInSelectors' => ['[class*="Ctrls_avatar"]'],
                    'loggedOutSelectors' => ['a:has-text("登录")', 'button:has-text("登录")'],
                    'loggedInCookieNames' => ['SUB'],
                    'loggedOutUrlIncludes' => ['/login.php', '/login'],
                    'timeoutMs' => 30000,
                ],
            ],
            'douyin' => [
                'version' => 2,
                'login' => [
                    'url' => 'https://creator.douyin.com/',
                    'successSelectors' => [],
                    'successCookieNames' => ['sessionid', 'sessionid_ss', 'sid_guard', 'sid_tt'],
                    'successUrlIncludes' => ['/creator-micro/', '/creator-micro'],
                    'timeoutMs' => 300000,
                    'keepOpenMs' => 3000,
                    'hint' => '请在抖音创作者中心扫码或登录。登录态仅保存在本机。',
                ],
                'check' => [
                    'url' => 'https://creator.douyin.com/',
                    'loggedInSelectors' => [],
                    'loggedOutSelectors' => [],
                    'loggedInCookieNames' => ['sessionid', 'sessionid_ss', 'sid_guard', 'sid_tt'],
                    'loggedInUrlIncludes' => ['/creator-micro/', '/creator-micro'],
                    'loggedOutUrlIncludes' => ['/login'],
                    'timeoutMs' => 30000,
                ],
            ],
            'wechat_mp' => [
                'version' => 2,
                'login' => [
                    'url' => 'https://mp.weixin.qq.com/',
                    'successSelectors' => [],
                    'successCookieNames' => ['slave_sid'],
                    'successUrlIncludes' => ['/cgi-bin/home', '/cgi-bin/appmsg'],
                    'timeoutMs' => 300000,
                    'keepOpenMs' => 3000,
                    'hint' => '请在微信公众号后台扫码登录。登录态仅保存在本机。',
                ],
                'check' => [
                    'url' => 'https://mp.weixin.qq.com/',
                    'loggedInSelectors' => [],
                    'loggedOutSelectors' => [],
                    'loggedInCookieNames' => ['slave_sid'],
                    'loggedInUrlIncludes' => ['/cgi-bin/home', '/cgi-bin/appmsg'],
                    'loggedOutUrlIncludes' => ['/cgi-bin/login'],
                    'timeoutMs' => 30000,
                ],
            ],
            'csdn' => [
                'version' => 2,
                'login' => [
                    'url' => 'https://editor.csdn.net/md/',
                    'successSelectors' => [],
                    'successCookieNames' => ['UserToken', 'userToken'],
                    'timeoutMs' => 300000,
                    'keepOpenMs' => 3000,
                    'hint' => '请在 CSDN 登录页完成扫码或账号登录。登录态仅保存在本机。',
                ],
                'check' => [
                    'url' => 'https://editor.csdn.net/md/',
                    'loggedInSelectors' => [],
                    'loggedOutSelectors' => [],
                    'loggedInCookieNames' => ['UserToken', 'userToken'],
                    'loggedOutUrlIncludes' => ['/login'],
                    'timeoutMs' => 30000,
                ],
            ],
            'local_demo' => [
                'version' => 1,
                'login' => [
                    'url' => '/demo/login',
                    'successSelectors' => ['[data-testid="demo-logged-in"]'],
                    'successCookieNames' => ['demo_session'],
                    'successUrlIncludes' => ['/demo/home'],
                    'timeoutMs' => 300000,
                    'keepOpenMs' => 1500,
                    'hint' => '演示平台会打开本地登录页。任意用户名/密码即可登录，用来验证 Cookie 不经服务器的完整链路。',
                ],
                'check' => [
                    'url' => '/demo/home',
                    'loggedInSelectors' => ['[data-testid="demo-logged-in"]'],
                    'loggedOutSelectors' => ['[data-testid="demo-login-form"]'],
                    'loggedInCookieNames' => ['demo_session'],
                    'loggedOutUrlIncludes' => ['/demo/login'],
                    'timeoutMs' => 15000,
                ],
            ],
        ];

        return array_replace($this->generic($platform), $sessions[$platform]);
    }

    /** @return array<string,mixed> */
    private function generic(string $platform): array
    {
        $urls = [
            'baijiahao' => 'https://baijiahao.baidu.com',
            'toutiao' => 'https://mp.toutiao.com',
            'weibo' => 'https://weibo.com',
            'douyin' => 'https://creator.douyin.com',
            'wechat_mp' => 'https://mp.weixin.qq.com',
            'csdn' => 'https://editor.csdn.net/md/',
            'local_demo' => '/demo',
        ];
        $url = $urls[$platform] ?? '/';

        return [
            'platform' => $platform,
            'version' => 1,
            'displayName' => $platform,
            'login' => ['url' => $url, 'successSelectors' => [], 'successCookieNames' => [], 'timeoutMs' => 300000, 'keepOpenMs' => 3000],
            'check' => ['url' => $url, 'loggedInSelectors' => [], 'loggedOutSelectors' => [], 'loggedInCookieNames' => [], 'timeoutMs' => 30000],
            'publish' => [
                'url' => $url,
                'timeoutMs' => 900000,
                'draftOnlyDefault' => true,
                'mode' => 'handoff',
                'handoffHint' => 'Open the platform editor, paste the prepared content, review it, and publish manually.',
                'steps' => [['action' => 'goto', 'url' => $url, 'waitUntil' => 'domcontentloaded'], ['action' => 'wait', 'ms' => 1500]],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function xiaohongshu(): array
    {
        return [
            'platform' => 'xiaohongshu',
            'version' => 4,
            'displayName' => 'xiaohongshu',
            'login' => [
                'url' => 'https://creator.xiaohongshu.com/login?source=official',
                'successSelectors' => ['a[href*="/user/profile"]', '[class*="user-name"]', '[class*="account-name"]', '.reds-avatar', 'img[class*="avatar"]'],
                'failureSelectors' => ['button:has-text("登录")'],
                'successCookieNames' => ['web_session', 'access-token-creator.xiaohongshu.com', 'galaxy_creator_session_id', 'x-user-id-creator.xiaohongshu.com'],
                'successUrlIncludes' => ['/new/home', '/publish', '/statistics', '/notebook', '/creator-micro'],
                'timeoutMs' => 300000,
                'keepOpenMs' => 4000,
                'hint' => '本机将打开小红书创作者登录页。请用小红书 App 扫码或账号密码登录。登录成功后会话会加密保存在本机，Cookie 不会上传。',
            ],
            'check' => [
                'url' => 'https://creator.xiaohongshu.com/new/home',
                'loggedInSelectors' => ['[class*="user-name"]', '[class*="account-name"]', 'img[class*="avatar"]', '.reds-avatar'],
                'loggedOutSelectors' => ['button:has-text("登录")', 'text=登录'],
                'loggedInCookieNames' => ['web_session', 'access-token-creator.xiaohongshu.com', 'galaxy_creator_session_id', 'x-user-id-creator.xiaohongshu.com'],
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
            'version' => 2,
            'displayName' => 'zhihu',
            'login' => [
                'url' => 'https://www.zhihu.com/signin',
                'successSelectors' => ['a[href*="/people/"]', 'button[aria-label="用户"]', '.AppHeader-profile'],
                'failureSelectors' => ['button:has-text("登录")', 'input[name="password"]'],
                'successCookieNames' => ['z_c0'],
                'successUrlIncludes' => ['/people/'],
                'timeoutMs' => 300000,
                'keepOpenMs' => 3000,
                'hint' => '在弹出的浏览器中完成知乎登录，建议扫码。Cookie 只保存在本机。',
            ],
            'check' => [
                'url' => 'https://www.zhihu.com',
                'loggedInSelectors' => ['.AppHeader-profile', 'a[href*="/people/"]'],
                'loggedOutSelectors' => ['button:has-text("登录")', 'a[href*="/signin"]'],
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
            'version' => 3,
            'displayName' => 'bilibili',
            'login' => [
                'url' => 'https://www.bilibili.com',
                'successSelectors' => [],
                'successCookieNames' => ['SESSDATA'],
                'timeoutMs' => 300000,
                'keepOpenMs' => 3000,
                'hint' => '在弹出的浏览器中登录 B 站。Cookie 只保存在本机。',
            ],
            'check' => [
                'url' => 'https://www.bilibili.com',
                'loggedInSelectors' => [],
                'loggedOutSelectors' => ['.header-login-entry', 'text=登录'],
                'loggedInCookieNames' => ['SESSDATA'],
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
