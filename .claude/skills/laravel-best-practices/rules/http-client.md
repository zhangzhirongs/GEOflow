# HTTP 客户端最佳实践

## 始终设置显式超时

默认超时是 30 秒——对大多数 API 调用来说太长。始终设置显式的 `timeout` 和 `connectTimeout` 以快速失败。

错误：
```php
$response = Http::get('https://api.example.com/users');
```

正确：
```php
$response = Http::timeout(5)
    ->connectTimeout(3)
    ->get('https://api.example.com/users');
```

对于特定服务的客户端，请在宏中定义超时：

```php
Http::macro('github', function () {
    return Http::baseUrl('https://api.github.com')
        ->timeout(10)
        ->connectTimeout(3)
        ->withToken(config('services.github.token'));
});

$response = Http::github()->get('/repos/laravel/framework');
```

## 对外部 API 使用带退避的重试

外部 API 会出现瞬时故障。使用带有递增延迟的 `retry()`。

错误：
```php
$response = Http::post('https://api.stripe.com/v1/charges', $data);

if ($response->failed()) {
    throw new PaymentFailedException('Charge failed');
}
```

正确：
```php
$response = Http::retry([100, 500, 1000])
    ->timeout(10)
    ->post('https://api.stripe.com/v1/charges', $data);
```

仅对特定错误进行重试：

```php
$response = Http::retry(3, 100, function (Throwable $exception, PendingRequest $request) {
    return $exception instanceof ConnectionException
        || ($exception instanceof RequestException && $exception->response->serverError());
})->post('https://api.example.com/data');
```

## 显式处理错误

HTTP Client 默认不会在 4xx/5xx 时抛出异常。始终检查 status 或使用 `throw()`。

错误：
```php
$response = Http::get('https://api.example.com/users/1');
$user = $response->json(); // Could be an error body
```

正确：
```php
$response = Http::timeout(5)
    ->get('https://api.example.com/users/1')
    ->throw();

$user = $response->json();
```

为实现优雅降级：

```php
$response = Http::get('https://api.example.com/users/1');

if ($response->successful()) {
    return $response->json();
}

if ($response->notFound()) {
    return null;
}

$response->throw();
```

## 使用请求池实现并发请求

进行多个独立 API 调用时，使用 `Http::pool()`，而不是顺序调用。

错误：
```php
$users = Http::get('https://api.example.com/users')->json();
$posts = Http::get('https://api.example.com/posts')->json();
$comments = Http::get('https://api.example.com/comments')->json();
```

正确：
```php
use Illuminate\Http\Client\Pool;

$responses = Http::pool(fn (Pool $pool) => [
    $pool->as('users')->get('https://api.example.com/users'),
    $pool->as('posts')->get('https://api.example.com/posts'),
    $pool->as('comments')->get('https://api.example.com/comments'),
]);

$users = $responses['users']->json();
$posts = $responses['posts']->json();
```

## 测试中的伪造 HTTP 调用

绝不要在测试中发起真实 HTTP 请求。使用 `Http::fake()` 和 `preventStrayRequests()`。

错误：
```php
it('syncs user from API', function () {
    $service = new UserSyncService;
    $service->sync(1); // Hits the real API
});
```

正确：
```php
it('syncs user from API', function () {
    Http::preventStrayRequests();

    Http::fake([
        'api.example.com/users/1' => Http::response([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]),
    ]);

    $service = new UserSyncService;
    $service->sync(1);

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://api.example.com/users/1';
    });
});
```

也应测试失败场景：

```php
Http::fake([
    'api.example.com/*' => Http::failedConnection(),
]);
```