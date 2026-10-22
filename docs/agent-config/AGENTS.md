# GEOFlow Agent 指令

此仓库已安装 Laravel Boost 支持。

在进行 Laravel、PHP、Tailwind、Horizon 或 AI SDK 更改之前，请阅读：

`../../.boost/guidelines.md`

Boost MCP 服务器配置在：

`../../.mcp.json`

工具特定的配置文件保留在仓库根目录或隐藏工具文件夹中的默认发现路径。

## 分发渠道删除安全性

- 通过 `DistributionChannelOperationLeaseService` 运行渠道范围的远程调用以及凭据/包导出，以便最终删除能够检测进行中的工作。
- 在渠道写入、任务-渠道绑定变更、队列认领、重试或立即分发操作之前锁定分发渠道行，并在渠道状态为 `deleting` 时拒绝工作。
- 最终删除必须经过两步影响审查。在移除本地数据之前，应在已锁定的删除事务中重新计算并验证影响指纹。
