# 高级查询模式

## 对 Has-Many 关系中的单值使用 `addSelect()` 子查询

不要为单个值（如最新时间戳）预加载整个 has-many 关系，而应通过 `addSelect()` 使用相关子查询。这会在主 SQL 查询中直接获取该值——零额外查询。

```php
public function scopeWithLastLoginAt($query): void
{
    $query->addSelect([
        'last_login_at' => Login::select('created_at')
            ->whereColumn('user_id', 'users.id')
            ->latest()
            ->take(1),
    ])->withCasts(['last_login_at' => 'datetime']);
}
```

## 通过子查询外键创建动态关系

扩展 `addSelect()` 模式，通过子查询获取外键，然后在该虚拟属性上定义 `belongsTo` 关系。这样无需加载整个集合即可提供完全填充的关联模型。

```php
public function lastLogin(): BelongsTo
{
    return $this->belongsTo(Login::class);
}

public function scopeWithLastLogin($query): void
{
    $query->addSelect([
        'last_login_id' => Login::select('id')
            ->whereColumn('user_id', 'users.id')
            ->latest()
            ->take(1),
    ])->with('lastLogin');
}
```

## 使用条件聚合替代多次计数查询

将 N 次独立的 `count()` 查询替换为在 `selectRaw()` 中使用 `CASE WHEN` 的单次查询。当你只需要标量值时，使用 `toBase()` 跳过模型水合。

```php
$statuses = Feature::toBase()
    ->selectRaw("count(case when status = 'Requested' then 1 end) as requested")
    ->selectRaw("count(case when status = 'Planned' then 1 end) as planned")
    ->selectRaw("count(case when status = 'Completed' then 1 end) as completed")
    ->first();
```

## 使用 `setRelation()` 防止循环 N+1

当父模型与其子项一起被预加载，而视图还需要 `$child->parent` 时，使用 `setRelation()` 注入已加载的父模型，而不是让 Eloquent 触发 N 次额外查询。

```php
$feature->load('comments.user');
$feature->comments->each->setRelation('feature', $feature);
```

## 优先使用 `whereIn` + 子查询而非 `whereHas`

`whereHas()` 会发出一个相关 `EXISTS` 子查询，该子查询会按行重新执行。将 `whereIn()` 与 `select('id')` 子查询配合使用，可让数据库改用索引查找，而无需将数据加载到 PHP 内存中。

错误（相关 EXISTS 会逐行重新执行）：

```php
$query->whereHas('company', fn ($q) => $q->where('name', 'like', $term));
```

正确（对索引友好的子查询，无 PHP 内存开销）：

```php
$query->whereIn('company_id', Company::where('name', 'like', $term)->select('id'));
```

## 有时两个简单查询胜过一个大查询

运行一个小的、有针对性的次要查询并通过 `whereIn` 传递其结果，通常比单个复杂的相关子查询或连接更快。当次要查询具有高度选择性并使用自己的索引时，额外的往返是值得的。

## 使用与 `orderBy` 列顺序匹配的复合索引

按多列排序时，创建一个复合索引，其列顺序与 `ORDER BY` 子句相同。单独的单列索引无法组合用于多列排序——在没有复合索引的情况下，数据库将进行 filesort。

```php
// 迁移
$table->index(['last_name', 'first_name']);

// 查询——列顺序必须与索引匹配
User::query()->orderBy('last_name')->orderBy('first_name')->paginate();
```

## 使用关联子查询实现一对多排序

按 has-many 关系中的值排序时，避免使用连接（它们会复制行）。改为在 `orderBy()` 内使用相关子查询，并配合 `addSelect` 作用域进行预加载。

```php
public function scopeOrderByLastLogin($query): void
{
    $query->orderByDesc(Login::select('created_at')
        ->whereColumn('user_id', 'users.id')
        ->latest()
        ->take(1)
    );
}
```