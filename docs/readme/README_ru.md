# GEOFlow

> 语言：[简体中文](../../README.md) | [英语](README_en.md) | [日语](README_ja.md) | [西班牙语](README_es.md) | [俄语](README_ru.md) | [葡萄牙语（巴西）](README_pt_BR.md)

> GEOFlow 是一套开源的 GEO（Generative Engine Optimization）内容工程与多站点分发系统。它把知识库、素材库、提示词、AI 任务、审核与发布、分析、GEOFlow Agent 目标站点包、WordPress REST 渠道、通用 HTTP API 渠道和远端静态页面分发整合为一条统一可管理的流程，将可信材料转化为可发布、可追踪、可分发的 GEO 资产。

[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/Database-PostgreSQL-336791)](https://www.postgresql.org/)
[![Docker](https://img.shields.io/badge/Docker-Compose-blue)](https://docs.docker.com/compose/)
[![License](https://img.shields.io/badge/License-Apache--2.0-blue.svg)](../../LICENSE)
[![GitHub 星标数](https://img.shields.io/github/stars/zhangzhirongs/GEOflow?style=social)](https://github.com/zhangzhirongs/GEOflow/stargazers)
[![GitHub 分叉数](https://img.shields.io/github/forks/zhangzhirongs/GEOflow?style=social)](https://github.com/zhangzhirongs/GEOflow/network/members)
[![GitHub 议题数](https://img.shields.io/github/issues/zhangzhirongs/GEOflow)](https://github.com/zhangzhirongs/GEOflow/issues)

GEOFlow 依据 [Apache License 2.0](../../LICENSE) 分发。用户可以将其用于使用、复制、修改和分发，包括商业目的，前提是保留版权声明和许可证声明，并遵守 Apache-2.0 关于专利、商标和免责的条款。

---

## ✨ 功能

| 功能 | 描述 |
|-------------|----------|
| 🤖 多模型生成 | OpenAI 风格 API 和 Gemini 原生端点、chat / embedding、Provider URL 适配、智能故障转移、重试和调用统计 |
| 🧠 知识库 RAG | 结构化规则分块、可选的 LLM 语义规划、稳定回退、在存在 embedding 模型时写入向量，并在生成时检索上下文 |
| 🗂 素材与提示词 | 标题、关键词、图片、作者、知识库、正文提示词和特殊提示词 |
| 📦 任务自动化 | 生成数量限制、草稿池、审核、发布频率、队列、重试、发布范围以及按任务筛选文章 |
| 📋 审核与文章 | 草稿、审核、发布、回收站、作者、分类、SEO 和任务来源在同一流程中 |
| 📡 多站点分发 | GEOFlow Agent、WordPress REST 和通用 HTTP API 渠道、密钥、目标站点包、静态模式、重写规则、远程编辑/删除、队列和日志 |
| 🧾 目标站点包 | 每个渠道的 PHP 智能体包：首页、文章页面、静态 assets、sitemap、`llms.txt` / TXT 地图和 Schema |
| 📊 分析 | 系统概览、单站点、多站点分发、访问日志、热门内容、AI 爬虫和趋势 |
| 🔍 SEO 与 LLM 友好输出 | SEO、Open Graph、Schema、GFM Markdown、独立 CSS、图片同步、sitemap 和 TXT 地图 |
| 🎨 前端与主题 | 主题、preview、在管理后台中切换主题，以及远程标题、copyright、主题和分类的同步 |
| 🌍 管理后台 i18n | 中文、英语、日语、西班牙语、俄语和葡萄牙语（巴西），包括 GEOFlow 2.0 模块 |
| 🔔 版本通知 | 检查 GitHub `version.json` 并通知新版本 |
| 🐳 Docker 就绪 | **Compose**：Postgres（pgvector）、Redis、应用、队列、调度器、Reverb 和生产 Nginx/php-fpm |

---

## GEOFlow CLI 0.2.0

仓库中有 `bin/geoflow`，用于通过 API v1 处理目录、任务、作业、素材和文章。官方支持 macOS、Linux 和 WSL。在原生 Windows 中，需要手动检查配置文件的 ACL。

[完整的 CLI 英文指南](../GEOFLOW_CLI_en.md)

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

涵盖管理面板、分析、任务、素材、模型和站点设置。

---

## 🆕 新版本要点

GEOFlow 2.0 的关键变化：

- **管理后台成为运营中枢**：保留三步 onboarding，入口按单站点、多站点分发和 companion 技能分组。
- **Gemini 与 OpenAI-compatible 提供商**：模型配置覆盖 OpenAI-style 路由和原生 Gemini chat / embedding。
- **语义分块规划**：支持结构化规则、自动模式和可选的 LLM 语义规划；LLM 仅规划边界，最终片段从原始文本重建。
- **独立分析**：系统概览、内容运营、任务/材料健康度、分发状态、访问日志和 AI 爬虫趋势位于 `/admin/analytics`。
- **分发端到端运行**：GEOFlow Agent 渠道、WordPress REST 和通用 HTTP API、密钥、连接检查、目标站点包、静态/rewrite 模式、设置同步、队列、日志、远程编辑与删除。
- **发布范围明确**：任务可发布到本地和渠道、仅发布到渠道，或仅发布到本地 GEOFlow；本地模式会禁用渠道选择。
- **目标站点可为静态站点**：分发会重新生成远程首页、文章页、sitemap、TXT 地图、`llms.txt`、图像和独立 CSS。
- **素材与 RAG 更完整**：知识片段、向量化状态、标题、关键词、图像、作者和提示词构成任务的输入层。
- **改进部署与安全**：生产 Docker 使用 Nginx + PHP-FPM，seed 不会覆盖现有管理员，可配置 Docker/Composer 镜像源。
- **本地化覆盖当前后台键值**：GEOFlow 2.0 模块不再显示原始翻译键或英文回退。

---

## 🏗 执行流程

```
Админка
  ↓
AI-настройки / материалы / промпты / задачи
  ↓
Планировщик / очередь / worker запускает AI-генерацию
  ↓
Черновик / ревью / публикация
  ↓
Локальные статьи и SEO-страницы
  ↓
Очередь дистрибуции / Agent целевого сайта
  ↓
Удалённая главная, статьи, sitemap, TXT-карты и llms.txt
```

---

## 🧱 架构

| 层级 | 描述 |
|---------|----------|
| Web / Admin | **Laravel**：路由、控制器、文章站点、**Blade** 管理后台、分析、分发、素材和任务 |
| API / 智能体 | 本地 API 与目标站点 PHP 智能体，用于健康检查、文章接收/更新/删除、设置同步和静态文件生成 |
| Scheduler / Queue / Reverb | **Scheduler**、**`queue:work` / Horizon** 用于生成与分发，必要时使用 **Reverb** |
| 域名与 Jobs | `app/Services`、`app/Jobs`、`app/Http/Controllers`，用于 AI、RAG、发布、分发和日志分析 |
| 存储 | **PostgreSQL**（推荐 **pgvector**）+ **Redis** + 目标站点上的 JSON/静态文件 |

主流程：配置模型和提示词 → 准备知识库、标题、关键词、图片和作者 → 将任务加入队列 → 由 worker 生成 → 草稿 / 审核 / 发布 → 本地 SEO 页面 → 分发到选定渠道 → 生产、分发、访问和 AI 爬虫分析。

---

## ⚡ 管理后台快速开始

1. **配置 API**：至少添加一个 chat 模型；使用 RAG 时，添加 embedding 模型并选择 chunking 策略。
2. **配置素材**：基于真实可验证的信息准备知识库、标题、关键词、图片和作者。
3. **创建任务**：选择素材、模型、生成数量、频率和发布范围；先通过草稿或审核验证流程。

---

## 🎯 使用场景与预期价值

GEOFlow 适用于以下实际场景：

- **独立 GEO 站点**
  用于在独立网站上发布 FAQ、产品素材、案例和品牌知识。目标是提高 AI 搜索中的可见性和内容运营效率，而不是批量生产低质量页面。
- **主站内的 GEO 子频道**
  用于在现有网站内启动独立的新闻、专家或 knowledge 板块。目标是更好地组织内容并简化其维护。
- **独立 GEO 信源**
  用于系统化积累特定主题或行业的指南、排名、分析和解读。目标是构建可信的内容资产，而不是用噪音污染互联网。
- **内部 GEO CMS / 内容运营系统**
  用于将 GEOFlow 作为内部 backend 工具，处理模型、素材、知识、提示词、审核和发布。目标是提高团队效率。
- **多站点或多渠道 GEO 部署**
  用于按照统一流程管理多个网站、渠道或主题。目标是实现工作的标准化和可重复性。
- **自动化来源管理与分发**
  用于以工程化方式处理知识库、主题更新和受控分发。目标是让有用信息保持结构化、可验证且易于查找。

系统的价值只有在拥有**真实、高质量且可维护的知识库**时才能实现。
GEOFlow 不用于制造虚假内容或大规模信息噪音。它的任务是通过处理可信内容来提升 AI 营销和 GEO 运营的效率。

---

## 🧭 推荐的部署与使用方式

- **作为独立 GEO 站点**
  部署完整的前台和管理后台，并将系统用作独立的内容平台。
- **作为现有站点的 GEO 子频道**
  可在子域名、目录或独立渠道中使用 GEOFlow，而无需彻底改造主站。
- **作为 GEO 信源站点**
  先构建知识库，然后接入任务和自动化以实现稳定更新。
- **作为内部 GEO 内容管理系统**
  重点介绍管理后台、模型、素材库、API 和编辑流程。
- **作为多站点平台**
  为多个品牌、主题或实验性站点使用通用流程和模板。
- **作为自动化来源管理系统**
  将标题库、图片库和提示词库以及知识库视为长期基础设施。

建议的工作顺序：

1. 先确定真实的业务任务和受众
2. 先构建知识库
3. 确保内容的准确性、可验证性和可维护性
4. 然后才通过自动化增强系统

如果知识库薄弱，自动化只会扩大噪音。在 GEOFlow 中，**知识库质量必须放在首位**。

---

## 🚀 快速开始

### 方案 1：Docker（开发 / 演示）

```bash
git clone https://github.com/zhangzhirongs/GEOflow.git
cd GEOFlow
cp .env.example .env
vi .env

docker compose build
docker compose up -d
```

- 站点：`http://localhost:18080`（端口 **`APP_PORT`**，默认 `18080`）
- 管理后台：`http://localhost:18080/geo_admin/login`（**`ADMIN_BASE_PATH`**，默认 `geo_admin`）

在 **`docker-compose.yml`** 时，服务 **`init`** 在数据库就绪后执行迁移和 `php artisan geoflow:install`；初始数据仅写入空数据库（默认账户——见下表）。

### 补充：Docker（生产）

生产环境请使用 **`docker-compose.prod.yml`** 搭配 **Nginx + php-fpm**，而不是 `php artisan serve`。

```bash
cp .env.prod.example .env.prod
vi .env.prod

docker compose --env-file .env.prod -f docker-compose.prod.yml build
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d postgres redis
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d init
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d app web queue scheduler reverb
```

- 前端与管理后台经由 `web`（Nginx），PHP 为容器 `app`（php-fpm）。
- **首次安装：** 生产服务 `init` 会先运行迁移，然后运行 `php artisan geoflow:install`。此顺序适用于空数据库。对于已有数据或迁移历史的实例，必须遵循第 3.1 节 `../../docs/deployment/DEPLOYMENT.md` 中的停止与排空流程。
- 详情请参阅 **`../../docs/deployment/DEPLOYMENT.md`**。

### 方案 2：本地 PHP

**要求：** PHP **8.3+**（`pdo_pgsql`、`redis` 等）、**PostgreSQL**、**Redis**、**Composer 2.x**。

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

附加终端：

```bash
php artisan queue:work redis --queue=geoflow,distribution,default --sleep=1 --tries=1 --timeout=300
php artisan schedule:work
php artisan reverb:start
```

管理后台：`http://127.0.0.1:8080/geo_admin/login`。**生产环境：** Nginx + PHP-FPM，根目录 **`public/`**。

---

## 默认凭据（`geoflow:install` 之后）

| 字段 | 值 |
|------|----------|
| 登录 | `GEOFLOW_ADMIN_USERNAME`，默认 `admin` |
| 密码 | 本地开发默认 `password`；生产环境请设置 `GEOFLOW_ADMIN_PASSWORD`。如果值为空且账户尚不存在，安装程序将在 init / `geoflow:install` 日志中生成一次性随机密码。 |

`geoflow:install` 仅在空数据库上运行初始 seeders。如果它检测到用户数据或业务数据，则只写入安装标记并跳过 seed。Admin seeder 仍保持幂等，且不会覆盖现有的登录名、邮箱或密码。

在全新的空数据库中，`geoflow:install` 会激活 Enterprise Signature 21 并导入 50 篇参考文章。最小安装请使用 `--without-demo`。已有数据的站点会保留主题、设置、作者、分类和文章。手动导入时请设置 `GEOFLOW_SEED_FRONTEND_DEMO=true` 并执行 `php artisan db:seed --force`；默认只添加缺失的记录。`GEOFLOW_SEED_FRONTEND_DEMO_OVERWRITE=true` 适用于可重置的演示数据库。

### 登录失败后的锁定与手动解锁

- 管理员账号在连续 **5** 次登录失败后会被自动锁定（`status=locked`）。
- 被锁定的账号在管理员执行手动解锁之前无法登录。
- 解锁命令：

```bash
php artisan geoflow:admin-unlock <username>
```

示例：

```bash
php artisan geoflow:admin-unlock admin
```

---

## Docker（简要）

**开发**（`docker-compose.yml`）：`postgres`、`redis`、`init`、`app`（`${APP_PORT:-18080}:8080`）、`queue`、`scheduler`、`reverb`（`${REVERB_EXPOSE_PORT:-18081}:8080`）。`docker/entrypoint.sh` 变量与 [GEOFlow](README_en.md) 中相同。

**生产**（`docker-compose.prod.yml`）：通过 `docker compose --env-file .env.prod -f docker-compose.prod.yml …` 启动（参见上方补充和 **`../../docs/deployment/DEPLOYMENT.md`**）。

---

## 开发与测试

```bash
composer test
./vendor/bin/pint
```

---

## 🌍 其他语言

- [简体中文](../../README.md)
- [英文](README_en.md)
- [日本語](README_ja.md)
- [西班牙语](README_es.md)

---

## 📄 许可证

GEOFlow 依据 [Apache License 2.0](../../LICENSE) 分发。该许可证允许个人及商业使用、修改、分发和私有部署，前提是遵守许可证声明、版权声明、变更声明、专利条款及免责声明。

---

## ⭐ 星标趋势

[![Star 历史图表](https://api.star-history.com/svg?repos=zhangzhirongs/GEOflow&type=Date)](https://star-history.com/#zhangzhirongs/GEOflow&Date)
