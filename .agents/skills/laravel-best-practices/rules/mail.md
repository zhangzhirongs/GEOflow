# 邮件最佳实践

## 在 Mailable 类上实现 `ShouldQueue`

无论邮件类如何分发，都会将队列设为默认方式。无需在每个调用点记住 `Mail::queue()`——`Mail::send()` 也会将其排队。

## 在事务内对 Mailables 使用 `afterCommit()`

在事务内派发的队列邮件可能在提交前被处理。请在构造函数中使用 `$this->afterCommit()`。

## 对于队列邮件（Queued Mailables），应使用 `assertQueued()` 而非 `assertSent()`

`Mail::assertSent()` 只捕获同步邮件。排队的 Mailable 会使 `assertSent` 失败，并给出“你是想使用 assertQueued() 吗？”的提示。

错误：当 mailable 实现 `ShouldQueue` 时，`Mail::assertSent(OrderShipped::class);`。

正确：`Mail::assertQueued(OrderShipped::class);`

## 使用 Markdown 可邮寄类发送事务性邮件

Markdown 邮件类会自动生成 HTML 和纯文本两个版本，使用响应式组件，并允许全局样式定制。使用 `--markdown` 标志生成。

## 将内容测试与发送测试分离

内容测试：直接实例化 mailable，调用 `assertSeeInHtml()`。
发送测试：使用 `Mail::fake()` 和 `assertSent()`/`assertQueued()`。
不要将它们混在一起——这会使关注点混杂并使测试变得脆弱。