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

    /* ============================================================
       列表页皮肤层（参考 CSWG 任务列表观感）
       作用域限定在 .admin-main，隔离站点前台 / 登录页。
       所有规则为未分层样式，天然覆盖 Tailwind Play CDN 的 @layer utilities。
       ============================================================ */
    :root {
        --qdk-list-radius: 10px;
        --qdk-list-shadow: 0 2px 12px rgba(16, 24, 40, .05);
        --qdk-list-stripe: #fafbfc;
        --qdk-list-head-bg: #f7f8fa;
    }

    /* 卡片：柔和圆角 + 轻阴影 + 浅边框 */
    .admin-main .bg-white.shadow.rounded-lg,
    .admin-main .bg-white.rounded-lg.border,
    .admin-main .rounded-lg.border.bg-white.shadow-sm {
        border-radius: var(--qdk-list-radius);
        box-shadow: var(--qdk-list-shadow);
        border: 1px solid var(--qdk-border);
    }

    /* 表格容器圆角裁切（表头/斑马纹不溢出） */
    .admin-main .bg-white.shadow.rounded-lg,
    .admin-main .rounded-lg.border.bg-white.shadow-sm { overflow: hidden; }

    /* 表格：表头更柔和 */
    .admin-main table.min-w-full > thead { background: var(--qdk-list-head-bg); }
    .admin-main table.min-w-full > thead > tr > th {
        color: var(--qdk-text-sub);
        font-weight: 600;
        letter-spacing: .02em;
        padding-top: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--qdk-border);
    }

    /* 表格：行高更透气 + 斑马纹 + 柔和悬停（悬停规则在后，优先级高于斑马纹） */
    .admin-main table.min-w-full > tbody > tr { transition: background-color .15s ease; }
    .admin-main table.min-w-full > tbody > tr > td { padding-top: 13px; padding-bottom: 13px; vertical-align: middle; }
    .admin-main table.min-w-full > tbody > tr:nth-child(even) { background-color: var(--qdk-list-stripe); }
    .admin-main table.min-w-full > tbody > tr:hover { background-color: var(--qdk-primary-weak); }

    /* 表单控件：统一高度 / 圆角 / 主色聚焦（对未接入组件的页面也生效） */
    .admin-main form :is(select, input[type="text"], input[type="search"], input[type="date"], input[type="number"], input[type="email"], input[type="url"], input[type="password"]),
    .admin-main .admin-filter-control {
        border-radius: 8px;
        border: 1px solid #d5d9e0;
        background-color: #fff;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .admin-main form :is(select, input[type="text"], input[type="search"], input[type="date"], input[type="number"], input[type="email"], input[type="url"], input[type="password"]):focus,
    .admin-main .admin-filter-control:focus {
        outline: none;
        border-color: var(--qdk-primary);
        box-shadow: 0 0 0 3px rgba(59, 110, 246, .15);
    }

    /* 分页：圆角 + 柔和悬停 + 主色当前页 */
    .admin-main nav[role="navigation"] a,
    .admin-main nav[role="navigation"] span[aria-current] > span,
    .admin-main nav[role="navigation"] > div:last-child span,
    .admin-main nav[role="navigation"] > div:last-child a {
        border-radius: 8px !important;
        transition: background-color .15s ease, color .15s ease;
    }
    .admin-main nav[role="navigation"] a:hover { background-color: var(--qdk-primary-weak) !important; color: var(--qdk-primary) !important; }
    .admin-main nav[role="navigation"] span[aria-current] > span { background-color: var(--qdk-primary) !important; border-color: var(--qdk-primary) !important; color: #fff !important; }

    /* ===== 紧凑筛选栏组件（x-admin.filter-bar / filter-field） ===== */
    .admin-filter-bar {
        margin-bottom: 20px;
        padding: 12px 16px;
        background: #fff;
        border: 1px solid var(--qdk-border);
        border-radius: var(--qdk-list-radius);
        box-shadow: var(--qdk-list-shadow);
    }
    .admin-filter-bar__row { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; width: 100%; }
    .admin-filter-bar__form { display: flex; flex-wrap: wrap; align-items: center; gap: 12px 16px; flex: 1; min-width: 0; }
    .admin-filter-bar__actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
    .admin-filter-field { display: inline-flex; align-items: center; gap: 8px; flex-shrink: 0; }
    .admin-filter-field > label { font-size: 13px; font-weight: 500; color: var(--qdk-text); white-space: nowrap; }
    .admin-filter-field__control { display: inline-flex; flex-shrink: 0; }
    .admin-filter-field__control > * { width: 100%; }
    .admin-filter-control { height: 34px; padding: 0 10px; font-size: 13px; color: var(--qdk-text); }
    select.admin-filter-control { padding-right: 26px; }
    .admin-filter-actions { display: inline-flex; align-items: center; gap: 8px; margin-left: 4px; }
    .admin-filter-btn {
        display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 14px;
        border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; transition: all .15s ease; border: 1px solid transparent;
    }
    .admin-filter-btn--primary { background: var(--qdk-primary); color: #fff; }
    .admin-filter-btn--primary:hover { background: #2f5fe0; }
    .admin-filter-btn--ghost { background: #fff; color: var(--qdk-text); border-color: #d5d9e0; }
    .admin-filter-btn--ghost:hover { background: #f6f7f9; }
    @media (max-width: 768px) {
        .admin-filter-bar__row { flex-direction: column; align-items: stretch; }
        .admin-filter-field { display: flex; }
        .admin-filter-field > label { min-width: 64px; }
    }
</style>
