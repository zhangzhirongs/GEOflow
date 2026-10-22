# 管理端分析模块实施计划

> **面向智能体工作者：** 必需子技能：使用 superpowers:subagent-driven-development（推荐）或 superpowers:executing-plans 逐项实现此计划任务。步骤使用复选框（`- [ ]`）语法进行跟踪。

**目标：** 在“首页”之后新增管理后台“数据分析”部分，集中内容运营分析，并为网站、渠道站点和 AI 爬虫流量准备结构化日志分析流水线。

**架构：** 保留现有仪表盘作为运营入口页面，并将更深入的分析视图移入新的 `Admin\AnalyticsController`。通过聚焦的服务复用现有仪表盘查询逻辑，然后新增独立的分析日志摄取层，包含规范化事件和每日聚合。第一个可运行切片应立即展示由数据库支持的内容分析，而日志解析则在清晰的空状态和导入任务之后落地。

**技术栈：** Laravel 12、Blade、管理后台已使用的 Tailwind 工具类、PostgreSQL、Redis 队列/调度器、PHPUnit 功能/单元测试、Laravel 迁移和 Eloquent 模型。

---

## 范围

### 已包含

- 在 `首页` 之后立即添加顶部导航项 `数据分析`。
- 添加 `/admin/analytics` 页面，包含日期、站点/渠道、内容、流量类型和日志来源筛选器。
- 复用当前仪表板指标用于内容、任务、素材、AI、URL 导入、分发和热门文章分析。
- 添加内容运营图表：发布趋势、任务状态趋势、内容漏斗、AI/API 使用摘要、分发状态、热门内容。
- 添加日志分析设计与首个实现外壳：日志来源注册表、导入记录、爬虫分类器、解析事件存储、聚合表和空状态。
- 支持未来解析 Nginx/Apache 每日访问日志，并同步目标渠道站点日志。

### 推迟到首个可运行切片之后

- 在本地日志导入路径稳定后，可以实现从生成的目标站点包进行完整的远程渠道日志上传。
- 在显式配置 IP 地理位置数据库或提供商之前，不应包含地理分析。
- 实时流式图表不属于第一版；按日和按小时聚合已足够。

---

## 文件映射

### 路由和导航

- 修改 `routes/web.php`
  - 添加 `use App\Http\Controllers\Admin\AnalyticsController;`
  - 添加 `Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics');`
- 修改 `resources/views/admin/partials/header.blade.php`
  - 在 `dashboard` 之后插入 `analytics` 菜单。
  - 将分析路由名称添加到 `$subMap`。
- 修改 `lang/zh_CN/admin.php`
  - 添加 `admin.nav.analytics`。
  - 添加 `admin.analytics.*` 文案。
- 修改 `lang/en/admin.php`
  - 为测试和语言切换添加英语回退文案。

### 控制器与服务

- 创建 `app/Http/Controllers/Admin/AnalyticsController.php`
  - 负责请求过滤器和页面渲染。
  - 不包含原始查询逻辑。
- 创建 `app/Services/Admin/AdminDashboardMetricsService.php`
  - 提取当前在 `DashboardController` 中私有的可复用逻辑。
  - 提供现有的仪表盘统计信息和图表系列。
- 修改 `app/Http/Controllers/Admin/DashboardController.php`
  - 将指标委托给 `AdminDashboardMetricsService`。
  - 保持仪表盘行为不变。
- 创建 `app/Services/Admin/Analytics/AnalyticsFilter.php`
  - 规范化 `date_from`、`date_to`、`channel_id`、`task_id`、`category_id`、`article_id`、`traffic_type` 和 `log_source`。
- 创建 `app/Services/Admin/Analytics/AnalyticsOverviewService.php`
  - 构建内容操作摘要和图表数据集。
- 创建 `app/Services/Admin/Analytics/AnalyticsLogQueryService.php`
  - 根据解析后的日志聚合构建流量摘要。

### 日志分析数据层

- 创建迁移 `database/migrations/2026_05_21_000000_create_analytics_tables.php`
  - `analytics_log_sources`
  - `analytics_log_imports`
  - `analytics_log_events`
  - `analytics_daily_metrics`
  - `analytics_path_daily_metrics`
  - `analytics_article_daily_metrics`
  - `analytics_bot_daily_metrics`
- 创建模型：
  - `app/Models/AnalyticsLogSource.php`
  - `app/Models/AnalyticsLogImport.php`
  - `app/Models/AnalyticsLogEvent.php`
  - `app/Models/AnalyticsDailyMetric.php`
  - `app/Models/AnalyticsPathDailyMetric.php`
  - `app/Models/AnalyticsArticleDailyMetric.php`
  - `app/Models/AnalyticsBotDailyMetric.php`

### 日志解析与导入

- 创建 `app/Services/Admin/Analytics/AccessLogParser.php`
  - 解析 Nginx/Apache 的 common 和 combined 日志行。
- 创建 `app/Services/Admin/Analytics/CrawlerClassifier.php`
  - 将用户代理分类为 `human`、`search_bot`、`ai_bot`、`other_bot` 和 `unknown`。
- 创建 `app/Services/Admin/Analytics/AnalyticsLogImporter.php`
  - 导入行数据，存储规范化事件，并更新聚合表。
- 创建 `app/Console/Commands/GeoFlowImportAnalyticsLogsCommand.php`
  - 命令：`php artisan geoflow:analytics-import-logs --source=local --date=2026-05-21`
- 修改 `routes/console.php`，或修改调度器注册（如果项目在那里使用定时命令）。

### 视图

- 创建 `resources/views/admin/analytics/index.blade.php`
  - 使用现有的管理后台页面外壳和卡片样式。
  - 包含筛选表单、KPI 卡片、内容分析部分、日志分析部分和空状态。
- 创建局部模板：
  - `resources/views/admin/analytics/_filters.blade.php`
  - `resources/views/admin/analytics/_kpis.blade.php`
  - `resources/views/admin/analytics/_content-section.blade.php`
  - `resources/views/admin/analytics/_log-section.blade.php`
  - `resources/views/admin/analytics/_line-chart.blade.php`
  - `resources/views/admin/analytics/_bar-chart.blade.php`
  - `resources/views/admin/analytics/_funnel.blade.php`

### 测试

- 创建 `tests/Feature/AdminAnalyticsPageTest.php`
- 创建 `tests/Unit/AnalyticsFilterTest.php`
- 创建 `tests/Unit/AccessLogParserTest.php`
- 创建 `tests/Unit/CrawlerClassifierTest.php`
- 创建 `tests/Unit/AnalyticsLogImporterTest.php`
- 如果仪表盘指标提取会改变渲染字符串，请更新 `tests/Feature/AdminDashboardQuickStartTest.php`。

---

## UI 设计

### 页面布局

1. 表头行
   - 标题：`数据分析`
   - 副标题：`按日期、站点、内容和日志来源查看内容生产与访问趋势`
   - 右侧：最后更新时间和刷新按钮

2. 筛选栏
   - 日期预设按钮：今天、昨天、近 7 天、近 30 天、近 90 天
   - 自定义日期范围输入
   - Channel 选择器
   - 任务/分类/文章选择器
   - 流量类型选择器
   - 日志来源选择器

3. KPI 网格
   - 8 张卡片，响应式：桌面 4 列、平板 2 列、移动端 1 列
   - 短标签从不换行；描述可以换行

4. 内容分析部分
   - 桌面端双列图表网格
   - 热门内容和分发状态图表下方的表格

5. 日志分析部分
   - 导入日志前的空状态
   - 导入后：流量趋势、AI 爬虫细分、热门页面、状态码分布、热门来源

### 视觉约束

- 遵循当前管理后台 UI：白色卡片、浅灰色背景、蓝色主操作、克制的边框。
- 使用管理后台已加载的 Lucide 图标。
- 不要在第一个切片中引入新的 JS 图表依赖项。使用 Blade 生成的 SVG 图表和表格来匹配当前仪表盘。
- 保持运营数据密集展示。避免营销风格的主视觉面板。
- 长标题和 URL 可以换行；短数字/状态字段应使用 `whitespace-nowrap`。

---

## 数据模型

### `analytics_log_sources`

用途：定义日志来源。

列：

- `id`
- `name`
- `source_type`: `local_file`, `uploaded_file`, `channel_agent`
- `channel_id`：指向 `distribution_channels` 的可空外键
- `path_pattern`：可为空的字符串，例如 `/www/wwwlogs/example.com.log`
- `status`: `active`, `paused`
- `last_imported_at`
- `created_at`, `updated_at`

### `analytics_log_imports`

用途：记录每次导入运行。

列：

- `id`
- `source_id`
- `log_date`
- `file_path`
- `file_hash`
- `line_count`
- `parsed_count`
- `skipped_count`
- `status`: `pending`, `running`, `completed`, `failed`
- `error_message`
- `started_at`, `finished_at`
- `created_at`, `updated_at`

### `analytics_log_events`

用途：存储规范化后的解析请求，用于下钻和重新聚合。

列：

- `id`
- `source_id`
- `import_id`
- `channel_id`
- `article_id`
- `occurred_at`
- `host`
- `method`
- `path`
- `query_hash`
- `status_code`
- `bytes_sent`
- `request_time_ms`
- `ip_hash`
- `user_agent`
- `crawler_family`
- `traffic_type`
- `referer_host`
- `created_at`

索引：

- `(occurred_at)`
- `(channel_id, occurred_at)`
- `(article_id, occurred_at)`
- `(traffic_type, occurred_at)`
- `(crawler_family, occurred_at)`
- `(path, occurred_at)`

### 聚合表

- `analytics_daily_metrics`：每个日期/渠道/来源一行。
- `analytics_path_daily_metrics`：每个日期/路径/渠道/来源一行。
- `analytics_article_daily_metrics`：每个日期/文章/渠道/来源一行。
- `analytics_bot_daily_metrics`：每个日期/crawler_family/渠道/来源一行。

聚合应存储：

- `pv`
- `unique_ip_count`
- `human_pv`
- `search_bot_pv`
- `ai_bot_pv`
- `other_bot_pv`
- `error_4xx`
- `error_5xx`

---

## 任务计划

### 任务 1：添加分析导航和空页面

**文件：**

- 创建：`app/Http/Controllers/Admin/AnalyticsController.php`
- 创建：`resources/views/admin/analytics/index.blade.php`
- 修改：`routes/web.php`
- 修改：`resources/views/admin/partials/header.blade.php`
- 修改：`lang/zh_CN/admin.php`
- 修改：`lang/en/admin.php`
- 测试: `tests/Feature/AdminAnalyticsPageTest.php`

- [ ] 第 1 步：为路由和导航编写功能测试。

测试预期：

- `route('admin.analytics')` 对已认证管理员返回 200。
- 页面包含 `数据分析`。
- 顶部菜单中，首页位于数据分析之前。
- 数据分析菜单在 `/admin/analytics` 上处于活动状态。

运行：

```bash
docker exec geoflow-app php artisan test tests/Feature/AdminAnalyticsPageTest.php --filter=analytics_page
```

实现前的预期：路由未定义或页面未找到。

- [ ] 第 2 步：添加路由和控制器骨架。

控制器返回：

- `pageTitle`: `__('admin.analytics.page_title')`
- `activeMenu`: `analytics`
- `filters`：默认规范化值
- `kpis`、`content` 和 `logs` 的空数组

- [ ] 第 3 步：添加 Blade 页面骨架。

使用现有管理后台布局：

- `@extends('admin.layouts.app')`
- 页眉行
- 带有默认日期和来源控件的筛选区域
- KPI 空白卡片
- 内容分析空状态
- 日志分析空状态

- [ ] 第 4 步：添加菜单项。

在 `$menu` 中，插入：

```php
'analytics' => ['route' => 'admin.analytics', 'name' => __('admin.nav.analytics')],
```

紧接在 `dashboard` 之后。

- [ ] 第 5 步：运行测试和针对性标头测试。

```bash
docker exec geoflow-app php artisan test tests/Feature/AdminAnalyticsPageTest.php tests/Feature/AdminHeaderNotificationTest.php --compact
```

预期：pass。

---

### 任务 2：提取可复用的仪表盘指标

**文件：**

- 创建：`app/Services/Admin/AdminDashboardMetricsService.php`
- 修改：`app/Http/Controllers/Admin/DashboardController.php`
- 测试: `tests/Feature/AdminDashboardQuickStartTest.php`

- [ ] 第 1 步：为仪表盘渲染编写回归测试。

使用现有的 `AdminDashboardQuickStartTest`，并为以下内容添加断言：

- 文章总数卡片
- 活动任务卡片
- 热门文章区块
- 内容漏斗区块

- [ ] 第 2 步：将私有查询方法从 `DashboardController` 移到 `AdminDashboardMetricsService`。

服务公共方法：

- `stats(): array`
- `todayStats(): array`
- `weekStats(): array`
- `categoryDistribution(): array`
- `latestArticles(): array`
- `articleTrendSeries(int $days = 7): array`
- `articleTrendChartPaths(array $articleTrend): array`
- `performanceStats(int $completedTasks, int $failedJobs): array`
- `contentFunnel(array $stats): array`
- `taskHealth(): array`
- `materialHealth(): array`
- `aiHealth(): array`
- `urlImportHealth(): array`
- `popularArticles(int $limit = 5): array`
- `todoItems(array $stats, array $materialHealth, array $aiHealth, array $urlImportHealth): array`

- [ ] 第 3 步：更新 `DashboardController` 以调用该服务。

视图负载必须保持不变，以便仪表板 UI 不发生改变。

- [ ] 第 4 步：运行仪表板测试。

```bash
docker exec geoflow-app php artisan test tests/Feature/AdminDashboardQuickStartTest.php --compact
```

预期：pass。

---

### 任务 3：添加分析过滤器

**文件：**

- 创建：`app/Services/Admin/Analytics/AnalyticsFilter.php`
- 修改：`app/Http/Controllers/Admin/AnalyticsController.php`
- 修改：`resources/views/admin/analytics/_filters.blade.php`
- 修改：`resources/views/admin/analytics/index.blade.php`
- 测试: `tests/Unit/AnalyticsFilterTest.php`

- [ ] 第 1 步：编写筛选条件归一化单元测试。

案例：

- 无输入时默认最近 7 天
- `preset=today` 将两个日期都设置为当前日期
- `preset=30d` 将 `date_from` 设置为今天往前 29 天，并将 `date_to` 设置为今天
- 无效日期回退到默认范围
- `date_from` 晚于 `date_to` 时会交换或钳制到有效范围
- channel/task/category/article ID 为整数或 null

- [ ] 第 2 步：实现不可变的筛选值对象。

属性：

- `dateFrom`
- `dateTo`
- `channelId`
- `taskId`
- `categoryId`
- `articleId`
- `trafficType`
- `logSource`
- `preset`

- [ ] 第 3 步：渲染筛选表单。

使用 `GET` 表单，以便筛选后的 URL 可共享。

- [ ] 第 4 步：运行筛选器测试。

```bash
docker exec geoflow-app php artisan test tests/Unit/AnalyticsFilterTest.php tests/Feature/AdminAnalyticsPageTest.php --compact
```

预期：pass。

---

### 任务 4：内容分析查询服务

**文件：**

- 创建：`app/Services/Admin/Analytics/AnalyticsOverviewService.php`
- 修改：`app/Http/Controllers/Admin/AnalyticsController.php`
- 修改：`resources/views/admin/analytics/_kpis.blade.php`
- 修改：`resources/views/admin/analytics/_content-section.blade.php`
- 测试: `tests/Feature/AdminAnalyticsPageTest.php`

- [ ] 第 1 步：使用已填充的文章、任务、任务运行、AI 模型和分发数据编写功能测试。

断言：

- KPI 卡片显示文章总数、已发布文章数、运行中任务数、失败作业数、AI 调用次数、分发失败次数。
- 发布趋势包含预置日期。
- 热门内容表包含种子数据中的文章标题。
- 分发状态包括已同步和失败计数。

- [ ] 第2步：实现 `AnalyticsOverviewService`。

方法：

- `kpis(AnalyticsFilter $filter): array`
- `publicationTrend(AnalyticsFilter $filter): array`
- `taskTrend(AnalyticsFilter $filter): array`
- `contentFunnel(AnalyticsFilter $filter): array`
- `distributionSummary(AnalyticsFilter $filter): array`
- `topContent(AnalyticsFilter $filter, int $limit = 10): array`
- `aiUsageSummary(AnalyticsFilter $filter): array`

- [ ] 第 3 步：将服务接入控制器。

控制器传递：

- `kpis`
- `publicationTrend`
- `taskTrend`
- `contentFunnel`
- `distributionSummary`
- `topContent`
- `aiUsageSummary`

- [ ] 第 4 步：渲染内容分析部分。

图表：

- 发布趋势折线图
- 任务状态堆叠条形图
- 内容漏斗横向条形图
- 分发状态卡片
- 热门内容表格

- [ ] 第 5 步：运行功能测试。

```bash
docker exec geoflow-app php artisan test tests/Feature/AdminAnalyticsPageTest.php --compact
```

预期：pass。

---

### 任务 5：分析日志 Schema 与模型

**文件：**

- 创建：`database/migrations/2026_05_21_000000_create_analytics_tables.php`
- 创建：`app/Models/AnalyticsLogSource.php`
- 创建：`app/Models/AnalyticsLogImport.php`
- 创建：`app/Models/AnalyticsLogEvent.php`
- 创建：`app/Models/AnalyticsDailyMetric.php`
- 创建：`app/Models/AnalyticsPathDailyMetric.php`
- 创建：`app/Models/AnalyticsArticleDailyMetric.php`
- 创建：`app/Models/AnalyticsBotDailyMetric.php`
- 测试: `tests/Unit/AnalyticsSchemaMigrationTest.php`

- [ ] 第 1 步：编写模式（schema）迁移测试。

断言：

- 所有分析表均存在
- 必需列存在
- 存在针对日期、渠道、文章、流量类型、爬虫系列的索引

- [ ] 第 2 步：创建迁移。

使用带保护的 `Schema::hasTable` 检查，确保迁移在本地迭代中保持安全。

- [ ] 第 3 步：创建 Eloquent 模型。

每个模型都应定义：

- `$fillable`
- `$casts`
- 适用情况下的关联关系

- [ ] 第 4 步：运行架构测试。

```bash
docker exec geoflow-app php artisan test tests/Unit/AnalyticsSchemaMigrationTest.php --compact
```

预期：pass。

---

### 任务 6：访问日志解析器和爬虫分类器

**文件：**

- 创建：`app/Services/Admin/Analytics/AccessLogParser.php`
- 创建：`app/Services/Admin/Analytics/CrawlerClassifier.php`
- 测试: `tests/Unit/AccessLogParserTest.php`
- 测试: `tests/Unit/CrawlerClassifierTest.php`

- [ ] 第 1 步：编写解析器测试。

支持的示例：

```text
127.0.0.1 - - [21/May/2026:10:15:30 +0800] "GET /article/demo HTTP/1.1" 200 1234 "-" "Mozilla/5.0"
127.0.0.1 - - [21/May/2026:10:15:31 +0800] "GET /geoflow/article/demo HTTP/1.1" 404 512 "https://example.com" "Googlebot/2.1"
```

预期的解析字段：

- IP
- occurred_at
- 方法
- 路径
- 协议
- status_code
- bytes_sent
- referer
- user_agent

- [ ] 第 2 步：编写分类器测试。

必需的分类：

- `Googlebot` => `search_bot`
- `Baiduspider` => `search_bot`
- `bingbot` => `search_bot`
- `GPTBot` => `ai_bot`
- `ChatGPT-User` => `ai_bot`
- `ClaudeBot` => `ai_bot`
- `PerplexityBot` => `ai_bot`
- `Bytespider` => `ai_bot`
- 普通浏览器 UA => `human`
- 空 UA => `unknown`

- [ ] 第 3 步：实现解析器和分类器。

分类器应返回：

- `traffic_type`
- `crawler_family`
- `is_bot`

- [ ] 第 4 步：运行单元测试。

```bash
docker exec geoflow-app php artisan test tests/Unit/AccessLogParserTest.php tests/Unit/CrawlerClassifierTest.php --compact
```

预期：pass。

---

### 任务 7：日志导入器与聚合

**文件：**

- 创建：`app/Services/Admin/Analytics/AnalyticsLogImporter.php`
- 创建：`app/Console/Commands/GeoFlowImportAnalyticsLogsCommand.php`
- 修改：`bootstrap/app.php`，或此 Laravel 12 应用使用的命令注册路径
- 测试: `tests/Unit/AnalyticsLogImporterTest.php`
- 测试: `tests/Feature/AnalyticsImportCommandTest.php`

- [ ] 第 1 步：编写导入器测试。

种子：

- 一个日志源
- 一篇 slug 为 `demo-article` 的文章
- `/article/demo-article`、`/missing` 和 `/` 的日志行

断言：

- 导入行已完成
- 事件已创建
- article_id 映射到 `/article/demo-article`
- 每日聚合增量 PV
- 路径聚合记录 `/missing` 404
- 机器人聚合记录 AI 爬虫计数

- [ ] 第 2 步：实现导入器。

导入器职责：

- 逐行读取文件
- 解析每一行
- 对用户代理进行分类
- 使用应用密钥通过 HMAC 对 IP 进行哈希
- 尽可能将路径映射到文章
- 分块创建事件
- 更新聚合表
- 记录跳过的行

- [ ] 第 3 步：实现 artisan 命令。

命令：

```bash
php artisan geoflow:analytics-import-logs --source=local --date=2026-05-21
```

行为：

- 按名称或 ID 查找活动源
- 根据源模式和日期构建文件路径
- 除非传入 `--force`，否则每个哈希只导入一次
- 打印已解析、已跳过和状态摘要

- [ ] 第 4 步：运行导入器测试。

```bash
docker exec geoflow-app php artisan test tests/Unit/AnalyticsLogImporterTest.php tests/Feature/AnalyticsImportCommandTest.php --compact
```

预期：pass。

---

### 任务 8：日志分析查询与 UI

**文件：**

- 修改：`app/Services/Admin/Analytics/AnalyticsLogQueryService.php`
- 修改：`app/Http/Controllers/Admin/AnalyticsController.php`
- 修改：`resources/views/admin/analytics/_log-section.blade.php`
- 测试: `tests/Feature/AdminAnalyticsPageTest.php`

- [ ] 第 1 步：使用聚合行编写功能测试。

断言：

- 流量趋势显示 PV 和 AI 爬虫数量
- 机器人细分显示 GPTBot 或 ChatGPT 系列
- 热门页面表格显示 URL 和状态码计数
- 当日志数据存在时，空状态会被隐藏

- [ ] 第 2 步：实现查询服务。

方法：

- `trafficKpis(AnalyticsFilter $filter): array`
- `trafficTrend(AnalyticsFilter $filter): array`
- `botBreakdown(AnalyticsFilter $filter): array`
- `topPaths(AnalyticsFilter $filter, int $limit = 10): array`
- `topArticles(AnalyticsFilter $filter, int $limit = 10): array`
- `statusCodeSummary(AnalyticsFilter $filter): array`
- `topReferrers(AnalyticsFilter $filter, int $limit = 10): array`

- [ ] 第 3 步：渲染日志分析区块。

卡片：

- PV
- 唯一 IP
- AI 爬虫 PV
- 4xx/5xx 错误计数

图表/表格：

- 流量趋势折线图
- 爬虫细分柱状图
- 热门页面表格
- 热门文章表格
- 状态码分布

- [ ] 第 4 步：运行分析页面测试。

```bash
docker exec geoflow-app php artisan test tests/Feature/AdminAnalyticsPageTest.php --compact
```

预期：pass。

---

### 任务 9：润色、缓存与完整验证

**文件：**

- 仅修改之前任务涉及的文件。

- [ ] 第 1 步：运行格式化工具。

```bash
docker exec geoflow-app ./vendor/bin/pint app/Http/Controllers/Admin/AnalyticsController.php app/Services/Admin/AdminDashboardMetricsService.php app/Services/Admin/Analytics tests/Feature/AdminAnalyticsPageTest.php tests/Unit/AnalyticsFilterTest.php tests/Unit/AccessLogParserTest.php tests/Unit/CrawlerClassifierTest.php tests/Unit/AnalyticsLogImporterTest.php
```

预期：PASS。

- [ ] 第 2 步：运行针对性的分析测试。

```bash
docker exec geoflow-app php artisan test tests/Feature/AdminAnalyticsPageTest.php tests/Unit/AnalyticsFilterTest.php tests/Unit/AccessLogParserTest.php tests/Unit/CrawlerClassifierTest.php tests/Unit/AnalyticsLogImporterTest.php --compact
```

预期：全部通过。

- [ ] 第 3 步：运行完整测试套件。

```bash
docker exec geoflow-app php artisan test --compact
```

预期：全部通过。

- [ ] 第 4 步：清除缓存。

```bash
docker exec geoflow-app php artisan optimize:clear
```

预期：配置、缓存、编译文件、事件、路由和视图均已清除。

- [ ] 第 5 步：浏览器冒烟测试。

打开：

- `http://localhost:18080/admin/analytics`
- `http://localhost:18080/admin/dashboard`

验证：

- 菜单顺序正确
- 分析菜单的激活状态正常工作
- 日期筛选器提交时不会破坏布局
- 内容卡片渲染
- 当不存在导入时，日志部分显示空状态
- 仪表盘仍渲染现有的快速开始和健康模块

---

## 推荐的实现拆分

### 首批开发

实现任务 1 至 4。

结果：

- 存在新的“数据分析”菜单和页面。
- 当前仪表盘数据可在分析表单中获取。
- 内容运营分析无需等待日志解析即可使用。

### 第二批开发

实现任务 5 至 8。

结果：

- 日志表、解析器、爬虫分类器、导入器命令和日志分析 UI 均已存在。
- 本地 Nginx/Apache 日志可以手动导入并分析。

### 第三开发批次

添加目标渠道站点日志同步。

结果：

- 目标站点包可以暴露或推送每日访问日志。
- 主管理员可以按渠道分析群站/渠道流量和 AI 爬虫访问。

---

## 验收标准

- 管理菜单显示 `首页`，随后显示 `数据分析`。
- `/admin/analytics` 受管理员认证保护，并遵循现有后端 UI 风格。
- 日期筛选器会影响内容运营指标。
- 内容分析展示发布趋势、任务趋势、漏斗、AI/API 摘要、分发状态和热门内容。
- 日志分析在导入前显示有用的空状态。
- 导入受支持的访问日志后，分析会显示 PV、独立 IP 数、AI 爬虫 PV、热门路径、热门文章、状态码和爬虫细分。
- 测试覆盖路由访问、过滤器归一化、仪表盘回归、解析器行为、爬虫分类、导入聚合和分析渲染。
- 完整测试套件通过后，实现才被视为完成。

---

## 开发前待定决策

推荐的首次构建范围：**仅任务 1 到 4**，然后在浏览器中审查页面。这样能快速创建有用的分析页面，并在添加日志摄取表和解析器行为之前降低风险。

如果第一批开发已获批准，实现应从任务 1 开始，并在任务 4 之后停止，以进行视觉和功能审查。
