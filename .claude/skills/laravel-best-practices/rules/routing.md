# 路由与控制器最佳实践

## 使用隐式路由模型绑定

让 Laravel 自动从路由参数解析模型。

错误：
```php
public function show(int $id)
{
    $post = Post::findOrFail($id);
}
```

正确：
```php
public function show(Post $post)
{
    return view('posts.show', ['post' => $post]);
}
```

## 对嵌套资源使用作用域绑定

自动强制实施父子关系。

```php
Route::get('/users/{user}/posts/{post}', function (User $user, Post $post) {
    // $post 自动限定在 $user 的作用域内
})->scopeBindings();
```

## 使用资源控制器

对 RESTful 端点使用 `Route::resource()` 或 `apiResource()`。

```php
Route::resource('posts', PostController::class);
// 在 routes/api.php 中——/api 前缀会自动应用
Route::apiResource('posts', Api\PostController::class);
```

## 保持控制器精简

每个方法尽量控制在 10 行以内。将业务逻辑提取到 action 或 service 类中。

错误：
```php
public function store(Request $request)
{
    $validated = $request->validate([...]);
    if ($request->hasFile('image')) {
        $request->file('image')->move(public_path('images'));
    }
    $post = Post::create($validated);
    $post->tags()->sync($validated['tags']);
    event(new PostCreated($post));
    return redirect()->route('posts.show', $post);
}
```

正确：
```php
public function store(StorePostRequest $request, CreatePostAction $create)
{
    $post = $create->execute($request->validated());

    return redirect()->route('posts.show', $post);
}
```

## 类型提示表单请求

对 Form Request 进行类型提示会在方法执行前触发自动验证和授权。

错误：
```php
public function store(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'title' => ['required', 'max:255'],
        'body' => ['required'],
    ]);

    Post::create($validated);

    return redirect()->route('posts.index');
}
```

正确：
```php
public function store(StorePostRequest $request): RedirectResponse
{
    Post::create($request->validated());

    return redirect()->route('posts.index');
}
```