# Eloquent 最佳实践

## 使用正确的关系类型

使用 `hasMany`、`belongsTo`、`morphMany` 等，并提供正确的返回类型提示。

```php
public function comments(): HasMany
{
    return $this->hasMany(Comment::class);
}

public function author(): BelongsTo
{
    return $this->belongsTo(User::class, 'user_id');
}
```

## 使用本地作用域实现可复用查询

将可复用的查询约束提取到本地作用域中，以避免重复。

错误：
```php
$active = User::where('verified', true)->whereNotNull('activated_at')->get();
$articles = Article::whereHas('user', function ($q) {
    $q->where('verified', true)->whereNotNull('activated_at');
})->get();
```

正确：
```php
public function scopeActive(Builder $query): Builder
{
    return $query->where('verified', true)->whereNotNull('activated_at');
}

// 用量
$active = User::active()->get();
$articles = Article::whereHas('user', fn ($q) => $q->active())->get();
```

## 谨慎应用全局作用域

全局作用域会静默修改该模型上的每个查询，从而使调试变得困难。优先使用局部作用域，仅将全局作用域保留给真正普遍适用的约束，如软删除或多租户。

错误（条件过滤器使用全局作用域）：
```php
class PublishedScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('published', true);
    }
}
// 现在，管理面板、报表和后台作业都会静默跳过草稿
```

正确（你选择启用的本地作用域）：
```php
public function scopePublished(Builder $query): Builder
{
    return $query->where('published', true);
}

Post::published()->paginate(); // Explicit
Post::paginate(); // Admin sees all
```

## 定义属性转换

使用 `casts()` 方法（或遵循项目约定的 `$casts` 属性）进行自动类型转换。

```php
protected function casts(): array
{
    return [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'total' => 'decimal:2',
    ];
}
```

## 正确转换日期列

始终转换日期列。在模板中使用 Carbon 实例，而不是手动格式化字符串。

错误：
```blade
{{ Carbon::createFromFormat('Y-d-m H-i', $order->ordered_at)->toDateString() }}
```

正确：
```php
protected function casts(): array
{
    return [
        'ordered_at' => 'datetime',
    ];
}
```

```blade
{{ $order->ordered_at->toDateString() }}
{{ $order->ordered_at->format('m-d') }}
```

## 使用 `whereBelongsTo()` 进行关联查询

比手动指定外键更简洁。

错误：
```php
Post::where('user_id', $user->id)->get();
```

正确：
```php
Post::whereBelongsTo($user)->get();
Post::whereBelongsTo($user, 'author')->get();
```

## 避免在查询中硬编码表名

绝不要在原始查询、连接或子查询中使用字符串字面量作为表名。硬编码表名会导致无法找到模型被使用的所有位置，并破坏重构（例如，重命名表需要在每一个原始字符串中逐一查找）。

错误：
```php
DB::table('users')->where('active', true)->get();

$query->join('companies', 'companies.id', '=', 'users.company_id');

DB::select('SELECT * FROM orders WHERE status = ?', ['pending']);
```

正确——引用模型的表：
```php
DB::table((new User)->getTable())->where('active', true)->get();

// 更好的做法——使用 Eloquent 或查询构建器而不是原始 SQL
User::where('active', true)->get();
Order::where('status', 'pending')->get();
```

尽可能优先使用 Eloquent 查询和关系，而不是 `DB::table()`——它们已经引用了模型的表。当 `DB::table()` 或原始连接不可避免时，始终使用 `(new Model)->getTable()` 以保持引用可追溯。

**例外 — 迁移：** 在迁移中，通过 `DB::table('settings')` 硬编码表名是可接受且首选的做法。模型会随时间变化，但迁移是冻结的快照——引用后来被重命名或删除的模型会破坏迁移。