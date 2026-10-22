# 旧版 GEOFlow 技能 ID 迁移

版本 `1.0.0` 将三个已停用的公共 ID 合并为 `geoflow`：

| 已停用 ID | 当前模式 |
|---|---|
| `yao-geoflow-cli` | `operations`，或在请求代码变更时使用 `development` |
| `yao-geoflow-design` | `public_frontend` 或 `channel_frontend` |
| `yao-geoflow-template` | 对于历史 PHP 包使用 `legacy_migration`，否则使用 `public_frontend` |

将显式调用更新为 `$geoflow`。在本地安装期间，先复制统一包，并验证其发现和校验脚本。将三个已停用的安装目录复制到持久路径（例如 `~/.codex/skill-backups/geoflow-YYYYMMDD/`）；系统临时目录不得作为唯一备份。在移除已停用的安装条目之前，验证文件数量或摘要，并确认本地 `outputs/`、`reports/` 和私有运维人员笔记均已存在。

仓库历史仍是已退役包实现的来源。统一包在当前模式下保留了它们可复用的参考与脚本能力。
