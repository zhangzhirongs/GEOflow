# 安全最佳实践

## 批量赋值保护

每个模型必须定义 `$fillable`（白名单）或 `$guarded`（黑名单）。

错误：
```php
class User extends Model
{
    protected $guarded = []; // All fields are mass assignable
}
```

正确：
```php
class User extends Model
{
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
}
```

绝不要在接受用户输入的模型上使用 `$guarded = []`。

## 对每个操作进行授权

在控制器中使用策略或门禁。绝不要跳过授权。

错误：
```php
public function update(UpdatePostRequest $request, Post $post)
{
    $post->update($request->validated());
}
```

正确：
```php
public function update(UpdatePostRequest $request, Post $post)
{
    Gate::authorize('update', $post);

    $post->update($request->validated());
}
```

或通过 Form Request：

```php
public function authorize(): bool
{
    return $this->user()->can('update', $this->route('post'));
}
```

## 防止 SQL 注入

始终使用参数绑定。切勿将用户输入插值到查询中。

错误：
```php
DB::select("SELECT * FROM users WHERE name = '{$request->name}'");
```

正确：
```php
User::where('name', $request->name)->get();

// 带绑定的原始表达式
User::whereRaw('LOWER(name) = ?', [strtolower($request->name)])->get();
```

## 转义输出以防止 XSS

使用 `{{ }}` 进行 HTML 转义。仅对可信且已预先清理的内容使用 `{!! !!}`。

错误：
```blade
{!! $user->bio !!}
```

正确：
```blade
{{ $user->bio }}
```

## CSRF 防护

在所有 POST/PUT/DELETE Blade 表单中包含 `@csrf`。在 Inertia 应用中，`@csrf` 指令会自动应用。

错误：
```blade
<form method="POST" action="/posts">
    <input type="text" name="title">
</form>
```

正确：
```blade
<form method="POST" action="/posts">
    @csrf
    <input type="text" name="title">
</form>
```

## 对认证和 API 路由进行限流

将 `throttle` 中间件应用于身份验证和 API 路由。

```php
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

Route::post('/login', LoginController::class)->middleware('throttle:login');
```

## 验证文件上传

验证扩展名、MIME 类型和大小。`mimes` 规则检查扩展名；使用 `mimetypes` 进行实际的 MIME 类型验证。永远不要信任客户端提供的文件名。

```php
public function rules(): array
{
    return [
        'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
    ];
}
```

使用生成的文件名存储：

```php
$path = $request->file('avatar')->store('avatars', 'public');
```

## 避免将机密信息写入代码

绝不要提交 `.env`。只能通过 `config()` 访问密钥。

错误：
```php
$key = env('API_KEY');
```

正确：
```php
// config/services.php
'api_key' => env('API_KEY'),

// 在应用代码中
$key = config('services.api_key');
```

## 审计依赖项

定期运行 `composer audit` 以检查依赖项中的已知漏洞。在 CI 中自动化此操作，以便在部署前发现问题。

```bash
composer audit
```

## 加密敏感数据库字段

对 API 密钥/令牌使用 `encrypted` 转换，并将该属性标记为 `hidden`。

错误：
```php
class Integration extends Model
{
    protected function casts(): array
    {
        return [
            'api_key' => 'string',
        ];
    }
}
```

正确：
```php
class Integration extends Model
{
    protected $hidden = ['api_key', 'api_secret'];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'api_secret' => 'encrypted',
        ];
    }
}
```