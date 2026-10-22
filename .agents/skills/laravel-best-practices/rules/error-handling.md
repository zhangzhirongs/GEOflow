# 错误处理最佳实践

## 异常报告与渲染

有两种可行方法——选择一种并在整个项目中一致应用。

**与异常类放在一起** — 使行为与异常定义相伴，更容易找到：

```php
class InvalidOrderException extends Exception
{
    public function report(): void { /* custom reporting */ }

    public function render(Request $request): Response
    {
        return response()->view('errors.invalid-order', status: 422);
    }
}
```

**集中在 `bootstrap/app.php` 中** — 所有异常处理都在一处，更容易看清全貌：

```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->report(function (InvalidOrderException $e) { /* ... */ });
    $exceptions->render(function (InvalidOrderException $e, Request $request) {
        return response()->view('errors.invalid-order', status: 422);
    });
})
```

检查现有代码库，并遵循其中已确立的模式。

## 为不应记录日志的异常使用 `ShouldntReport`

比在 `dontReport()` 中列出类更易发现。

```php
class PodcastProcessingException extends Exception implements ShouldntReport {}
```

## 限制高并发异常

单个失败的集成可能淹没错误跟踪。请使用 `throttle()` 按异常类型进行限流。

## 启用 `dontReportDuplicates()`

当在多个 catch 块中调用 `report($e)` 时，防止同一个异常实例被多次记录日志。

## 为 API 路由强制 JSON 错误渲染

Laravel 会自动检测 `Accept: application/json`，但 API 客户端可能不会设置它。请为 API 路由显式声明 JSON 渲染。

```php
$exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
    return $request->is('api/*') || $request->expectsJson();
});
```

## 为异常类添加上下文

通过`context()`方法在源头将结构化数据附加到异常——Laravel 会自动将其包含在日志条目中。

```php
class InvalidOrderException extends Exception
{
    public function context(): array
    {
        return ['order_id' => $this->orderId];
    }
}
```