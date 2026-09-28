@php
    $currentAdmin = auth('admin')->user();
    $adminBrandName = $adminBrandName ?? \App\Support\AdminWeb::siteName();
    $brandInitial = mb_strtoupper(mb_substr(trim((string) $adminBrandName), 0, 1) ?: 'G');
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
@endphp
<header class="admin-navbar">
    <a href="{{ route('admin.dashboard') }}" class="logo-area">
        <span class="logo-mark">{{ $brandInitial }}</span>
        <span class="logo-text">{{ $adminBrandName }}</span>
    </a>

    <button type="button" class="collapse-btn" onclick="toggleSidebar()" aria-label="{{ __('admin.nav.dashboard') }}">
        <i data-lucide="panel-left" class="w-5 h-5"></i>
    </button>

    <div class="flex shrink-0 items-center gap-2 sm:gap-3 ml-auto pr-4 sm:pr-5">
        <a href="{{ route('admin.ai.configurator') }}" class="admin-ai-btn">
            <i data-lucide="sparkles" class="w-4 h-4"></i>
            <span class="hidden sm:inline">{{ __('admin.nav.ai_config') }}</span>
        </a>

        <div class="relative">
            <button onclick="toggleAdminNotifications()" class="relative rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors duration-200" type="button" aria-label="{{ __('admin.header.notifications.label') }}" title="{{ __('admin.header.notifications.label') }}">
                <i data-lucide="bell" class="w-5 h-5"></i>
                @if($hasVersionUpdate)
                    <span data-update-indicator class="absolute right-1.5 top-1.5 h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white"></span>
                @endif
            </button>

            <div id="admin-notification-menu" class="hidden absolute right-0 mt-3 w-80 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl z-50">
                <div class="border-b border-gray-100 px-4 py-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-sm font-semibold text-gray-900">{{ __('admin.header.notifications.title') }}</div>
                        @if($hasVersionUpdate)
                            <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-600">{{ __('admin.header.notifications.badge_new') }}</span>
                        @endif
                    </div>
                </div>
                <div class="px-4 py-4">
                    @if($hasVersionUpdate)
                        <div class="text-sm font-semibold text-gray-900">
                            {{ __('admin.header.notifications.update_available', ['version' => (string) ($updateState['latest_version'] ?? '')]) }}
                        </div>
                        <p class="mt-2 text-sm leading-6 text-gray-600">{{ __('admin.header.notifications.update_desc') }}</p>
                        @if($updateSummary !== '')
                            <p class="mt-2 text-sm leading-6 text-gray-600">{{ $updateSummary }}</p>
                        @endif
                    @elseif($notificationStatus === 'current')
                        <div class="text-sm font-semibold text-gray-900">{{ __('admin.header.notifications.up_to_date') }}</div>
                        <p class="mt-2 text-sm leading-6 text-gray-600">{{ __('admin.header.notifications.no_update_desc') }}</p>
                    @elseif($notificationStatus === 'disabled')
                        <div class="text-sm font-semibold text-gray-900">{{ __('admin.header.notifications.disabled') }}</div>
                        <p class="mt-2 text-sm leading-6 text-gray-600">{{ __('admin.header.notifications.disabled_desc') }}</p>
                    @else
                        <div class="text-sm font-semibold text-gray-900">{{ __('admin.header.notifications.unavailable') }}</div>
                        <p class="mt-2 text-sm leading-6 text-gray-600">{{ __('admin.header.notifications.unavailable_desc') }}</p>
                    @endif

                    <div class="mt-4 space-y-1 rounded-xl bg-gray-50 px-3 py-3 text-xs text-gray-500">
                        <div>{{ __('admin.header.notifications.current_version', ['version' => (string) ($updateState['current_version'] ?? config('geoflow.app_version', '2.0'))]) }}</div>
                        @if(!empty($updateState['latest_version']))
                            <div>{{ __('admin.header.notifications.latest_version', ['version' => (string) $updateState['latest_version']]) }}</div>
                        @endif
                        <div>{{ __('admin.header.notifications.daily_check') }}</div>
                        @if(!empty($updateState['checked_at']))
                            <div>{{ __('admin.header.notifications.checked_at', ['time' => (string) $updateState['checked_at']]) }}</div>
                        @endif
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if($isUpdateCenterEnabled && $isSuperAdmin)
                            <a href="{{ $notificationUpdateCenterUrl }}" class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white hover:bg-blue-700">
                                {{ __('admin.header.notifications.open_update_center') }}
                            </a>
                        @endif
                        <a href="{{ $notificationChangelogUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white hover:bg-blue-700">
                            {{ __('admin.header.notifications.view_changelog') }}
                        </a>
                        <a href="{{ $notificationGithubUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">
                            {{ __('admin.header.notifications.open_github') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="hidden md:flex items-center rounded-lg border border-gray-200 bg-white px-2 py-1 shadow-sm">
            <i data-lucide="languages" class="w-4 h-4 text-gray-400 mr-1.5"></i>
            <select
                class="admin-locale-select appearance-none bg-transparent pr-5 text-sm font-medium text-gray-700 outline-none cursor-pointer"
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
            <button onclick="toggleUserMenu()" class="flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900 transition-colors duration-200" type="button">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                    <i data-lucide="user" class="w-4 h-4 text-blue-600"></i>
                </div>
                <span class="hidden sm:inline text-gray-700">{{ $currentAdmin->username ?? '' }}</span>
                <i data-lucide="chevron-down" class="w-4 h-4"></i>
            </button>

            <div id="user-menu" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg py-1 z-50">
                <div class="px-4 py-2 border-b border-gray-100">
                    <div class="text-sm text-gray-700">{{ __('admin.header.welcome', ['name' => $currentAdmin->username ?? '']) }}</div>
                    <div class="text-xs text-gray-400">{{ $adminRoleLabel }}</div>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <i data-lucide="home" class="w-4 h-4 inline mr-2"></i>
                    {{ __('admin.nav.back_home') }}
                </a>
                <a href="{{ route('admin.ai.configurator') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <i data-lucide="wand-sparkles" class="w-4 h-4 inline mr-2"></i>
                    {{ __('admin.nav.ai_config') }}
                </a>
                <div class="border-t border-gray-100"></div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                        <i data-lucide="log-out" class="w-4 h-4 inline mr-2"></i>
                        {{ __('admin.button.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<style>
    .admin-locale-select {
        background-image: linear-gradient(45deg, transparent 50%, #6b7280 50%), linear-gradient(135deg, #6b7280 50%, transparent 50%);
        background-position: calc(100% - 8px) 52%, calc(100% - 4px) 52%;
        background-size: 4px 4px, 4px 4px;
        background-repeat: no-repeat;
    }
    .admin-ai-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #fff;
        font-size: 13px;
        font-weight: 500;
        padding: 7px 14px;
        border-radius: 18px;
        background: linear-gradient(135deg, var(--qdk-purple), #5b7cfa);
        box-shadow: 0 4px 10px rgba(108, 94, 252, 0.32);
        text-decoration: none;
        white-space: nowrap;
    }
    .admin-ai-btn:hover { opacity: 0.92; }
</style>

<script>
    function toggleUserMenu() {
        document.getElementById('user-menu')?.classList.toggle('hidden');
    }

    function toggleAdminNotifications() {
        document.getElementById('admin-notification-menu')?.classList.toggle('hidden');
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
            userMenu.classList.add('hidden');
        }
        if (notificationMenu && !event.target.closest('[onclick="toggleAdminNotifications()"]') && !notificationMenu.contains(event.target)) {
            notificationMenu.classList.add('hidden');
        }
    });
</script>
