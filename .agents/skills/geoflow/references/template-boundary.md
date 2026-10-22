<!--
版权所有 © 2026 姚金刚。保留所有权利。
项目：geoflow
创建者：姚金刚
日期：2026-05-16
X: https://x.com/yaojingang
-->

# 模板边界

`public_frontend` 模式涵盖 GEOFlow 前端模板克隆、主题发现、预览优先的主题编辑，以及在保留 GEOFlow 渲染契约的前提下进行受控设计调整。

## 允许的操作

- 盘点现有前端页面模块
- 盘点前端页面使用的变量和辅助函数
- 发现当前工作区中现有主题及其可编辑文件，对于 Laravel GEOFlow 优先使用 `resources/views/theme`
- 选择目标主题并将其派生到预览编辑会话中
- 检查参考 URL 以获取设计令牌和布局方向
- 设计一个兼容 GEOFlow 的主题包
- 审查当前模板的层级、间距、排版、密度和响应式问题
- 提出增量式 token 优化和模块级视觉清理
- 当 hero/media、chart-lite、metric、text、service/category、case/resource、visual CTA 和 portal 区块可以使用当前 GEOFlow 数据或明确的静态主题文案时，用它们丰富默认首页
- 当当前 GEOFlow 工作区暴露 `HomepageModuleBuilder` 和管理员导入路由时，为 `homepage_modules` 和 `homepage_style` 生成经过审查的 `homepage-design.json` 载荷
- 为涉及的模块生成前后预览说明
- 生成预览优先的集成计划
- 建议管理员之后应如何公开模板选择

## 禁止的操作

- 仅为模仿参考站点而重写 GEOFlow 业务逻辑
- 用硬编码的模拟内容替换现有 PHP 数据查询
- 更改路由规则，例如 `/article/{slug}`、`/category/{slug}` 或 `/archive/...`
- 移除 SEO 或结构化数据生成
- 未经预览和确认直接在生产环境激活
- 在尚未审查预览分支时先编辑线上目标主题
- 将外部站点的完整 HTML 复制为运行时模板契约
- 在仅设计请求期间编辑 Laravel 控制器、模型、迁移或路由

## 不可妥协的 GEOFlow 契约

- 主页仍由已发布文章、精选文章、分类、搜索状态和分页数据驱动
- 更丰富的默认首页模块仍由当前视图输入驱动，例如站点文案、轮播图、精选文章、热门文章、最新文章、卡片摘要、首页构建器记录、首页样式令牌和路由辅助函数
- 搜索、分类和分类缺失状态仍为聚焦的结果页面，除非明确重新设计
- 文章详情仍由文章、相关文章、标签、SEO 区块和文章详情广告位数据驱动
- 分类页面仍由分类元数据和分页文章列表驱动
- 归档页面仍由归档概览或特定月份的归档数据驱动
- 前端继续使用 GEOFlow 路由、辅助函数和数据字段

## 安全替换面

- 模块容器内的 HTML 结构
- CSS 令牌、间距、阴影、边框和颜色
- 图标体系与按钮样式
- 现有模块的布局组合
- 现有模块的设计层级、视觉密度和响应式行为
- 首页模块组成，包括大图区块、静态文本/数值面板、纯 CSS 图表、指标卡、CTA 条带以及替代的文章列表呈现方式
- 首页构建器 JSON，包括 `style`、`modules`、内置模块类型、导入模式说明以及现有路由安全链接
- 重复卡片、章节标题和元数据行之间的一致性清理
- 广告区块视觉呈现
- 页眉 / 页脚呈现
- 主题清单元数据、预览路由和非运行时说明

## 不安全的替换面

- 移除文章标题、内容、分类、作者、标签、slug 或广告 CTA 字段的必需占位符
- 编造需要 GEOFlow 尚未提供的后端数据的模块
- 编造当前视图未提供的企业事实、客户标志、客户评价、转化表单、定价、分析数据或图表数据
- 添加虚假表单或交互，暗示存在后端支持
- 替换 canonical URL 或 schema 数据契约
- 将文章内容渲染从 markdown 渲染的文章正文改为其他方式，除非系统被显式更新
- 将预览会话备份存储在 `resources/views/theme` 或 `/themes` 下，并使其在没有清晰预览标记的情况下看起来像线上模板
- 声称 Laravel GEOFlow 中存在隔离预览 URL，除非 `routes/web.php` 提供它们

## 首页增强边界

无需后端更改即可允许：

- 当当前工作区暴露 `homepage_modules`、`homepage_style`、`homepage-modules/import` 和 `resources/views/site/partials/homepage-modules.blade.php` 时，使用 `HomepageModuleBuilder` 和管理后台导入 JSON
- 使用 `homepageCarouselSlides` 处理首屏主图、图片横幅和轮播驱动的 CTA
- 使用 `hotArticles` 展示热门、优先、公告或势头模块
- 将 `featuredArticles` 用作案例研究、精选资源或解决方案卡片
- 使用 `articles` 和 `cardSummaries` 展示最新资源、阅读路径和紧凑网格
- 在适合轻量图表可视化时，使用集合计数、已加载文章上的分类名称、文章日期以及存在时的浏览量
- 将站点标题/副标题/描述用于品牌声明、服务定位和文本模块
- 使用构建器模块类型，例如 `hero`、`rich_text`、`image_band`、`metric_band`、`chart_band`、`feature_grid`、`article_collection`、`cta_band`、`lead_form`，并搭配现有的活跃 `lead_form_slug` 和经过清理的 `custom_html`

需要显式的系统变更作用域：

- 当前 GEOFlow 工作区尚未暴露 `HomepageModuleBuilder` 和首页模块路由时，管理后台中的可编辑首页模块
- 新的数据库支持的内容区块
- 客户徽标库、推荐语记录、产品目录、新的潜在客户表单记录或激活、定价表，或真实的分析小组件
- 新路由、控制器、迁移或后台作业

## 首页构建器导入边界

审核后允许：

- 准备带有规范字段和路由安全链接的 `homepage-design.json`
- 推荐 `replace` 或 `append` 模式
- 请操作员通过管理首页模块 UI 导入
- 仅当用户明确批准确切的 JSON 负载和导入模式时，才提交导入路由

在仅设计阶段不允许：

- 静默地将预设或导入负载应用到线上站点
- 将 `custom_html` 用于脚本、表单、iframe、事件处理器或未提供的声明
- 当当前应用实时渲染构建器 JSON 时，将其导入视为隔离预览
