# 配置最佳实践

## `env()` 仅限配置文件

当配置已缓存时，直接调用 `env()` 可能返回 `null`。

错误：
```php
$key = env('API_KEY');
```

正确：
```php
// config/services.php
'key' => env('API_KEY'),

// 应用代码
$key = config('services.key');
```

## 使用加密的环境变量或外部密钥

绝不要在版本控制中把生产密钥存储在明文 `.env` 文件中。

错误：
```bash

# .env 被提交到仓库或在 Slack 中共享

STRIPE_SECRET=sk_live_abc123
AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI
```

正确：
```bash
php artisan env:encrypt --env=production --readable
php artisan env:decrypt --env=production
```

对于云部署，优先使用平台的原生机密存储（AWS Secrets Manager、Vault 等），并在运行时注入。

## 使用 `App::environment()` 进行环境检查

错误：
```php
if (env('APP_ENV') === 'production') {
```

正确：
```php
if (app()->isProduction()) {
// 或
if (App::environment('production')) {
```

## 使用常量与语言文件

针对模型状态、类型和状态值，使用类常量而不是硬编码的魔法字符串。

```php
// 错误
return $this->type === 'normal';

// 正确
return $this->type === self::TYPE_NORMAL;
```

如果应用已使用语言文件进行本地化，面向用户的字符串也请使用 `__()`。不要仅为纯英文应用引入语言文件——在这种场景下，简单字符串字面量即可。

```php
// 仅当项目中已存在 lang 文件时
return back()->with('message', __('app.article_added'));
```