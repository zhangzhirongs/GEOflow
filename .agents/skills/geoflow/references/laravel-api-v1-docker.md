<!--
版权所有 © 2026 姚金刚。保留所有权利。
项目：geoflow
创建者：姚金刚
日期：2026-05-16
X: https://x.com/yaojingang
-->

# Laravel API v1、Docker 与管理后台回退

当目标 GEOFlow 工作区是 Laravel 应用且没有可用的 `bin/geoflow` 包装器时，使用此参考。`/api/v1` 是常规的可脚本化内容操作路径。当前 GEOFlow 在可配置的管理后台前缀下还包含许多仅限管理后台的工作流；对于这些工作流，请使用已认证的管理后台 Web。

## 检测

一个 Laravel GEOFlow 工作区应包含：

- `artisan`
- `routes/api.php`
- `app/Http/Controllers/Api/V1`
- `docker-compose.yml` 或 `compose.yml`，当通过 Docker 部署时

不要假设旧的根级 PHP 入口点存在。

## API 基础 URL

`GEOFLOW_BASE_URL` 必须指向公共 Web 根目录：

```bash
export GEOFLOW_BASE_URL="http://127.0.0.1:18080"
```

不要将其设置为：

- `http://127.0.0.1:18080/api/v1`
- `http://127.0.0.1:18080/geo_admin`
- 内部 Docker 服务名，除非代理运行在同一网络内
- 返回 HTML 错误页面的代理端点

技能本身会追加 `/api/v1/...`。

## 必需的令牌

API 回退需要：

从密钥管理器或隐藏的交互式提示中加载令牌。避免将该值放入 shell 历史记录：

```bash
read -rsp 'GEOFlow API token: ' GEOFLOW_API_TOKEN
printf '\n'
export GEOFLOW_API_TOKEN
```

令牌必须允许所请求操作所需的作用域：

- `catalog:read`
- `tasks:read`
- `tasks:write`
- `jobs:read`
- `articles:read`
- `articles:write`
- `articles:publish`
- `materials:read`
- `materials:write`

如果不存在令牌，优先使用交互式本地 CLI 登录。对于仅 API 的安装，请使用 [命令映射](command-map.md#首次登录) 中受保护的请求/响应文件流程；该流程可将密码和返回的令牌排除在进程参数和终端输出之外。

当前登录端点会为经过身份验证的管理员返回一个具有所有可用 API v1 scopes 的 token。不要在面向用户的输出中打印完整 token。

## Docker 检查

在 GEOFlow 工作区中：

```bash
docker compose ps
docker compose exec app php artisan route:list --path=api/v1
docker compose exec app php artisan about
```

如果 PHP 服务名称不是 `app`，请检查 `docker compose ps` 并使用实际的服务名称。

对于生产环境的 compose，应用可能运行在 Nginx `web` 服务和 PHP-FPM `app` 服务之后。浏览器/API 访问应使用对外暴露的 Web 端口，而不是内部 PHP-FPM 端口。

对于数据库主机问题：

- 在 Docker Compose 内，当服务名为 `postgres` 时，`DB_HOST=postgres` 通常是正确的
- 在 Docker Compose 之外，除非 DNS/hosts 提供解析，否则 `postgres` 将无法解析
- 浏览器访问应使用暴露的主机端口，例如 `127.0.0.1:18080`

对于队列行为：

- 生成任务使用 `geoflow` 队列
- 分发作业使用 `distribution` 队列
- 当前 API v1 不管理分发渠道、目标包、Analytics、URL Import、System Updates、Theme Replication、API Tokens、管理员用户、AI 配置、站点设置或安全设置

## 预检

在 GEOFlow 仓库检出目录中，运行项目本地的技能：

```bash
.agents/skills/geoflow/scripts/geoflow_preflight.sh "/path/to/GEOFlow"
```

对于实质性工作：

```bash
.agents/skills/geoflow/scripts/geoflow_preflight.sh "/path/to/GEOFlow" "" catalog,materials
```

对于 admin-web 工作：

```bash
.agents/skills/geoflow/scripts/geoflow_preflight.sh "/path/to/GEOFlow" "" admin
```

从全局 Codex 安装中运行时，请将脚本路径替换为 `~/.codex/skills/geoflow/scripts/geoflow_preflight.sh`。在技能目录内工作时，`scripts/geoflow_preflight.sh` 是相对于包根目录的形式。该辅助程序需要 Bash、Python 3.10+ 和 `curl`；不可执行的旧版 `bin/geoflow` 还需要 PHP CLI。

仅当经过身份验证的 API 读取返回 JSON 时，预检才算成功。仅检查公共主页是不够的。

## 非 JSON 响应诊断

如果响应正文以 HTML 开头，例如：

```text
<!doctype html>
```

不要将其诊断为一个 AI 响应格式错误。它通常意味着：

- `GEOFLOW_BASE_URL` 指向了错误的路径
- 应用位于代理错误页面之后
- 请求到达了 frontend/admin Web 路由，而不是 `/api/v1`
- Docker 服务未在预期端口上暴露 Laravel 应用
- 服务器返回了框架错误页面，而不是 JSON

在重试业务操作前，修正 URL、代理、容器或 Laravel 路由设置。

## 当前 API 接口范围

Laravel API v1 路径为：

- `POST /api/v1/auth/login`
- `GET /api/v1/catalog`
- `GET /api/v1/tasks`
- `POST /api/v1/tasks`
- `GET /api/v1/tasks/{id}`
- `PATCH /api/v1/tasks/{id}`
- `DELETE /api/v1/tasks/{id}`
- `POST /api/v1/tasks/{id}/start`
- `POST /api/v1/tasks/{id}/stop`
- `POST /api/v1/tasks/{id}/enqueue`
- `GET /api/v1/tasks/{id}/jobs`
- `GET /api/v1/jobs/{id}`
- `GET /api/v1/materials`
- `GET /api/v1/materials/{type}`
- `POST /api/v1/materials/{type}`
- `GET /api/v1/materials/{type}/{id}`
- `PATCH /api/v1/materials/{type}/{id}`
- `DELETE /api/v1/materials/{type}/{id}`
- `GET /api/v1/materials/{type}/{id}/items`
- `POST /api/v1/materials/{type}/{id}/items`
- `DELETE /api/v1/materials/{type}/{id}/items`
- `GET /api/v1/articles`
- `POST /api/v1/articles`
- `GET /api/v1/articles/{id}`
- `PATCH /api/v1/articles/{id}`
- `POST /api/v1/articles/{id}/review`
- `POST /api/v1/articles/{id}/publish`
- `POST /api/v1/articles/{id}/trash`

将 `X-Idempotency-Key` 用于实现 API 幂等性的 POST 和 PATCH 操作。当前针对任务、素材库和素材项的 DELETE 路由不使用幂等性标头。

## 素材类型与载荷说明

素材类型：

- `categories`：`name`，可选 `slug`，`description`，`sort_order`
- `authors`：`name`，可选 `email`，`bio`，`avatar`，`website`，`social_links`
- `keyword-libraries`：`name`，可选 `description`
- `title-libraries`：`name`，可选 `description`
- `image-libraries`：`name`，可选 `description`
- `knowledge-bases`：`name`，`content`，可选 `description`，`file_type`，`file_path`

可写条目端点：

- `keyword-libraries/{id}/items`：使用 `keyword` 创建
- `title-libraries/{id}/items`：使用 `title` 创建，可选 `keyword`
- `image-libraries/{id}/items`：使用 `file_path` 创建，可选文件元数据

知识库条目列表会暴露分块；写入分块需通过更新父级知识库内容来完成。

## 任务负载说明

创建时必填：

- `name`
- `title_library_id`
- `prompt_id`
- `ai_model_id`

常见可选字段：

- `author_id`
- `image_library_id`
- `image_count`
- `knowledge_base_id`
- `knowledge_base_ids`：最多五个 ID；优先于 `knowledge_base_id`
- `fixed_category_id`
- `category_mode`：`smart` 或 `fixed`
- `model_selection_mode`：`fixed` 或 `smart_failover`
- `status`：`active` 或 `paused`
- `publish_scope`：`local_and_distribution`、`distribution_only` 或 `local_only`
- `distribution_strategy`：`broadcast`、`round_robin` 或 `random_balanced`
- `draft_limit`, `article_limit`, `publish_interval`
- `need_review`, `is_loop`, `auto_keywords`, `auto_description`

可选的材料字段可以省略，创建时将被持久化为 `null`。

API v1 任务响应可能包含 `task_progress`、`queue_overview`、`schedule_enabled`、`knowledge_base_ids`、`knowledge_bases`、分发计数器和最新作业状态字段。写入后请重新读取 `GET /api/v1/tasks/{id}`。

## 管理后台回退边界

当前 GEOFlow 具有管理界面能力，包括分发管理、目标站点包、WordPress REST 渠道、通用 HTTP API 渠道、前端能力刷新、目标设置同步预览、日志、队列重试、远程文章管理、手动发布、企业知识起草/发布、线索表单/线索/导出、六个 Analytics 页面、AI 来源提供方、文章编辑器辅助/风险扫描、URL 导入、系统更新、主题复制、首页模块编辑、站点设置、AI 配置、API 令牌和管理员用户。除非路由检查另有证明，否则这些不属于当前 `/api/v1` 操作面。当前 `routes/web.php` 没有实时 Theme Editor 路由。

仅将 API v1 用于已暴露的任务/文章/素材操作。当某操作需要仅管理员可用的能力时，如果用户已提供或已拥有有效的管理员会话，则切换到已认证的管理后台 Web 路由。否则报告缺少管理员会话/前置条件。
