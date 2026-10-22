# 数据库性能最佳实践

## 始终预加载关联关系

延迟加载会导致 N+1 查询问题——每次循环迭代都会执行一次查询。应始终使用 `with()` 预先加载关联关系。

错误（N+1 — 执行 1 + N 次查询）：
```php
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->author->name;
}
```

正确（总共 2 个查询）：
```php
$posts = Post::with('author')->get();
foreach ($posts as $post) {
    echo $post->author->name;
}
```

限制预加载仅选择所需列（始终包含外键）：

```php
$users = User::with(['posts' => function ($query) {
    $query->select('id', 'user_id', 'title')
          ->where('published', true)
          ->latest()
          ->limit(10);
}])->get();
```

## 在开发中防止延迟加载

在 `AppServiceProvider::boot()` 中启用此项，以便在开发期间捕获 N+1 问题。

```php
public function boot(): void
{
    Model::preventLazyLoading(! app()->isProduction());
}
```

当访问未预加载的关系时抛出 `LazyLoadingViolationException`。

## 仅选择所需的列

避免`SELECT *`——尤其是当表包含大型文本或 JSON 列时。

错误：
```php
$posts = Post::with('author')->get();
```

正确：
```php
$posts = Post::select('id', 'title', 'user_id', 'created_at')
    ->with(['author:id,name,avatar'])
    ->get();
```

在预加载关系上选择列时，始终包含外键列，否则关系将无法匹配。

## 分块处理大型数据集

绝不要一次加载数千条记录。批处理时使用分块。

错误：
```php
$users = User::all();
foreach ($users as $user) {
    $user->notify(new WeeklyDigest);
}
```

正确：
```php
User::where('subscribed', true)->chunk(200, function ($users) {
    foreach ($users as $user) {
        $user->notify(new WeeklyDigest);
    }
});
```

在迭代期间修改记录时使用 `chunkById()`——标准 `chunk()` 使用 OFFSET，而行发生变化时 OFFSET 会偏移：

```php
User::where('active', false)->chunkById(200, function ($users) {
    $users->each->delete();
});
```

## 添加数据库索引

为出现在 `WHERE`、`ORDER BY`、`JOIN` 和 `GROUP BY` 子句中的列建立索引。

错误：
```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->string('status');
    $table->timestamps();
});
```

正确：
```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->index()->constrained();
    $table->string('status')->index();
    $table->timestamps();
    $table->index(['status', 'created_at']);
});
```

为常见查询模式添加复合索引（例如 `WHERE status = ? ORDER BY created_at`）。

## 使用 `withCount()` 统计关联数量

绝不要仅仅为了计数而加载整个集合。

错误：
```php
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->comments->count();
}
```

正确：
```php
$posts = Post::withCount('comments')->get();
foreach ($posts as $post) {
    echo $post->comments_count;
}
```

条件计数：

```php
$posts = Post::withCount([
    'comments',
    'comments as approved_comments_count' => function ($query) {
        $query->where('approved', true);
    },
])->get();
```

## 使用 `cursor()` 进行内存高效的迭代

对于大型结果集的只读迭代，`cursor()` 会通过 PHP 生成器一次加载一条记录。

错误：
```php
$users = User::where('active', true)->get();
```

正确：
```php
foreach (User::where('active', true)->cursor() as $user) {
    ProcessUser::dispatch($user->id);
}
```

使用 `cursor()` 进行只读迭代。修改记录时使用 `chunk()` / `chunkById()`。

## Blade 模板中禁止查询

绝不要在 Blade 模板中执行查询。从控制器传递数据。

错误：
```blade
@foreach (User::all() as $user)
    {{ $user->profile->name }}
@endforeach
```

正确：
```php
// 控制器
$users = User::with('profile')->get();
return view('users.index', compact('users'));
```

```blade
@foreach ($users as $user)
    {{ $user->profile->name }}
@endforeach
```