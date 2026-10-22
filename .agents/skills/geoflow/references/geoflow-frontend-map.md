<!--
版权所有 © 2026 姚金刚。保留所有权利。
项目：geoflow
创建者：姚金刚
日期：2026-05-16
X: https://x.com/yaojingang
-->

# GEOFlow 前端模块与变量映射

本文档是 `geoflow` 的当前 GEOFlow 前端契约。它基于 Laravel 重写版，而不是较早的根级 PHP 前端。

## 1. 当前工作区基线

当前 GEOFlow 信号：

- Laravel 入口：`artisan`
- 公共路由：`routes/web.php`
- 站点控制器：`app/Http/Controllers/Site/*`
- 主题解析器：`app/Support/Site/SiteThemeViewResolver.php`
- 存在首页模块构建器时：`app/Support/Site/HomepageModuleBuilder.php`
- 内置前端视图：`resources/views/site`
- 存在时使用内置首页模块局部视图：`resources/views/site/partials/homepage-modules.blade.php`
- 可选主题：`resources/views/theme/{theme_id}`
- 管理主题选择：`resources/views/admin/site-settings/index.blade.php`
- 管理页面主题编辑器（若存在）：`app/Http/Controllers/Admin/SiteThemeEditorController.php`
- 主题编辑器服务（存在时）：`app/Services/Admin/SiteThemeEditorService.php`
- 存在时的公共主题资源：`public/themes/{theme_id}/theme.css` 以及可选的 `theme.js`

诸如根级 `index.php`、`article.php`、`category.php`、`archive.php` 和 `/themes` 之类的旧版 PHP 信号仅作为回退方案。

## 2. 路由契约

在仅设计运行期间，不要更改这些公共路由：

- `/`
- `/article/{slug}`
- `/category/{slug}`
- `/archive`
- `/archive/{year}/{month}`
- `/forms/{slug}`
- `/forms/{slug}/submissions`

Admin 路由基础路径可通过 `config/geoflow.php` 自定义；切勿在主题输出中硬编码 `/geo_admin`。
除非当前应用显式传入此类链接，否则主题不应添加管理链接。

当前首页模块管理路由（当存在于可配置的管理前缀下时）：

- `admin.site-settings.homepage-modules`：保存 `homepage_modules` 和 `homepage_style`
- `admin.site-settings.homepage-modules.preset`：在 `replace` 或 `append` 模式下应用内置预设
- `admin.site-settings.homepage-modules.import`：在 `replace` 或 `append` 模式下导入已审核的首页设计 JSON

不要自动调用这些路由。将导入或预设应用视为最终确定步骤，需要操作员审查确切的负载和模式。

## 3. 主题解析契约

`SiteThemeViewResolver::first($template, $data)` 解析为：

```text
theme.{active_theme}.{template}
site.{template}
```

如果主题未提供 Blade 视图，GEOFlow 会回退到 `resources/views/site`。

允许的主题 ID 模式：

```text
^[a-zA-Z0-9_-]+$
```

## 4. 主题包文件

当前主题根目录：

```text
resources/views/theme/{theme_id}/
```

推荐的文件：

```text
manifest.json
tokens.json
mapping.json
home.blade.php
article.blade.php
category.blade.php
archive-index.blade.php
archive-month.blade.php
layout.blade.php
partials/header.blade.php
partials/footer.blade.php
partials/article-card.blade.php
assets/theme.css
```

发现流程仅需要 `manifest.json`。缺失的 Blade 视图会回退到内置的 `site.*`。

当前运行时主题还可能从以下位置加载 CSS/JS：

```text
public/themes/{theme_id}/theme.css
public/themes/{theme_id}/theme.js
```

当 `resources/views/theme/{theme_id}/assets/theme.css` 和 `public/themes/{theme_id}/theme.css` 都存在时，在决定哪个文件是运行时样式的权威来源之前，检查当前布局。

## 5. 内置前端视图

稳定的内置视图：

- `resources/views/site/layout.blade.php`
- `resources/views/site/home.blade.php`
- `resources/views/site/article.blade.php`
- `resources/views/site/category.blade.php`
- `resources/views/site/archive-index.blade.php`
- `resources/views/site/archive-month.blade.php`
- `resources/views/site/partials/header.blade.php`
- `resources/views/site/partials/footer.blade.php`
- `resources/views/site/partials/article-card.blade.php`
- `resources/views/site/partials/lead-form.blade.php`

## 6. 共享布局模块

### `layout`

负责：

- HTML 外壳
- 页面标题和元描述
- 提供时的规范 URL
- 若提供，则包含 JSON-LD / schema 块
- 页眉 / 内容 / 页脚插槽

不要移除 SEO、canonical 或 schema 输出。

### `partials.header`

负责：

- 站点名称 / 徽标
- 首页链接
- 分类导航
- 存在时的归档链接
- 响应式导航

输入是由控制器提供的站点设置和分类集合。不要在主题中直接查询数据库。

### `partials.footer`

负责：

- 公开版权行
- 页脚链接和极简的公开元数据

保持其面向公众。管理端的版权/版本 UI 是独立的。

## 7. 首页模块

查看：`home`

稳定的模块：

- `home.hero`
- `home.hero_carousel`
- `home.hot_articles`
- `home.featured_articles`
- `home.latest_articles`
- `home.article_card`
- `home.rich_text_block`
- `home.metric_band`
- `home.chart_lite`
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
- `home.visual_band`
- `home.cta_band`
- `home.empty_state`
- `home.pagination`

典型数据：

- 站点设置
- 分类
- 首页轮播幻灯片
- 首页模块记录
- 主页样式令牌
- 按 slug 索引的活动潜在客户表单
- 精选文章
- 热门文章
- 分页的最新文章
- 文章摘要
- 分页元数据

当前升级信号：

- `homepageCarouselSlides`：来自站点设置的最多三张已启用幻灯片；字段为 `image_url`、`title` 和 `link_url`。
- `homepageModules`：从 `site_settings.homepage_modules` 规范化得到的已启用首页模块记录。
- `homepageStyle`：从 `site_settings.homepage_style` 规范化得到的全局首页样式令牌。
- `leadFormsBySlug`：当增长中心表存在时，可供默认站点首页模块使用的活跃线索表单。
- `showHomepageModules`：仅在默认首页第一页状态下为 true：无搜索、无分类、无缺失分类，且为第 1 页。
- `hotArticles`：标记为热门的已发布文章，通常仅在默认的第一个首页上可用。
- `featuredArticles`：已发布的精选文章，通常仅在默认的第一个首页上可用。
- `cardSummaries`：以文章 ID 为键的安全文章卡片摘要。

首页增强可以在不修改后端的情况下，从这些输入中组合出更完整的首页。安全的示例包括：来自轮播幻灯片的大幅主视觉图片、热门文章栏、基于当前集合计数的 KPI 卡片、基于已加载文章分类或浏览量的轻量图表条形、来自站点文案的文本/数值模块、首页构建器模块，以及指向公共路由的 CTA 区块。

除非用户明确要求该行为，否则不要让富首页模块出现在搜索结果、分类筛选或分类缺失状态之上。

## 7.1 首页模块构建器契约

当 `HomepageModuleBuilder` 存在时，系统可以通过站点设置存储和渲染可配置的首页模块，而无需仅依赖 Blade 自定义。

样式字段：

- `accent_color`, `background_color`, `surface_color`, `text_color`, `muted_color`
- `container_width`: `narrow`, `default`, `wide`
- `section_spacing`: `compact`, `normal`, `relaxed`
- `radius`: `none`, `soft`, `round`

模块字段：

- `id`, `type`, `layout`, `data_source`, `enabled`, `sort_order`
- `title`, `subtitle`, `body`, `image_url`, `link_text`, `link_url`, `limit`
- `custom_html`
- `lead_form_slug`
- `accent_color`, `surface_color`, `text_color`, `muted_color`, `alignment`

支持的模块类型：

- `hero`
- `rich_text`
- `image_band`
- `metric_band`
- `chart_band`
- `feature_grid`
- `article_collection`
- `cta_band`
- `lead_form`
- `custom_html`

支持的布局为 `single`、`split`、`grid` 和 `compact`。支持的文章来源为 `featured`、`hot` 和 `latest`。支持的对齐方式为 `left` 和 `center`。当前校验将模块数量上限设为 `HomepageModuleBuilder::MAX_MODULES`（通常为 `30`），并将文章集合数量上限设为 `12`。

`lead_form` 是默认站点首页模块类型。它会渲染 `resources/views/site/partials/lead-form.blade.php`，并要求一个规范的顶级 `lead_form_slug` 指向现有的活动线索表单。导入的设计草稿可能使用诸如 `form_slug`、`lead_form`、`form` 或 `conversion_form` 之类的别名，但技能编写的载荷应显式输出 `lead_form_slug`。

`public_frontend` 模式不会创建线索表单。如果不存在可用的已启用表单 slug，请使用 `cta_band` 链接到目标公共表单 URL，或切换到 `operations` 进行经过身份验证的线索表单创建。

默认站点支持的模块与远程目标包支持的模块是彼此独立的契约。当前默认站点可以支持 `lead_form`；当前受管理的 GeoFlow Agent 目标包可能不会在 `/geoflow-agent/v1/frontend-capabilities` 中暴露 `lead_form`。当远程支持缺失时，将渠道负载降级为 CTA/链接，或要求在同步前升级目标包。

内置预设（若存在）：

- `enterprise_brand`
- `content_portal`
- `service_solution`
- `report_hub`
- `product_launch`

智能体输出可通过 `homepage-modules/import` 以 JSON 形式导入。首选顶层结构：

```json
{
  "style": {
    "accent_color": "#2563eb",
    "container_width": "wide",
    "section_spacing": "relaxed",
    "radius": "soft"
  },
  "modules": [
    {
      "type": "hero",
      "layout": "split",
      "enabled": true,
      "sort_order": 10,
      "title": "Enterprise GEO Hub",
      "body": "Use homepage modules to present value, proof, resources, and next actions.",
      "link_text": "View resources",
      "link_url": "/archive"
    },
    {
      "type": "lead_form",
      "layout": "single",
      "enabled": true,
      "sort_order": 60,
      "title": "Request a GEO consultation",
      "body": "Use an existing active lead form; this payload only references the slug.",
      "lead_form_slug": "geo-consultation"
    }
  ]
}
```

导入器还接受常见别名，例如 `sections`、`blocks`、`style_tokens`、`kind`、`headline`、`copy`、`cta_label` 和 `cta_url`。仍应优先使用上面的规范字段名，以便审查明确。

## 8. 分类页模块

查看：`category`

稳定的模块：

- `category.header`
- `category.description`
- `category.article_list`
- `category.article_card`
- `category.pagination`
- `category.empty_state`

典型数据：

- 分类
- 文章
- 分页元数据

## 9. 文章页模块

查看：`article`

稳定的模块：

- `article.header`
- `article.cover_image`
- `article.meta`
- `article.prose`
- `article.tags`
- `article.related_articles`
- `article.ad_slot`

典型数据：

- 文章
- 作者
- 分类
- 标签
- 相关文章
- 渲染后的文章 HTML
- 文章图片
- 文章详情广告
- 规范 URL 和结构化数据

重要的渲染规则：不要显示仅包含文件名（如 `333.png`）的图片说明；除非系统提供有意义的说明文字，否则文章图片应保持视觉呈现。
重要的 markdown 规则：文章内容以渲染后的 HTML 传递给主题。主题应为标准 HTML 节点（如 `h2`、`h3`、`p`、`ul`、`ol`、`blockquote`、`table`、`pre` 和 `code`）设置样式；不应在列表卡片或文章详情视图中暴露原始 markdown 标记。

## 10. 归档模块

视图：`archive-index`、`archive-month`

稳定的模块：

- `archive.overview`
- `archive.month_group`
- `archive.article_list`
- `archive.article_card`
- `archive.pagination`

典型数据：

- 归档月份
- 选定的年份/月份
- 文章
- 分页元数据

## 11. 安全编辑面

主题包中安全的内容包括：

- 现有页面/模块边界内的 Blade 标记
- CSS 令牌、间距、阴影、颜色、边框、排版、响应式行为
- 卡片布局和元数据呈现
- 从当前视图变量或明确的静态主题文案中衍生的首页组成模块
- 页眉/页脚呈现
- 广告位呈现
- `manifest.json`, `tokens.json`, `mapping.json`
- 当前布局加载 `asset('themes/{theme_id}/...')` 时的公开主题 CSS/JS 文件

未经明确的系统变更批准，这样做不安全：

- 控制器
- 模型
- 迁移
- 路由
- markdown 渲染器
- SEO/schema 生成
- 数据库查询
- 管理后台主题激活
- 管理后台基础路径行为
- 多语言持久化行为
- 动态企业数据，例如客户、徽标、客户评价、产品、套餐或分析数据，除非 GEOFlow 已将其传递到视图，或用户明确扩大范围

## 12. 预览说明

当前 Laravel GEOFlow 不保证存在独立的 `/preview/{theme}` 路由。设计运行应采用以下任一方式：

- 生成用于评审的静态预览产物，或
- 在 `resources/views/theme` 下创建预览主题，并要求操作员仅在审核后激活它，或
- 当前应用提供 `SiteThemeEditorController` 时，使用后台主题编辑器预览，或者
- 仅当当前应用实际提供专用预览路由时，才使用该路由。

绝不要根据旧版 PHP 系统臆造预览 URL。
