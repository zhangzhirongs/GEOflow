# 标签与静默

## 在哪里找到它

使用 `search-docs` 搜索：
- 有关标签 API 和自动标签行为的说明，请参阅 `"horizon tags"`
- 有关 `silenced` 和 `silenced_tags` 配置选项的说明，请参阅 `"horizon silenced jobs"`

## 注意事项

### Eloquent 模型任务会自动打上标签，无需任何额外代码

如果任务的构造函数接受 Eloquent 模型实例，Horizon 会自动用 `ModelClass:id` 标记该任务，例如 `App\Models\User:42`。这些标签无需修改任务类即可在仪表板中筛选。仅当需要自动标记之外的自定义标签时，才添加 `tags()` 方法。

### `silenced` 会从仪表盘已完成列表中隐藏任务，但不会阻止它们继续运行

将作业类添加到 `config/horizon.php` 中的 `silenced` 数组会将其从已完成作业视图中移除。该作业仍会正常运行。这是仪表盘的降噪工具，而不是禁用作业的方法。

### `silenced_tags` 会从已完成列表中隐藏所有带有匹配标签的任务

任何带有匹配标签字符串的作业都会从已完成作业视图中隐藏。这适用于静默某一类作业（例如所有标记为 `notifications` 的作业），而不是静默特定的类。