# 测试最佳实践

## 优先使用 `LazilyRefreshDatabase` 而非 `RefreshDatabase`

`RefreshDatabase` 每个进程迁移一次，并将每个测试包裹在回滚事务中。如果架构已是最新，`LazilyRefreshDatabase` 甚至会跳过第一次迁移。

## 使用模型断言而非原始数据库断言

错误：`$this->assertDatabaseHas('users', ['id' => $user->id]);`

正确：`$this->assertModelExists($user);`

更具表现力、类型安全，且失败时提供更清晰的消息。

## 使用工厂状态与序列

命名状态让测试具有自文档性。序列消除重复的设置。

错误：`User::factory()->create(['email_verified_at' => null]);`

正确：`User::factory()->unverified()->create();`

## 使用 `Exceptions::fake()` 断言异常上报

不要使用 `withoutExceptionHandling()`，而应使用 `Exceptions::fake()` 来断言在请求正常完成的同时报告了正确的异常。

## 在工厂设置后调用 `Event::fake()`

模型工厂依赖模型事件（例如 `creating` 生成 UUID）。在工厂调用之前调用 `Event::fake()` 会使这些事件静默，从而产生损坏的模型。

错误：`Event::fake(); $user = User::factory()->create();`

正确：`$user = User::factory()->create(); Event::fake();`

## 使用 `recycle()` 在多个工厂之间共享关联实例

如果没有 `recycle()`，嵌套工厂会为同一概念实体创建单独的实例。

```php
Ticket::factory()
    ->recycle(Airline::factory()->create())
    ->create();
```