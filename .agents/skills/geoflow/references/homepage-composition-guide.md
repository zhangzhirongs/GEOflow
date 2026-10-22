<!--
版权所有 © 2026 姚金刚。保留所有权利。
项目：geoflow
创建者：姚金刚
日期：2026-06-23
X: https://x.com/yaojingang
-->

# 主页构成指南

当 GEOFlow 主题请求要求更完整的首页、企业站点风格、门户式首页，或超出基础文章列表的模块时，使用此参考。

## 目标

将 GEOFlow 首页从简单的信息流转变为组合式首页，同时保留 Laravel 主题契约。首页可以感觉像公司网站、资源中心、产品新闻室或媒体门户，但它仍必须根据现有站点设置、文章集合、路由辅助函数和主题资源进行渲染。

## 当前数据范围

安全的首页输入包括：

- `siteTitle`, `siteSubtitle`, `siteDescription`, `siteKeywords`
- `homepageCarouselSlides`：最多三张带有 `image_url`、`title` 和 `link_url` 的已启用幻灯片
- `homepageModules`：来自 `site_settings.homepage_modules` 的已启用模块记录
- `homepageStyle`：来自 `site_settings.homepage_style` 的全局首页样式令牌
- `showHomepageModules`：默认首页第一页渲染的控制器守卫
- `featuredArticles`：精选的引导性或案例研究风格的条目
- `hotArticles`：势头、热门或提醒式条目
- `articles`：最新已发布文章的分页器
- `cardSummaries`：以文章 ID 为键的安全文本摘要
- `search`, `category`, `categoryMissing`, `viewTitle`, `pageDescription`, `canonicalUrl`
- 已加载项上已有的文章字段，例如标题、slug、摘要、分类、作者、发布日期、可用时的封面/图片字段，以及存在时的浏览量

不要从 Blade 主题查询数据库来虚构更多首页数据。如果某个模块需要尚未传递给视图的数据，请将其标记为后端/系统变更需求，而不是静默硬编码。

## 首页构建器模式

当前 GEOFlow 可能暴露 `HomepageModuleBuilder`、`homepage_modules`、`homepage_style` 以及管理导入路由 `homepage-modules/import`。当这些信号存在时，对于首页模块和样式工作，优先使用可导入的 `homepage-design.json` 载荷。仅当所请求的视觉处理无法由构建器表示，或所选主题有意覆盖默认 partial 时，才使用 Blade/主题编辑。

Builder 样式字段：

- colors: `accent_color`, `background_color`, `surface_color`, `text_color`, `muted_color`
- 布局令牌：`container_width`（`narrow`、`default`、`wide`），`section_spacing`（`compact`、`normal`、`relaxed`），`radius`（`none`、`soft`、`round`）

Builder 模块字段：

- identity/order：`id`、`enabled`、`sort_order`
- behavior: `type`, `layout`, `data_source`, `limit`, `alignment`
- content: `title`, `subtitle`, `body`, `image_url`, `link_text`, `link_url`, `lead_form_slug`, `custom_html`
- 模块样式：`accent_color`、`surface_color`、`text_color`、`muted_color`

Builder 模块类型：

- `hero`：标题、正文、CTA、可选图片
- `rich_text`：纯说明性内容
- `image_band`：带可选 CTA 的大型视觉区块
- `metric_band`：行值写为 `Label|Value|Note`
- `chart_band`：以 `Label|Value|Note` 编写的 CSS 条形图行
- `feature_grid`：以 `Title|Description|URL` 形式编写的功能行
- `article_collection`：来自 `featured`、`hot` 或 `latest` 的文章
- `cta_band`：指向现有路由的转化/导航区块
- `lead_form`：由规范顶层 `lead_form_slug` 引用的现有活跃线索表单；创建或激活该表单是单独的需认证操作
- `custom_html`：仅用于简单结构化文案的已净化 HTML

支持的布局为 `single`、`split`、`grid` 和 `compact`。支持的对齐方式为 `left` 和 `center`。文章集合来源为 `featured`、`hot` 和 `latest`。

首选导入结构：

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
      "enabled": true,
      "sort_order": 10,
      "title": "Enterprise GEO Hub",
      "subtitle": "Knowledge workflow",
      "body": "Organize value, proof, resources, and next actions on one homepage.",
      "link_text": "View resources",
      "link_url": "/archive"
    },
    {
      "type": "article_collection",
      "layout": "grid",
      "data_source": "featured",
      "enabled": true,
      "sort_order": 40,
      "title": "Featured resources",
      "limit": 6
    }
  ]
}
```

当请求是重新设计首页模块堆栈时，使用 `replace`。仅当操作员希望保留现有模块并添加新模块时，才使用 `append`。在操作员审查确切的 JSON 和模式之前，绝不要提交导入路由。

## 模块族

### 主视觉与媒体

- `home.hero_statement`：站点标题、副标题、描述、主路由 CTA
- `home.hero_article`：带有摘要和阅读更多 CTA 的头条精选/最新文章
- `home.hero_carousel`：将 `homepageCarouselSlides` 作为以图片为主导的轮播或堆叠式功能面板
- `home.hero_split`：文本/价值主张加上头条文章或幻灯片视觉

### 文本与值块

- `home.value_statement`：由简短站点描述驱动的定位区块
- `home.feature_grid`：3-6 张文本卡片，来源于分类、精选文章主题或静态主题文案
- `home.process_steps`：当用户要求企业或服务定位时，用于方法论/流程的静态主题文案
- `home.faq_teaser`：静态主题文案，以及仅当当前主题拥有该文案时才出现的相关文章链接

### 指标与轻量图表区块

默认使用轻量级 HTML/CSS 图表，而不是添加图表依赖。

- `home.metric_band`：来自当前集合的计数，例如精选数、热门数、最新数
- `home.topic_distribution`：按已加载文章中的分类名称分组的条形图
- `home.momentum_chart`：根据文章发布日期生成的时间线或条形图
- `home.view_heat`：当该字段存在时，根据文章浏览次数生成的条形图
- `home.comparison_matrix`：用于服务/方案/对比式企业页面的静态主题文案

### 由文章派生的门户区块

- `home.featured_cases`：以案例研究或解决方案形式呈现的精选文章
- `home.hot_alerts`：将热门文章作为突发/趋势/优先资源
- `home.latest_resources`：使用紧凑卡片或分组行展示的最新文章
- `home.topic_rail`：当分类数据存在时，链接到公共分类路由的分类或主题标签
- `home.resource_tabs`：当前文章集合的视觉分组；避免大量使用 JS 的标签页，除非当前主题已经具备安全的脚本支持

### 视觉与 CTA 块

- `home.image_band`：轮播幻灯片图片或文章图片作为大型视觉面板
- `home.logo_strip`：纯文本信任条，除非系统传入真实 logo 数据
- `home.cta_band`：指向搜索、最新文章、归档或所选文章的路由安全 CTA
- `home.newsletter_like`：仅当它链接到现有路由或保持为非功能性文案时，才使用视觉订阅式区块；不要创建暗示后端支持的虚假表单

## 组合模式

### 企业门户

推荐顺序：

1. 主视觉宣言或轮播图
2. 指标栏或轻量图表证明区块
3. 功能/服务网格
4. 来自 `featuredArticles` 的精选案例
5. 来自 `articles` 的最新资源
6. CTA 横幅与页脚强化

### 内容中心

推荐顺序：

1. 头条文章主视觉
2. 热门文章栏
3. 主题/分类入口
4. 精选文章网格
5. 最新文章信息流
6. 归档/搜索 CTA

### 知识中心

推荐顺序：

1. 文字主导的主视觉
2. 阅读路径卡片
3. 主题分布图表
4. 精选资源
5. 最新资源
6. 搜索/归档 CTA

### 企业门户首页构建器

推荐的构建器顺序：

1. 采用 `split` 布局的 `hero`
2. `metric_band`或`chart_band`
3. `feature_grid`
4. 来自 `featured` 的 `article_collection`
5. 来自 `latest` 的 `article_collection`
6. `cta_band`

## 设计规则

- 以首页为先的模块应具有清晰的区块节奏，而不是一个长列表。
- 使用视觉多样性：一个主视觉区、一个数据/证明区、一个文本/价值区、一个源自文章的区域、一个 CTA 或导航区。
- 保留列表/搜索/分类状态。丰富的首页模块通常应仅在默认首页渲染：`search === ''`、无分类、无缺失分类、第一页。
- 当系统提供 `showHomepageModules` 行为时，予以保留。
- 搜索和分类视图应保持以任务为中心，并且不应在结果上下文上方显示企业填充模块。
- 避免虚假仪表盘。图表必须能够根据当前采集数据解释，或明确为静态主题插图。
- 避免虚假表单、虚假推荐语、虚假徽标和虚假客户声明，除非用户提供该内容。
- 保持主题中的模块文案可编辑，并避免硬编码看起来像真实业务数据的公司声明。
- 优先使用 CSS 条形图、网格、时间线、徽章和简单的 SVG 装饰，而不是新增 JS 依赖。
- 保持 `custom_html` 小巧且便于审查。仅使用经过清理的结构化标签（`p`、列表、标题、引用块、链接、简单容器）；不要依赖脚本、内联事件处理器、表单、iframe 或 `custom_html` 内的图片。

## 输出要求

对于首页增强，请包含：

- `homepage-composition-plan.md` 或 `change-plan.md` 中的某一节
- 首页模块构建器可用时的 `homepage-design.json`
- 每个新增首页模块的 `mapping.json` 个键
- 每个模块的明确数据源
- 集合为空时的回退行为
- 导入模式建议：`replace` 或 `append`
- 针对桌面端和移动端首页状态的预览说明
- 一份模块声明，说明若用户希望这些模块成为真正的动态内容，则需要哪些后端数据
