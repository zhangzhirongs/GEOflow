# 分发目标站点地图

在评估或设计 GeoFlow Agent 目标站点前端行为时，使用此参考。

## GeoFlow Agent 包对外接口

目标包包含：

- `config.php`：默认站点/渠道设置、凭据、静态发布标志、前端体验字段
- `public/index.php`：已签名的 API 端点、文章存储、静态重建、前端渲染器
- `assets/css/site.css`：目标包基础与模块 CSS
- `storage/articles/*.json`：远程文章载荷存储
- `index.html`、`llms.txt`、`sitemap.txt`：静态输出

## 预期能力端点

在可用时，检查：

```text
GET /geoflow-agent/v1/frontend-capabilities
```

签名请求事件可以是 `frontend.capabilities` 或 `health.check`。

预期的响应字段：

- `ok`
- `service`
- `capability_version`（`1.2`，用于当前受管前端体验契约）
- `package_version`
- `active_theme`
- `front_mode`
- `frontend_experience_mode`
- `current_settings`
- `supported_modules`
- `supported_routes`
- `supports_homepage_style`
- `supports_home_carousel_slides`
- `supports_article_text_ads`
- `supports_static_generation`

`current_settings` 应汇总运行时目标状态，且不暴露机密：

- `active_theme`, `front_mode`, `frontend_experience_mode`
- `homepage_modules_count`, `homepage_module_types`
- `home_carousel_slides_count`
- `article_text_ads_count`
- `homepage_style_keys`

## 渲染器预期

通用远程主页应：

- 加载规范化后的站点设置
- 当模块存在时，在文章列表之前渲染首页模块
- 当模块为空时，保持原始 hero/list 行为
- 即使没有文章，也允许静态模块渲染
- 按 `featured`、`hot` 或 `latest` 筛选 `article_collection`
- 清理 `custom_html`

当前受管理的目标包模块列表可能比默认站点更窄。特别是，默认站点的 `lead_form` 模块需要目标包显式支持，才能按原样同步。

特殊目标主题可以保留自定义渲染器，但它们仍应保留文章页面、SEO 元数据、静态重建和签名更新端点。

## 能力注意事项

- 目标包是受控渲染器，而不是远程模板编辑器。
- 缺少 capability 端点并不能证明渠道已损坏；它可能是较旧的包。
- Channel 设置可以独立于内容刷新进行同步，但前端更改通常需要静态重建或内容刷新才能在静态模式下可见。
- 如果 `supported_modules` 不包含 `lead_form`，则将该模块降级为 CTA/链接，在带有操作员可见说明的情况下省略它，或在同步前升级目标包。
