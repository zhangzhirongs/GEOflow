<!--
版权所有 © 2026 姚金刚。保留所有权利。
项目：geoflow
创建者：姚金刚
日期：2026-07-05
X: https://x.com/yaojingang
-->

# GEOFlow 当前能力地图

此参考将当前仓库映射到其支持的操作面。在执行变更前检查目标工作区：

```bash
bin/geoflow --version
bin/geoflow --help
php artisan route:list --path=api/v1
php artisan route:list --except-vendor
```

如果目标部署中不存在某个命令或路由，请报告该能力在该处不可用。

## 操作界面

- `CLI 0.2.0`：当前 API v1 的目录、任务、作业、素材和文章操作的首选客户端。
- `API v1`：当 CLI 不存在时的回退方案。使用 bearer 认证和 JSON。仅向支持它的操作添加 `X-Idempotency-Key`；当前的 DELETE 路由不使用它。
- `Admin web`：API v1 之外的功能。使用已认证的管理员会话、CSRF 令牌以及实际可配置的管理员前缀。
- `Super-admin web`：受保护的工作流，例如分发、手动发布设置、系统更新、主题复制、管理员用户和 API 令牌。
- `Local Artisan`：在已部署的 Laravel 应用内部运行的维护命令。

不要用直接 SQL 或虚构的 API 路径替代后台管理或 Artisan 能力。

## CLI 与 API v1 覆盖范围

CLI 0.2.0 和 API v1 覆盖相同的可脚本化内容范围：

- 身份验证：`POST /api/v1/auth/login`
- 目录：`GET /api/v1/catalog`
- 任务：列表、创建、查看、更新、删除、启动、停止、入队和任务作业
- 作业：显示单次任务运行
- 材料：摘要、类型化 CRUD、类型化条目列表/创建/删除，以及图片上传
- 文章：列表、创建、显示、更新、审核、发布和移入回收站

CLI 增加了本地 profile 处理、端点与凭据绑定、HTTPS 策略、密钥提示/stdin、JSON 输入、响应大小限制、脱敏以及删除确认。完整命令集请参阅 [命令映射](command-map.md)。

API v1 不暴露分发渠道管理、手动发布、企业知识、线索管理、Analytics、AI 来源提供方管理、文章编辑器辅助、URL Import、System Updates、Theme Replication、首页模块编辑、API 令牌管理、管理员用户管理、站点设置或安全设置。

### 任务契约

创建操作需要 `name`、`title_library_id`、`prompt_id` 和 `ai_model_id`。常见可选字段包括：

- `author_id`, `image_library_id`, `image_count`
- `knowledge_base_id`，或带有最多五个 ID 的 `knowledge_base_ids`
- `fixed_category_id`
- `status`：`active` 或 `paused`
- `category_mode`：`smart` 或 `fixed`
- `model_selection_mode`：`fixed` 或 `smart_failover`
- `publish_scope`：`local_and_distribution`、`distribution_only` 或 `local_only`
- `distribution_strategy`：`broadcast`、`round_robin` 或 `random_balanced`
- `draft_limit`, `article_limit`, `publish_interval`
- `need_review`, `is_loop`, `auto_keywords`, `auto_description`

`knowledge_base_ids` 优先于旧版 `knowledge_base_id`。具体的 `distribution_channel_ids[]` 绑定仍属于管理员任务表单流程。

## 管理后台前缀

路由前缀来自 `geoflow.admin_base_path` 和 `ADMIN_BASE_PATH`；仓库默认值为 `geo_admin`。下面的示例使用 `/{admin}` 作为占位符。发送请求前，请从配置或 `route:list` 解析真实路径。

Admin 写操作需要当前 cookies 和 CSRF。受 `admin.super` 保护的路由还要求超级管理员账户。某些敏感操作会增加速率限制、当前密码检查或影响确认。

## 分析页面

当前页面包括：

- 概览：`GET /{admin}/analytics`
- 内容：`GET /{admin}/analytics/content`
- 流量：`GET /{admin}/analytics/traffic`
- AI 可见性：`GET /{admin}/analytics/ai-visibility`
- 线索：`GET /{admin}/analytics/leads`
- 分发：`GET /{admin}/analytics/distribution`，受`admin.super`保护

概览可能会将旧版内容、流量或渠道查询参数重定向到匹配页面。请从所请求页面读取渲染后的筛选器、KPI 卡片、表格和图表数据。不要凭记忆重建指标。

## 手动发布工作台

`/{admin}/manual-publications` 下的路由提供：

- `GET /` 列表、筛选器、分配感知统计信息和状态视图
- `GET /create` 和 `POST /` 的创建
- `GET /export` CSV 导出
- `GET /{manualPublicationId}` 详情
- `GET /{manualPublicationId}/edit` 和 `PUT /{manualPublicationId}` 的更新
- `POST /{manualPublicationId}/transition` 工作流转换与完成回读
- `/settings` 下用于角色和平台账户引用的超级管理员设置

超级管理员创建并配置工作项。普通活动管理员根据 `ManualPublicationPolicy` 查看并转换分配给自己的工作项。从文章创建时，仅接受具有 `approved` 或 `auto_approved` 审核状态的文章。工作台存储文案、公共账号引用、负责人、日程、风险状态、重复警告、完成 URL 和结果备注。它不存储平台密码、cookie、访问令牌或 OAuth 凭据。

CSV 导出可能包含内容和外部账户引用。需要明确的导出请求，并保护生成的文件。

## 文章编辑器辅助与风险扫描

Admin 文章路由在 CLI/API v1 之外新增能力：

- `GET /{admin}/articles/editor/titles` 加载助手标题选项。
- `POST /{admin}/articles/editor/generate` 运行 AI 编辑器助手，并受 `throttle:10,1` 限制。
- `POST /{admin}/articles/{articleId}/risk-scan` 重新运行文章风险扫描。
- `POST /{admin}/articles/{articleId}/editor/images/upload` 上传编辑器图片。
- `POST /{admin}/articles/editor/wechat-html` 导出微信 HTML。
- 批量审核/状态/删除/恢复/强制删除、单个恢复/强制删除以及清空回收站仍为管理员流程。

在助手操作或风险操作之前读取文章编辑页面，保留其 CSRF/会话状态，并在之后读取生成的文章或重定向页面。风险覆盖在工作流要求时必须提供明确原因。

## AI 来源提供方

`/{admin}/ai-source-providers` 下的路由提供：

- `GET /` 提供方、绑定、配额和健康概览
- `POST /` 创建受支持的来源提供程序
- `PUT /{providerId}` 更新提供程序设置
- `POST /{providerId}/test` 测试提供程序，受 `admin-sensitive` 限制
- `POST /{providerId}/delete` 删除未使用的提供程序
- `POST /model-bindings` 更新 Ark 和 DeepSeek 模型绑定
- `POST /model-bindings/upsert-api` 创建或更新绑定的模型 API
- `POST /model-bindings/test` 测试结构化输出，受 `admin-sensitive` 限制

提供商端点符合当前端点策略。更改端点来源需要新的 API 密钥。存储的密钥已加密，失败的表单在重新填充输入时会排除 `api_key`。在报告中隐去提供商和模型密钥。

## 分发渠道与安全删除

分发路由受 `admin.super` 保护。它们涵盖渠道列表/创建/查看/编辑/更新、暂停/激活、健康检查、密钥轮换/查看、目标包下载、设置同步预览/应用、前端能力刷新，以及作业编辑/删除/重试。

渠道类型为 `geoflow_agent`、`wordpress_rest` 和 `generic_http_api`。WordPress Application Passwords 和通用 API 密钥在保存后仍保持机密。GEOFlow Agent 设置同步需要新的能力检查以及精确预览。

渠道删除是分阶段的 admin-web 工作流：

1. `GET /{admin}/distribution/{channelId}/delete` 渲染当前影响和指纹。
2. `POST /{admin}/distribution/{channelId}/delete/prepare`将渠道移动到`deleting`，并将已排队的分发标记为失败，以便工作进程无法启动新的发送。
3. 如果操作员停止，`POST /{admin}/distribution/{channelId}/delete/cancel` 会将准备好的渠道返回到 `paused`。
4. `DELETE /{admin}/distribution/{channelId}` 在 `admin-sensitive` 速率限制下执行最终删除。

最终请求需要准确的渠道名称、当前密码、当前影响指纹、历史确认，以及在存在相应影响时对远程内容、任务变更或凭据的确认。新的发送作业和新的渠道操作会阻止删除。过期的发送或操作状态需要其自身的强制确认。

最终删除会分离任务，将孤立的 `local_and_distribution` 任务切换为 `local_only`，暂停孤立的 `distribution_only` 任务，移除本地分发和机密，并写入一条经过脱敏的审计记录，附带远程清理清单。它并不证明远程内容已被删除。请将远程清理作为单独的后续事项报告。

## 站点设置、首页模块和主题复制

当前站点设置路由包括：

- `GET|POST /{admin}/site-settings`
- `POST /{admin}/site-settings/theme`
- `GET /{admin}/site-settings/homepage-modules`
- `POST /{admin}/site-settings/homepage-modules`
- `POST /{admin}/site-settings/homepage-modules/preset`
- `POST /{admin}/site-settings/homepage-modules/import`
- 文章图片/文字广告设置和敏感词管理

GET 主页路由是编辑器页面，并提供持久化的模块/样式、支持的模块/布局值、已启用的线索表单以及预设元数据。切换到 `public_frontend` 模式来设计或验证主页 payload，然后返回 `operations` 以进行已批准的 POST 和回读。

超级管理员主题复制（Theme Replication）路由涵盖创建、查看、状态、`home|category|article` 预览、重试、迭代、发布、复制、归档、删除草稿以及包下载。预览和包状态并不能证明已发布。

当前的 `routes/web.php` 不暴露实时 Theme Editor 编辑、预览、草稿、发布或丢弃路由。除非经检查的目标部署有明确路由，否则将该操作视为不可用。

## 企业知识、线索、URL 导入和系统更新

Enterprise Knowledge 路由涵盖列表/创建、工作区、状态、自动保存、验证、编辑器图片上传、修订还原、发布到知识库以及项目删除。发布后同时读取项目状态以及生成的知识库/分块状态。

线索管理涵盖线索表单的 CRUD/状态、线索列表/详情/更新以及 CSV 导出。公开捕获路由为 `GET /forms/{slug}`，并受 `POST /forms/{slug}/submissions` 限流。线索详情和导出包含个人数据。

URL 导入是一个超级管理员流程，涵盖创建、历史、显示、运行、状态和提交。在运行前验证分析模型，并在提交前等待终止状态。

系统更新涵盖检查、计划、手动命令确认、备份、应用、运行状态、重试、标记失败、完整回滚以及单文件回滚。应用、重试、标记失败和回滚需要精确的运行/备份目标、当前门控状态以及明确的用户批准。

## 登录限制与凭据吊销

管理后台 Web 登录和 API 登录都使用`admin-login`限流器，目前为每个 IP 每分钟 30 次尝试。Web 登录还会按规范化用户名加 IP 跟踪失败尝试。默认值为五次失败和 900 秒临时锁定，由`geoflow.max_login_attempts`和`geoflow.login_lockout_seconds`配置。此临时限流器不会改变`admins.status`。

带有 `status=locked` 的 Admin 会保持手动锁定，直到运行支持的解锁命令：

```bash
php artisan geoflow:admin-unlock USERNAME
```

超级管理员在 `/{admin}/api-tokens` 下管理 API 令牌。创建令牌时会显示一次明文。`POST /{admin}/api-tokens/{tokenId}/revoke` 会物理删除该 Sanctum 令牌，因此后续 API 调用会返回 `401`。

密码更改、管理员状态更改和管理员删除都会调用 `revokeAuthenticationCredentials()`。这会递增 `auth_version`、轮换 remember token，并删除受影响管理员的所有 API token。现有管理员会话在下次请求时将无法通过 `auth_version` 检查。

## 本地 Artisan 维护

这些命令没有 API v1 或 GEOFlow CLI 0.2.0 的等效项：

```bash
php artisan geoflow:recover-knowledge-syncs --stale=600 --limit=50
php artisan geoflow:prune-expired-cache --limit=5000
```

`geoflow:recover-knowledge-syncs` 会重新排队那些已停止推进的知识分块同步流水线。它会将 `--stale` 限制为至少 60 秒，并将 `--limit` 限制在 1 到 200 之间。调度器每五分钟运行一次，并启用重叠保护。

仅当配置的限流器缓存使用数据库驱动时，`geoflow:prune-expired-cache` 才会删除过期行。其他缓存驱动会返回空操作消息。它会将 `--limit` 限制在 1 到 20,000 之间，每小时运行一次，并使用重叠保护。

当部署已容器化时，在正确的应用容器中运行本地 Artisan 命令：

```bash
docker compose exec app php artisan geoflow:recover-knowledge-syncs --stale=600 --limit=50
```

## 报告标准

对于每个操作，报告接口面、确切路由或命令、资源 ID、验证回读、最终状态和脱敏机密处理。将失败分类为身份验证/会话、CSRF、权限、路由缺失、验证、业务数据、队列/worker、锁定资源、速率限制、远程目标、更新预检、前端能力不匹配或路由接口面不匹配。
