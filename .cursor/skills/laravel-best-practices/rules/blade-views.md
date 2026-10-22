# Blade 与视图最佳实践

## 在组件模板中使用 `$attributes->merge()`

硬编码类会阻止使用者添加自己的类。`merge()` 可以干净地合并类属性。

```blade
<div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }}>
    {{ $message }}
</div>
```

## 为每个组件的脚本使用 `@pushOnce`

如果组件在 `@foreach` 内渲染，`@push` 会插入脚本 N 次。`@pushOnce` 保证它只被包含一次。

## 优先使用 Blade 组件而非 `@include`

`@include` 隐式共享所有父级变量（隐藏耦合）。组件具有显式 props、属性包和插槽。

## 使用视图 Composer 共享视图数据

如果每个渲染侧边栏的控制器都必须传入 `$categories`，那就是重复代码。View Composer 可将其集中处理。

## 使用 Blade 片段进行局部重新渲染（htmx/Turbo）

单个视图可以返回完整页面或仅返回片段，从而保持路由清晰。

```php
return view('dashboard', compact('users'))
    ->fragmentIf($request->hasHeader('HX-Request'), 'user-list');
```

## 为深度嵌套的组件 Props 使用 `@aware`

避免在每一层嵌套组件中重复传递父级 props。