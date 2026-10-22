# Collection 最佳实践

## 对简单操作使用高阶消息

错误：
```php
$users->each(function (User $user) {
    $user->markAsVip();
});
```

正确：`$users->each->markAsVip();`

可与 `each`、`map`、`sum`、`filter`、`reject`、`contains` 等配合使用。

## 正确选择 `cursor()` 与 `lazy()`

- `cursor()` —— 内存中一个模型，但无法预加载关系（N+1 风险）。
- `lazy()` — 分块分页返回扁平的 LazyCollection，支持预加载。

错误：`User::with('roles')->cursor()` — 预加载被静默忽略。

正确：关系访问用 `User::with('roles')->lazy()`；仅属性操作使用 `User::cursor()`。

## 在迭代过程中更新记录时使用 `lazyById()`

`lazy()` 使用偏移分页——在迭代期间更新记录可能会跳过或重复处理。`lazyById()` 使用 `id > last_id`，可安全应对变更。

## 对集合执行批量操作时使用 `toQuery()`

避免手动构造`whereIn`。

错误：`User::whereIn('id', $users->pluck('id'))->update([...]);`

正确：`$users->toQuery()->update([...]);`

## 对自定义集合类使用 `#[CollectedBy]`

比覆盖 `newCollection()` 更具声明性。

```php
#[CollectedBy(UserCollection::class)]
class User extends Model {}
```