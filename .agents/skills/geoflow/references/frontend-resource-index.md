# GEOFlow 前端资源索引

仅加载与所选前端阶段匹配的部分。

## 公共前端

- 界面与数据契约：[GEOFlow 前端模块与变量映射](geoflow-frontend-map.md)
- 主题编辑生命周期：[主题编辑工作流](theme-edit-workflow.md)
- Laravel Blade 契约：[Laravel 主题契约](laravel-theme-contract.md)
- 主题包结构：[主题编辑与包契约](theme-package-contract.md)
- 首页模块组成：[主页构成指南](homepage-composition-guide.md)
- 参考站点分析与视觉优化：[设计优化手册](design-optimization-playbook.md) 和 [模板边界](template-boundary.md)
- 确定性工具：[discover_themes.py](../scripts/discover_themes.py)、[validate_homepage_design_payload.py](../scripts/validate_homepage_design_payload.py)、[prepare_theme_edit_session.py](../scripts/prepare_theme_edit_session.py)、[finalize_theme_edit_session.py](../scripts/finalize_theme_edit_session.py) 和 [serve_preview.py](../scripts/serve_preview.py)

## 渠道前端

- 能力与同步契约：[渠道前端契约](channel-frontend-contract.md)
- 目标包界面：[分发目标站点地图](distribution-target-site-map.md)
- 后端连接工作流：[后端连接工作流](backend-connection-workflow.md)
- 确定性工具：[discover_frontend_surfaces.py](../scripts/discover_frontend_surfaces.py)、[compare_default_vs_channel_frontend.py](../scripts/compare_default_vs_channel_frontend.py) 和 [build_sync_preview_report.py](../scripts/build_sync_preview_report.py)

将预览、需认证的导入、渠道同步、激活和发布保持为独立阶段。对于产品或目标包代码变更，切换到 `development`；对于已批准的对运行中系统的变更，切换到 `operations`。
