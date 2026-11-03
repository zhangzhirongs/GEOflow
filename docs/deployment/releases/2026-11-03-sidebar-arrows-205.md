# 205 侧栏多余箭头修复

发布标识：`20261103-032928-sync-95ea127`，服务器 `192.168.169.205`。

问题原因：前一轮箭头移除只保留在本地，服务器运行的 sidebar.blade.php 仍包含 nav-group-chevron 图标标签。发布前直接读取线上容器模板确认标签存在。

本次同步侧栏模板和 public/css/admin-ui.css，移除三个折叠分组的多余向下图标及其无用样式。保留 details/summary、标题、菜单项及原有折叠行为。独立 Web 镜像同时覆盖 public/css，避免应用与浏览器收到的静态样式不同步。发布流程清理 Blade 缓存并更新应用服务。此前本地侧栏回归测试 2 项、16 条断言通过，发布包 PHP 语法检查通过。

发布后直接检查线上应用容器模板：与发布包内容一致，nav-group-chevron 和 chevron-down 均不存在，details 和 summary 保留。通过 HTTP 获取 admin-ui.css，字节内容与发布包一致；JS 和另外两份 CSS 均 HTTP 200 且 SHA256 匹配。没有执行浏览器交互端到端测试。

服务验证通过：GEOFlow 健康和登录页 HTTP 200、未认证智能体接口 HTTP 401、内部身份接口 HTTP 404，认证 CLI catalog 成功，六个应用服务使用同一新镜像，Nginx 配置检查通过，智能体服务 active。发布状态 phase=verified、web_assets_verified=true。

应用镜像 `geoflow-app-prod:20261103-032928-sync-95ea127`；Web 镜像 `geoflow-web-prod:20261103-032928-sync-95ea127`。智能体按既有脚本复制到同版本目录并重启，代码内容未改。没有数据库结构迁移。

发布包 SHA256：`5b6b711f51b3203518d0baf2b82f8426a9dd513fd86466943121c96727e3a063`。原文件、数据库归档、SQLite 快照和旧镜像保留在 `/opt/geoflow-backups/20261103-032928-sync-95ea127`。
