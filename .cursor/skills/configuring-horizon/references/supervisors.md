# Supervisor 与均衡配置

## 在哪里找到它

在编写任何 supervisor 配置之前，使用 `search-docs` 搜索，因为选项名称和默认值会随 Horizon 版本变化：
- 有关完整选项列表，请参阅 `"horizon supervisor configuration"`
- 有关 auto、simple 和 false 模式的说明，请参阅 `"horizon balancing strategies"`
- 有关 autoScalingStrategy 的详情，请参阅 `"horizon autoscaling workers"`
- 有关 defaults 与 environments 合并的说明，请参阅 `"horizon environment configuration"`

## 注意事项

### `environments` 数组合并到 `defaults` 中，而不是替换它

`defaults` 数组定义完整的基础 supervisor 配置。`environments` 数组按环境对其进行补丁，仅覆盖所列出的键。无需在每个环境块中重复每个键。一种常见模式是在 `defaults` 中定义 `connection`、`queue`、`balance`、`autoScalingStrategy`、`tries` 和 `timeout`，然后仅在 `production` 中覆盖 `maxProcesses`、`balanceMaxShift` 和 `balanceCooldown`。

### 使用单独命名的 supervisor 来实施队列优先级

在单个 supervisor 上使用 `balance: auto` 时，Horizon 不会强制执行队列顺序。负载均衡时会忽略 `queue` 数组的顺序。要在处理 `default` 之前先处理 `notifications`，请使用两个单独命名的 supervisor：一个用于高优先级队列，其 `maxProcesses` 较高；另一个用于低优先级队列，其上限较低。文档中对此有明确说明。

### 使用 `balance: false` 在专用队列上保持固定数量的工作进程

自动均衡适合可变负载，但如果某个队列应始终保持恰好 N 个工作进程，例如将视频处理队列限制为 2，请设置`balance: false`和`maxProcesses: 2`。自动均衡会在突发期间将其扩容，这可能并不理想。

### 设置 `balanceCooldown` 以防止在突发负载下工作进程快速扩缩容

使用 `balance: auto` 时，监督器可以在突发负载下快速扩容和缩容。将 `balanceCooldown` 设置为两次扩缩容决策之间的秒数（通常为 3 到 5），以平滑此过程。`balanceMaxShift` 限制每个周期添加或移除的进程数。