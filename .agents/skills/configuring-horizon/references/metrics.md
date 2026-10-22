# 指标与快照

## 在哪里找到它

使用 `search-docs` 搜索：
- 有关 snapshot 命令和调度的说明，请参阅 `"horizon metrics snapshot"`
- 有关保留配置的说明，请参阅 `"horizon trim snapshots"`

## 注意事项

### 在调度 `horizon:snapshot` 之前，指标仪表盘会一直保持空白

运行 `horizon` artisan 命令不会自动填充指标。指标图基于快照构建，因此必须通过 Laravel 的调度器安排 `horizon:snapshot` 每 5 分钟运行一次。

### 在调度器中注册快照，而不是手动运行它

单次手动运行会暂时填充仪表盘，但不会保持其更新。请在 `"horizon metrics snapshot"` 中搜索确切的调度器注册语法，该语法在 Laravel 10 和 11+ 之间有所不同。

### `metrics.trim_snapshots` 是快照计数，而不是时间长度

`config/horizon.php` 中的 `trim_snapshots.job` 和 `trim_snapshots.queue` 值是要保留的快照数量，而不是分钟数或小时数。默认在 5 分钟间隔下保留 24 个快照，可提供 2 小时历史记录。增大该值可以保留更多历史记录，但会增加 Redis 内存使用量。