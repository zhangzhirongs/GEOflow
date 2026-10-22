# 架构最佳实践

## 单一用途的操作类

将离散的业务操作提取为可调用的 Action 类。

```php
class CreateOrderAction
{
    public function __construct(private InventoryService $inventory) {}

    public function execute(array $data): Order
    {
        $order = Order::create($data);
        $this->inventory->reserve($order);

        return $order;
    }
}
```

## 使用依赖注入

始终使用构造函数注入。避免在类内使用 `app()` 或 `resolve()`。

错误：
```php
class OrderController extends Controller
{
    public function store(StoreOrderRequest $request)
    {
        $service = app(OrderService::class);

        return $service->create($request->validated());
    }
}
```

正确：
```php
class OrderController extends Controller
{
    public function __construct(private OrderService $service) {}

    public function store(StoreOrderRequest $request)
    {
        return $this->service->create($request->validated());
    }
}
```

## 面向接口编码

在系统边界（支付网关、通知渠道、外部 API）依赖契约，以实现可测试性和可替换性。

错误（具体依赖）：
```php
class OrderService
{
    public function __construct(private StripeGateway $gateway) {}
}
```

正确（接口依赖）：
```php
interface PaymentGateway
{
    public function charge(int $amount, string $customerId): PaymentResult;
}

class OrderService
{
    public function __construct(private PaymentGateway $gateway) {}
}
```

在服务提供者中绑定：

```php
$this->app->bind(PaymentGateway::class, StripeGateway::class);
```

## 默认按降序排序

未指定显式顺序时，按 `id` 或 `created_at` 降序排序。没有显式的 `ORDER BY` 时，行顺序未定义。

错误：
```php
$posts = Post::paginate();
```

正确：
```php
$posts = Post::latest()->paginate();
```

## 使用原子锁应对竞态条件

防止与 `Cache::lock()` 或 `lockForUpdate()` 出现竞态条件。

```php
Cache::lock('order-processing-'.$order->id, 10)->block(5, function () use ($order) {
    $order->process();
});

// 或在查询层面
$product = Product::where('id', $id)->lockForUpdate()->first();
```

## 使用 `mb_*` 字符串函数

当不存在 Laravel 辅助函数时，为 UTF-8 安全性优先使用 `mb_strlen`、`mb_strtolower` 等。标准 PHP 字符串函数计算的是字节数，而不是字符数。

错误：
```php
strlen('José');          // 5 (bytes, not characters)
strtolower('MÜNCHEN');  // 'mÜnchen' — fails on multibyte
```

正确：
```php
mb_strlen('José');             // 4 (characters)
mb_strtolower('MÜNCHEN');     // 'münchen'

// 可用时优先使用 Laravel 的 Str 辅助函数
Str::length('José');          // 4
Str::lower('MÜNCHEN');        // 'münchen'
```

## 使用 `defer()` 执行响应后工作

对于不需要在崩溃后继续存在的轻量任务（日志记录、分析、清理），请使用 `defer()`，而不是派发作业。回调会在 HTTP 响应发送后运行——没有队列开销。

错误（琐碎工作带来作业开销）：
```php
dispatch(new LogPageView($page));
```

正确（在响应后运行，同一进程）：
```php
defer(fn () => PageView::create(['page_id' => $page->id, 'user_id' => auth()->id()]));
```

当工作必须在进程崩溃后仍能存续或需要重试逻辑时，使用任务。对于即发即忘的工作，使用 `defer()`。

## 使用 `Context` 存储请求作用域数据

`Context` facade 会在整个请求生命周期中透传数据——中间件、控制器、任务、日志——无需手动传递参数。

```php
// 在中间件中
Context::add('tenant_id', $request->header('X-Tenant-ID'));

// 之后在任何地方——控制器、作业、日志上下文
$tenantId = Context::get('tenant_id');
```

上下文数据会自动传播到队列任务，并包含在日志条目中。对于应在队列任务中可用但要从日志上下文中排除的敏感数据，使用 `Context::addHidden()`。如果数据不得离开当前进程，请不要将其存储在 `Context` 中。

## 使用 `Concurrency::run()` 进行并行执行

使用子进程并行运行独立操作——无需异步库。

```php
use Illuminate\Support\Facades\Concurrency;

[$users, $orders] = Concurrency::run([
    fn () => User::count(),
    fn () => Order::where('status', 'pending')->count(),
]);
```

每个闭包都在独立进程中运行，并拥有完整的 Laravel 访问权限。用于原本会顺序执行的独立数据库查询、API 调用或计算。

## 约定优于配置

遵循 Laravel 约定。不要不必要地覆盖默认值。

错误：
```php
class Customer extends Model
{
    protected $table = 'Customer';
    protected $primaryKey = 'customer_id';

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_customer', 'customer_id', 'role_id');
    }
}
```

正确：
```php
class Customer extends Model
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
```