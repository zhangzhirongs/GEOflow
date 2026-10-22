# 队列与任务最佳实践

## 将 `retry_after` 设置为大于 `timeout`

如果 `retry_after` 短于任务的 `timeout`，队列 worker 会在任务仍在运行时重新派发该任务，从而导致重复执行。

不正确（`retry_after` ≤ `timeout`）：
```php
class ProcessReport implements ShouldQueue
{
    public $timeout = 120;
}

// config/queue.php — retry_after: 90 ← 作业仍在运行时就被重试！
```

正确（`retry_after` > `timeout`）：
```php
class ProcessReport implements ShouldQueue
{
    public $timeout = 120;
}

// config/queue.php — retry_after: 180 ← 安全地长于任何作业超时
```

## 使用指数退避

在重试之间使用逐步延长的延迟，以避免反复冲击故障服务。

错误（固定重试间隔）：
```php
class SyncWithStripe implements ShouldQueue
{
    public $tries = 3;
    // 默认：立即重试，使 API 不堪重负
}
```

正确（指数退避）：
```php
class SyncWithStripe implements ShouldQueue
{
    public $tries = 3;
    public $backoff = [1, 5, 10];
}
```

## 实现 `ShouldBeUnique`

防止重复处理作业。

```php
class GenerateInvoice implements ShouldQueue, ShouldBeUnique
{
    public function uniqueId(): string
    {
        return $this->order->id;
    }

    public $uniqueFor = 3600;
}
```

## 始终实现 `failed()`

显式处理错误，不要依赖静默失败。

```php
public function failed(?Throwable $exception): void
{
    $this->podcast->update(['status' => 'failed']);
    Log::error('Processing failed', ['id' => $this->podcast->id, 'error' => $exception->getMessage()]);
}
```

## 在作业中对外部 API 调用进行限流

使用 `RateLimited` 中间件对调用第三方 API 的作业进行限流。

```php
public function middleware(): array
{
    return [new RateLimited('external-api')];
}
```

## 批量处理相关作业

当作业应一起成功或一起失败时，使用 `Bus::batch()`。

```php
Bus::batch([
    new ImportCsvChunk($chunk1),
    new ImportCsvChunk($chunk2),
])
->then(fn (Batch $batch) => Notification::send($user, new ImportComplete))
->catch(fn (Batch $batch, Throwable $e) => Log::error('Batch failed'))
->dispatch();
```

## `retryUntil()` 需要 `$tries = 0`

使用基于时间的重试限制时，设置 `$tries = 0` 以避免过早失败。

```php
public $tries = 0;

public function retryUntil(): \DateTimeInterface
{
    return now()->addHours(4);
}
```

## 使用 `ShouldBeUniqueUntilProcessing` 提前释放锁

`ShouldBeUnique` 持有锁直到任务完成。`ShouldBeUniqueUntilProcessing` 在处理开始时释放锁，允许新实例排队。

```php
class UpdateSearchIndex implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    // 锁在处理开始时释放，而不是在完成时释放
}
```

## 对复杂队列场景使用 Horizon

当你需要监控、自动扩缩容、失败跟踪或具有不同优先级的多个队列时，使用 Laravel Horizon。

```php
// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['high', 'default', 'low'],
            'balance' => 'auto',
            'minProcesses' => 1,
            'maxProcesses' => 10,
            'tries' => 3,
        ],
    ],
],
```