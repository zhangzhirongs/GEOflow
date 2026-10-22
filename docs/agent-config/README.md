# 智能体配置

此文件夹将人类可读的代理说明集中在一处，以便仓库根目录保持专注于应用程序文件。

## 文件

- `AGENTS.md` - GEOFlow 的通用代理指令。
- `CODEX.md` - 面向 Codex / GPT 的指令。
- `CLAUDE.md` - 面向 Claude 的指令。
- `GEMINI.md` - 面向 Gemini 的指令。

## 必需的根级工具

某些集成文件有意保留在仓库根目录，因为相关工具会按约定发现它们。除非已更改并验证工具配置，否则不要将这些文件移入 `docs/`：

- `.mcp.json` - 用于 Laravel Boost 的共享 MCP 服务器配置。
- `boost.json` - Laravel Boost 安装与技能配置。
- `opencode.json` - OpenCode MCP 配置。
- `.codex/config.toml` - Codex MCP 配置。
- `.cursor/mcp.json` - Cursor MCP 配置。
- `.gemini/settings.json` - Gemini MCP 配置。

完整的 Laravel Boost 指南位于：

`../../.boost/guidelines.md`
