# 验证与表单最佳实践

## 使用表单请求类

将验证从控制器中提取到专用的 Form Request 类中。

错误：
```php
public function store(Request $request)
{
    $request->validate([
        'title' => 'required|max:255',
        'body' => 'required',
    ]);
}
```

正确：
```php
public function store(StorePostRequest $request)
{
    Post::create($request->validated());
}
```

## 规则的数组与字符串表示法

数组语法更易读，并且能与`Rule::`对象清晰地组合。在新代码中优先使用它，但先检查现有的 Form Requests，并匹配项目已经使用的任何写法。

```php
// 新代码优先使用
'email' => ['required', 'email', Rule::unique('users')],

// 如果项目使用字符串表示法，请遵循现有约定
'email' => 'required|email|unique:users',
```

## 始终使用 `validated()`

只获取经过验证的数据。切勿将 `$request->all()` 用于批量操作。

错误：
```php
Post::create($request->all());
```

正确：
```php
Post::create($request->validated());
```

## 使用 `Rule::when()` 进行条件验证

```php
'company_name' => [
    Rule::when($this->account_type === 'business', ['required', 'string', 'max:255']),
],
```

## 使用 `after()` 方法进行自定义验证

对于依赖多个字段的自定义验证逻辑，使用 `after()` 而不是 `withValidator()`。

```php
public function after(): array
{
    return [
        function (Validator $validator) {
            if ($this->quantity > Product::find($this->product_id)?->stock) {
                $validator->errors()->add('quantity', 'Not enough stock.');
            }
        },
    ];
}
```