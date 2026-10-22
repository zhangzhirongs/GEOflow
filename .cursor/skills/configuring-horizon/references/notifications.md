# 通知与警报

## 在哪里找到它

使用 `search-docs` 搜索：
- 有关 Horizon 内置通知路由辅助方法的说明，请参阅 `"horizon notifications"`
- 有关 LongWaitDetected 事件的详细信息，请参阅 `"horizon long wait detected"`

## 注意事项

### `config/horizon.php` 中的 `waits` 控制 LongWaitDetected 阈值

`waits` 数组（例如 `'redis:default' => 60`）定义任务在队列中等待多少秒后 Horizon 触发 `LongWaitDetected` 事件。此值在配置文件中设置，而不是在 Horizon 的通知路由中设置。如果警报触发过于频繁或过晚，请调整 `waits`，而不是调整路由配置。

### 在 `HorizonServiceProvider` 中使用 Horizon 的内置通知路由

在 `App\Providers\HorizonServiceProvider` 的 `boot()` 方法中，使用 `Horizon::routeMailNotificationsTo()`、`Horizon::routeSlackNotificationsTo()` 或 `Horizon::routeSmsNotificationsTo()` 配置通知。Horizon 已将 `LongWaitDetected` 连接到其通知发送器，因此文档中的设置是通知路由，而不是手动注册监听器。

### 失败任务警报与 Horizon 文档中记录的通知路由相互独立

Horizon 12.x 文档涵盖内置的长时间等待通知。不要假设文档在 `HorizonServiceProvider` 中提供了 `JobFailed` 监听器示例。如果用户需要失败任务告警，应将其视为自定义队列事件处理，并查阅队列文档，而不是 Horizon 的通知路由 API。