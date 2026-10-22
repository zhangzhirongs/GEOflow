# GEOFlow

> 语言：[简体中文](../../README.md) | [英语](README_en.md) | [日语](README_ja.md) | [西班牙语](README_es.md) | [俄语](README_ru.md) | [葡萄牙语（巴西）](README_pt_BR.md)

> GEOFlow 是一套开源的 GEO（Generative Engine Optimization）内容工程与多站点分发系统。它把知识库、素材库、提示词、AI 生成任务、审核与发布、分析、GEOFlow Agent 目标站点包、WordPress REST 渠道、通用 HTTP API 渠道和远端静态页面分发连接成一条可维护的工作流，将可信源材料转化为可追踪、可发布、可多渠道传播的 GEO 内容资产。

[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/Database-PostgreSQL-336791)](https://www.postgresql.org/)
[![Docker](https://img.shields.io/badge/Docker-Compose-blue)](https://docs.docker.com/compose/)
[![License](https://img.shields.io/badge/License-Apache--2.0-blue.svg)](../../LICENSE)
[![GitHub 星标数](https://img.shields.io/github/stars/zhangzhirongs/GEOflow?style=social)](https://github.com/zhangzhirongs/GEOflow/stargazers)
[![GitHub 分叉数](https://img.shields.io/github/forks/zhangzhirongs/GEOflow?style=social)](https://github.com/zhangzhirongs/GEOflow/network/members)
[![GitHub 议题数](https://img.shields.io/github/issues/zhangzhirongs/GEOflow)](https://github.com/zhangzhirongs/GEOflow/issues)

GEOFlow 依据 [Apache License 2.0](../../LICENSE) 发布。你可以使用、复制、修改和分发它，包括用于商业目的，前提是保留版权和许可证声明，并遵守 Apache-2.0 的专利、商标和免责声明条款。

---

## ✨ 你可以用它做什么

| 功能 | 描述 |
|---------|-------------|
| 🤖 多模型生成 | OpenAI 风格 API 和 Gemini 原生端点、chat / embedding 模型、Provider URL 适配、智能故障转移、重试和使用统计 |
| 🧠 知识库 RAG | 上传文档，使用结构化规则分块、可选的 LLM 语义规划以及稳定回退；配置 embedding 模型时写入向量，然后在生成过程中检索相关上下文 |
| 🗂 素材与提示词 | 标题库、关键词库、图片库、作者、知识库、正文提示词和特殊提示词 |
| 📦 任务自动化 | 生成限制、草稿池、审核开关、发布节奏、队列、重试、发布范围控制和按任务筛选文章 |
| 📋 审核与文章管理 | 草稿、审核、发布、回收站、作者、分类、SEO 字段和任务来源跟踪 |
| 📡 多站点分发 | GEOFlow Agent、WordPress REST 和通用 HTTP API 渠道、密钥、目标站点包、静态模式、重写规则、远程文章编辑/删除、队列和日志 |
| 🧾 目标站点包 | 每个渠道的 PHP 智能体包，包含首页、文章页面、静态 assets、sitemap、`llms.txt` / TXT 地图和 Schema 输出 |
| 📊 分析 | 系统概览、单站点运营、多站点分发、访问日志、热门内容、AI 爬虫识别和趋势图表 |
| 🔍 SEO 与 LLM 友好输出 | SEO 元数据、Open Graph、Schema、GFM Markdown、独立 CSS、图片同步、sitemap 和 TXT 地图 |
| 🎨 前台与主题 | 默认主题、主题包、预览路由、后台切换，以及 GEOFlow Agent 远程同步标题/版权/主题/分类 |
| 🌍 管理后台 i18n | 中文、英语、日语、西班牙语、俄语和葡萄牙语（巴西），包括 GEOFlow 2.0 模块 |
| 🔔 版本更新 | 管理员可以检查 GitHub `version.json`，并在有较新版本可用时通知管理员 |
| 🐳 可直接部署 | **Docker Compose** 用于 PostgreSQL（pgvector）、Redis、应用、队列、调度器、Reverb 和生产 Nginx/php-fpm |

---

## GEOFlow CLI 0.2.0

该仓库包含 `bin/geoflow`，用于 API v1 的 catalog、task、job、material 和 article 操作。它在 macOS、Linux 和 WSL 上支持安全配置文件、登录、JSON 文件或 stdin 输入、删除确认以及结构化错误提示。原生 Windows ACL 需要手动验证。

[完整 CLI 指南](../GEOFLOW_CLI_en.md) | [中文文档](../GEOFLOW_CLI.md)

---

## 🖼 UI 预览

<table>
  <tr>
    <td width="34%" rowspan="3"><img src="../../docs/images/screenshots/analytics-en.png" alt="GEOFlow 分析预览" /><br /><sub>分析</sub></td>
    <td width="33%" rowspan="2"><img src="../../docs/images/screenshots/site-settings-en.png" alt="GEOFlow 站点设置预览" /><br /><sub>站点设置</sub></td>
    <td width="33%"><img src="../../docs/images/screenshots/dashboard-en.png" alt="GEOFlow 管理后台预览" /><br /><sub>管理后台</sub></td>
  </tr>
  <tr>
    <td width="33%"><img src="../../docs/images/screenshots/tasks-en.png" alt="GEOFlow 任务管理预览" /><br /><sub>任务管理</sub></td>
  </tr>
  <tr>
    <td width="33%"><img src="../../docs/images/screenshots/ai-config-en.png" alt="GEOFlow AI 模型配置预览" /><br /><sub>AI 模型配置</sub></td>
    <td width="33%"><img src="../../docs/images/screenshots/materials-en.png" alt="GEOFlow 素材预览" /><br /><sub>素材</sub></td>
  </tr>
</table>

这些页面涵盖管理仪表盘、分析、任务调度、素材、模型配置和站点设置。更多管理文档位于 `../../docs/` 下。

---

## 🆕 新版本亮点

GEOFlow 2.0 的重点包括：

- **Dashboard 作为运营中心**：保留三步设置指南，并按单站点运营、多站点分发和配套技能对条目分组。
- **Gemini 和兼容 OpenAI 的提供商均获得一等支持**：模型设置涵盖 OpenAI 风格的提供商以及原生 Gemini 聊天 / 嵌入路由。
- **知识库支持语义分块规划**：可选择结构化规则分块、自动策略或可选的 LLM 语义规划；由 LLM 规划边界，最终分块从源文本重建。
- **独立分析页面**：系统概览、内容运营、任务健康度、材料健康度、分发状态、访问日志和 AI 爬虫趋势位于 `/admin/analytics` 下。
- **分发管理可端到端使用**：GEOFlow Agent、WordPress REST 和通用 HTTP API 渠道，密钥，连接测试，目标站点包下载，静态/rewrite 模式，远程设置同步，队列，日志，远程编辑和远程删除。
- **发布范围是明确的**：任务可以本地发布并发布到渠道，也可以仅发布到选定渠道，或仅发布到本地 GEOFlow 站点。仅本地模式会禁用渠道选择，且绝不会进入远程分发队列。
- **目标站点可以以静态站点运行**：分发可以重新生成远程首页、文章页、sitemap、TXT 地图、`llms.txt`、图片和独立 CSS。
- **材料与 RAG 更完整**：知识分块、向量化状态、标题库、关键词库、图片库、作者和 prompts 构成任务输入层。
- **部署和安全性更强**：生产 Docker 使用 Nginx + PHP-FPM，已初始化的管理员不会被覆盖，Docker/Composer 镜像可配置。
- **当前管理后台键值的本地化已完整覆盖**：GEOFlow 2.0 模块不再回退到原始翻译键或英文文案。

---

## 🏗 运行时结构

```
Admin dashboard
    ↓
AI config / materials / prompts / task settings
    ↓
Scheduler / queue / worker runs AI generation
    ↓
Draft / review / publish
    ↓
Local front-end articles & SEO pages
    ↓
Distribution queue / target-site Agent
    ↓
Remote static homepage, article pages, sitemap, TXT maps, and llms.txt
```

---

## 🧱 系统架构

| 层 | 描述 |
|-------|-------------|
| Web / 管理后台 | **Laravel** 路由和控制器；文章站点、**Blade** 管理后台、分析、分发、素材和任务 |
| API / 智能体 | 本地 API 与目标站点 PHP 智能体，用于健康检查、文章接收/更新/删除、远程设置同步和静态文件生成 |
| Scheduler / 队列 / Reverb | **Laravel Scheduler**；用于生成和分发的 **`queue:work` / Horizon** 消费者；启用时使用 **Reverb** |
| 域与任务 | `app/Services`、`app/Jobs`、`app/Http/Controllers` 等，用于 AI 生成、RAG、发布、分发和日志分析 |
| 持久化 | **PostgreSQL**（推荐：与 Compose 对齐的 **pgvector**）+ 用于队列/缓存的 **Redis** + 目标站点 JSON/静态文件 |

核心流水线：

1. 在管理后台配置模型、提示词和库
2. 准备知识、标题、关键词、图片和作者素材，并在需要时选择知识分块策略
3. 创建任务并移交给调度器/队列
4. 工作进程调用模型生成正文和元数据
5. 文章经历草稿、审核和发布流程
6. 本地前端渲染文章和 SEO 页面
7. 所选渠道会将内容加入分发队列，并同步到 GEOFlow Agent 或 WordPress 目标站点
8. 分析功能跟踪内容生产、分发状态、访问日志和 AI 爬虫趋势

---

## ⚡ 管理员快速开始

登录后，使用仪表板快速开始路径进行第一轮验证：

1. **配置 API**：至少添加一个可用的聊天模型；如果需要知识库 RAG 检索，请添加一个 embedding 模型并选择一种分块策略。
2. **配置素材**：准备知识库、标题库、关键词库、图片库和作者。从真实、可验证的业务信息开始。
3. **创建任务**：选择库、素材、模型、生成数量、发布节奏和发布范围。在启用完整自动发布和多站点分发之前，先采用草稿或审核流程。

---

## 🎯 用例与预期结果

GEOFlow 适用于这些实际场景：

- **独立的 GEO 网站**
  将产品内容、FAQ、案例和品牌知识组织到一个可持续维护的系统中。目标是提升 AI 搜索可见性和运营效率，而不是批量创建薄弱页面。
- **官方站点内的 GEO 子渠道**
  在现有站点下添加专用的新闻、知识或解决方案渠道——为搜索和引用组织内容，并让团队更新更轻松。
- **独立的 GEO 来源站点**
  发布针对某个行业或主题的高质量说明、列表、指南和参考资料——构建可信资产，而不是网络噪音。
- **内部 GEO 内容管理**
  用作模型、资产、知识、审核和发布的生产后端——提升团队效率并减少工具泛滥。
- **多站点 / 多栏目 GEO**
  以一套运营模式运营多个网点或模板——标准化生产与维护。
- **自动化来源管理与分发**
  设计知识库、主题更新和分发——帮助有价值的信息保持结构化且可检索。

价值应建立在**真实、高质量、持续维护的知识库**之上。
GEOFlow 不用于编造噪音、大规模污染或虚假声明——它帮助团队生产和分发**可信**内容，并提升 GEO 运营效率。

---

## 🧭 建议的部署和使用模式

- **作为独立的 GEO 网站**
  部署完整前端和后台管理；将产品、FAQ、案例和专题内容作为一等资产运营。
- **作为 GEO 子渠道**
  作为子目录、子域或侧渠道部署，无需重建主站。
- **作为 GEO 源站**
  优先构建知识库，然后使用任务进行稳定、受控的更新。
- **作为内部 GEO 后端**
  弱化公共站点；重点关注后台管理、模型、素材、调度、审核和 API。
- **作为多站点或多渠道系统**
  跨品牌、主题或实验复用工作流。
- **作为自动化来源管理层**
  将标题/图像/提示词库和知识作为长期基础设施进行投入。

建议的工作顺序：

1. 明确真实目标与受众
2. 在进行大规模自动化之前先构建知识库
3. 保持内容准确、可验证且可维护
4. 然后通过模型、任务和模板进行扩展

薄弱的知识库加上强大的自动化只会放大噪声。在 GEOFlow 中，**知识库质量优先**。

---

## 🚀 快速开始

### 选项 1：Docker（开发 / 演示）

```bash
git clone https://github.com/zhangzhirongs/GEOflow.git
cd GEOFlow
cp .env.example .env
vi .env   # DB, Redis, APP_URL, ADMIN_BASE_PATH, REVERB_*, etc.

docker compose build
docker compose up -d
```

- 站点（默认）：`http://localhost:18080`（主机端口来自 **`APP_PORT`**，默认 `18080`）
- 管理员登录：`http://localhost:18080/geo_admin/login`（路径前缀来自 **`ADMIN_BASE_PATH`**，默认 `geo_admin`）

在 **`docker-compose.yml`** 下，**`init`** 服务会在数据库就绪后执行首次迁移和种子数据初始化（默认管理员——见下文）。

### 选项 1 附加项：Docker（生产环境）

对于生产环境，请使用 **`docker-compose.prod.yml`** 搭配 **Nginx + php-fpm**，而不是 `php artisan serve`。

对于全新空数据库上的首次安装，你可以使用参考部署脚本运行主机检查、准备 `.env.prod`、部署容器，并运行部署后健康检查：

```bash
curl -fsSL https://raw.githubusercontent.com/zhangzhirongs/GEOflow/main/deploy-scripts/geoflow-docker-deploy.sh -o geoflow-docker-deploy.sh
bash geoflow-docker-deploy.sh
```

见 [`../../deploy-scripts/README.md`](../../deploy-scripts/README.md)。

```bash
cp .env.prod.example .env.prod
vi .env.prod

docker compose --env-file .env.prod -f docker-compose.prod.yml build
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d postgres redis
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d init
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d app web queue scheduler reverb
```

- 前端和管理后台都通过 `web`（Nginx）进入
- PHP 由 `app` 执行（php-fpm）
- **首次安装：** 生产 `init` 服务会运行迁移，然后运行 `php artisan geoflow:install`。全新的数据库会获得主题 21 和包含 50 篇文章的参考包。带有数据或迁移历史的部署会保留其现有主题和内容，并且必须遵循 `../../docs/deployment/DEPLOYMENT.md` 第 3.1 节中的“停止并排空”升级协议。
- 详情参见 `../../docs/deployment/DEPLOYMENT.md`

### 选项 2：本地 PHP 技术栈

**先决条件：** PHP **8.3+**，包含 `pdo_pgsql`、`redis` 及其他典型 Laravel 扩展；本地 **PostgreSQL** 和 **Redis**；**Composer 2.x**。

```bash
git clone https://github.com/zhangzhirongs/GEOflow.git
cd GEOFlow
cp .env.example .env
# 编辑 .env：DB_* → Postgres，REDIS_* → Redis，QUEUE_CONNECTION=redis，等。

composer install --no-interaction --prefer-dist
php artisan key:generate

GEOFLOW_SECURITY_FRESH_INSTALL_CONFIRMED=true php artisan migrate --force
php artisan geoflow:install                                            # first install on an empty database
# 跳过主题和参考文章，同时保留首次安装设置：php artisan geoflow:install --without-demo
php artisan storage:link

php artisan serve --host=127.0.0.1 --port=8080
```

在独立的终端中：

```bash
php artisan queue:work redis --queue=geoflow,distribution,default --sleep=1 --tries=1 --timeout=300
php artisan schedule:work
php artisan reverb:start
```

- 管理端：`http://127.0.0.1:8080/geo_admin/login`（如 `ADMIN_BASE_PATH` 发生变化则调整）
- 在生产环境中，可以使用 `php artisan horizon` 代替 `queue:work`，并由 systemd 或 Supervisor 监管

---

## 环境检查清单

| Component | 备注 |
|-----------|--------|
| PHP | **8.3+**（Docker 镜像可能使用 8.4） |
| 扩展 | 标准 Laravel 集合；`pdo_pgsql` 用于 Postgres；`redis` 用于队列 |
| Composer | 2.x |
| 数据库 | **PostgreSQL**（推荐：**pgvector**，与 Compose 镜像相同） |
| Redis | 队列/缓存（你可以为仅本地的极简测试设置 `QUEUE_CONNECTION=sync`——不用于生产） |

---

## 源部署说明

```bash
chmod -R ug+rwx storage bootstrap/cache
```

**默认管理员**（在首次空数据库 `php artisan geoflow:install` 之后，见 `Database\Seeders\AdminUserSeeder`）：

| 字段 | 值 |
|-------|--------|
| 用户名 | `GEOFLOW_ADMIN_USERNAME`, 默认 `admin` |
| 密码 | 本地/开发默认 `password`；生产环境设置 `GEOFLOW_ADMIN_PASSWORD`。如果它为空且账户尚不存在，安装程序会在 init / `geoflow:install` 日志中生成一次性随机密码。 |

在全新的空数据库上，`geoflow:install` 会添加默认的 Enterprise Signature 主题、50 篇文章参考包和示例百度统计代码。`--without-demo` 会跳过主题和参考文章，同时保留管理员和示例分析代码。该示例会立即从 `hm.baidu.com` 加载脚本，并将公共站点访问数据发送到示例百度统计账户；在生产环境启动前，请在站点设置 → 分析代码下将其替换为您自己的代码，或清空它以禁用分析。当检测到现有用户或业务数据时，该命令会记录安装标记，并保留当前主题、设置、分类、作者、文章和分析代码。`AdminUserSeeder` 保持幂等，且绝不会覆盖现有的用户名、电子邮件或密码。

### 管理员登录锁定与手动解锁

- 管理员账户在连续 **5** 次登录失败后自动锁定（`status=locked`）。
- 锁定账户在管理员手动解锁之前无法登录。
- 解锁命令：

```bash
php artisan geoflow:admin-unlock <username>
```

示例：

```bash
php artisan geoflow:admin-unlock admin
```

**生产环境 HTTP：** Nginx/Apache + **PHP-FPM**，文档根目录为 **`public/`**——不要将项目根目录暴露为 Web 根目录。

---

## Docker 部署说明

### 开发环境 Compose 服务

| 服务 | 角色 |
|---------|------|
| `postgres` | PostgreSQL 16 + pgvector |
| `redis` | Redis 7 |
| `init` | 一次性引导（`restart: "no"`） |
| `app` | `php artisan serve`, 映射 **`${APP_PORT:-18080}:8080`** |
| `queue` | `queue:work redis` |
| `scheduler` | `schedule:work` |
| `reverb` | WebSocket，映射 **`${REVERB_EXPOSE_PORT:-18081}:8080`** |

可选的仅限 localhost 的数据库/Redis 主机端口：参见 `docker-compose.yml` 中的 `DB_EXPOSE_PORT` 和 `REDIS_EXPOSE_PORT`。

### `docker/entrypoint.sh` 变量

| 变量 | 默认值 | 含义 |
|----------|---------|---------|
| `COMPOSER_ON_START` | `true` | 在容器启动时运行 `composer install` |
| `AUTO_MIGRATE` | `true` | 启动时运行 `php artisan migrate --force`；现有部署仍需遵循停止并排空的协议来执行安全迁移 |
| `AUTO_INIT_ONCE` | 仅在 `init` 上运行 `true` | 运行 `migrate` + `geoflow:install`；安装程序决定数据库是否为空 |
| `AUTO_INSTALL_ONCE` | `false` | 在迁移后运行 `geoflow:install`；不要在长时间运行的服务上启用 |

当 `.env` 不包含有效的 `APP_KEY` 时，入口点会自动运行 `key:generate --force`；无需额外的开关。

`./storage` 和 `./.env` 已挂载；应用代码位于镜像中。生产环境请使用新的 **`docker-compose.prod.yml`** 技术栈（`Nginx + php-fpm`），并参阅 `../../docs/deployment/DEPLOYMENT.md`。

**现有部署升级：** 不要直接运行 `git pull` → `build` → `up -d`。请遵循 [部署第 3.1 节](../deployment/DEPLOYMENT.md#31-受管图片删除升级门禁) 中的停止并排空后的迁移和就绪协议。

---

## 开发与测试

```bash
composer test
./vendor/bin/pint
```

---

## 🌍 其他语言的 README

- [简体中文](../../README.md)
- [日本語](README_ja.md)
- [西班牙语](README_es.md)
- [俄语](README_ru.md)
- [葡萄牙语（BR）](README_pt_BR.md)

---

## 📄 许可证

GEOFlow 根据 [Apache License 2.0](../../LICENSE) 授权。它允许个人和商业使用、修改、再分发和私有部署，前提是遵守许可证、版权声明、变更声明、专利条款和免责声明。

---

## ⭐ Star 历史

[![Star 历史图表](https://api.star-history.com/svg?repos=zhangzhirongs/GEOflow&type=Date)](https://star-history.com/#zhangzhirongs/GEOflow&Date)
