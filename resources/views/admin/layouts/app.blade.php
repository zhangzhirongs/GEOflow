@php
    $adminBrandName = \App\Support\AdminWeb::siteName();
    $adminBrandInitial = mb_strtoupper(mb_substr(trim((string) $adminBrandName), 0, 1) ?: 'G');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (is_array($anonymousUsageTelemetryPayload ?? null))
        <meta name="geoflow-telemetry-endpoint" content="{{ $anonymousUsageTelemetryPayload['endpoint'] }}">
        <meta name="geoflow-telemetry-event" content="{{ $anonymousUsageTelemetryPayload['event'] }}">
        <meta name="geoflow-telemetry-instance" content="{{ $anonymousUsageTelemetryPayload['instance_id'] }}">
        <meta name="geoflow-telemetry-user" content="{{ $anonymousUsageTelemetryPayload['user_hash'] }}">
        <meta name="geoflow-telemetry-version" content="{{ $anonymousUsageTelemetryPayload['version'] }}">
        <meta name="geoflow-telemetry-interval" content="{{ $anonymousUsageTelemetryPayload['interval_seconds'] }}">
    @endif
    <title>@isset($pageTitle){{ $pageTitle }} — @endisset{{ $adminBrandName }}</title>
    <script src="{{ asset('js/tailwindcss.play-cdn.js') }}"></script>
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    @include('admin.partials.shell-styles')
    {{-- 折叠状态提前应用，避免刷新闪烁 --}}
    <script>
        (function () {
            try {
                if (localStorage.getItem('geoflow.sidebar.collapsed') === '1') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {}
        })();
    </script>
    @stack('styles')
</head>
<body class="admin-body-root">
<div id="admin-shell" class="admin-shell">
    @include('admin.partials.sidebar', [
        'activeMenu' => $activeMenu ?? '',
        'adminBrandName' => $adminBrandName,
        'adminBrandInitial' => $adminBrandInitial,
    ])
    <div id="admin-sidebar-overlay" class="admin-sidebar-overlay" onclick="toggleSidebarDrawer(false)"></div>
    <div class="admin-shell-body">
        @include('admin.partials.header', [
            'adminBrandName' => $adminBrandName,
            'adminSiteName' => $adminSiteName ?? $adminBrandName,
            'pageTitle' => $pageTitle ?? '',
        ])
        <main class="admin-main">
            <div class="admin-content">
                @if (session('message'))
                    <div class="admin-flash-alert is-success mb-4" role="status">
                        <i data-lucide="circle-check" class="nav-icon shrink-0"></i>
                        <span>{{ session('message') }}</span>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="admin-flash-alert is-error mb-4" role="alert">
                        <i data-lucide="circle-alert" class="nav-icon shrink-0"></i>
                        <div>
                            @foreach ($errors->all() as $err)
                                <div>{{ $err }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif
                @yield('content')
            </div>
            @include('admin.partials.footer')
        </main>
    </div>
</div>
@include('admin.partials.welcome-modal')
@vite('resources/js/app.js')
@if (is_array($anonymousUsageTelemetryPayload ?? null))
    <script src="{{ asset('js/geoflow-pulse.js') }}" defer></script>
@endif
<script>
    (function () {
        var openPanel = function (panel, btn, open) {
            if (!panel) { return; }
            panel.classList.toggle('is-open', open);
            if (btn) { btn.setAttribute('aria-expanded', open ? 'true' : 'false'); }
        };
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-filter-more-toggle]');
            if (!btn) { return; }
            var panel = document.getElementById(btn.getAttribute('aria-controls'));
            if (!panel) { return; }
            openPanel(panel, btn, !panel.classList.contains('is-open'));
        });
        document.querySelectorAll('[data-filter-advanced]').forEach(function (panel) {
            if (panel.classList.contains('is-open')) { return; }
            var active = Array.prototype.some.call(panel.querySelectorAll('input, select'), function (el) {
                if (el.type === 'hidden') { return false; }
                if (el.type === 'checkbox' || el.type === 'radio') { return el.checked; }
                return el.value !== null && String(el.value).trim() !== '';
            });
            if (active) {
                var btn = document.querySelector('[data-filter-more-toggle][aria-controls="' + panel.id + '"]');
                openPanel(panel, btn, true);
            }
        });
    })();
</script>
@stack('scripts')
</body>
</html>
