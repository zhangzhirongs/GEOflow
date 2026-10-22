# 渠道前端契约

当目标界面是 `channel_site`、`cross_channel_sync` 或 `template_mapping` 时，使用此参考。

## 一等目标

GEOFlow Agent 渠道是一等 `channel_frontend` 目标。WordPress REST 和 Generic API 渠道可以接收文章/内容载荷和选定设置；此模式必须先验证其声明的渲染能力，再使用 GEOFlow 首页模块。

## 设置约定

渠道前端体验位于 `distribution_channels.site_settings` 以及 `distribution_channels.channel_config` 中。

`site_settings` 可能包括：

- `site_name`, `site_subtitle`, `site_description`, `site_keywords`
- `copyright_info`, `site_logo`, `site_favicon`
- `seo_title_template`, `seo_description_template`
- `featured_limit`, `per_page`
- `homepage_style`
- `homepage_modules`
- `home_carousel_slides`

默认站点的 `homepage_modules` 可能包含 `lead_form` 模块。同步前，渠道负载必须根据目标包的实时或缓存 `supported_modules` 进行过滤。

`channel_config` 可能包括：

- `frontend_experience_mode`
- `article_text_ad_policy`
- `frontend_capabilities_cache`
- 特定渠道类型的传输设置

`frontend_capabilities_cache` 是非敏感的 GeoFlow Agent 能力快照。它可能包含：

- `status`, `checked_at`, `message`, `reachable`
- `package_version`, `capability_version`
- `active_theme`, `front_mode`, `frontend_experience_mode`
- `supported_modules`, `supported_routes`
- `supports_homepage_style`, `supports_home_carousel_slides`, `supports_article_text_ads`, `supports_static_generation`
- `agent_base_url`

目标载荷应包含：

- `active_theme`
- `front_mode`
- `frontend_experience_mode`
- `homepage_style`
- `homepage_modules`
- `home_carousel_slides`
- `article_text_ads`

同步预览与能力报告应汇总以下内容：

- `active_theme`
- `front_mode`
- `frontend_experience_mode`
- 首页模块数量和模块类型
- 轮播幻灯片数量
- 主页样式令牌键
- 文本广告模块数量

GeoFlow Agent 的目标可能暴露 `/geoflow-agent/v1/frontend-capabilities`。应将其视为实时的远程支持约定，涵盖包版本、支持的模块、支持的路由、当前主题、当前 `front_mode`、轮播支持、首页样式支持以及文章文字广告支持。

后端页面和`php artisan geoflow:frontend-experience <channel_id> --json`应默认读取缓存。若要进行全新读取，请使用`scripts/compare_default_vs_channel_frontend.py --workspace <workspace> --channel <channel_id> --live-remote`或`scripts/build_sync_preview_report.py`。该辅助函数会验证缓存的端点，然后要求应用的 HTTP 客户端在使用前立即验证在实时请求进程中加载的端点，并禁用重定向。受支持的应用契约在端点构造与`DistributionHttpClient::signedGetJson`内部的`get($endpoint)`之间调用`assertSafeSignedEndpoint($endpoint)`；该防护必须强制 HTTPS 或显式回环 HTTP。较旧的应用版本会继续使用缓存报告，直到两种保护措施都存在。在管理员刷新操作之前也应用相同的门禁。实时命令读取不会持久化缓存。

同步确认采用预览优先的流程：

- 单频道：`GET distribution/{channelId}/sync-settings/preview`
- 所有活动的 GeoFlow Agent 渠道：`GET distribution/sync-settings-all/preview`
- 已选频道：`POST distribution/sync-settings-selected/preview`
- 已确认同步：原始同步 POST 加上 `frontend_sync_confirmed=1`

## 体验模式

- `inherit_default`：渠道在同步时使用当前默认站点前端体验。
- `snapshot_default`：频道在保存时复制当前默认站点前端体验，然后安全地分叉。
- `custom`：渠道使用其自己的 JSON 设置。

## 模块语义

共享模块词汇表为：

- `hero`
- `rich_text`
- `image_band`
- `metric_band`
- `chart_band`
- `feature_grid`
- `article_collection`
- `cta_band`
- `custom_html`

默认站点可能还支持使用 `lead_form_slug` 的 `lead_form`。不要假设 GeoFlow Agent 目标包可以渲染 `lead_form`，除非 `/geoflow-agent/v1/frontend-capabilities` 明确将其包含在 `supported_modules` 中。

`article_collection` 必须使用来自 `featured`、`hot` 和 `latest` 的 `data_source` 值。远程文章负载需要 `is_featured` 和 `is_hot` 才能正常工作。

如果目标包缺少 `lead_form`，请在同步前使用这些经过审查的回退方案之一：

- 将其替换为链接到默认站点上的 `/forms/{slug}` 或频道特定公开表单 URL 的 `cta_band`
- 从频道负载中移除该模块并报告该省略项
- 在同步原始模块之前要求升级目标包

## 技能护栏

- 优先使用 JSON 设置和同步，而不是编辑远程 PHP/Blade。
- 将默认站点身份字段和渠道站点身份字段与首页体验字段分开。
- 将渠道同步视为需要操作员审核的最终化步骤。
- 不要将不受支持的外部系统映射到 GEOFlow 渲染器保证。
- 切勿仅因默认站点能够渲染，就静默同步不受支持的模块类型。
