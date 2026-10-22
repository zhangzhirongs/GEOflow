# GEOFlow 系统能力发现

GEOFlow 在 API、管理后台、公共站点、worker 和渠道包等多个层面持续演进。每个任务中都要先发现目标工作区，以便技能遵循实际部署情况。

## 静态发现

运行：

```bash
python3 scripts/discover_geoflow_workspace.py /path/to/GEOFlow
python3 scripts/discover_frontend_surfaces.py /path/to/GEOFlow
python3 scripts/discover_themes.py /path/to/GEOFlow
```

第一个命令会盘点应用层和功能证据。前端命令会添加主页、主题、lead-form 和 channel-renderer 的详细信息。

## 运行时发现

当依赖项和环境配置可用时，检查：

```bash
php artisan --version
php artisan route:list --json
php artisan list
php artisan about
```

对于已安装的 CLI，检查 `bin/geoflow --help` 和所选子命令帮助。对于远程实例，在执行变更前使用预检和经过身份验证的读取端点。

## 证据优先级

按以下顺序使用证据：

1. 当前目标路由、命令帮助、能力端点和测试。
2. 当前目标源文件和配置。
3. 生成的发现快照。
4. 此包的参考映射。

如果各层不一致，请报告不匹配之处，并遵循当前目标。缺少路由或类意味着该工作区中该能力不可用，直到实现或升级将其添加。

## 能力组

当存在证据时，工作区发现脚本会跟踪这些分组：

- Laravel 应用和本地 CLI
- API v1 与管理 Web
- 公开站点与线索捕获
- 素材、任务、作业和文章
- 企业知识与增长中心
- 分析与 AI 可见性
- 分发和渠道前端体验
- URL 导入与系统更新
- 主题目录、复制、实时编辑器和首页构建器
- 队列与 Horizon
- 旧版根目录 PHP 前端

分组列表是一种导航辅助。它不会创建路由，也不保证部署状态。
