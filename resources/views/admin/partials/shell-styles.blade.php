@php
    // 后台统一设计系统样式：令牌 / 壳层 / 组件 / 存量页面兼容层，全部集中在 public/css/admin-ui.css
    // 版本号取文件修改时间，避免改样式后浏览器命中旧缓存
    $adminUiCssPath = public_path('css/admin-ui.css');
    $adminUiCssVersion = is_file($adminUiCssPath) ? (string) filemtime($adminUiCssPath) : '1';
@endphp
<link rel="stylesheet" href="{{ asset('css/admin-ui.css') }}?v={{ $adminUiCssVersion }}">
<style>
    /* 仅保留与 Blade 路由/语言相关的少量动态样式，其余样式见 admin-ui.css */
    .admin-locale-select {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background: transparent url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat right 8px center;
        border: 1px solid var(--gf-gray-200);
        border-radius: var(--gf-radius-control);
        color: var(--gf-gray-700);
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        height: 40px;
        padding: 0 28px 0 10px;
        transition: border-color 120ms ease-out, background-color 120ms ease-out;
    }
    .admin-locale-select:hover {
        border-color: var(--gf-gray-300);
    }
    .admin-locale-select:focus {
        border-color: var(--gf-blue-600);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        outline: none;
    }

    /* 顶栏浮层（通知 / 用户菜单） */
    .admin-popover {
        background: #fff;
        border: 1px solid var(--gf-gray-200);
        box-shadow: var(--gf-shadow-menu);
        display: none;
        overflow: hidden;
        position: absolute;
        right: 0;
        top: calc(100% + 10px);
        z-index: 60;
    }
    .admin-popover.is-open {
        display: block;
    }
    .admin-popover--notice {
        border-radius: var(--gf-radius-focus);
        width: min(360px, calc(100vw - 32px));
    }
    .admin-popover--user {
        border-radius: var(--gf-radius-nav);
        width: 224px;
    }
    .admin-popover__head {
        align-items: center;
        border-bottom: 1px solid var(--gf-gray-100);
        display: flex;
        gap: 12px;
        justify-content: space-between;
        padding: 14px 16px;
    }
    .admin-popover__title {
        color: var(--gf-gray-900);
        font-size: 14px;
        font-weight: 600;
    }
    .admin-popover__body {
        padding: 16px;
    }
    .admin-popover__copy {
        color: var(--gf-gray-600);
        font-size: 13px;
        line-height: 22px;
        margin: 8px 0 0;
    }
    .admin-popover__meta {
        background: var(--gf-gray-50);
        border-radius: var(--gf-radius-nav);
        color: var(--gf-gray-500);
        font-size: 12px;
        line-height: 20px;
        margin-top: 14px;
        padding: 10px 12px;
    }
    .admin-popover__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
    }
    .admin-menu-meta {
        border-bottom: 1px solid var(--gf-gray-100);
        padding: 12px 16px;
    }
    .admin-menu-meta__name {
        color: var(--gf-gray-900);
        font-size: 14px;
        font-weight: 600;
    }
    .admin-menu-meta__role {
        color: var(--gf-gray-400);
        font-size: 12px;
        margin-top: 2px;
    }
    .admin-menu-link {
        align-items: center;
        color: var(--gf-gray-700);
        display: flex;
        font-size: 14px;
        gap: 8px;
        min-height: 40px;
        padding: 8px 16px;
        transition: background-color 120ms ease-out;
    }
    .admin-menu-link:hover {
        background: var(--gf-gray-100);
    }
    .admin-menu-link--danger {
        color: var(--gf-red-600);
    }
    .admin-menu-link--danger:hover {
        background: var(--gf-red-50);
    }
    .admin-menu-link .nav-icon {
        height: 16px;
        width: 16px;
    }
    .admin-menu-divider {
        border-top: 1px solid var(--gf-gray-100);
    }
</style>
