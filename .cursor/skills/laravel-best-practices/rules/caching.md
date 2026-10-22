# 缓存最佳实践

## 使用 `Cache::remember()` 而非手动 Get/Put

更简洁的 cache-aside 模式，可移除样板代码。对于竞态条件使用 `Cache::lock()`。

错误：
```php
$val = Cache::get('stats');
if (! $val) {
    $val = $this->computeStats();
    Cache::put('stats', $val, 60);
}
```

正确：
```php
$val = Cache::remember('stats', 60, fn () => $this->computeStats());
```

## 使用 `Cache::flexible()` 实现陈旧内容优先、后台再验证（Stale-While-Revalidate）

对于高流量键，当缓存过期时，总会有一个用户得到慢响应。`flexible()` 会在后台刷新的同时提供略微陈旧的数据。

错误：`Cache::remember('users', 300, fn () => User::all());`

正确：`Cache::flexible('users', [300, 600], fn () => User::all());`——新鲜期为 5 分钟，最多 10 分钟内可提供过期但仍被服务的数据，通过延迟函数刷新。

## 使用 `Cache::memo()` 避免在单次请求内重复命中

如果同一缓存键在每次请求中被多次读取（例如，一个服务从多个位置被调用），`memo()` 会将解析后的值存储在内存中。

`Cache::memo()->get('settings');` — 5 次调用 = 1 次 Redis 往返，而不是 5 次。

## 使用缓存标签使相关分组失效

没有标签时，使一组条目失效需要跟踪每个键。标签让你可以原子化地刷新。仅适用于 `redis`、`memcached`、`dynamodb`——不适用于 `file` 或 `database`。

```php
Cache::tags(['user-1'])->flush();
```

## 使用 `Cache::add()` 实现原子条件写入

`add()` 仅在键不存在时写入——原子操作，检查与写入之间不存在竞态条件。

错误：`if (! Cache::has('lock')) { Cache::put('lock', true, 10); }`

正确：`Cache::add('lock', true, 10);`

## 使用 `once()` 实现按请求记忆化

`once()` 会在对象的生命周期内（对于闭包则是请求生命周期）记忆化函数的返回值。与 `Cache::memo()` 不同，它完全不会访问缓存存储——纯内存。

```php
public function roles(): Collection
{
    return once(fn () => $this->loadRoles());
}
```

多次调用会返回缓存结果而不会重新执行。对于在每次请求中多次调用的高开销计算，使用 `once()`。如果还需要跨请求缓存，使用 `Cache::memo()`。

## 在生产环境配置故障转移缓存存储

如果 Redis 宕机，应用会自动回退到辅助存储。

```php
'failover' => ['driver' => 'failover', 'stores' => ['redis', 'database']],
```