@php
    $currentAdmin = auth('admin')->user();
    $adminBrandName = $adminBrandName ?? \App\Support\AdminWeb::siteName();
    $isSuperAdmin = $currentAdmin && method_exists($currentAdmin, 'canManageProtectedWorkflows') && $currentAdmin->canManageProtectedWorkflows();
    $adminRoleLabel = $isSuperAdmin ? __('admin.header.super_admin') : __('admin.header.admin');
    $updateNotification = is_array($adminUpdateNotificationPayload ?? null) ? $adminUpdateNotificationPayload : [];
    $updateState = is_array($updateNotification['state'] ?? null) ? $updateNotification['state'] : [];
    $updateLinks = is_array($updateNotification['links'] ?? null) ? $updateNotification['links'] : [];
    $hasVersionUpdate = !empty($updateState['is_update_available']);
    $isUpdateCenterEnabled = (bool) config('geoflow.update_center_enabled', true);
    $localeForChangelog = app()->getLocale() === 'en' ? 'en' : 'zh-CN';
    $updatePayload = is_array($updateState['payload'] ?? null) ? $updateState['payload'] : [];
    $updateSummary = (string) ($localeForChangelog === 'en'
        ? ($updatePayload['summary_en'] ?? '')
        : ($updatePayload['summary_zh'] ?? ''));
    $changelogLinks = is_array($updateLinks['changelog'] ?? null) ? $updateLinks['changelog'] : [];
    $notificationChangelogUrl = (string) ($changelogLinks[$localeForChangelog] ?? $changelogLinks['zh-CN'] ?? 'https://github.com/zhangzhirongs/GEOflow/blob/main/docs/CHANGELOG.md');
    $notificationGithubUrl = (string) ($updateLinks['github'] ?? 'https://github.com/zhangzhirongs/GEOflow');
    $notificationUpdateCenterUrl = $isUpdateCenterEnabled && $isSuperAdmin ? \App\Support\AdminWeb::routePath('admin.system-updates.index') : '';
    $notificationStatus = (string) ($updateState['status'] ?? 'disabled');
    $topbarTitle = trim((string) ($pageTitle ?? '')) !== '' ? $pageTitle : $adminBrandName;
@endphp
<header class="admin-navbar">
    {{-- 移动端抽屉开关 --}}
    <button type="button" class="admin-navbar__menu" onclick="toggleSidebar()" aria-label="{{ __('admin.nav.dashboard') }}">
        <i data-lucide="panel-left" class="nav-icon"></i>
    </button>

    {{-- 当前页上下文 --}}
    <div class="admin-navbar__context">
        <i data-lucide="layout-dashboard" class="nav-icon"></i>
        <span class="admin-navbar__title">{{ $topbarTitle }}</span>
    </div>

    <div class="admin-navbar__actions">
        <a href="{{ route('admin.ai.configurator') }}" class="qdk-btn qdk-btn-sm qdk-btn-ghost" title="{{ __('admin.nav.ai_config') }}">
            <i data-lucide="sparkles" class="nav-icon"></i>
            <span class="hidden sm:inline">{{ __('admin.nav.ai_config') }}</span>
        </a>

        <div class="relative">
            <button type="button"
                    class="admin-icon-btn"
                    onclick="toggleAdminNotifications()"
                    aria-expanded="false"
                    aria-label="{{ __('admin.header.notifications.label') }}"
                    title="{{ __('admin.header.notifications.label') }}">
                <i data-lucide="bell" class="nav-icon"></i>
                @if($hasVersionUpdate)
                    <span data-update-indicator class="admin-icon-btn__dot"></span>
                @endif
            </button>

            <div id="admin-notification-menu" class="admin-popover admin-popover--notice">
                <div class="admin-popover__head">
                    <div class="admin-popover__title">{{ __('admin.header.notifications.title') }}</div>
                    @if($hasVersionUpdate)
                        <span class="qdk-badge qdk-badge-blue">{{ __('admin.header.notifications.badge_new') }}</span>
                    @endif
                </div>
                <div class="admin-popover__body">
                    @if($hasVersionUpdate)
                        <div class="admin-popover__title">
                            {{ __('admin.header.notifications.update_available', ['version' => (string) ($updateState['latest_version'] ?? '')]) }}
                        </div>
                        <p class="admin-popover__copy">{{ __('admin.header.notifications.update_desc') }}</p>
                        @if($updateSummary !== '')
                            <p class="admin-popover__copy">{{ $updateSummary }}</p>
                        @endif
                    @elseif($notificationStatus === 'current')
                        <div class="admin-popover__title">{{ __('admin.header.notifications.up_to_date') }}</div>
                        <p class="admin-popover__copy">{{ __('admin.header.notifications.no_update_desc') }}</p>
                    @elseif($notificationStatus === 'disabled')
                        <div class="admin-popover__title">{{ __('admin.header.notifications.disabled') }}</div>
                        <p class="admin-popover__copy">{{ __('admin.header.notifications.disabled_desc') }}</p>
                    @else
                        <div class="admin-popover__title">{{ __('admin.header.notifications.unavailable') }}</div>
                        <p class="admin-popover__copy">{{ __('admin.header.notifications.unavailable_desc') }}</p>
                    @endif

                    <div class="admin-popover__meta space-y-0.5">
                        <div>{{ __('admin.header.notifications.current_version', ['version' => (string) ($updateState['current_version'] ?? config('geoflow.app_version', '2.0'))]) }}</div>
                        @if(!empty($updateState['latest_version']))
                            <div>{{ __('admin.header.notifications.latest_version', ['version' => (string) $updateState['latest_version']]) }}</div>
                        @endif
                        <div>{{ __('admin.header.notifications.daily_check') }}</div>
                        @if(!empty($updateState['checked_at']))
                            <div>{{ __('admin.header.notifications.checked_at', ['time' => (string) $updateState['checked_at']]) }}</div>
                        @endif
                    </div>

                    <div class="admin-popover__actions">
                        @if($isUpdateCenterEnabled && $isSuperAdmin)
                            <a href="{{ $notificationUpdateCenterUrl }}" class="qdk-btn qdk-btn-sm qdk-btn-primary">
                                {{ __('admin.header.notifications.open_update_center') }}
                            </a>
                        @endif
                        <a href="{{ $notificationChangelogUrl }}" target="_blank" rel="noopener noreferrer" class="qdk-btn qdk-btn-sm qdk-btn-ghost">
                            {{ __('admin.header.notifications.view_changelog') }}
                        </a>
                        <a href="{{ $notificationGithubUrl }}" target="_blank" rel="noopener noreferrer" class="qdk-btn qdk-btn-sm qdk-btn-ghost">
                            {{ __('admin.header.notifications.open_github') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="hidden md:block">
            <select
                class="admin-locale-select"
                aria-label="{{ __('admin.header.language') }}"
                onchange="if (this.value) window.location.href = this.value"
            >
                @foreach (\App\Support\AdminWeb::supportedLocales() as $localeCode => $localeLabel)
                    <option value="{{ route('admin.locale.switch', ['locale' => $localeCode]) }}" @selected(app()->getLocale() === $localeCode)>
                        {{ $localeLabel }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="relative">
            <button type="button" class="admin-user-btn" onclick="toggleUserMenu()" aria-expanded="false">
                <span class="admin-avatar">
                    <i data-lucide="user" class="nav-icon"></i>
                </span>
                <span class="hidden text-sm font-medium text-gray-700 sm:inline">{{ $currentAdmin->username ?? '' }}</span>
                <i data-lucide="chevron-down" class="nav-icon"></i>
            </button>

            <div id="user-menu" class="admin-popover admin-popover--user">
                <div class="admin-menu-meta">
                    <div class="admin-menu-meta__name">{{ __('admin.header.welcome', ['name' => $currentAdmin->username ?? '']) }}</div>
                    <div class="admin-menu-meta__role">{{ $adminRoleLabel }}</div>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="admin-menu-link">
                    <i data-lucide="home" class="nav-icon"></i>
                    {{ __('admin.nav.back_home') }}
                </a>
                <a href="{{ route('admin.ai.configurator') }}" class="admin-menu-link">
                    <i data-lucide="wand-sparkles" class="nav-icon"></i>
                    {{ __('admin.nav.ai_config') }}
                </a>
                <div class="admin-menu-divider"></div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="admin-menu-link admin-menu-link--danger w-full text-left">
                        <i data-lucide="log-out" class="nav-icon"></i>
                        {{ __('admin.button.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<script>
    function setAdminPopover(el, open) {
        if (!el) { return; }
        el.classList.toggle('is-open', !!open);
    }

    function toggleUserMenu() {
        var menu = document.getElementById('user-menu');
        var notice = document.getElementById('admin-notification-menu');
        if (!menu) { return; }
        var willOpen = !menu.classList.contains('is-open');
        setAdminPopover(notice, false);
        setAdminPopover(menu, willOpen);
    }

    function toggleAdminNotifications() {
        var menu = document.getElementById('admin-notification-menu');
        var user = document.getElementById('user-menu');
        if (!menu) { return; }
        var willOpen = !menu.classList.contains('is-open');
        setAdminPopover(user, false);
        setAdminPopover(menu, willOpen);
    }

    function toggleSidebar() {
        var root = document.documentElement;
        // 移动端：切换抽屉；桌面端：切换迷你折叠
        if (window.matchMedia('(max-width: 768px)').matches) {
            root.classList.toggle('sidebar-drawer-open');
            return;
        }
        var collapsed = root.classList.toggle('sidebar-collapsed');
        try { localStorage.setItem('geoflow.sidebar.collapsed', collapsed ? '1' : '0'); } catch (e) {}
    }

    function toggleSidebarDrawer(open) {
        document.documentElement.classList.toggle('sidebar-drawer-open', !!open);
    }

    document.addEventListener('click', function (event) {
        var userMenu = document.getElementById('user-menu');
        var notificationMenu = document.getElementById('admin-notification-menu');
        if (userMenu && !event.target.closest('[onclick="toggleUserMenu()"]') && !userMenu.contains(event.target)) {
            setAdminPopover(userMenu, false);
        }
        if (notificationMenu && !event.target.closest('[onclick="toggleAdminNotifications()"]') && !notificationMenu.contains(event.target)) {
            setAdminPopover(notificationMenu, false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') { return; }
        setAdminPopover(document.getElementById('user-menu'), false);
        setAdminPopover(document.getElementById('admin-notification-menu'), false);
        document.documentElement.classList.remove('sidebar-drawer-open');
    });
</script>
