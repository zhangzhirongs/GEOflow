# 事件与通知最佳实践

## 依赖事件发现

Laravel 通过读取 `handle(EventType $event)` 类型提示来自动发现监听器。无需在 `AppServiceProvider` 中手动注册。

## 在生产部署中运行 `event:cache`

在开发环境中，事件发现会按请求扫描文件系统。在生产环境中缓存它：`php artisan optimize` 或 `php artisan event:cache`。

## 在事务内使用 `ShouldDispatchAfterCommit`

如果没有它，排队的监听器可能会在数据库事务提交之前处理，读取尚不存在的数据。

```php
class OrderShipped implements ShouldDispatchAfterCommit {}
```

## 始终将通知加入队列

通知经常调用外部 API（电子邮件、短信、Slack）。如果没有 `ShouldQueue`，它们会阻塞 HTTP 响应。

```php
class InvoicePaid extends Notification implements ShouldQueue
{
    use Queueable;
}
```

## 在事务中对通知使用 `afterCommit()`

与事件相同的竞态条件——调用 `afterCommit()` 将派发延迟到事务提交之后。

```php
$user->notify((new InvoicePaid($invoice))->afterCommit());
```

## 将通知渠道路由到专用队列

邮件通知和数据库通知具有不同的优先级。使用 `viaQueues()` 将它们路由到不同队列。

## 对非用户收件人使用按需通知

避免创建虚拟模型来向任意地址发送通知。

```php
Notification::route('mail', 'admin@example.com')->notify(new SystemAlert());
```

## 在 Notifiable 模型上实现 `HasLocalePreference`

Laravel 会自动为所有通知和邮件类使用用户首选语言环境——无需每次调用都设置 `locale()`。