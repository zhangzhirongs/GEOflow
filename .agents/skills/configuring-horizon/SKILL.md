---
name: configuring-horizon
description: 每当用户在 Laravel 上下文中按名称提及 Horizon 时，使用此技能。涵盖完整的 Horizon 生命周期：安装 Horizon（horizon:install、Sail 设置）、配置 config/horizon.php（supervisor 块、队列分配、平衡策略、minProcesses/maxProcesses）、修复仪表板（通过 Gate::define viewHorizon 授权、空白指标、horizon:snapshot 调度），以及排查生产问题（worker 崩溃、超时链顺序、LongWaitDetected 通知、waits 配置）。还涵盖作业标记和静默。不要用于没有 Horizon 的通用 Laravel 队列、SQS 或数据库驱动、独立 Redis 设置、Linux supervisord、Telescope 或作业批处理。
license: MIT
metadata:
  author: laravel
  display_name: Horizon 队列配置
---

# Horizon 队列配置

## 文档

使用 `search-docs` 获取详细的 Horizon 模式和文档，涵盖配置、监督进程、负载均衡、仪表盘授权、标签、通知、指标和部署。

如需针对特定主题的深入指导，请在实施前阅读相关参考文件：

- `references/supervisors.md` 涵盖 supervisor 块、负载均衡策略、多队列设置和自动扩缩容
- `references/notifications.md` 涵盖 LongWaitDetected 警报、通知路由和 `waits` 配置
- `references/tags.md` 涵盖任务标记、仪表盘过滤以及静默嘈杂任务
- `references/metrics.md` 涵盖空白指标仪表盘、快照调度和保留配置

## 基本用法

### 安装

```bash
php artisan horizon:install
```

### Supervisor 配置

在 `config/horizon.php` 中定义 supervisor。`environments` 数组会合并到 `defaults` 中，而不会替换整个 supervisor 块：

<!-- Supervisor 配置 -->
```php
'defaults' => [
    'supervisor-1' => [
        'connection' => 'redis',
        'queue' => ['default'],
        'balance' => 'auto',
        'minProcesses' => 1,
        'maxProcesses' => 10,
        'tries' => 3,
    ],
],

'environments' => [
    'production' => [
        'supervisor-1' => ['maxProcesses' => 20, 'balanceCooldown' => 3],
    ],
    'local' => [
        'supervisor-1' => ['maxProcesses' => 2],
    ],
],
```

### 仪表板授权

在 `App\Providers\HorizonServiceProvider` 中限制访问：

<!-- 仪表盘门控 -->
```php
protected function gate(): void
{
    Gate::define('viewHorizon', function (User $user) {
        return $user->is_admin;
    });
}
```

## 验证

1. 运行 `php artisan horizon` 并访问 `/horizon`
2. 确认仪表盘访问权限已按预期受限
3. 检查调度`horizon:snapshot`后指标是否正常填充

## 常见陷阱

- Horizon 仅适用于 Redis 队列驱动。不支持 database 和 SQS 等其他驱动。
- 不支持 Redis Cluster。Horizon 需要独立的 Redis 连接。
- 在进行更改之前，始终检查 `config/horizon.php`，以了解当前的 supervisor 和环境配置。
- `environments` 数组仅覆盖你指定的键。它会合并到 `defaults` 中，而不会替换它。
- 超时链必须按顺序设置：作业 `timeout` 小于 supervisor `timeout` 小于 `retry_after`。顺序错误可能导致作业在 Horizon 完成超时处理之前被重试。
- 在调度 `horizon:snapshot` 之前，指标仪表板会一直空白。仅运行 `php artisan horizon` 不会填充指标。
- 要获取最新的 Horizon 文档，始终使用 `search-docs`，而不是仅依赖此技能。