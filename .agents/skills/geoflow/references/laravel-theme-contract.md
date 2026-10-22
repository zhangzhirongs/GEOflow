<!--
版权所有 © 2026 姚金刚。保留所有权利。
项目：geoflow
创建者：姚金刚
日期：2026-05-16
X: https://x.com/yaojingang
-->

# Laravel 主题契约

本契约取代当前 GEOFlow 重写中关于旧版 PHP 入口点的假设。

## 工作区检测

当前 GEOFlow 工作区通过以下文件和目录进行检测：

- `artisan`
- `routes/web.php`
- `app/Support/Site/SiteThemeViewResolver.php`
- 当首页构建器支持可用时，使用 `app/Support/Site/HomepageModuleBuilder.php`
- `resources/views/site`
- 当首页构建器渲染可用时，`resources/views/site/partials/homepage-modules.blade.php`
- `resources/views/theme`
- `resources/views/admin/site-settings/index.blade.php`

旧版 GEOFlow 工作区可能仍包含根级 `index.php`、`article.php`、`category.php`、`archive.php` 和 `/themes`。仅将它们作为回退方案来支持。

## 运行时主题根目录

当前主题包位于：

```text
resources/views/theme/{theme_id}/
```

默认内置前端位于：

```text
resources/views/site/
```

当前运行时资源通常位于：

```text
public/themes/{theme_id}/theme.css
public/themes/{theme_id}/theme.js
```

较旧或草稿包也可能包含 `resources/views/theme/{theme_id}/assets/theme.css`。在决定必须编辑哪些 CSS/JS 文件之前，请检查当前的 `layout.blade.php`。

`SiteThemeViewResolver::first($template, $data)` 解析为：

```text
theme.{active_theme}.{template}
site.{template}
```

主题 ID 必须匹配：

```text
^[a-zA-Z0-9_-]+$
```

活动主题存储在站点设置中为 `active_theme`；当为空时，GEOFlow 渲染内置的 `resources/views/site` 视图。

管理后台基础路径可配置。不要在主题包、预览、文档或生成的链接中硬编码 `/geo_admin`。

## 主题包结构

最小软件包：

```text
resources/views/theme/{theme_id}/
  manifest.json
```

典型包：

```text
resources/views/theme/{theme_id}/
  manifest.json
  tokens.json
  mapping.json
  home.blade.php
  article.blade.php
  category.blade.php
  archive-index.blade.php
  archive-month.blade.php
  layout.blade.php
  partials/
    header.blade.php
    footer.blade.php
    article-card.blade.php
  assets/
    theme.css

public/themes/{theme_id}/
  theme.css
  theme.js
```

任何缺失的 Blade 模板都会自动回退到 `resources/views/site`。
当目录具有有效的 `manifest.json` 或至少一个 `home.blade.php` 时，Admin 主题发现可以列出该目录，但真正的主题包应包含 `manifest.json`。

## 首页构建器契约

当前 GEOFlow 除 Blade 主题包外，还可以提供可配置的首页构建器。该构建器存储：

```text
site_settings.homepage_modules
site_settings.homepage_style
```

站点 `HomeController` 可能会将这些变量传入主页视图：

```text
homepageModules
homepageStyle
showHomepageModules
```

内置渲染器为：

```text
resources/views/site/partials/homepage-modules.blade.php
```

支持的构建器模块类型包括：

```text
hero
rich_text
image_band
metric_band
chart_band
feature_grid
article_collection
cta_band
lead_form
custom_html
```

`lead_form` 要求为现有的活动表单提供一个规范的顶级 `lead_form_slug`。主题或首页设计载荷可以引用该 slug；表单创建和激活仍属于需要身份验证的操作。

支持的样式令牌包括：

```text
accent_color
background_color
surface_color
text_color
muted_color
container_width
section_spacing
radius
```

管理后台设置页面可能会为这些设置暴露保存、预设和导入路由。设计运行可以准备 `homepage-design.json`，但除非操作员确认确切的 JSON 以及 `replace` 或 `append` 模式，否则不应直接应用它。

## 可编辑文件

安全编辑：

- `manifest.json`
- `tokens.json`
- `mapping.json`
- 根 `*.blade.php`
- `partials/*.blade.php`
- `assets/theme.css`
- `public/themes/{theme_id}/theme.css`
- 当当前主题已经使用它时，`public/themes/{theme_id}/theme.js`
- 诸如 `homepage-design.json` 和 `homepage-composition-plan.md` 的首页设计产物
- 设计说明，例如 `edit-session.json`、`change-plan.md`、`preview-notes.md`

在仅设计运行中，不要编辑控制器、模型、迁移、路由或数据库查询。

## 页面模板

稳定的模板名称：

- `home`
- `article`
- `category`
- `archive-index`
- `archive-month`
- `layout`
- `partials.header`
- `partials.footer`
- `partials.article-card`

稳定的公共路由：

- `/`
- `/article/{slug}`
- `/category/{slug}`
- `/archive`
- `/archive/{year}/{month}`

稳定的公共数据/渲染预期：

- 页面标题、元描述、关键词、canonical URL 以及 JSON-LD/schema 块必须保留在布局流中
- 文章正文内容已是渲染后的 HTML；不要将其作为纯 Markdown 重新转义
- 仅为文件名的图片说明（如 `333.png`）不应显示
- 前端语言遵循系统/站点行为；除非系统契约发生变化，否则不要添加独立的公开语言选择器
- 页脚版权应保持面向公众且居中/极简，除非主题明确拥有页脚布局
- 默认首页可以使用 `homepageCarouselSlides`、`featuredArticles`、`hotArticles`、`articles` 和 `cardSummaries` 来组合更丰富的门户或企业版块
- 当首页构建器存在时，默认首页也可以使用 `homepageModules`、`homepageStyle` 和 `showHomepageModules`
- 搜索/分类/分类缺失的首页状态必须保持清晰的结果上下文，并且不应意外继承所有默认首页营销模块

## 预览实际情况

当前 GEOFlow 默认不暴露独立的 `/preview/{theme}` 运行时路由。将预览视为以下之一：

- 在 `public_frontend` 中生成的静态预览文件，或
- 一个预览主题包，出现在 Admin -> Site Settings 中，仅在操作员确认后激活，或者
- 当 `SiteThemeEditorController` 和 `SiteThemeEditorService` 存在时的管理后台主题编辑器预览，或
- 若应用未来添加专用预览路由，则使用该路由。

在 `routes/web.php` 确认之前，绝不要声称存在实时预览路由。
