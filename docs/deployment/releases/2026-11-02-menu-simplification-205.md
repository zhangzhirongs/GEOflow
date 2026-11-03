# GEOFlow 与智能体菜单简化

2026-11-02 已发布到 `192.168.169.205`，GEOFlow 和智能体的导航样式统一收敛。

- 发布标识：`20261102-094420-sync-95ea127`
- GEOFlow 镜像：`geoflow-app-prod:20261102-094420-sync-95ea127`；app、queue、knowledge-queue、system-update-queue、scheduler、reverb 使用同一镜像。
- 智能体目录：`/opt/agent-framework/releases/20261102-094420-sync-95ea127`
- 备份：`/opt/geoflow-backups/20261102-094420-sync-95ea127`
- 发布状态：`/opt/geoflow-backups/update-205-latest.json`
- 发布包 SHA256：`37ca0acc4fb51f149ca38cff0df7dd8621504b461654eb000bfac0a58f850737`

GEOFlow 侧栏维持首页、AI 工作台、分析、内容生产为主要入口；内容资产、AI 配置、系统管理使用原生折叠分组，当前路由所在组自动展开，并兼容桌面迷你侧栏及移动抽屉。智能体侧栏主推新对话、对话和最近对话，技能、MCP、模型配置收于“更多工具”。原路由、授权规则、登录、文章预览/确认保存和聊天数据保留。

发布前通过两项新增 GEOFlow 侧栏测试（13 项断言）、Blade 缓存编译和 PHP 语法检查。此前 AI 工作台相关运行测试发现两项既有文案断言失败，另 14 项通过；新增侧栏用例独立全部通过。智能体前端构建及 12 项测试通过。

线上检查：GEOFlow `/up`、登录页、Agent CSS HTTP 200；同源智能体未登录接口 HTTP 401，内部认证端点对外 HTTP 404；GEOFlow CLI 认证 catalog 成功，智能体服务 active，Nginx 配置有效，所有 GEOFlow 运行服务一致使用发布镜像。用户刷新 GEOFlow 页面即可看到新菜单。

PostgreSQL 归档和 SQLite 一致性快照保存在备份目录。回退使用 `/opt/agent-framework/current/.venv/bin/python /root/server_update_205.py rollback`；不恢复业务数据库。

## 菜单样式补充

后续又将“内容运营”改成默认展开的折叠分组，使其与低频分组交互一致；分组标题增加当前栏目高亮，当前链接增加左侧状态色条，顶部常用入口获得独立区域。新增两个分组行为测试通过（13 项断言）。补充发布：`20261102-095656-sync-95ea127`，镜像 `geoflow-app-prod:20261102-095656-sync-95ea127`，智能体目录 `/opt/agent-framework/releases/20261102-095656-sync-95ea127`，备份 `/opt/geoflow-backups/20261102-095656-sync-95ea127`。包 SHA256：`01820abdf497e568e67d86907aa5d02b8cae329645387cb1f11ef291b491d943`。健康检查、登录页、同源智能体健康和 Blade 视图缓存编译通过。

用户反馈上一版菜单仍显杂乱，已调整为仅折叠低频的“内容资产”“AI 配置”“系统管理”；首页、AI 工作台、分析和内容运营保持常驻。移除常用区底色边框、分组活动底色和菜单活动色条。折叠分组只在当前路由属于该组时自动展开。Docker 回归测试 2 项通过（16 项断言），生产 Blade 语法检查和服务健康检查通过。补充发布：`20261102-101101-sync-95ea127`，镜像 `geoflow-app-prod:20261102-101101-sync-95ea127`，智能体目录 `/opt/agent-framework/releases/20261102-101101-sync-95ea127`，备份 `/opt/geoflow-backups/20261102-101101-sync-95ea127`。包 SHA256：`26ae7f00a7e3fa9e9c2367b5ca3d4ee27f0013b4de53b88ff4f12bb79c9f1e81`。发布状态为 `verified`。

本轮继续压缩导航纵向留白，减少分组间的视觉分割，将折叠组标题改为轻量一致的标题行，并统一常用入口的两级层次。Docker 侧栏回归测试 2 项通过（16 项断言）。发布标识：`20261102-101925-sync-95ea127`，镜像 `geoflow-app-prod:20261102-101925-sync-95ea127`，智能体目录 `/opt/agent-framework/releases/20261102-101925-sync-95ea127`，备份 `/opt/geoflow-backups/20261102-101925-sync-95ea127`。包 SHA256：`b1981cb16b6917806689c19258cf6e7ea8545bf1aca693feb77cfcc2067e6963`，状态 `verified`。
