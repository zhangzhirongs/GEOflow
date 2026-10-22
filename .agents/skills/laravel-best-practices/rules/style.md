# 约定与风格

## 遵循 Laravel 命名约定

| 项目 | 约定 | 好 | 差 |
|------|-----------|------|-----|
| Controller | 单数 | `ArticleController` | `ArticlesController` |
| Model | 单数 | `User` | `Users` |
| 表 | 复数、snake_case | `article_comments` | `articleComments` |
| 中间表 | 单数按字母顺序 | `article_user` | `user_article` |
| 列 | snake_case，不含模型名 | `meta_title` | `article_meta_title` |
| 外键 | 单数模型 + `_id` | `article_id` | `articles_id` |
| 路由 | 复数 | `articles/1` | `article/1` |
| 路由名称 | 带点的 snake_case | `users.show_active` | `users.show-active` |
| 方法 | camelCase | `getAll` | `get_all` |
| 变量 | camelCase | `$articlesWithAuthor` | `$articles_with_author` |
| Collection | 描述性、复数 | `$activeUsers` | `$data` |
| 对象 | 描述性、单数 | `$activeUser` | `$users` |
| 视图 | kebab-case | `show-filtered.blade.php` | `showFiltered.blade.php` |
| Config | snake_case | `google_calendar.php` | `googleCalendar.php` |
| Enum | 单数 | `UserType` | `UserTypes` |

## 优先使用更简短易读的语法

| 详细 | 简短 |
|---------|---------|
| `Session::get('cart')` | `session('cart')` |
| `$request->session()->get('cart')` | `session('cart')` |
| `$request->input('name')` | `$request->name` |
| `return Redirect::back()` | `return back()` |
| `Carbon::now()` | `now()` |
| `App::make('Class')` | `app('Class')` |
| `->where('column', '=', 1)` | `->where('column', 1)` |
| `->orderBy('created_at', 'desc')` | `->latest()` |
| `->orderBy('created_at', 'asc')` | `->oldest()` |
| `->first()->name` | `->value('name')` |

## 使用 Laravel 字符串与数组辅助函数

Laravel 提供 `Str`、`Arr`、`Number` 和 `Uri` 辅助类，它们比原生 PHP 函数更易读、可链式调用且对 UTF-8 安全。应始终优先使用它们。

字符串——优先使用 `Str` 和流式 `Str::of()`，而不是原生 PHP：
```php
// 错误
$slug = strtolower(str_replace(' ', '-', $title));
$short = substr($text, 0, 100) . '...';
$class = substr(strrchr('App\Models\User', '\'), 1);

// 正确
$slug = Str::slug($title);
$short = Str::limit($text, 100);
$class = class_basename('App\Models\User');
```

流畅字符串 — 链式操作以进行复杂转换：
```php
// 错误
$result = strtolower(trim(str_replace('_', '-', $input)));

// 正确
$result = Str::of($input)->trim()->replace('_', '-')->lower();
```

优先使用的关键 `Str` 方法：`Str::slug()`、`Str::limit()`、`Str::contains()`、`Str::before()`、`Str::after()`、`Str::between()`、`Str::camel()`、`Str::snake()`、`Str::kebab()`、`Str::headline()`、`Str::squish()`、`Str::mask()`、`Str::uuid()`、`Str::ulid()`、`Str::random()`、`Str::is()`。

数组——优先使用`Arr`而不是原生 PHP：
```php
// 错误
$name = isset($array['user']['name']) ? $array['user']['name'] : 'default';

// 正确
$name = Arr::get($array, 'user.name', 'default');
```

关键 `Arr` 方法：`Arr::get()`、`Arr::has()`、`Arr::only()`、`Arr::except()`、`Arr::first()`、`Arr::flatten()`、`Arr::pluck()`、`Arr::where()`、`Arr::wrap()`。

数字——使用 `Number` 进行显示格式化：
```php
Number::format(1000000);          // "1,000,000"
Number::currency(1500, 'USD');    // "$1,500.00"
Number::abbreviate(1000000);      // "1M"
Number::fileSize(1024 * 1024);    // "1 MB"
Number::percentage(75.5);         // "75.5%"
```

URI——使用 `Uri` 进行 URL 操作：
```php
$uri = Uri::of('https://example.com/search')
    ->withQuery(['q' => 'laravel', 'page' => 1]);
```

使用 `$request->string('name')` 直接从请求输入获取流畅的 `Stringable`，以便立即链式调用。

使用 `search-docs` 查看可用方法的完整列表——这些辅助方法非常丰富。

## Blade 中禁止内联 JS/CSS

不要将 JS 或 CSS 放入 Blade 模板。不要将 HTML 放入 PHP 类。

错误：
```blade
let article = `{{ json_encode($article) }}`;
```

正确：
```blade
<button class="js-fav-article" data-article='@json($article)'>{{ $article->name }}</button>
```

通过 data 属性将数据传递给 JS，或使用专用的 PHP-to-JS 包。

## 禁止不必要的注释

代码本身应当可读。使用描述性的方法和变量名称，而不是注释。唯一的例外是配置文件，其中预期会有描述性注释。

错误：
```php
// 检查是否存在任何联接
if (count((array) $builder->getQuery()->joins) > 0)
```

正确：
```php
if ($this->hasJoins())
```