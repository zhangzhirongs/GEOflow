# 任务调度最佳实践

## 在可变时长任务上使用 `withoutOverlapping()`

如果没有它，长时间运行的任务会在下一个 tick 生成第二个实例，导致重复处理或资源耗尽。

## 在多服务器部署中使用 `onOneServer()`

如果没有它，每台服务器都会同时运行同一任务。需要共享缓存驱动（Redis、数据库、Memcached）。

## 使用 `runInBackground()` 处理并发长任务

默认情况下，同一 tick 上的任务顺序运行。第一个任务较慢会延迟所有后续任务。`runInBackground()`会将它们作为独立进程运行。

## 使用 `environments()` 限制任务

防止在预发布环境中意外执行仅限生产环境的任务（计费、报表）。

```php
Schedule::command('billing:charge')->monthly()->environments(['production']);
```

## 使用 `takeUntilTimeout()` 进行有时间限制的处理

每 15 分钟运行一次且处理无界游标的任务可能与下一次运行重叠。请限制执行时间。

## 使用调度分组共享配置

避免在许多任务中重复`->onOneServer()->timezone('America/New_York')`。

```php
Schedule::daily()
    ->onOneServer()
    ->timezone('America/New_York')
    ->group(function () {
        Schedule::command('emails:send --force');
        Schedule::command('emails:prune');
    });
```