# 迁移最佳实践

## 使用 Artisan 生成迁移

始终使用 `php artisan make:migration` 以实现一致的命名和时间戳。

错误（手动创建文件）：
```php
// database/migrations/posts_migration.php  ← 命名错误，没有时间戳
```

正确（由 Artisan 生成）：
```bash
php artisan make:migration create_posts_table
php artisan make:migration add_slug_to_posts_table
```

## 为外键使用 `constrained()`

自动命名和引用完整性。

```php
$table->foreignId('user_id')->constrained()->cascadeOnDelete();

// 非标准名称
$table->foreignId('author_id')->constrained('users');
```

## 切勿修改已部署的迁移

迁移在生产环境运行过后，应将其视为不可变。要更改表，请创建新的迁移。

错误（编辑已部署的迁移）：
```php
// 2024_01_01_create_posts_table.php — 已在生产环境中使用
$table->string('slug')->unique(); // ← added after deployment
```

正确（新建迁移来修改）：
```php
// 2024_03_15_add_slug_to_posts_table.php
Schema::table('posts', function (Blueprint $table) {
    $table->string('slug')->unique()->after('title');
});
```

## 在迁移中添加索引

创建表时就添加索引，不要事后补加。用于 `WHERE`、`ORDER BY` 和 `JOIN` 子句的列需要索引。

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
    $table->foreignId('user_id')->constrained()->index();
    $table->string('status')->index();
    $table->timestamp('shipped_at')->nullable()->index();
    $table->timestamps();
});
```

## Model `$attributes` 中的镜像默认值

当某列具有数据库默认值时，在模型中镜像该默认值，以便新实例在保存前具有正确的值。

```php
// 迁移
$table->string('status')->default('pending');

// 模型
protected $attributes = [
    'status' => 'pending',
];
```

## 默认编写可逆的 `down()` 方法

为可安全回滚的 schema 变更实现 `down()`，以便 `migrate:rollback` 在 CI 和失败的部署中正常工作。

```php
public function down(): void
{
    Schema::table('posts', function (Blueprint $table) {
        $table->dropColumn('slug');
    });
}
```

对于有意设计为不可逆的迁移（例如破坏性数据回填），请留下清晰注释，并要求通过前向修复迁移来处理，而不是假装支持回滚。

## 保持迁移聚焦

每个迁移只关注一件事。切勿混用 DDL（架构变更）和 DML（数据操作）。

错误（部分失败会造成不可恢复状态）：
```php
public function up(): void
{
    Schema::create('settings', function (Blueprint $table) { ... });
    DB::table('settings')->insert(['key' => 'version', 'value' => '1.0']);
}
```

正确（分开的迁移）：
```php
// 迁移 1：create_settings_table
Schema::create('settings', function (Blueprint $table) { ... });

// 迁移 2：seed_default_settings
DB::table('settings')->insert(['key' => 'version', 'value' => '1.0']);
```