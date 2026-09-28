<style>
    :root {
        --qdk-primary: #3b6ef6;
        --qdk-primary-weak: #eef3ff;
        --qdk-purple: #6d5efc;
        --qdk-header-h: 56px;
        --qdk-sidebar-w: 220px;
        --qdk-sidebar-mini-w: 64px;
        --qdk-bg: #f6f7f9;
        --qdk-border: #eef0f4;
        --qdk-text: #1f2329;
        --qdk-text-sub: #8a9099;
    }

    html, body { height: 100%; }
    body.admin-body-root {
        margin: 0;
        background: var(--qdk-bg);
        color: var(--qdk-text);
        font-size: 14px;
    }

    .admin-shell {
        height: 100vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .admin-layout-body {
        flex: 1;
        display: flex;
        min-height: 0;
    }

    /* ===== 顶部导航条 ===== */
    .admin-navbar {
        height: var(--qdk-header-h);
        display: flex;
        align-items: center;
        background: #fff;
        border-bottom: 1px solid var(--qdk-border);
        flex-shrink: 0;
        z-index: 40;
    }
    .admin-navbar .logo-area {
        width: var(--qdk-sidebar-w);
        height: 100%;
        display: flex;
        align-items: center;
        gap: 8px;
        padding-left: 18px;
        border-right: 1px solid var(--qdk-border);
        transition: width 0.2s ease, padding 0.2s ease;
        overflow: hidden;
    }
    html.sidebar-collapsed .admin-navbar .logo-area {
        width: var(--qdk-sidebar-mini-w);
        padding-left: 0;
        justify-content: center;
    }
    .admin-navbar .logo-mark {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: linear-gradient(135deg, var(--qdk-purple), var(--qdk-primary));
        color: #fff;
        font-weight: 700;
        font-size: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .admin-navbar .logo-text {
        font-size: 16px;
        font-weight: 700;
        letter-spacing: 0.5px;
        white-space: nowrap;
        color: var(--qdk-text);
    }
    html.sidebar-collapsed .admin-navbar .logo-text { display: none; }
    .admin-navbar .collapse-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        margin-left: 12px;
        border-radius: 8px;
        color: #5f6672;
        cursor: pointer;
        border: none;
        background: transparent;
    }
    .admin-navbar .collapse-btn:hover { background: #f3f5f9; color: var(--qdk-primary); }
    /* ===== 侧边栏 ===== */
    .admin-sidebar {
        width: var(--qdk-sidebar-w);
        background: #fff;
        border-right: 1px solid var(--qdk-border);
        flex-shrink: 0;
        overflow-x: hidden;
        overflow-y: auto;
        transition: width 0.2s ease;
        padding: 8px 10px 24px;
    }
    html.sidebar-collapsed .admin-sidebar { width: var(--qdk-sidebar-mini-w); padding: 8px 6px 24px; }
    .admin-sidebar::-webkit-scrollbar { width: 6px; }
    .admin-sidebar::-webkit-scrollbar-thumb { background: #d5d9e0; border-radius: 3px; }

    .nav-group { margin-top: 14px; }
    .nav-group:first-child { margin-top: 4px; }
    .nav-group-title {
        font-size: 12px;
        color: #9aa1ac;
        letter-spacing: 0.5px;
        padding: 6px 12px;
        white-space: nowrap;
    }
    html.sidebar-collapsed .nav-group-title { display: none; }
    html.sidebar-collapsed .nav-group { margin-top: 8px; border-top: 1px solid var(--qdk-border); padding-top: 8px; }
    html.sidebar-collapsed .nav-group:first-child { border-top: none; }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 10px;
        height: 40px;
        margin: 2px 0;
        padding: 0 12px;
        border-radius: 8px;
        color: #4a505c;
        text-decoration: none;
        white-space: nowrap;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .nav-item:hover { background: #f3f5f9; }
    .nav-item .nav-icon {
        width: 18px;
        height: 18px;
        color: #8b909a;
        flex-shrink: 0;
    }
    .nav-item .nav-label { font-size: 14px; }
    .nav-item.is-active {
        background: var(--qdk-primary-weak);
        color: var(--qdk-primary);
        font-weight: 600;
    }
    .nav-item.is-active .nav-icon { color: var(--qdk-primary); }
    html.sidebar-collapsed .nav-item { justify-content: center; padding: 0; }
    html.sidebar-collapsed .nav-item .nav-label { display: none; }

    /* ===== 主内容区 ===== */
    .admin-main {
        flex: 1;
        overflow-y: auto;
        background: var(--qdk-bg);
        min-width: 0;
    }

    /* ===== 移动端抽屉 ===== */
    .admin-sidebar-overlay { display: none; }
    @media (max-width: 768px) {
        .admin-navbar .logo-area { width: auto; border-right: none; padding-left: 14px; }
        .admin-sidebar {
            position: fixed;
            top: var(--qdk-header-h);
            left: 0;
            bottom: 0;
            z-index: 50;
            transform: translateX(-100%);
            transition: transform 0.22s ease;
            box-shadow: 2px 0 16px rgba(15, 23, 42, 0.12);
        }
        html.sidebar-drawer-open .admin-sidebar { transform: translateX(0); }
        html.sidebar-collapsed .admin-sidebar { width: var(--qdk-sidebar-w); padding: 8px 10px 24px; }
        html.sidebar-collapsed .admin-sidebar .nav-label,
        html.sidebar-collapsed .admin-sidebar .nav-group-title { display: block; }
        html.sidebar-collapsed .admin-sidebar .nav-item { justify-content: flex-start; padding: 0 12px; }
        html.sidebar-collapsed .nav-group { border-top: none; padding-top: 0; }
        html.sidebar-drawer-open .admin-sidebar-overlay {
            display: block;
            position: fixed;
            inset: var(--qdk-header-h) 0 0 0;
            background: rgba(15, 23, 42, 0.4);
            z-index: 45;
        }
    }

    /* ===== 统一管理端 UI 组件 ===== */
    .admin-main-inner input:not([type=checkbox]):not([type=radio]):focus,
    .admin-main-inner textarea:focus,
    .admin-main-inner select:focus {
        border-color: var(--qdk-primary) !important;
        box-shadow: 0 0 0 2px rgba(59, 110, 246, 0.18) !important;
        outline: none;
    }
    .qdk-page-title { font-size: 20px; font-weight: 700; color: var(--qdk-text); margin: 0; }
    .qdk-page-sub { margin-top: 3px; font-size: 13px; color: var(--qdk-text-sub); }
    .qdk-back {
        width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        color: #8b909a; background: #fff; border: 1px solid var(--qdk-border);
        transition: all .15s ease;
    }
    .qdk-back:hover { color: var(--qdk-primary); border-color: #cdd8f5; background: var(--qdk-primary-weak); }

    .qdk-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        height: 38px; padding: 0 16px; border-radius: 9px;
        font-size: 14px; font-weight: 500; line-height: 1;
        border: 1px solid transparent; cursor: pointer; white-space: nowrap;
        transition: all .15s ease; text-decoration: none;
    }
    .qdk-btn-sm { height: 32px; padding: 0 12px; font-size: 13px; border-radius: 8px; }
    .qdk-btn-primary { background: var(--qdk-primary); color: #fff; }
    .qdk-btn-primary:hover { background: #2f5ad4; color: #fff; }
    .qdk-btn-ghost { background: #fff; color: #4a505c; border-color: #dfe3ea; }
    .qdk-btn-ghost:hover { background: #f5f7fa; border-color: #cdd3dd; }
    .qdk-btn-danger { background: #ef4444; color: #fff; }
    .qdk-btn-danger:hover { background: #dc2626; color: #fff; }
    /* QDK_KIT_MARKER */
    .qdk-card {
        background: #fff; border: 1px solid var(--qdk-border);
        border-radius: 12px; box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
    }
    .qdk-card-head { padding: 16px 20px; border-bottom: 1px solid var(--qdk-border); }
    .qdk-card-title { font-size: 15px; font-weight: 600; color: var(--qdk-text); margin: 0; }
    .qdk-card-sub { margin-top: 3px; font-size: 13px; color: var(--qdk-text-sub); }

    .qdk-stat {
        background: #fff; border: 1px solid var(--qdk-border); border-radius: 12px;
        padding: 18px; display: flex; align-items: center; gap: 14px;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
    }
    .qdk-stat-icon {
        width: 44px; height: 44px; border-radius: 11px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: var(--qdk-primary-weak); color: var(--qdk-primary);
    }
    .qdk-stat-label { font-size: 13px; color: var(--qdk-text-sub); }
    .qdk-stat-value { font-size: 22px; font-weight: 700; color: var(--qdk-text); line-height: 1.25; }

    .qdk-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 500;
        background: var(--qdk-primary-weak); color: var(--qdk-primary);
    }
    .qdk-badge-gray { background: #f2f4f7; color: #5f6672; }
    .qdk-badge-green { background: #e6f7ee; color: #10894e; }
    .qdk-badge-amber { background: #fef3e2; color: #b76a09; }

    .qdk-empty { padding: 56px 24px; text-align: center; }
    .qdk-empty-icon { width: 44px; height: 44px; margin: 0 auto 14px; color: #c2c7d0; }
    .qdk-empty-title { font-size: 15px; font-weight: 600; color: var(--qdk-text); }
    .qdk-empty-sub { margin-top: 4px; font-size: 13px; color: var(--qdk-text-sub); }

    .qdk-row { transition: background .15s ease; }
    .qdk-row:hover { background: #fafbfc; }
    .qdk-link { color: var(--qdk-text); transition: color .15s ease; }
    .qdk-link:hover { color: var(--qdk-primary); }
</style>
