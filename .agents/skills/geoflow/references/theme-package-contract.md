<!--
版权所有 © 2026 姚金刚。保留所有权利。
项目：geoflow
创建者：姚金刚
日期：2026-05-16
X: https://x.com/yaojingang
-->

# 主题编辑与包契约

GEOFlow 设计运行的输出应是预览优先的会话或包，而不是直接覆盖生产环境。

## 模式 A：完整新主题包

```text
resources/views/theme/
  template-YYYYMMDD-XXX/
    manifest.json
    tokens.json
    mapping.json
    assets/
      theme.css
      preview.css
    layout.blade.php
    home.blade.php
    category.blade.php
    article.blade.php
    archive-index.blade.php
    archive-month.blade.php
    partials/
      header.blade.php
      footer.blade.php
      article-card.blade.php

public/themes/template-YYYYMMDD-XXX/
  theme.css
  theme.js
```

## 模式 B：预览主题编辑会话

```text
resources/views/theme/
  target-theme-edit-YYYYMMDD-XXX/
    manifest.json
    edit-session.json
    design-audit.md
    tokens.delta.json
    mapping.delta.json
    change-plan.md
    preview-notes.md
    assets/
      theme.css
      preview.css
    home.blade.php
    category.blade.php
    article.blade.php
    archive-index.blade.php
    archive-month.blade.php
    partials/
      header.blade.php
      footer.blade.php
      article-card.blade.php

public/themes/target-theme-edit-YYYYMMDD-XXX/
  theme.css
  theme.js
```

## 模式 C：主页构建器设计 JSON

当当前 GEOFlow 工作区暴露 `HomepageModuleBuilder`、`homepage_modules`、`homepage_style` 以及管理后台导入路由时，使用此模式。

```text
homepage-composition-plan.md
homepage-design.json
preview-notes.md
```

首选 JSON 结构：

```json
{
  "style": {
    "accent_color": "#2563eb",
    "background_color": "#ffffff",
    "surface_color": "#ffffff",
    "text_color": "#111827",
    "muted_color": "#6b7280",
    "container_width": "wide",
    "section_spacing": "relaxed",
    "radius": "soft"
  },
  "modules": [
    {
      "type": "hero",
      "layout": "split",
      "data_source": "latest",
      "enabled": true,
      "sort_order": 10,
      "title": "Enterprise GEO Hub",
      "subtitle": "Knowledge workflow",
      "body": "Organize homepage content with custom modules.",
      "image_url": "",
      "link_text": "View resources",
      "link_url": "/archive",
      "lead_form_slug": "",
      "limit": 4,
      "custom_html": "",
      "alignment": "left"
    }
  ]
}
```

对于此模式，请包含：

- 导入模式：`replace` 或 `append`
- 模块类型、布局、来源和回退说明
- 每个 `link_url` 和 `image_url` 的路由安全
- 每个 `lead_form` 模块都有一个现有的活动 `lead_form_slug`，表单创建或激活保留在经过身份验证的运营阶段
- 使用时的 `custom_html` 安全说明
- 警告：除非在暂存环境应用，否则导入可能会影响线上默认首页

## 最小清单字段

- `id`
- `name`
- `mode`
- `created_at`
- `compatible_pages`
- `compatible_modules`
- `preview_routes`
- `notes`
- 可选：
  - `source_reference_url`
  - `base_template_id`
  - `target_theme_id`
  - `optimization_goals`
  - `change_scope`
  - `session_state`

推荐的 Laravel 字段：

- `framework`: `laravel`
- `geoflow_contract`: `site-theme-view-resolver`
- `requires_admin_activation`：当不存在隔离的预览路由时，`true`
- `blade_templates`：提供的 Blade 模板列表
- `fallback_templates`：预期回退到 `resources/views/site` 的模板列表

## 最小编辑会话字段

- `base_theme_id`
- `preview_theme_id`
- 当运行时 CSS/JS 已被分叉时，`public_assets_dir`
- `created_at`
- `change_request`
- `preview_routes`
- `finalize_options`

## 最小映射输出

- `header`
- `footer`
- `home.hero`
- `home.hero_carousel`
- `home.hero_media`
- `home.builder.hero`
- `home.builder.rich_text`
- `home.builder.image_band`
- `home.builder.metric_band`
- `home.builder.chart_band`
- `home.builder.feature_grid`
- `home.builder.article_collection`
- `home.builder.cta_band`
- `home.builder.lead_form`
- `home.builder.custom_html`
- `home.metric_band`
- `home.chart_lite`
- `home.text_value_block`
- `home.service_or_topic_grid`
- `home.case_or_resource_grid`
- `home.hot_articles`
- `home.featured_list`
- `home.cta_band`
- `home.article_card`
- `category.article_card`
- `article.hero`
- `article.prose_shell`
- `article.related_articles`
- `article.sticky_ad`
- `archive.overview_row`
- `archive.article_card`
- 当 `mode = edit_theme` 或 `mode = optimize` 时：
  - `change_scope`
  - `unchanged_contracts`
  - `delta_strategy`
  - `edited_files`

## 最终确定路径

- `publish_as_new_theme`：保留或重命名预览分叉，以便管理端主题发现功能可以将其列为新模板
- `replace_base_theme`：备份原始目标主题，然后从已确认的预览分叉替换它
- `activate_after_confirmation`：仅在操作员确认已审核的预览会话后激活
- `import_homepage_design_after_confirmation`：仅在操作员确认确切的 JSON 和模式后，才导入已审核的首页构建器负载

## 预览契约

该包应至少在以下环境中预览：

- 主页预览
- 分类预览
- 文章详情预览
- 归档概览预览

当前 Laravel GEOFlow 可能不提供独立的 `/preview/{theme}` URL。预览主题编辑会话应明确说明评审使用的是静态预览产物、临时管理端激活，还是在 `routes/web.php` 中发现的真实预览路由。

优化运行还应针对每个涉及的模块包含简短的优化前后理由说明。

首页增强运行还应包括：

- `homepage-composition-plan.md` 或 `change-plan.md` 中的等效部分
- 当前系统支持首页构建器导入时的 `homepage-design.json`
- 每个新增首页模块的数据源
- 默认、空状态、搜索、分类和移动端行为
- CSS/JS 是在 `resources/views/theme/{theme_id}/assets` 还是 `public/themes/{theme_id}` 中编辑
- 模块/样式工作是经由主题文件还是首页构建器导入 JSON 交付
- 使用构建器 JSON 时的 `replace` 或 `append` 导入模式
- 任何需要后端工作而非仅主题工作的所需模块

在操作员确认替换、作为新内容发布或激活之前，预览必须与当前活动的公共模板隔离。

## 不可妥协的渲染规则

- 保持布局级标题、描述、关键词、canonical 和 schema 槽位不变
- 不要在列表摘要或文章正文中渲染原始 Markdown 标记
- 当说明文字仅是文件名时，不要显示图片说明文字
- 不要硬编码管理后台 URL
- 在仅设计包中，不要更改控制器、数据库查询、路由定义或 Markdown 渲染服务
