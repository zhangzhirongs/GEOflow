# 后端连接工作流

当用户要求连接 GEOFlow 后端、管理渠道模板，或比较默认前端与渠道前端能力时，在 `channel_frontend` 中使用此工作流。

## 发现步骤

1. 确认工作区包含 `artisan`、`routes/web.php`、`resources/views/site` 和 `resources/views/theme`。
2. 运行`scripts/discover_frontend_surfaces.py <workspace>`进行静态能力检测。
3. 如果系统命令存在且已知 channel id，请运行：

```bash
php artisan geoflow:frontend-experience <channel_id> --json
```

4. 在提出 UI 或 JSON 更改之前，比较 default-site、channel-site、target-package 和缓存的远程能力。当用户需要全新的远程读取时，通过捆绑的比较/报告辅助工具传递 `--live-remote`。除非 `DistributionHttpClient::signedGetJson` 在使用前立即验证其新加载的端点，否则该辅助工具会阻止请求；它将其限制为 HTTPS 或显式环回 HTTP，并禁用重定向。实时读取不得视为持久化状态。
5. 对于主页 JSON，使用 `scripts/validate_homepage_design_payload.py` 进行验证。

## 渠道-站点流程

1. 识别渠道类型。
2. 如果不是`geoflow_agent`，请说明不保证模块渲染。
3. 对于 GeoFlow Agent，选择模式：
   - `inherit_default`
   - `snapshot_default`
   - `custom`
4. 当 channel id 可用时，通过 `geoflow:frontend-experience <channel_id> --json` 读取缓存的 `/geoflow-agent/v1/frontend-capabilities`。如果已过期或未检查，请使用带有 `--live-remote` 的捆绑辅助工具进行经过验证的非持久化读取，或在相同的端点安全检查后推荐管理员刷新操作。
5. 为 `homepage_style`、`homepage_modules` 和 `home_carousel_slides` 准备已审核的 JSON。
6. 汇总同步差异：主题、`front_mode`、主页模块数、轮播图数、文字广告数和远程支持状态。
7. 同步前请使用同步预览报告。在操作员批准确切的负载和目标渠道后，原始同步 POST 必须包含 `frontend_sync_confirmed=1`。

## 跨渠道流程

1. 清点活跃的 GeoFlow Agent 渠道。
2. 比较每个渠道的`frontend_experience_mode`、`template_key`、`front_mode`以及支持的模块。
3. 对于应跟踪默认前端的渠道，优先使用 `inherit_default`。
4. 对于需要初始副本但之后会分叉的渠道，优先使用 `snapshot_default`。
5. 对于品牌/垂直领域特定渠道，优先使用 `custom`。

## 输出检查清单

每个渠道前端建议都应包含：

- 目标界面以及渠道 ID 或渠道选择器
- 模式选择
- 主题/模板映射
- 模块/样式/幻灯片 JSON 摘要
- 远程能力状态
- 远程缓存状态与检查时间
- 同步摘要：主题、前台模式、模块数量、轮播数量、文字广告数量
- 同步风险、建议操作和回滚说明
