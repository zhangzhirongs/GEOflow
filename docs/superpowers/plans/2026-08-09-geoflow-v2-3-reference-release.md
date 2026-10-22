# GEOFlow v2.3 参考发布实施计划

> **面向智能体工作者：** 必需子技能：使用 superpowers:subagent-driven-development（推荐）或 superpowers:executing-plans 逐项实现此计划任务。步骤使用复选框（`- [ ]`）语法进行跟踪。

**目标：** 发布 GEOFlow v2.3.0，包含 Enterprise Signature 主题、关于页和归档页、50 篇可读参考文章，以及仅用于全新安装的示例数据。

**架构：** 该版本随附一个带版本号的参考内容包，由 JSON 元数据和 Markdown 正文组成。`geoflow:install` 仅在证明数据库为全新状态后才导入它，而升级会保留所有现有主题、站点设置、分类、作者和文章。

**技术栈：** Laravel 12、PHP 8.3+、Blade、PostgreSQL/SQLite、Vite、PHPUnit。

---

### 任务 1：公共页面契约

**文件：**
- 创建：`app/Http/Controllers/Site/AboutController.php`
- 创建：`resources/views/site/about.blade.php`
- 创建：`resources/views/site/partials/about-content.blade.php`
- 创建：`resources/views/theme/geoflow-template-21-enterprise-signature/about.blade.php`
- 修改：`routes/web.php`
- 修改：`resources/views/theme/geoflow-template-21-enterprise-signature/manifest.json`
- 测试: `tests/Feature/EnterpriseSignatureThemeTest.php`

- [ ] 添加一个失败测试，证明 `/about`、`/archive` 和月度归档是相互独立的公开页面。
- [ ] 运行聚焦测试，并确认其因缺少 `site.about` 而失败。
- [ ] 添加 About 控制器、视图、导航和已发布主题元数据，同时保留归档路由。
- [ ] 运行聚焦测试，并确认所有页面契约断言通过。

### 任务 2：带版本的参考内容

**文件：**
- 创建：`database/seeders/data/frontend-reference-v1/manifest.json`
- 创建：`database/seeders/data/frontend-reference-v1/articles/*.md`
- 创建：`database/seeders/FrontendReferenceSeeder.php`
- 修改：`database/seeders/FrontendDemoSeeder.php`
- 测试: `tests/Feature/FrontendReferenceContentTest.php`

- [ ] 为恰好 50 篇文章、两个活跃分类、稳定唯一的 slug 以及可读的 Markdown 文件添加失败测试。
- [ ] 导出已批准的本地文章，更新与版本绑定的措辞，移除本地占位符，并挑选精选/热门示例。
- [ ] 实现清单读取器与仅插入的参考内容填充器（seeder）。
- [ ] 运行聚焦的内容与填充器测试，直到通过。

### 任务 3：全新安装默认值和升级保护

**文件：**
- 修改：`app/Console/Commands/GeoFlowInstallCommand.php`
- 修改：`database/seeders/DatabaseSeeder.php`
- 修改：`.env.example`
- 修改：`.env.prod.example`
- 测试: `tests/Feature/GeoFlowInstallCommandTest.php`
- 测试: `tests/Feature/DatabaseSeederTest.php`

- [ ] 添加失败测试，证明全新安装会自动获得主题 21 和 50 篇文章。
- [ ] 添加失败测试，证明已标记和未标记的现有数据库会保留所有现有值。
- [ ] 为最小化全新安装添加 `--without-demo`，并保留原始 `db:seed` 为选择启用项。
- [ ] 在安装标记中记录参考内容版本、数量和主题。
- [ ] 运行安装与数据库填充器测试套件，直到通过。

### 任务 4：发布元数据和文档

**文件：**
- 修改：`version.json`
- 修改：`docs/CHANGELOG.md`
- 修改：`docs/CHANGELOG_en.md`
- 修改：`README.md`
- 创建：`docs/reference-content/frontend-reference-v1.md`

- [ ] 将版本元数据和发布 URL 对齐到 v2.3.0。
- [ ] 为主题、50 篇文章包、About/归档契约以及升级保护编写文档。
- [ ] 验证没有已发布的参考文章仍保留过时版本或本地域名措辞。

### 任务 5：发布验证与交付

**文件：**
- 修改：`bin/git/check-open-source-release.sh`
- 修改：`bin/git/prepare-open-source-release.sh`

- [ ] 运行 Pint、聚焦测试、完整的 PHPUnit 测试套件、Vite 构建、JavaScript 测试以及原型验证。
- [ ] 运行 Composer 和 npm 审计，并检查生成的包内容。
- [ ] 运行安全、架构与对抗性评审；修复已确认的问题。
- [ ] 通过私有开源门禁，将隔离的私有源码同步到公开发布工作树，并验证同步后的差异。
- [ ] 提交、推送 `codex/unified-latest-updates`、创建 GitHub 拉取请求，并回读 CI 状态。
