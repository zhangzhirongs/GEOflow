<!--
版权所有 © 2026 姚金刚。保留所有权利。
项目：geoflow
创建者：姚金刚
日期：2026-05-16
X: https://x.com/yaojingang
-->

# 主题编辑工作流

当请求是调整 GEOFlow 工作区中已存在的主题时，使用此工作流。

## 需要检测的系统信号

当工作区包含以下内容时，当前 GEOFlow 主题系统被视为存在：

- `artisan`
- `routes/web.php`
- `resources/views/site`
- `resources/views/theme/<theme-id>/...`
- `app/Support/Site/SiteThemeViewResolver.php`
- 当首页构建器存在时，使用 `app/Support/Site/HomepageModuleBuilder.php`
- 当首页构建器渲染存在时，`resources/views/site/partials/homepage-modules.blade.php`
- `resources/views/admin/site-settings/index.blade.php` 中的主题选择
- 当构建器 UI 存在时，在 `routes/web.php` 中保存/预设/导入主页模块的路由
- `site_settings.active_theme` 存储当前活动主题 ID
- `site_settings.homepage_modules` 和 `site_settings.homepage_style` 在存在时存储首页构建器内容

旧版 PHP 工作区可能仍使用 `/themes`、`includes/theme_preview.php` 和 `theme-preview.php`；仅将它们视为回退方案。

## 工作流状态

### 1. 发现

- 先检查 `resources/views/theme`，仅当缺少 Laravel 迹象时才检查旧版 `/themes`
- 读取每个 `manifest.json`
- 收集可编辑文件：
  - `home.blade.php`
  - `category.blade.php`
  - `article.blade.php`
  - `archive-index.blade.php`
  - `archive-month.blade.php`
  - `layout.blade.php`
  - `partials/*.blade.php`
  - `assets/theme.css`
  - `public/themes/<theme-id>/theme.css`
  - 当已经存在时，`public/themes/<theme-id>/theme.js`
  - 可选的 `tokens.json`
  - 可选的 `mapping.json`
- 记录哪些主题已经看起来像预览分支或编辑会话分支
- 检测当前系统是否支持首页构建器 JSON 导入，并列出支持的模块类型、样式令牌、预设和导入模式

推荐的辅助函数：
- `scripts/discover_themes.py`

### 2. 选择

- 解析 `target_theme_id`
- 如果用户未指定主题，在已知时优先使用活动主题
- 如果活动主题未知，展示已发现的主题，并要求模型在其计划中明确保留所选主题

### 3. Fork 预览会话

- 首轮不要直接编辑线上目标主题
- 在 `resources/views/theme/<preview-theme-id>` 下为 Laravel GEOFlow 创建预览分支
- 当运行时资源存在时，将 `public/themes/<base-theme-id>` 复制到 `public/themes/<preview-theme-id>`
- 在 `manifest.json` 中明确将 fork 标记为预览或编辑会话
- 输出公开路由示例，并清楚说明当前应用是否有隔离的预览路由

推荐的辅助函数：
- `scripts/prepare_theme_edit_session.py`

### 4. AI 编辑循环

安全编辑范围：

- 所选主题内的 Blade 文件
- `assets/theme.css`
- `public/themes/<theme-id>/theme.css` 以及当主题布局加载公共运行时资源时可选的 `theme.js`
- `manifest.json`
- 可选的令牌和映射文件

编辑循环期间固定不变的契约：

- 不要硬编码 `/geo_admin`
- 保留 SEO、canonical 和 schema 输出
- 保留文章渲染 HTML 的行为，并通过 CSS 设置表格、标题、列表和代码块的样式
- 不要显示仅包含文件名的图片说明
- 保持预览/激活分离
- 仅在默认首页保留丰富首页模块，除非有意重新设计搜索/分类状态

典型请求：

- 让布局更宽
- 让标题更粗
- 减少卡片噪音
- 简化元数据
- 仅当能够基于现有 GEOFlow 数据构建时，才添加展示模块
- 用 hero/media、图表、指标、文本模块、service/category 区块、case/resource 区块、大型视觉元素或 CTA 横幅丰富默认首页，同时保持 search/category 状态干净

首页构建器路径：

- 当 `HomepageModuleBuilder` 可用时，为适合构建器的模块/样式更改创建 `homepage-design.json`
- 包含 `replace` 或 `append` 导入模式指南
- 在同一区块中，应先使用构建器支持的类型，再考虑发明仅限 Blade 的模块
- 保持 `custom_html` 小巧、已清理且不含脚本/表单/iframe
- 在操作员接受确切载荷之前，不要向导入路由提交请求

### 5. 审阅预览

- 检查主页、分类、文章和归档的预览 URL
- 说明改了什么、哪些保持不变，以及风险仍存在于何处
- 将默认首页的丰富程度与搜索和分类结果状态分开验证
- 使用构建器路径时单独审核 `homepage-design.json`，包括样式令牌、模块顺序、链接、文章源和导入模式
- 持续在预览分支上迭代，直到操作员确认

### 6. 完成

预览批准后，选择一种路径：

- `publish_as_new_theme`
  - 将预览 fork 保留为新主题，或将其重命名为稳定的主题 id
  - 管理后台主题选择器随后可将其发现为新模板
- `replace_base_theme`
  - 对于 Laravel，在 `storage/app/private/geoflow-theme-backups` 下备份
  - 对于旧版 PHP，将 `--backup-root` 作为 Web 工作区之外的持久父级传入；辅助程序会创建一个专用的 mode-`0700` 子级，并拒绝临时位置
  - 从已确认的预览 fork 替换目标主题
  - 警告：如果目标主题处于启用状态，线上站点可能会立即发生变化
- `activate_after_confirmation`
  - 仅在审核后才应激活
  - 优先使用管理员站点设置流程，除非用户明确希望直接修改系统
- `import_homepage_design_after_confirmation`
  - 仅当存在首页构建器导入时适用
  - 通过管理流程或路由导入已审核的 `homepage-design.json`
  - 警告：导入的首页模块可能会立即影响线上默认首页

推荐的辅助函数：
- `scripts/finalize_theme_edit_session.py`

## 安全规则

- 先预览，后上线
- 将备份保存在主题发现范围和 Web 服务根目录之外
- 仅在基础主题、预览主题和匹配的公共资源处于同一个独占编辑会话中时才进行最终确定；暂停并发的编辑者、部署者和最终确定者
- finalize 辅助程序使用独占工作区锁，并在另一个 finalizer 可能仍在运行时失败关闭；删除陈旧锁前先检查它
- 在进行线上替换前，要求 `manifest.json` 为有效的对象结构，并且 `edit-session.json` ID 一致
- 拒绝复制主题树中的符号链接、命名管道、套接字、设备及其他非普通条目
- 在首次线上重命名前暂存视图树和公共资源树；如果提交失败，恢复每个线上树并报告任何仍存在的清理路径
- 不要添加需要 GEOFlow 尚未暴露的后端字段的模块
- 不要编造当前视图无法支持的企业声明、徽标、表单、客户评价或图表数据
- 不要将首页构建器导入视为预览路由；除非操作员有单独的预发布工作流，否则它是设置变更
- 除非用户明确扩大范围，否则不要改动路由、搜索、SEO 生成或 schema 生成
