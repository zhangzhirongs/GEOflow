---
name: geoflow
description: 跨 Laravel 后端/后台管理/API/CLI、默认站点、主题、线索和 GeoFlow Agent 渠道站点开发或运营 GEOFlow。用于代码变更、运行中系统操作、前端或模板编辑、渠道能力同步、旧版 PHP 迁移，或已停用的 yao-geoflow-cli、yao-geoflow-design 和 yao-geoflow-template ID。先发现真实路由。不包含无关工作、数据库捷径、虚构路由、绕过身份验证、泄露机密、直接复制以及未经批准的线上或破坏性操作。
metadata:
  display_name: GEOFlow 开发与运营
---

# GEOFlow 开发与运营

## 能力描述

GEOFlow 是一个基于 Laravel 的 GEO 内容工程和多站点分发平台。此技能会将工作路由到 AI 模型配置、素材、提示词、知识库和 RAG、内容任务、文章审核与发布、分析、线索、主题、首页模块、企业知识、分发渠道、目标站点包、队列和旧版迁移等环节。

通过 MCP 服务器暴露 GEOFlow 时，请保留这些能力边界。优先使用只读发现工具，使用 CLI/API v1 进行目录、任务、作业、素材和文章操作，并对 API v1 未暴露的能力使用经过身份验证的管理员路由。每次变更都必须标识目标，保留授权和幂等性，并在之后回读资源。删除、密钥轮换、系统更新、回滚、线索导出、渠道删除和主题发布等高风险工具需要明确的目标和确认字段。

MCP 结果应返回结构化响应对象，其中包含 `success`、`operation`、`resource`、最终 `state`、`verification`，以及脱敏后的 `warnings` 或 `error` 数据。将本地持久化成功、排队任务成功和远程渠道成功区分开；不要将其中一项报告为其他项的证明。

## 路由

1. 进行源码工作时运行 `scripts/discover_geoflow_workspace.py <workspace>`，或在运行时变更前运行 `scripts/geoflow_preflight.sh "<workspace>" [config] [checks]`。可用时检查当前 CLI 帮助和路由。
2. 加载一个路由：

- `development`：[GEOFlow 开发工作流](references/development-workflow.md) 和 [GEOFlow 系统能力发现](references/system-capability-discovery.md)
- `operations`：[操作边界](references/operation-boundary.md)、[命令映射](references/command-map.md) 和 [GEOFlow 当前能力地图](references/geoflow-current-capability-map.md)
- `public_frontend`：[GEOFlow 前端资源索引](references/frontend-resource-index.md) 和 [GEOFlow 前端模块与变量映射](references/geoflow-frontend-map.md)
- `channel_frontend`：[GEOFlow 前端资源索引](references/frontend-resource-index.md) 和 [渠道前端契约](references/channel-frontend-contract.md)
- `legacy_migration`：[旧版 GEOFlow 模板迁移](references/legacy-template-migration.md) 和 [旧版 GEOFlow 技能 ID 迁移](references/legacy-skill-id-migration.md)

3. 混合工作遵循：发现、实现、测试或预览、批准后定稿。

## 模式边界

每个阶段都有一个模式。`development` 编辑代码和测试；`operations` 使用受支持的运行时接口；`public_frontend` 准备主题或负载；`channel_frontend` 处理目标包契约。跨越这些边界时切换模式。将经过身份验证的表单创建、同步、激活和发布保留为已确认的运维阶段。

## 安全约束

- 遵循仓库规则和聚焦测试。
- 在 macOS、Linux 或 WSL 上使用 Python 3.10+ 和所需 Bash 运行捆绑的辅助工具；运行时预检还需要 `curl`，实时渠道报告需要 PHP CLI 以及可用的项目 `artisan`。如果依赖不可用，请保持只读发现，报告缺失的验证层，并且不要声称实时成功。
- 保留身份验证、CSRF、幂等性、权限、回读、契约和机密信息脱敏。
- 将预览、导入、同步、激活、发布、更新、回滚和破坏性操作分开。对于高风险步骤，要求精确目标并明确批准。
- 报告所选模式、证据、涉及的文件/路由/资源、验证、最终状态和剩余风险。
