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
</style>
