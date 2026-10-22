# GEOFlow

> 语言：[简体中文](../../README.md) | [英语](README_en.md) | [日语](README_ja.md) | [西班牙语](README_es.md) | [俄语](README_ru.md) | [葡萄牙语（巴西）](README_pt_BR.md)

> GEOFlow 是一套面向 GEO（Generative Engine Optimization）的开源内容工程与多站点分发系统。它把知识库、素材库、提示词、AI 生成任务、审核与发布、数据分析、GEOFlow Agent 目标站点包、WordPress REST 渠道、通用 HTTP API 渠道和远端静态页面分发集成到一个运营流程中，将可信资料转化为可追踪、易于发布和分发的 GEO 内容资产。

[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/Database-PostgreSQL-336791)](https://www.postgresql.org/)
[![Docker](https://img.shields.io/badge/Docker-Compose-blue)](https://docs.docker.com/compose/)
[![License](https://img.shields.io/badge/License-Apache--2.0-blue.svg)](../../LICENSE)
[![GitHub 星标数](https://img.shields.io/github/stars/zhangzhirongs/GEOflow?style=social)](https://github.com/zhangzhirongs/GEOflow/stargazers)
[![GitHub 分叉数](https://img.shields.io/github/forks/zhangzhirongs/GEOflow?style=social)](https://github.com/zhangzhirongs/GEOflow/network/members)
[![GitHub 议题数](https://img.shields.io/github/issues/zhangzhirongs/GEOflow)](https://github.com/zhangzhirongs/GEOflow/issues)

GEOFlow 依据 [Apache License 2.0](../../LICENSE) 发布。在保留版权声明和许可证声明、遵守 Apache-2.0 的专利、商标及免责条款的前提下，可以包括商业使用在内的使用、复制、修改和再分发。

---

## ✨ GEOFlow 能做什么

| 機能 | 説明 |
|------|------|
| 🤖 多模型生成 | OpenAI 兼容 API 和 Gemini 原生 API、chat / embedding、Provider URL 自动适配、智能故障转移、重试、使用统计 |
| 🧠 知识库 RAG | 基于规则的分割、可选的 LLM 语义规划、稳定回退、配置 embedding 时的向量存储、生成时的相关上下文检索 |
| 🗂 素材与提示词 | 标题、关键词、图片、作者、知识库、正文提示词、特殊提示词 |
| 📦 任务自动化 | 生成数量、草稿池、审核设置、发布频率、队列、失败重试、发布范围、按任务筛选文章 |
| 📋 审核与文章管理 | 草稿、审核、发布、回收站、作者、分类、SEO、任务来源统一管理 |
| 📡 多站点分发 | GEOFlow Agent、WordPress REST、通用 HTTP API 渠道、密钥、目标站点包、静态模式、rewrite 规则、远程编辑/删除、队列、日志 |
| 🧾 目标站点包 | 每个渠道的 PHP 智能体、首页、文章页、静态资源、sitemap、`llms.txt` / TXT 地图、Schema |
| 📊 数据分析 | 系统概览、单站点运营、多站点分发、访问日志、Top 内容、AI 爬虫识别、趋势 |
| 🔍 SEO 与面向 LLM 的输出 | SEO 元信息、OG、Schema、GFM Markdown、独立 CSS、图片同步、sitemap、TXT 地图 |
| 🎨 前端与主题 | 主题、预览、在管理界面中切换主题、远程站点标题・版权・主题・分类同步 |
| 🌍 管理界面 i18n | 中文、英语、日语、西班牙语、俄语、葡萄牙语（巴西）。也涵盖 GEOFlow 2.0 模块 |
| 🔔 版本通知 | 检查 GitHub `version.json`，并在管理界面中通知新版本 |
| 🐳 可立即部署 | **Docker Compose**：PostgreSQL（pgvector）、Redis、应用、队列、调度器、Reverb、生产 Nginx/php-fpm |

---

## GEOFlow CLI 0.2.0

可以从仓库自带的 `bin/geoflow` 操作 API v1 的目录、任务、作业、素材和文章。正式支持 macOS、Linux、WSL。在 Windows 原生环境中，请手动检查配置文件的 ACL。

[CLI 指南（英语）](../GEOFLOW_CLI_en.md)

---

## 🖼 界面预览

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

涵盖管理后台首页、数据分析、任务、素材、模型设置和站点设置的主要路径。

---

## 🆕 新版本的主要更新点

GEOFlow 2.0 的主要变更点如下。

- **将管理后台打造为运营导航**：保留 3 步引导流程，并整理为单站点运营、多站点分发、相关技能资源。
- **同时支持 Gemini 和 OpenAI 兼容 Provider**：可在模型设置中处理 OpenAI 兼容路由与 Gemini 原生 chat / embedding。
- **知识分割新增语义规划**：可选择基于规则、自动、可选的 LLM 语义规划。LLM 仅规划边界，最终 chunk 会从原文稳定重建。
- **数据分析页面独立化**：将系统概览、内容运营、任务/素材健康度、分发状态、访问日志、AI 爬虫趋势集中到 `/admin/analytics`。
- **分发管理可投入实际运行**：提供 GEOFlow Agent、WordPress REST、通用 HTTP API 渠道、密钥、连接测试、目标站点包、静态/rewrite 模式、远程设置同步、队列、日志、远程编辑/删除。
- **明确公开范围**：可选择主站＋频道、仅频道、仅主站。仅选择主站时，频道选择将失效。
- **目标站点可静态运行**：分发时会重新生成首页、文章页、sitemap、TXT 地图、`llms.txt`、图像、独立 CSS。
- **强化素材与 RAG**：将知识切分、向量化状态、标题、关键词、图片、作者、提示词整合为任务输入层。
- **改进部署与安全性**：生产 Docker 使用 Nginx + PHP-FPM，seed 不会覆盖现有管理员，并可配置 Docker/Composer 镜像。
- **当前管理后台键已支持多语言**：2.0 新模块中更不容易出现裸露的翻译 key 或英语回退。

---

## 🏗 実行構成

```
管理画面
  ↓
AI 設定 / 素材 / プロンプト / タスク設定
  ↓
スケジューラ / キュー / Worker が AI 生成
  ↓
下書き / レビュー / 公開
  ↓
ローカルフロント記事と SEO ページ
  ↓
配信キュー / ターゲットサイト Agent
  ↓
リモート静的ホーム、記事ページ、sitemap、TXT マップ、llms.txt
```

---

## 🧱 系统架构

| 層 | 説明 |
|----|------|
| Web / Admin | **Laravel** 路由与控制器。文章站点、**Blade** 管理界面、分析、分发、素材、任务 |
| API / 智能体 | 本地 API 与目标站点 PHP 智能体。健康检查、文章接收/更新/删除、远程设置同步、静态文件生成 |
| Scheduler / Queue / Reverb | **Scheduler**、**`queue:work` / Horizon** 用于生成・分发处理，根据需要 **Reverb** |
| 域名 / Jobs | 通过 `app/Services`、`app/Jobs`、`app/Http/Controllers` 等处理 AI 生成、RAG、发布、分发、日志分析 |
| 持久化 | **PostgreSQL**（推荐 **pgvector**）+ **Redis** + 目标站点 JSON/静态文件 |

主要流程：在管理中设置模型和提示词 → 准备知识、标题、关键词、图片和作者 → 创建任务并加入队列 → Worker 生成 → 草稿至发布 → 输出本地 SEO 页面 → 分发到选定渠道 → 在分析中查看生产、分发、访问和 AI 爬虫情况。

---

## ⚡ 管理后台的最短操作路径

1. **配置 API**：至少添加 1 个 chat 模型。使用 RAG 时，添加 embedding 模型并选择知识分块策略。
2. **设置素材**：准备知识、标题、关键词、图片和作者。请先使用真实存在且可验证的资料。
3. **创建任务**：选择素材、模型、生成数量、发布频率和发布范围，最初通过草稿或审核进行验证。

---

## 🎯 预期场景与可获得的价值

GEOFlow 适合以下实务场景。

- **独立的 GEO 官方网站**
  可作为持续整理和发布产品说明、FAQ、案例和品牌知识的网站运营。目的是提高在 AI 搜索中的可见性和可信度，而不是批量生产低质量页面。
- **现有官方网站内的 GEO 子频道**
  可以在现有网站中添加新闻、知识、解说等专用频道。目的是将信息结构化，使其更易于搜索和引用。
- **独立的 GEO 信源站点**
  可以持续积累面向特定行业或主题的文章、指南、排名和解说。目的是创建可信的外部内容资产，而不是信息污染。
- **面向内部的 GEO 内容管理系统**
  可作为统一处理模型、素材、知识、提示词、审核和发布流程的内部后端使用。目的是提高运营效率。
- **多站点 / 多渠道运营**
  可以使用相同的运营设计管理多个主题、站点和频道。目的是提高标准化和可维护性。
- **自动化的信源管理与分发**
  可以对知识、专题、更新和分发进行工程化运营。目的是让有价值的信息更易于用户和 AI 理解、引用和搜索。

该系统的价值只有在具备**真实、高质量且持续维护的知识库**的前提下才能成立。
它不是用来污染互联网噪声的机制，而是为了更高效地管理和分发可信信息的基础设施。

---

## 🧭 按场景的部署与使用方法

- **作为独立 GEO 站点引入**
  一并部署前台和管理后台，并将其作为官方信息和解说内容的基地来运营。
- **作为官方网站的 GEO 子频道引入**
  无需大幅重做现有网站，而是在子域名或目录下作为专用频道展开。
- **作为 GEO 信源站点引入**
  首先优先整备知识库，然后使用任务功能进行稳定更新。
- **作为内部内容运营基座引入**
  相比前台，更重视后台、素材管理和 API 集成，并将其作为内部制作和分发基础设施使用。
- **作为多站点运营基础引入**
  面向多个主题或多个品牌横向展开相同的工作流。
- **作为自动化的信源管理系统引入**
  将标题库、图片库、提示词体系和知识视为长期基础设施。

推荐顺序如下。

1. 先定义实际的业务目的和读者
2. 先完善知识库
3. 确保内容的准确性和持续可维护性
4. 在此基础上通过自动化提高效率

在知识库薄弱的情况下加强自动化，只会增加噪声。在 GEOFlow 中应**最优先考虑知识库的质量**。

---

## 🚀 快速开始

### 方法 1：Docker（开发／演示）

```bash
git clone https://github.com/zhangzhirongs/GEOflow.git
cd GEOFlow
cp .env.example .env
vi .env

docker compose build
docker compose up -d
```

- 站点：`http://localhost:18080`（**`APP_PORT`**，默认 `18080`）
- 管理登录：`http://localhost:18080/geo_admin/login`（**`ADMIN_BASE_PATH`**，默认 `geo_admin`）

在 **`docker-compose.yml`** 中，**`init`** 会在数据库准备完成后执行迁移和 `php artisan geoflow:install`。初始数据仅在数据库为空时写入（默认管理员见下表）。

### 補足：Docker（本番）

生产环境推荐在 **`docker-compose.prod.yml`** 中使用 **Nginx + php-fpm**（非 `php artisan serve` 的架构）。

```bash
cp .env.prod.example .env.prod
vi .env.prod

docker compose --env-file .env.prod -f docker-compose.prod.yml build
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d postgres redis
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d init
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d app web queue scheduler reverb
```

- 前端／管理经由 `web`（Nginx），PHP 为 `app`（php-fpm）。
- **首次安装：** 生产环境的 `init` 服务会在迁移后执行 `php artisan geoflow:install`。在全新的空数据库中，将导入 Enterprise Signature 21 主题和 50 篇参考文章。现有站点保留当前主题和内容，请执行 `../../docs/deployment/DEPLOYMENT.md` 第 3.1 节的停止/排空流程。
- 步骤详情请参阅 **`../../docs/deployment/DEPLOYMENT.md`**。

### 方法 2：本地 PHP

**前提:** PHP **8.3+**（`pdo_pgsql`、`redis` 等）、**PostgreSQL**、**Redis**、**Composer 2.x**。

```bash
git clone https://github.com/zhangzhirongs/GEOflow.git
cd GEOFlow
cp .env.example .env
composer install --no-interaction --prefer-dist
php artisan key:generate

GEOFLOW_SECURITY_FRESH_INSTALL_CONFIRMED=true php artisan migrate --force
php artisan geoflow:install
php artisan storage:link

php artisan serve --host=127.0.0.1 --port=8080
```

另一个终端：

```bash
php artisan queue:work redis --queue=geoflow,distribution,default --sleep=1 --tries=1 --timeout=300
php artisan schedule:work
php artisan reverb:start
```

管理：`http://127.0.0.1:8080/geo_admin/login`。**生产环境**使用 Nginx + PHP-FPM，文档根目录为 **`public/`**。

---

## 环境检查清单

| 项目 | 备注 |
|------|------|
| PHP | **8.3+**（Docker 中可能为 8.4） |
| DB | **PostgreSQL**（**pgvector** 推奨） |
| Redis | 用于队列（仅本地验证可 `QUEUE_CONNECTION=sync`） |

---

## 默认管理员（`geoflow:install` 之后）

| 項目 | 値 |
|------|-----|
| 用户名 | `GEOFLOW_ADMIN_USERNAME`，默认为 `admin` |
| 密码 | 本地开发默认 `password`。生产环境请设置 `GEOFLOW_ADMIN_PASSWORD`。如果未设置且账户尚不存在，安装程序会将一次性随机密码输出到 init / `geoflow:install` 日志中。 |

`geoflow:install` 会在全新的空数据库中新增管理员、Enterprise Signature 21 主题和 50 篇参考文章。最小配置可使用 `--without-demo`。如果检测到现有业务数据，则只写入标记，并保留当前主题、设置、分类、作者和文章。Admin seeder 也是幂等的。

手动导入参考包时，请设置 `GEOFLOW_SEED_FRONTEND_DEMO=true` 并执行 `php artisan db:seed --force`。默认只添加缺失的作者、分类和文章。请将 `GEOFLOW_SEED_FRONTEND_DEMO_OVERWRITE=true` 限制在可重置的演示数据库中。

### 登录失败时的锁定与手动解除

- 管理员账号在连续 **5** 次登录失败后会被自动锁定（`status=locked`）。
- 被锁定的账号在管理员手动解除之前无法登录。
- 解除命令：

```bash
php artisan geoflow:admin-unlock <username>
```

例:

```bash
php artisan geoflow:admin-unlock admin
```

---

## Docker 補足

**开发**（`docker-compose.yml`）：`postgres` / `redis` / `init` / `app`（`${APP_PORT:-18080}:8080`）/ `queue` / `scheduler` / `reverb`（`${REVERB_EXPOSE_PORT:-18081}:8080`）。`docker/entrypoint.sh` 的变量与 [GEOFlow](README_en.md) 含义相同。

**生产**（`docker-compose.prod.yml`）：通过 `docker compose --env-file .env.prod -f docker-compose.prod.yml …` 启动（参见上方“补充：Docker（生产）”以及 **`../../docs/deployment/DEPLOYMENT.md`**）。

---

## 开发与测试

```bash
composer test
./vendor/bin/pint
```

---

## 🌍 他言語 README

- [简体中文](../../README.md)
- [英文](README_en.md)
- [西班牙语](README_es.md)
- [俄语](README_ru.md)

---

## 📄 许可证

GEOFlow 依据 [Apache License 2.0](../../LICENSE) 提供。该许可证允许个人使用、商业使用、修改、再分发和私有部署，前提是遵守许可证声明、版权声明、变更通知、专利条款和免责声明。

---

## ⭐ 星标趋势

[![Star 历史图表](https://api.star-history.com/svg?repos=zhangzhirongs/GEOflow&type=Date)](https://star-history.com/#zhangzhirongs/GEOflow&Date)
