# GEOFlow

> 语言：[简体中文](../../README.md) | [英语](README_en.md) | [日语](README_ja.md) | [西班牙语](README_es.md) | [俄语](README_ru.md) | **葡萄牙语（巴西）**

> GEOFlow 是一套开源的 GEO（Generative Engine Optimization）内容工程与多站点分发系统。它把知识库、素材库、提示词、AI 生成任务、审核与发布、分析、GEOFlow Agent 目标站点包、WordPress REST 渠道、通用 HTTP API 渠道和远端静态页面分发连接起来，将可信材料转化为可发布、可追踪、可分发的 GEO 资产。

[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/Database-PostgreSQL-336791)](https://www.postgresql.org/)
[![Docker](https://img.shields.io/badge/Docker-Compose-blue)](https://docs.docker.com/compose/)
[![License](https://img.shields.io/badge/License-Apache--2.0-blue.svg)](../../LICENSE)
[![GitHub 星标数](https://img.shields.io/github/stars/zhangzhirongs/GEOflow?style=social)](https://github.com/zhangzhirongs/GEOflow/stargazers)
[![GitHub 分叉数](https://img.shields.io/github/forks/zhangzhirongs/GEOflow?style=social)](https://github.com/zhangzhirongs/GEOflow/network/members)
[![GitHub 议题数](https://img.shields.io/github/issues/zhangzhirongs/GEOflow)](https://github.com/zhangzhirongs/GEOflow/issues)

GEOFlow 在 [Apache 许可证 2.0](../../LICENSE) 下发布。您可以出于商业目的使用、复制、修改和分发，前提是保留版权和许可证声明，并遵守 Apache-2.0 的专利、商标和免责条款。

---

## ✨ 你可以用它做什么

| 资源 | 描述 |
|---------|-----------|
| 🤖 多模型生成 | OpenAI 风格 API 和 Gemini 原生端点、chat / embedding 模型、URL 适配、智能故障转移、重试和使用统计 |
| 🧠 知识库 RAG | 基于规则的分块、可选的 LLM 语义规划、稳定回退、使用 embedding 生成向量，并在生成过程中检索上下文 |
| 🗂 素材与提示词 | 标题、关键词、图片、作者、知识库、正文提示词和特殊提示词 |
| 📦 任务自动化 | 生成限制、草稿池、审核、发布节奏、队列、重试、发布范围以及按任务筛选 |
| 📋 审核与文章 | 草稿、审核、发布、回收站、作者、分类、SEO 和任务来源，集成于单一流程 |
| 📡 多站点分发 | GEOFlow Agent、WordPress REST 和通用 HTTP API 渠道、密钥、目标站点包、静态模式、重写规则、远程编辑/删除、队列和日志 |
| 🧾 目标站点包 | 每个渠道的 PHP 智能体包，包含首页、文章页面、静态 assets、sitemap、`llms.txt` / TXT 地图和 Schema |
| 📊 分析 | 全局视图、单站点运营、多站点分发、访问日志、热门内容、AI 爬虫和趋势 |
| 🔍 SEO 与 LLM 友好输出 | SEO、Open Graph、Schema、Markdown GFM、独立 CSS、图片同步、sitemap 和 TXT 地图 |
| 🎨 前台与主题 | 主题、预览、通过后台切换，以及标题、版权、主题和分类的远程同步 |
| 🌍 管理后台 i18n | 中文、英语、日语、西班牙语、俄语和葡萄牙语，包括 GEOFlow 2.0 模块 |
| 🔔 版本更新 | 管理员可以检查 GitHub 的 `version.json`，并在有较新版本可用时通知 |
| 🐳 可直接部署 | **Docker Compose**：PostgreSQL（pgvector）、Redis、应用、队列、调度器、Reverb 以及使用 Nginx/php-fpm 的生产环境 |

---

## GEOFlow CLI 0.2.0

该仓库包含 `bin/geoflow`，用于通过 API v1 管理目录、任务、作业、材料和文章。官方支持覆盖 macOS、Linux 和 WSL。在原生 Windows 上，请手动确认配置文件的 ACL。

[CLI 英文完整指南](../GEOFLOW_CLI_en.md)

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

这些屏幕涵盖 admin 面板、分析、任务、材料、模型配置和站点设置。

---

## 🆕 新版本亮点

GEOFlow 2.0 的亮点：

- **Dashboard 作为运营中心**：保留三步指南，并按单站点运营、多站点分发和配套技能组织条目。
- **Gemini 与 OpenAI-compatible 提供商**：模型配置覆盖 OpenAI-style 路由和原生 Gemini 的 chat / embedding。
- **面向知识的语义分块**：支持结构化规则、自动模式或可选的 LLM 语义规划；LLM 规划边界，最终分块从原始文本重建。
- **独立分析**：全局视图、内容运营、任务/材料健康状况、分发状态、访问日志和 AI 爬虫趋势都位于 `/admin/analytics`。
- **端到端分发**：GEOFlow Agent、WordPress REST 和通用 HTTP API 渠道，密钥，连接测试，目标站点包，静态/rewrite 模式，远程配置同步，队列，日志，远程编辑和删除。
- **明确的发布范围**：任务可以在本地和渠道中发布、仅在渠道中发布或仅在本地 GEOFlow 中发布；本地模式会禁用渠道选择。
- **目标站点可以是静态站点**：分发会重新生成远程首页、文章页、sitemap、TXT 地图、`llms.txt`、图片和独立 CSS。
- **材料与 RAG 更完整**：chunks、向量化状态、标题、关键词、图片、作者和 prompts 构成任务的输入层。
- **部署和安全性更佳**：生产 Docker 使用 Nginx + PHP-FPM，seeder 不会覆盖现有管理员，Docker/Composer 镜像可配置。
- **当前模块的 i18n 覆盖**：GEOFlow 2.0 模块不再依赖原始键或英文回退。

---

## 🏗 执行流程

```
Admin
  ↓
Configuração IA / materiais / prompts / tarefas
  ↓
Scheduler / fila / worker executa IA
  ↓
Rascunho / Revisão / Publicação
  ↓
Artigos locais e páginas SEO
  ↓
Fila de distribuição / Agent do site-alvo
  ↓
Home remota, artigos, sitemap, mapas TXT e llms.txt
```

---

## 🧱 架构

| 层级 | 描述 |
|--------|------------|
| Web / 管理后台 | **Laravel**：路由、控制器、文章站点、**Blade** 管理后台、分析、分发、素材和任务 |
| API / 智能体 | 本地 API 与目标站点的 PHP 智能体，用于健康检查、接收/更新/删除文章、同步配置和生成静态文件 |
| Scheduler / 队列 / Reverb | **Scheduler**、用于生成和分发的 **`queue:work` / Horizon**，如需要则 **Reverb** |
| 域与任务 | `app/Services`、`app/Jobs`、`app/Http/Controllers`，用于 AI、RAG、发布、分发和日志分析 |
| 存储 | **PostgreSQL**（推荐 **pgvector**）+ **Redis** + 目标站点中的 JSON/静态文件 |

主流程：配置模型和提示词 → 准备知识库、标题、关键词、图片和作者 → 队列中的任务 → worker 生成内容 → 草稿 / 审核 / 发布 → 本地 SEO 页面 → 分发到选定渠道 → 生产、分发、访问和 AI 爬虫分析。

---

## ⚡ 管理员快速开始

1. **配置 API**：至少添加一个聊天模型；对于 RAG，请添加一个 embedding 模型并选择一种分块策略。
2. **配置素材**：基于真实且可验证的信息准备知识库、标题、关键词、图片和作者。
3. **创建任务**：选择素材、模型、生成数量、频率和发布范围；先通过草稿或审核流程进行测试。

---

## 🎯 用例与预期结果

GEOFlow 适用于以下实际场景：

- **独立的 GEO 站点**
  将产品内容、FAQ、案例和品牌知识组织到一个可持续维护的系统中。目标是提升 AI 搜索可见性和运营效率，而不是批量创建薄弱页面。
- **官方站点内的 GEO 子渠道**
  在现有站点内添加新闻、知识或解决方案频道。为搜索、引用和团队更新组织内容。
- **独立的 GEO 来源站点**
  发布关于某个行业或主题的高质量说明、排名、指南和参考资料。构建可信资产，而不是网络噪音。
- **内部 GEO 内容管理**
  作为模型、资产、知识、审核和发布的生产后端。减少工具分散并提高团队效率。
- **GEO 多站点 / 多栏目**
  以共同的运营标准运营多个渠道、品牌或模式。
- **来源与分发的自动化管理**
  构建知识库、主题更新和分发结构，以使有价值的信息保持可检索。

该价值必须源自**真实、可信且持续维护的知识库**。
GEOFlow 不应用于制造噪音、污染互联网或发布虚假声明。它旨在帮助团队制作和分发**可信**内容，并提高 GEO 的运营效率。

---

## 🧭 建议的部署和使用模式

- **作为独立的 GEO 网站**
  发布完整的前端和管理后台；将产品、FAQ、案例和主题作为自有资产来运营。
- **作为 GEO 子渠道**
  使用子目录、子域名或侧边渠道，而无需重建主站。
- **作为 GEO 来源站点**
  优先处理知识库，并使用任务进行受控且持续的更新。
- **作为内部 GEO 后端**
  减少对公开站点的关注，专注于 admin、模型、材料、排期、审核和 API。
- **作为多站点或多渠道系统**
  在品牌、主题和实验之间复用工作流。
- **作为自动化来源管理层**
  将标题、图像、提示词和知识库视为长期基础设施。

推荐顺序：

1. 定义真实目标和目标受众
2. 在大规模自动化之前先构建知识库
3. 保持内容正确、可验证且可持续
4. 之后才通过模型、任务和模板进行扩展

薄弱的知识库即使有强大的自动化，也只会放大噪声。在 GEOFlow 中，**知识库质量优先**。

---

## 🚀 使用 Docker Compose 部署

### 快速配置

1. 克隆项目：
```bash
git clone https://github.com/zhangzhirongs/GEOflow.git
cd GEOFlow
```

2. 复制环境文件：
```bash
cp .env.example .env
```

3. 启动容器：
```bash
docker compose up -d
```

访问 `http://localhost:18080`（前端）和 `http://localhost:18080/geo_admin`（管理后台）。

对于在空数据库中的首次安装，请配置 `.env.prod` 并使用 `docker compose --env-file .env.prod -f docker-compose.prod.yml up -d`。服务 `init` 会执行迁移，然后执行 `php artisan geoflow:install`。带有数据或迁移历史的实例应遵循 `../deployment/DEPLOYMENT.md` 中第 3.1 节的停止和排空协议。

### 端口

| 服务 | 端口 |
|---------|-------|
| App（development） | 18080 |
| App（生产 nginx） | 18080 |
| Postgres | 15432 |
| Redis | 16379 |
| Reverb | 18081 |

---

## 🧩 源代码部署说明

```bash
chmod -R ug+rwx storage bootstrap/cache
```

**默认管理员**在 `geoflow:install` 之后：

| 字段 | 值 |
|-------|-------|
| 用户 | `GEOFLOW_ADMIN_USERNAME`，默认 `admin` |
| 密码 | 在本地开发中，默认是 `password`；在生产环境中设置 `GEOFLOW_ADMIN_PASSWORD`。如果它为空且账户尚不存在，安装程序会在 init / `geoflow:install` 日志中生成一次性随机密码。 |

`geoflow:install` 仅在数据库为空时执行初始 seeders。如果检测到用户数据或业务数据，则只写入安装标记并忽略 seed。admin seeder 仍保持幂等，且绝不覆盖现有的用户名、邮箱或密码。

在全新空数据库中，`geoflow:install` 会激活 Enterprise Signature 21 并导入包含 50 篇参考文章的数据包。使用 `--without-demo` 进行最小安装。带有数据的站点会保留主题、配置、作者、分类和文章。对于手动导入，请设置 `GEOFLOW_SEED_FRONTEND_DEMO=true` 并执行 `php artisan db:seed --force`；默认模式仅补全缺失记录。将 `GEOFLOW_SEED_FRONTEND_DEMO_OVERWRITE=true` 保留给可重置的演示数据库。

### 管理员登录锁定与手动解锁

- 管理员账户在连续 **5** 次无效登录尝试后会被锁定。
- 被锁定的账户需要由管理员解锁。
- 解锁命令：

```bash
php artisan geoflow:admin-unlock <username>
```

**生产环境 HTTP：** 使用 Nginx/Apache + **PHP-FPM**，文档根目录设为 **`public/`**。不要将项目根目录暴露为 Web 根目录。

---

## 🐳 Docker 部署说明

### 开发 Compose 服务

| 服务 | 角色 |
|---------|-------|
| `postgres` | PostgreSQL 16 + pgvector |
| `redis` | Redis 7 |
| `init` | 单一引导 (`restart: "no"`) |
| `app` | `php artisan serve`, 映射 **`${APP_PORT:-18080}:8080`** |
| `queue` | `queue:work redis` |
| `scheduler` | `schedule:work` |
| `reverb` | WebSocket，映射 **`${REVERB_EXPOSE_PORT:-18081}:8080`** |

生产环境请使用带有 Nginx + php-fpm 的 **`docker-compose.prod.yml`** 栈，并参阅 `../deployment/DEPLOYMENT.md`。

**更新现有安装：** 不要直接执行 `git pull` → `build` → `up -d`。请遵循 [部署第 3.1 节](../deployment/DEPLOYMENT.md#31-受管图片删除升级门禁) 中的停止、排空、迁移和就绪协议。

---

## 开发与测试

```bash
composer test
./vendor/bin/pint
```

---

## 📖 文档

- [完整文档](../../README.md)
- [更新日志](../CHANGELOG.md)

---

## ❤️ 致谢

- [Laravel](https://laravel.com/) - PHP 框架
- [Laravel AI SDK](https://laravel.com/ai) - AI 集成
- [Laravel Horizon](https://laravel.com/horizon) - 队列管理
- [Laravel Reverb](https://laravel.com/reverb) - WebSocket
- [pgvector](https://github.com/pgvector/pgvector) - PostgreSQL 中的向量

---

## 📄 许可证

GEOFlow 是依据 [Apache License 2.0](../../LICENSE) 发布的自由软件。

---

## 🌍 其他语言的 README

- [简体中文](../../README.md)
- [英文](README_en.md)
- [日本語](README_ja.md)
- [西班牙语](README_es.md)
- [俄语](README_ru.md)

---

<p align="center">
  <a href="https://github.com/zhangzhirongs/GEOflow">
    <img src="https://img.shields.io/github/stars/zhangzhirongs/GEOflow?style=flat" alt="GitHub 星标" />
  </a>
  <a href="https://github.com/zhangzhirongs/GEOflow">
    <img src="https://img.shields.io/github/forks/zhangzhirongs/GEOflow?style=flat" alt="GitHub 复刻" />
  </a>
  <a href="https://github.com/zhangzhirongs/GEOflow/issues">
    <img src="https://img.shields.io/github/issues/zhangzhirongs/GEOflow?style=flat" alt="GitHub 议题" />
  </a>
</p>

## ⭐ Star 历史

[![Star 历史图表](https://api.star-history.com/svg?repos=zhangzhirongs/GEOflow&type=Date)](https://star-history.com/#zhangzhirongs/GEOflow&Date)
