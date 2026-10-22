---
name: laravel-best-practices
description: 在编写、审查或重构 Laravel PHP 代码时始终应用此技能。这包括创建或修改控制器、模型、迁移、表单请求、策略、作业、计划命令、服务类和 Eloquent 查询。触发条件包括 N+1 和查询性能问题、缓存策略、授权和安全模式、验证、错误处理、队列和作业配置、路由定义以及架构决策。也用于 Laravel 代码审查和重构现有 Laravel 代码以遵循最佳实践。涵盖任何涉及 Laravel 后端 PHP 代码模式的任务。
license: MIT
metadata:
  author: laravel
  display_name: Laravel 最佳实践
---

# Laravel 最佳实践

Laravel 最佳实践，按影响优先级排序。每条规则都说明要做什么以及为什么。对于精确的 API 语法，请使用`search-docs`验证。

## 一致性优先

在应用任何规则之前，先检查应用已经做了什么。Laravel 提供多种有效方法——最佳选择是代码库已经采用的方法，即使另一种模式在理论上更好。不一致比次优模式更糟。

检查同级文件、相关控制器、模型或测试中是否已有既定模式。如果存在，请遵循它——不要引入第二种方式。这些规则是在尚无模式时的默认规则，而不是覆盖规则。

## 快速参考

### 1. 数据库性能 → `rules/db-performance.md`

- 使用`with()`进行预加载，以防止 N+1 查询
- 在开发环境中启用`Model::preventLazyLoading()`
- 仅选择需要的列，避免 `SELECT *`
- `chunk()` / `chunkById()` 用于大型数据集
- 为 `WHERE`、`ORDER BY`、`JOIN` 中使用的列建立索引
- `withCount()` 而不是加载关联来计数
- `cursor()` 用于内存高效的只读迭代
- 切勿在 Blade 模板中执行查询

### 2. 高级查询模式 → `rules/advanced-queries.md`

- 对于单个值，使用 `addSelect()` 子查询，而不是预加载整个 has-many 关系
- 通过子查询 FK + `belongsTo` 实现动态关系
- 对多个计数查询使用条件聚合（`CASE WHEN` 在 `selectRaw` 中）
- `setRelation()` 以防止循环 N+1 查询
- `whereIn` + `pluck()` 取代 `whereHas`，以获得更好的索引使用
- 两个简单查询可能胜过一个复杂查询
- 匹配 `orderBy` 列顺序的复合索引
- 在 `orderBy` 中使用相关子查询进行 has-many 排序（避免连接）

### 3. 安全 → `rules/security.md`

- 在每个模型上定义 `$fillable` 或 `$guarded`，通过策略或门禁授权每个操作
- 不得使用包含用户输入的原始 SQL——请使用 Eloquent 或查询构建器
- `{{ }}` 用于输出转义，`@csrf` 用于所有 POST/PUT/DELETE 表单，`throttle` 用于认证和 API 路由
- 验证文件上传的 MIME 类型、扩展名和大小
- 切勿提交 `.env`，机密信息请使用 `config()`，敏感数据库字段请使用 `encrypted` 类型转换

### 4. 缓存 → `rules/caching.md`

- `Cache::remember()` 优于手动 get/put
- `Cache::flexible()` 用于高流量数据的 stale-while-revalidate
- `Cache::memo()` 用于避免请求内的冗余缓存命中
- 用于使相关组失效的缓存标签
- `Cache::add()` 用于原子条件写入
- 使用 `once()` 按请求或按对象生命周期进行记忆化
- `Cache::lock()` / `lockForUpdate()` 用于竞态条件
- 生产环境中的故障转移缓存存储

### 5. Eloquent 模式 → `rules/eloquent.md`

- 使用返回类型提示修正关系类型
- 用于可复用查询约束的局部作用域
- 谨慎使用全局作用域——记录它们的存在
- `casts()` 方法中的属性类型转换
- 转换日期列，在模板中使用 Carbon 实例
- `whereBelongsTo($model)` 以获得更简洁的查询
- 切勿硬编码表名——请使用 `(new Model)->getTable()` 或 Eloquent 查询

### 6. 验证与表单 → `rules/validation.md`

- 使用 Form Request 类，而不是内联验证
- 新代码使用数组表示法 `['required', 'email']`；遵循现有约定
- 仅使用 `$request->validated()` — 绝不使用 `$request->all()`
- 使用 `Rule::when()` 进行条件验证
- 使用 `after()` 而不是 `withValidator()`

### 7. 配置 → `rules/config.md`

- `env()` 仅限在配置文件内部使用
- `App::environment()` 或 `app()->isProduction()`
- 使用配置、语言文件和常量，而非硬编码文本

### 8. 测试模式 → `rules/testing.md`

- 为了速度，使用 `LazilyRefreshDatabase` 而非 `RefreshDatabase`
- `assertModelExists()` 优先于原始 `assertDatabaseHas()`
- 工厂状态和序列优先于手动覆盖
- 使用假对象（`Event::fake()`、`Exceptions::fake()` 等）——但始终在工厂设置之后使用，而不是之前
- 使用 `recycle()` 在多个工厂之间共享关系实例

### 9. 队列与作业模式 → `rules/queue-jobs.md`

- `retry_after` 必须大于任务 `timeout`；使用指数退避 `[1, 5, 10]`
- 使用 `ShouldBeUnique` 防止重复；使用 `ShouldBeUniqueUntilProcessing` 提前释放锁
- 始终实现 `failed()`；使用 `retryUntil()` 时，设置 `$tries = 0`
- 外部 API 调用使用 `RateLimited` 中间件；相关任务使用 `Bus::batch()`
- 适用于复杂多队列场景的 Horizon

### 10. 路由与控制器 → `rules/routing.md`

- 隐式路由模型绑定
- 嵌套资源的作用域绑定
- `Route::resource()` 或 `apiResource()`
- 少于 10 行的方法——提取到操作/服务中
- 通过类型提示 Form Requests 实现自动验证

### 11. HTTP 客户端 → `rules/http-client.md`

- 每个请求上都显式包含 `timeout` 和 `connectTimeout`
- 对外部 API 使用带指数退避的 `retry()`
- 检查响应状态或使用 `throw()`
- 使用 `Http::pool()` 处理并发的独立请求
- 测试中的 `Http::fake()` 和 `preventStrayRequests()`

### 12. 事件、通知与邮件 → `rules/events-notifications.md`, `rules/mail.md`

- 优先采用事件发现而非手动注册；在生产环境中使用`event:cache`
- 事务内使用 `ShouldDispatchAfterCommit` / `afterCommit()`
- 使用 `ShouldQueue` 将通知和邮件类加入队列
- 针对非用户接收者的按需通知
- `HasLocalePreference` 在可通知模型上
- 队列邮件使用 `assertQueued()` 而非 `assertSent()`
- 用于事务性电子邮件的 Markdown 可邮寄类

### 13. 错误处理 → `rules/error-handling.md`

- 在异常类上或 `bootstrap/app.php` 中使用 `report()`/`render()`——遵循现有模式
- 对于绝不应记录日志的异常，使用 `ShouldntReport`
- 对高吞吐量异常进行限流，以保护日志接收端
- `dontReportDuplicates()`：用于多重捕获场景
- 强制 API 路由进行 JSON 渲染
- 通过异常类上的 `context()` 提供结构化上下文

### 14. 任务调度 → `rules/scheduling.md`

- `withoutOverlapping()` 用于时长可变的任务
- 在多服务器部署中使用 `onOneServer()`
- `runInBackground()` 用于并发的长任务
- `environments()`：用于限制到适当的环境
- `takeUntilTimeout()` 用于有时间限制的处理
- 用于共享配置的调度组

### 15. 架构 → `rules/architecture.md`

- 单一职责的 Action 类；优先使用依赖注入而非 `app()` 辅助函数
- 优先使用官方 Laravel 包并遵循约定，不要覆盖默认值
- 默认为 `ORDER BY id DESC` 或 `created_at DESC`；为保障 UTF-8 安全使用 `mb_*`
- `defer()` 用于响应后工作；`Context` 用于请求作用域数据；`Concurrency::run()` 用于并行执行

### 16. 迁移 → `rules/migrations.md`

- 使用 `php artisan make:migration` 生成迁移
- `constrained()` 用于外键
- 切勿修改已在生产环境运行过的数据库迁移
- 在迁移中添加索引，而不是事后补加
- 在模型 `$attributes` 中镜像列默认值
- 默认可逆的 `down()`；对有意不可逆的更改采用前向修复迁移
- 每个迁移只关注一件事——绝不要混合 DDL 和 DML

### 17. 集合 → `rules/collections.md`

- 简单集合操作的高阶消息
- `cursor()` 与 `lazy()` —— 根据关系需求选择
- 在迭代过程中更新记录时的 `lazyById()`
- `toQuery()` 用于对集合进行批量操作

### 18. Blade 与视图 → `rules/blade-views.md`

- 组件模板中的 `$attributes->merge()`
- 基于 `@include` 的 Blade 组件；`@pushOnce` 用于各组件脚本
- 用于共享视图数据的 View Composers
- `@aware` 用于深层嵌套的组件 props

### 19. 约定与风格 → `rules/style.md`

- 所有实体遵循 Laravel 命名约定
- 优先使用 Laravel 辅助函数（`Str`、`Arr`、`Number`、`Uri`、`Str::of()`、`$request->string()`），而不是原生 PHP 函数
- Blade 中不得包含 JS/CSS，PHP 类中不得包含 HTML
- 代码应具备可读性；仅在配置文件中使用注释

## 如何应用

始终使用子代理读取规则文件并探索此技能的内容。

1. 识别文件类型并选择相关章节（例如，迁移 → §16，控制器 → §1、§3、§5、§6、§10）
2. 检查同级文件中的现有模式——遵循一致性优先原则，优先遵循这些模式
3. 使用 `search-docs` 验证已安装 Laravel 版本的 API 语法