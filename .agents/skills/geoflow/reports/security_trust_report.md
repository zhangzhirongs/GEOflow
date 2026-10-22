# 安全信任报告

- 正常：`True`
- 自动化扫描器文件：`46`
- 脚本文件：`12`（`10` Python，`2` Bash）
- 内部脚本模块：`1`
- 密钥发现项：`0`
- 出站或委派实时网络入口点：`3`
- 仅回环地址的预览监听器：`1`
- 网络策略已覆盖的脚本：`3`
- 网络策略未覆盖的脚本：`0`
- 文件写入脚本：`3`
- 自动审批所需权限：`2 / 2`
- 已审查能力：`3`（`network`、`file_write`、`subprocess`）
- 权限审批缺口：`0`
- 已检查 CLI 帮助冒烟测试：`9`
- CLI 帮助冒烟测试失败：`0`
- Bash 语法检查：`2 / 2`
- 交互式脚本：`0`
- 包哈希范围：`source-contract-without-generated-reports`
- 包哈希文件：`46`
- 包 SHA256：`d953e16a97ee24da76c0971cd76fa9497d5e74381903966587f7a8b03a28ff84`

## 失败

- 无

## 警告

- 不需要依赖或锁文件；运行时前置条件记录在 `README.md` 中。
- 上游自动信任扫描器目前仅对 Python 直接调用进行分类。此经审查的报告还涵盖 Bash `curl`、委托的 Laravel 实时读取以及回环预览监听器。包回归测试会强制执行补充清单。

## 依赖证据

- 文件：`none`
- 已固定的条目：`0`
- 未固定条目：`0`

## 网络策略

- 策略文件：`security/network_policy.json`
- 存在：`True`
- 覆盖的脚本：`scripts/geoflow_preflight.sh`、`scripts/build_sync_preview_report.py`、`scripts/compare_default_vs_channel_frontend.py`
- 缺少脚本：`none`
- 不匹配项：`0`

## 权限治理

- 策略文件：`security/permission_policy.json`
- 存在：`True`
- 所需能力：`file_write, subprocess`
- 已批准的能力：`file_write, subprocess`
- 单独审查的能力：`network`
- 缺少审批：`none`
- 无效审批：`none`
- 已过期的审批：`none`

## CLI 帮助冒烟测试

- 已启用：`True`
- 超时秒数：`5.0`
- 已检查脚本：`9`
- 已通过的脚本：`9`
- 失败的脚本：`none`

## 脚本范围

| 脚本 | 接口 | 已声明 | Argparse | 主入口保护 | 输入 | 网络模式 | 文件写入 | 子进程 | 原因 |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| scripts/build_sync_preview_report.py | cli | False | True | True | False | delegated-live | False | True | 固定参数的本地 Artisan 命令；实时模式将签名远程读取委托给 GEOFlow。 |
| scripts/channel_endpoint_safety.py | internal-module | True | False | False | False | False | False | False | 共享的端点和重定向安全检查，由渠道报告 CLI 导入。 |
| scripts/compare_default_vs_channel_frontend.py | cli | False | True | True | False | delegated-live | False | True | 固定参数的本地 Artisan 命令；实时模式将签名远程读取委托给 GEOFlow。 |
| scripts/discover_frontend_surfaces.py | cli | False | True | True | False | False | False | False | 默认 CLI 分类；为内部模块添加 SCRIPT_INTERFACE。 |
| scripts/discover_geoflow_workspace.py | cli | False | True | True | False | False | True | False | 默认 CLI 分类；为内部模块添加 SCRIPT_INTERFACE。 |
| scripts/discover_themes.py | cli | False | True | True | False | False | False | False | 默认 CLI 分类；为内部模块添加 SCRIPT_INTERFACE。 |
| scripts/finalize_theme_edit_session.py | cli | False | True | True | False | False | True | False | 默认 CLI 分类；为内部模块添加 SCRIPT_INTERFACE。 |
| scripts/geoflow_preflight.sh | cli | True | n/a | n/a | False | direct-curl | temporary-only | False | 校验操作员选定的 GEOFlow 端点，限制响应范围，并对敏感输出进行脱敏。 |
| scripts/install_codex_skill.sh | cli | True | n/a | n/a | False | False | bounded-install | False | 通过同一文件系统暂存来复制所声明的产物契约，并将先前的技能移动到持久备份。 |
| scripts/prepare_theme_edit_session.py | cli | False | True | True | False | False | True | False | 默认 CLI 分类；为内部模块添加 SCRIPT_INTERFACE。 |
| scripts/serve_preview.py | cli | False | True | True | False | loopback-listener | False | False | 在 `127.0.0.1` 上从随附的预览目录提供固定允许列表。 |
| scripts/validate_homepage_design_payload.py | cli | False | True | True | False | False | False | False | 默认 CLI 分类；为内部模块添加 SCRIPT_INTERFACE。 |
