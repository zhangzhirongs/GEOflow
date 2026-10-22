# GEOFlow CLI 0.2.0

GEOFlow CLI 是仓库的 API v1 客户端，用于目录、任务、作业、素材和文章操作。它负责配置文件、登录、HTTPS 策略、密钥脱敏、JSON 校验、删除确认和 API 错误提示。

支持的平台是 macOS、Linux 和 WSL。原生 Windows 可以运行 PHP，但 CLI 无法验证 Windows ACL。如果必须从原生 Windows 保存令牌，请手动限制配置文件，或在 WSL 中运行 CLI。

## 安装与先决条件

CLI 随 GEOFlow 源码树一同提供。它需要：

- PHP 8.3 或更高版本。
- 使用 Composer 安装的项目依赖。
- 可网络访问已启用 `/api/v1` 的 GEOFlow 实例。
- 管理员登录或具有所需作用域的 API 令牌。

```bash
git clone https://github.com/zhangzhirongs/GEOflow.git
cd GEOFlow
composer install --no-interaction --prefer-dist

bin/geoflow --version
bin/geoflow --help
```

如果在复制仓库时丢失了可执行位，请恢复它：

```bash
chmod +x bin/geoflow
```

你也可以通过 PHP 调用该文件：

```bash
php bin/geoflow --version
```

`--version` 返回 JSON。当前版本为 `0.2.0`。请将 `--help` 视为命令名称和基本用法的权威来源。

## 配置档案与优先级

CLI 每次调用按以下顺序选择一个 profile：

1. `--config PATH`
2. 当前工作目录中已有的 `.geoflow.json`
3. `~/.config/geoflow/config.json`

选择配置文件后，每个值遵循以下优先级：

1. CLI 选项
2. 环境变量
3. 选定的配置文件
4. 内置默认

支持的环境变量：

| 设置 | 环境变量 |
|---|---|
| 基础 URL | `GEOFLOW_BASE_URL` |
| 令牌 | `GEOFLOW_TOKEN`, `GEOFLOW_API_TOKEN` |
| 超时 | `GEOFLOW_TIMEOUT` |
| 远程 HTTP 异常 | `GEOFLOW_ALLOW_INSECURE_HTTP` |

在不暴露令牌的情况下检查已解析的来源：

```bash
bin/geoflow --config /path/to/profile.json config show
```

JSON 输出会遮蔽 token，并包含端点来源、凭据来源和绑定状态。

### 端点与凭据绑定

CLI 会拒绝可能将令牌发送到错误主机的来源组合：

- 从当前目录的 `.geoflow.json` 隐式加载的端点必须使用同一文件中的令牌。
- `--base-url` 需要在同一次调用中提供凭据。优先使用 `--token-stdin`。
- `GEOFLOW_BASE_URL` 无法从配置档继承令牌。将其与 `GEOFLOW_TOKEN`、`GEOFLOW_API_TOKEN` 或 `--token-stdin` 搭配使用。
- 使用 `--config` 选择的配置文件和默认主页配置文件均为受信任配置文件。
- 保存在配置文件中的远程 HTTP 异常仅适用于该配置文件的端点。当 `--base-url` 或 `GEOFLOW_BASE_URL` 替换端点时，请传递 `--allow-insecure-http` 或在同一调用环境中设置 `GEOFLOW_ALLOW_INSECURE_HTTP`。

Git 会在仓库的每一层目录中忽略 `.geoflow.json`。请勿将真实 token 放入副本、上传内容、shell 日志和提交中。

## HTTPS 和 HTTP

当 URL 没有 scheme 时，CLI 会添加 `https://`。对于以下回环主机，它允许使用 HTTP：

- `localhost` 和 `*.localhost`
- `127.0.0.0/8`
- `::1`

其他 HTTP 目标需要 `--allow-insecure-http`。仅对已批准的测试主机使用此例外。HTTPS 证书验证保持启用，且 API 客户端不跟随重定向。为在传输过程中强制执行 5 MiB 响应限制，CLI 请求 `identity` 编码，并拒绝带有压缩 `Content-Encoding` 的 API 响应。

```bash
bin/geoflow --allow-insecure-http login \
  --base-url http://test-host.example \
  --username admin
```

生产环境使用 HTTPS。

## 初始化配置文件并登录

### 使用现有令牌初始化

交互式终端可以使用隐藏令牌提示并写入默认配置文件：

```bash
bin/geoflow config init --base-url https://geoflow.example.com
```

自动化可以通过受保护的 stdin 提供一行 token：

```bash
bin/geoflow --token-stdin config init \
  --base-url https://geoflow.example.com \
  --file /path/to/profile.json
```

将密钥管理器或隐藏输入连接到 stdin。不要将真实令牌粘贴到示例中。覆盖现有配置文件需要 `--force`。

在 macOS、Linux 和 WSL 上，包含令牌的配置文件以模式 `0600` 写入。默认配置文件目录限制为 `0700`。

### 使用管理员账户登录

交互式登录会隐藏密码：

```bash
bin/geoflow login \
  --base-url https://geoflow.example.com \
  --username admin
```

将登录结果写入所选配置文件：

```bash
bin/geoflow --config /path/to/profile.json login \
  --base-url https://geoflow.example.com \
  --username admin
```

在现有配置文件中刷新无效或过期的令牌：

```bash
bin/geoflow --config /path/to/profile.json login \
  --base-url https://geoflow.example.com \
  --username admin \
  --force
```

非交互式登录通过 `--password-stdin` 读取一行密码。旧版 `--token` 和 `--password` 选项为兼容性仍被接受，会发出弃用警告，并计划在下一个主要 CLI 版本中移除。新的自动化应使用隐藏提示、环境变量或 stdin。

## 全局选项

| 选项 | 含义 |
|---|---|
| `--config PATH` | 选择受信任的配置文件。 |
| `--base-url URL` | 为本次调用设置 GEOFlow Web 根目录。 |
| `--token-stdin` | 从 stdin 读取一行令牌。 |
| `--timeout SECONDS` | 设置正的请求超时时间。 |
| `--allow-insecure-http` | 允许为明确批准的测试主机使用远程 HTTP。 |
| `--no-interaction`, `-n` | 禁用提示。 |
| `--help`, `-h` | 显示帮助。 |
| `--version`, `-V` | 显示 JSON 版本数据。 |
| `--quiet`, `-q` | 抑制正常输出。 |
| `--verbose`, `-v` | 提高详细程度。可重复使用。 |

全局选项可以出现在子命令之前。下面的示例将 `--config` 放在最前面，以便于审计命令日志。

## 完整命令参考

### 配置、登录与目录

```text
geoflow config init --base-url URL [--token-stdin] [--file PATH] [--force]
geoflow config show [--config PATH]
geoflow login --base-url URL [--username USER] [--password-stdin] [--file PATH] [--force]
geoflow catalog
```

`catalog` 返回模型、提示词、关键词库、标题库、图片库、知识库、作者和分类。请在首次变更前运行它：

```bash
bin/geoflow --config /path/to/profile.json catalog
```

### 任务与作业

```text
geoflow task list [--page N] [--per-page N] [--status STATUS] [--search TEXT]
geoflow task create --json FILE [--idempotency-key KEY]
geoflow task get TASK_ID
geoflow task update TASK_ID --json FILE [--idempotency-key KEY]
geoflow task delete TASK_ID [--yes]
geoflow task start TASK_ID [--enqueue-now] [--idempotency-key KEY]
geoflow task stop TASK_ID [--idempotency-key KEY]
geoflow task enqueue TASK_ID [--job-type TYPE] [--payload-json FILE] [--idempotency-key KEY]
geoflow task jobs TASK_ID [--status STATUS] [--limit N]
geoflow job get JOB_ID
```

创建任务需要 `name`、`title_library_id`、`prompt_id` 和 `ai_model_id`：

```json
{
  "name": "CLI task",
  "title_library_id": 1,
  "prompt_id": 2,
  "ai_model_id": 3,
  "status": "paused",
  "publish_scope": "local_only",
  "knowledge_base_ids": [4, 5],
  "need_review": 1
}
```

```bash
bin/geoflow --config /path/to/profile.json task create \
  --json ./task.json \
  --idempotency-key task-create-001

bin/geoflow --config /path/to/profile.json task start 12 \
  --enqueue-now \
  --idempotency-key task-start-12

bin/geoflow --config /path/to/profile.json task jobs 12 --limit 20
bin/geoflow --config /path/to/profile.json job get 88
```

`knowledge_base_ids` 最多接受五个 ID，并优先于旧版 `knowledge_base_id`。API v1 和 CLI 不会绑定具体的 `distribution_channel_ids`；当任务必须面向选定的渠道时，请使用管理员任务表单。

### 材料

支持的类型：

- `categories`
- `authors`
- `keyword-libraries`，别名 `keywords`
- `title-libraries`，别名 `titles`
- `image-libraries`，别名 `images`
- `knowledge-bases`，别名 `knowledge`

```text
geoflow material summary
geoflow material list TYPE [--page N] [--per-page N] [--search TEXT]
geoflow material create TYPE --json FILE [--idempotency-key KEY]
geoflow material get TYPE ID
geoflow material update TYPE ID --json FILE [--idempotency-key KEY]
geoflow material delete TYPE ID [--yes]
geoflow material item-list TYPE ID [--page N] [--per-page N]
geoflow material item-create TYPE ID --json FILE [--idempotency-key KEY]
geoflow material item-upload TYPE ID --image FILE [--idempotency-key KEY]
geoflow material item-delete TYPE ID (--ids 1,2 | --json FILE) [--yes]
```

`item-upload` 支持 `image-libraries` 或 `images`，并使用 `--image`：

```bash
bin/geoflow --config /path/to/profile.json material item-upload images 9 \
  --image ./cover.png \
  --idempotency-key image-upload-001
```

请选择恰好一个条目删除输入：

```bash
bin/geoflow --config /path/to/profile.json material item-delete titles 34 --ids 101,102
bin/geoflow --config /path/to/profile.json material item-delete titles 34 --json ./delete-items.json
```

知识库条目是生成的分块，且为只读。请更新父级知识库内容以重建它们。

### 文章

```text
geoflow article list [--page N] [--per-page N] [--task-id ID] [--status STATUS]
  [--review-status STATUS] [--author-id ID] [--search TEXT]
geoflow article create (--json FILE | direct fields) [--idempotency-key KEY]
geoflow article get ARTICLE_ID
geoflow article update ARTICLE_ID (--json FILE | direct fields) [--idempotency-key KEY]
geoflow article review ARTICLE_ID --status STATUS [--note TEXT]
  [--risk-override-reason TEXT] [--idempotency-key KEY]
geoflow article publish ARTICLE_ID [--idempotency-key KEY]
geoflow article trash ARTICLE_ID [--idempotency-key KEY]
```

文章创建和更新共享这些直接字段：

- `--title`
- `--excerpt`
- `--slug`
- `--keywords`
- `--meta-description`
- `--task-id`
- `--author-id`
- `--category-id`
- `--content`
- `--content-file`

这些工作流字段仅在创建时可用：

- `--status`
- `--review-status`
- `--ai-generated`

使用 `article review`、`article publish` 或 `article trash` 进行工作流转换。更新命令在发送请求之前会拒绝三个仅创建字段。

文章创建要求非空标题和内容。`--content`和`--content-file`互斥。当`--json`存在时，JSON 对象提供请求体。

```bash
bin/geoflow --config /path/to/profile.json article create \
  --title "CLI article" \
  --content-file ./article.md \
  --author-id 5 \
  --category-id 2 \
  --idempotency-key article-create-001

bin/geoflow --config /path/to/profile.json article review 101 \
  --status approved \
  --note "Reviewed" \
  --idempotency-key article-review-101

bin/geoflow --config /path/to/profile.json article publish 101 \
  --idempotency-key article-publish-101

bin/geoflow --config /path/to/profile.json article get 101
```

发布后，再次读取文章并使用持久化的 `/article/{slug}` URL。不要报告旧版 `article.php?id=...` 兼容形式。

## JSON 文件与标准输入

`--json FILE` 和 `--payload-json FILE` 要求顶层为 JSON 对象。使用 `-` 从标准输入读取它：

```bash
bin/geoflow --config /path/to/profile.json task create \
  --json - \
  --idempotency-key task-create-stdin
```

`--content-file -` 从标准输入读取文章内容。JSON 和文本输入限制为 5 MiB。`--token-stdin` 和 `--password-stdin` 读取一行，安全限制为 64 KiB。

一个进程只有一个 stdin 流。不要组合两个都需要它的选项，例如 `--token-stdin` 和 `--json -`。

## 删除确认与 `--yes`

这些命令会请求交互式确认：

- `task delete`
- `material delete`
- `material item-delete`

非交互式执行需要 `--yes`。请先读取确切的 ID 和当前状态：

```bash
bin/geoflow --config /path/to/profile.json task get 12
bin/geoflow --config /path/to/profile.json --no-interaction task delete 12 --yes
```

`--yes` 只确认精确的 CLI 目标。Token 作用域、服务器授权、锁、业务校验和后台高风险确认仍然适用。

当前 DELETE 操作不使用幂等键。不要将 `X-Idempotency-Key` 添加到任务删除、素材库删除或素材项删除中。受支持的 POST 和 PATCH 操作可以使用 `--idempotency-key`，包括创建、更新、任务操作、文章审核/发布/移入回收站以及图片上传。

## 输出、错误与退出码

成功的 API 命令会将服务器 JSON 对象写入 stdout。`config show` 和 `--version` 也会生成 JSON。警告和错误会写入 stderr，敏感字段将被脱敏。

当前退出代码：

| 退出码 | 含义 |
|---|---|
| `0` | 命令成功。 |
| `1` | 参数、配置、传输、API 或意外运行时错误。 |

结合 JSON `error.code` 解读 HTTP 状态：

| HTTP | 操作 |
|---|---|
| `401` | 令牌无效或已过期。请重新登录或更新令牌。 |
| `403` | 令牌缺少所需作用域。请签发一个最小权限的替换令牌。 |
| `409` | 幂等性冲突、请求进行中或结果不确定。请先读取资源状态。 |
| `422` | 验证失败。请更正 `field_errors` 中报告的字段。 |
| `423` | 目标资源已锁定。重试前请检查其工作流状态。 |
| `429` | 已达到速率限制。若存在 `retry_after`，请等待。 |
| `500` | 服务器错误。请保留请求 ID 并检查应用或队列日志。 |

CLI 会拒绝空响应、非 JSON 响应、非对象 JSON、不带 `success=true` 的 2xx 响应，以及大于 5 MiB 的响应。

## 典型工作流

### 只读检查

```bash
bin/geoflow --config /path/to/profile.json catalog
bin/geoflow --config /path/to/profile.json material summary
bin/geoflow --config /path/to/profile.json task list --per-page 20
bin/geoflow --config /path/to/profile.json article list --per-page 20
```

### 创建任务并检查其运行记录

```bash
bin/geoflow --config /path/to/profile.json task create \
  --json ./task.json \
  --idempotency-key task-create-001

bin/geoflow --config /path/to/profile.json task start 12 \
  --enqueue-now \
  --idempotency-key task-start-12

bin/geoflow --config /path/to/profile.json task jobs 12 --limit 20
```

每次变更后，使用 `task get`、`job get` 或匹配的列表命令读取持久化状态。

### 创建、审阅并发布文章

```bash
bin/geoflow --config /path/to/profile.json article create \
  --json ./article.json \
  --idempotency-key article-create-001

bin/geoflow --config /path/to/profile.json article review 101 \
  --status approved \
  --idempotency-key article-review-101

bin/geoflow --config /path/to/profile.json article publish 101 \
  --idempotency-key article-publish-101

bin/geoflow --config /path/to/profile.json article get 101
```

如果风险门禁要求覆盖，请添加 `--risk-override-reason`，并提供真实、可审计的原因。

## 在 Docker 中运行

开发环境 Compose 应用服务名为 `app`：

```bash
docker compose exec app php bin/geoflow --version
docker compose exec app php bin/geoflow --help
docker compose exec app php bin/geoflow --config /path/in/container/profile.json catalog
```

配置文件路径必须存在于容器内。通过只读密钥挂载或受保护的卷提供它。不要让令牌进入镜像层、Compose 文件和公共环境文件。

对生产 Compose 文件使用相同模式：

```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml \
  exec app php bin/geoflow --version
```

当调用方在主机上运行时，通常可以运行主机上的 `bin/geoflow` 副本，并将 `base_url` 指向 Nginx 暴露的 HTTPS 地址。

## API 与管理后台边界

CLI 0.2.0 覆盖 API v1 公开的目录、任务、作业、素材和文章操作。如果 CLI 不存在，请使用匹配的 `/api/v1` 路由，并携带具有所需 scope 的 token。

这些功能目前需要已认证的管理员会话：

- 分析概览、内容、流量、AI 可见性、潜在客户和分发页面。
- 手动发布工作台。
- 分发渠道、目标包、密钥、远程同步和分阶段渠道删除。
- AI 来源提供者与模型绑定。
- 文章 AI 助手、风险复查、编辑器图片和批量操作。
- 企业知识、线索管理、URL 导入和系统更新。
- 首页模块 GET 编辑器、站点设置和主题复制。
- API Token、管理员用户、密码和安全设置。

管理后台前缀来自 `ADMIN_BASE_PATH`；仓库默认值为 `geo_admin`。运行 `php artisan route:list --except-vendor` 以发现目标部署。当前仓库没有可用的 Theme Editor 路由。

本地维护命令同样没有等效的 CLI 或 API：

```bash
php artisan geoflow:recover-knowledge-syncs --stale=600 --limit=50
php artisan geoflow:prune-expired-cache --limit=5000
```

## 故障排查

### Composer 自动加载缺失

针对已检出的代码版本运行 Composer：

```bash
composer install --no-interaction --prefer-dist
```

### 缺少 `base_url` 或令牌

检查选定的来源：

```bash
bin/geoflow --config /path/to/profile.json config show
```

然后使用 `login` 或 `config init` 完成配置文件。如果凭据绑定无效，请选择明确的配置文件，或在同一次调用中将 `--base-url` 与 `--token-stdin` 配对。

### 远程 HTTP 被拒绝

生产环境使用 HTTPS。经批准的测试主机可在其调用或配置文件中启用 `allow_insecure_http`。此设置不会禁用 HTTPS 证书验证。

### 服务器返回 HTML

CLI 需要 JSON API。请检查：

- `base_url` 指向 GEOFlow Web 根目录。
- 该地址尚不包含 `/api/v1` 或管理后台前缀。
- 反向代理未将 API 请求路由到登录页或错误页。
- Docker Web 端口可到达 Laravel 或 Nginx 入口点。

### `401`、`403`、`423` 或 `429`

刷新 `401` 的令牌。检查 `403` 的作用域。读取 `423` 的工作流状态。读取 `retry_after` 并等待 `429`。重复的登录尝试无法解决缺失的作用域、锁定或速率限制。

### 在非交互式进程中删除失败

读取目标并确认其 ID，然后将 `--yes` 添加到那条确切的删除命令中。仅凭 `--no-interaction` 并不能确认删除。

### 原生 Windows ACL 警告

CLI 报告无法校验 ACL。WSL 是受支持的途径。如果必须使用原生 Windows，请使用 Windows 权限工具将 profile 限制为当前用户。
