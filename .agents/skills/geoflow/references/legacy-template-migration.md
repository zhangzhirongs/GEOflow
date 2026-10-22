# 旧版 GEOFlow 模板迁移

当输入明确包含历史 GEOFlow PHP 模板包或提及已停用的模板技能时，使用此参考。

## 遗留信号

- 根 `index.php`、`article.php`、`category.php` 或 `archive.php`
- `includes/header.php`、`includes/footer.php` 或 `includes/functions.php`
- 输出包含 `tokens.json`、`mapping.json` 和 `manifest.json` 的包
- Tailwind CDN 或根 `assets/css/custom.css` 假设

这些信号描述的是历史 PHP 前端契约。

## 当前 Laravel 目标

将可复用的视觉决策映射到当前 GEOFlow 界面：

| 遗留产物 | 当前目标位置 |
|---|---|
| 页面级 PHP 文件 | `resources/views/theme/{theme_id}/*.blade.php` 或内置 `site.*` 回退 |
| 包含页眉/页脚 | 主题 `partials/` 和 `layout.blade.php` |
| CSS 令牌 | `tokens.json`、主题 CSS 或首页样式 JSON |
| 模块映射 | `mapping.json` 加上支持的首页模块 |
| 静态首页区块 | 当模块类型存在时使用 HomepageModuleBuilder 载荷 |
| 激活说明 | 预览主题、已审核的导入，或明确的主题激活交接 |

保留公共路由、控制器提供的数据、SEO/schema、Markdown 渲染的文章内容、潜在客户表单 slug 以及渠道能力检查。

## 迁移输出

生成：

1. 检测到的遗留文件和假设
2. 可复用的令牌和布局模式
3. 当前 Laravel 主题和首页模块映射
4. 不支持或依赖数据的章节
5. 预览计划与验证路由
6. 激活或渠道同步交接，并附回滚说明

历史预览文件仅用于展示设计意图。在启用之前，请根据当前工作区验证每个迁移后的产物。
