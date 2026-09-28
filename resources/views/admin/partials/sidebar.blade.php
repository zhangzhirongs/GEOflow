@php
    $currentAdmin = auth('admin')->user();
    $isSuperAdmin = $currentAdmin
        && method_exists($currentAdmin, 'canManageProtectedWorkflows')
        && $currentAdmin->canManageProtectedWorkflows();
    $isUpdateCenterEnabled = (bool) config('geoflow.update_center_enabled', true);

    // 侧边栏分组配置：label / icon(lucide) / route / match(路由名前缀，最长前缀命中为激活项)
    $navSections = [
        [
            'title' => null,
            'items' => [
                ['label' => __('admin.nav.dashboard'), 'icon' => 'home', 'route' => 'admin.dashboard', 'match' => ['admin.dashboard', 'admin.system-updates.']],
                ['label' => __('admin.nav.ai_config'), 'icon' => 'layout-grid', 'route' => 'admin.ai.configurator', 'match' => ['admin.ai.configurator']],
                ['label' => __('admin.nav.analytics'), 'icon' => 'trending-up', 'route' => 'admin.analytics', 'match' => ['admin.analytics']],
            ],
        ],
        [
            'title' => __('admin.ai_configurator.groups.content.title'),
            'items' => [
                ['label' => __('admin.nav.tasks'), 'icon' => 'list-checks', 'route' => 'admin.tasks.index', 'match' => ['admin.tasks.']],
                ['label' => __('admin.nav.articles'), 'icon' => 'file-text', 'route' => 'admin.articles.index', 'match' => ['admin.articles.']],
                ['label' => __('admin.ai_configurator.groups.content.manual_publications'), 'icon' => 'send', 'route' => 'admin.manual-publications.index', 'match' => ['admin.manual-publications.']],
            ],
        ],
        [
            'title' => __('admin.ai_configurator.groups.assets.title'),
            'items' => [
                ['label' => __('admin.nav.materials'), 'icon' => 'database', 'route' => 'admin.materials.index', 'match' => ['admin.materials.']],
                ['label' => __('admin.ai_configurator.groups.assets.knowledge'), 'icon' => 'brain', 'route' => 'admin.knowledge-bases.index', 'match' => ['admin.knowledge-bases.']],
                ['label' => __('admin.ai_configurator.groups.assets.titles'), 'icon' => 'library-big', 'route' => 'admin.title-libraries.index', 'match' => ['admin.title-libraries.']],
                ['label' => __('admin.ai_configurator.groups.assets.keywords'), 'icon' => 'tags', 'route' => 'admin.keyword-libraries.index', 'match' => ['admin.keyword-libraries.']],
                ['label' => __('admin.ai_configurator.groups.assets.images'), 'icon' => 'images', 'route' => 'admin.image-libraries.index', 'match' => ['admin.image-libraries.']],
                ['label' => __('admin.ai_configurator.groups.assets.authors'), 'icon' => 'user-pen', 'route' => 'admin.authors.index', 'match' => ['admin.authors.']],
                ['label' => __('admin.ai_configurator.groups.assets.categories'), 'icon' => 'folders', 'route' => 'admin.categories.index', 'match' => ['admin.categories.']],
                ['label' => __('admin.ai_configurator.groups.assets.url_import'), 'icon' => 'link-2', 'route' => 'admin.url-import', 'match' => ['admin.url-import'], 'restricted' => true],
            ],
        ],
        [
            'title' => __('admin.ai_configurator.groups.ai.title'),
            'items' => [
                ['label' => __('admin.ai_configurator.skills.models.title'), 'icon' => 'cpu', 'route' => 'admin.ai-models.index', 'match' => ['admin.ai-models.']],
                ['label' => __('admin.ai_configurator.skills.prompts.title'), 'icon' => 'message-square-text', 'route' => 'admin.ai-prompts', 'match' => ['admin.ai-prompts']],
                ['label' => __('admin.ai_configurator.special_title'), 'icon' => 'braces', 'route' => 'admin.ai-special-prompts', 'match' => ['admin.ai-special-prompts']],
                ['label' => __('admin.ai_configurator.skills.providers.title'), 'icon' => 'search-check', 'route' => 'admin.ai-source-providers.index', 'match' => ['admin.ai-source-providers.']],
            ],
        ],
        [
            'title' => __('admin.ai_configurator.groups.system.title'),
            'items' => [
                ['label' => __('admin.nav.site_settings'), 'icon' => 'settings-2', 'route' => 'admin.site-settings.index', 'match' => ['admin.site-settings.']],
                ['label' => __('admin.nav.security'), 'icon' => 'shield-check', 'route' => 'admin.site-settings.sensitive-words', 'match' => ['admin.site-settings.sensitive-words', 'admin.security-settings.']],
                ['label' => __('admin.nav.distribution'), 'icon' => 'radio-tower', 'route' => 'admin.distribution.index', 'match' => ['admin.distribution.'], 'restricted' => true],
                ['label' => __('admin.nav.admin_users'), 'icon' => 'users', 'route' => 'admin.admin-users.index', 'match' => ['admin.admin-users.'], 'restricted' => true],
                ['label' => __('admin.nav.api_tokens'), 'icon' => 'key-round', 'route' => 'admin.api-tokens.index', 'match' => ['admin.api-tokens.'], 'restricted' => true],
                ['label' => __('admin.nav.activity_logs'), 'icon' => 'clipboard-list', 'route' => 'admin.admin-activity-logs', 'match' => ['admin.admin-activity-logs'], 'restricted' => true],
            ],
        ],
    ];

    // 过滤受限项 + 去除空分组
    $navSections = collect($navSections)
        ->map(function (array $section) use ($isSuperAdmin) {
            $section['items'] = array_values(array_filter(
                $section['items'],
                static fn (array $item): bool => $isSuperAdmin || empty($item['restricted'])
            ));
            return $section;
        })
        ->filter(static fn (array $section): bool => $section['items'] !== [])
        ->values()
        ->all();

    // 最长前缀命中当前路由，得到唯一激活项
    $routeName = (string) (request()->route()?->getName() ?? '');
    $activeSection = null;
    $activeIndex = null;
    $bestLen = -1;
    foreach ($navSections as $sIdx => $section) {
        foreach ($section['items'] as $iIdx => $item) {
            foreach (($item['match'] ?? []) as $prefix) {
                if ($routeName === $prefix || str_starts_with($routeName, $prefix)) {
                    if (strlen($prefix) > $bestLen) {
                        $bestLen = strlen($prefix);
                        $activeSection = $sIdx;
                        $activeIndex = $iIdx;
                    }
                }
            }
        }
    }
@endphp
<aside class="admin-sidebar" aria-label="{{ __('admin.nav.dashboard') }}">
    @foreach ($navSections as $sIdx => $section)
        <div class="nav-group">
            @if (!empty($section['title']))
                <div class="nav-group-title">{{ $section['title'] }}</div>
            @endif
            @foreach ($section['items'] as $iIdx => $item)
                @php $isActive = $sIdx === $activeSection && $iIdx === $activeIndex; @endphp
                <a href="{{ route($item['route']) }}"
                   class="nav-item @if($isActive) is-active @endif"
                   title="{{ $item['label'] }}"
                   @if($isActive) aria-current="page" @endif>
                    <i data-lucide="{{ $item['icon'] }}" class="nav-icon"></i>
                    <span class="nav-label">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    @endforeach
</aside>
