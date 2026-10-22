# GEOFlow 开发与运营

`geoflow` 是用于产品开发、运行系统运维、默认公共前端、Laravel Blade 主题、访客与线索流程、GeoFlow Agent 渠道站点以及旧版模板迁移的单一 GEOFlow 技能。

## 模式

- `development`：Laravel 后端、管理后台、API、CLI、迁移、队列和测试
- `operations`：CLI、API v1 和已认证的管理员工作流
- `public_frontend`：默认站点、主题、首页模块、线索表单、文章/分类/归档页面以及预览编辑
- `channel_frontend`：目标包、能力检查、渠道体验设置和同步预览
- `legacy_migration`：旧的根 PHP 模板契约和历史包迁移

该包取代 `yao-geoflow-cli`、`yao-geoflow-design` 和 `yao-geoflow-template`。详细契约保留在 `references/` 中，确定性的发现、验证、预览和预检逻辑保留在 `scripts/` 中。

## 安装

兼容智能体技能的工具可以在 GEOFlow 检出中直接从 `.agents/skills/geoflow` 发现此包。

若要执行干净的全局 Codex 安装或升级，请从 GEOFlow 仓库根目录运行此命令：

```bash
bash .agents/skills/geoflow/scripts/install_codex_skill.sh
```

安装器只复制 `evals/expected_artifacts.json` 中声明的文件，验证暂存包，将当前的 `geoflow` 和已停用的 `yao-geoflow-*` 目录移入唯一的 `~/.codex/skill-backups/geoflow-<timestamp>.<suffix>/` 目录，并在同一文件系统上将暂存包切换到对应位置。完成后重启 Codex。

要回滚，请使用安装程序打印的备份路径：

```bash
mv ~/.codex/skills/geoflow ~/.codex/skills/geoflow.failed
mv ~/.codex/skill-backups/geoflow-<timestamp>.<suffix>/geoflow ~/.codex/skills/geoflow
```

如果备份中包含必须恢复的已停用技能目录，请将这些目录从同一时间戳的备份移动到 `~/.codex/skills/`，然后重启 Codex。

## 运行时要求

| 模式或辅助工具 | 必需工具 | 支持的主机 |
|---|---|---|
| 软件包安装 | Bash、Python 3.10+ | macOS、Linux 或通过 WSL 的 Windows |
| 来源发现、公共前端、旧版迁移 | Python 3.10+ | macOS、Linux 或 WSL |
| 运行时预检与 API 回退 | Bash、Python 3.10+、`curl` | macOS、Linux 或 WSL |
| 实时渠道能力报告 | Python 3.10+、PHP CLI、项目 `artisan` 和已安装的 Composer 依赖 | 可正常工作的 GEOFlow 应用检出 |
| 产品开发 | 目标 GEOFlow 检出所声明的依赖，通常是 PHP/Composer、Node.js 和可选的 Docker | 遵循仓库自身的支持矩阵 |

原生 Windows PowerShell 不是捆绑的 Bash 辅助脚本所支持的执行 shell。请使用 WSL 或手动加载引用。当所需工具不可用时，应保持在只读发现模式，报告缺失的验证层，并且不要声称预检、实时能力、预览、同步、激活或发布成功。
