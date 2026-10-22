---
name: tailwindcss-development
description: 只要用户消息以任何形式包含 'tailwind'，就始终调用。以下情况也调用：构建响应式网格布局（多列卡片网格、产品网格）、flex/grid 页面结构（带侧边栏的仪表板、固定顶栏、移动端切换导航）、为 UI 组件设置样式（卡片、表格、导航栏、定价区、表单、输入框、徽章）、添加深色模式变体、修复间距或排版，以及 Tailwind v3/v4 工作。核心用例：在 HTML 模板（Blade、JSX、Vue）中编写或修复 Tailwind 工具类。跳过后端 PHP 逻辑、数据库查询、API 路由、没有 HTML/CSS 组件的 JavaScript、CSS 文件审计、构建工具配置和原生 CSS。
license: MIT
metadata:
  author: laravel
  display_name: Tailwind CSS 开发
---

# Tailwind CSS 开发

## 文档

使用 `search-docs` 获取详细的 Tailwind CSS v4 模式和文档。

## 基本用法

- 使用 Tailwind CSS 类来设置 HTML 样式。引入新模式前，检查并遵循项目中现有的 Tailwind 约定。
- 主动提出将重复模式提取为符合项目约定的组件（例如 Blade、JSX、Vue）。
- 考虑类的位置、顺序、优先级和默认值。移除冗余类，谨慎地向父元素或子元素添加类以减少重复，并按逻辑对元素分组。

## Tailwind CSS v4 特定内容

- 始终使用 Tailwind CSS v4，并避免使用已弃用的工具类。
- Tailwind v4 不支持 `corePlugins`。

### CSS 优先配置

在 Tailwind v4 中，配置采用 CSS 优先方式，使用 `@theme` 指令——不需要单独的 `tailwind.config.js` 文件：

<!-- CSS 优先配置 -->
```css
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
```

### 导入语法

在 Tailwind v4 中，使用常规 CSS `@import` 语句导入 Tailwind，而不是使用 v3 中的 `@tailwind` 指令：

<!-- v4 导入语法 -->
```diff
- @tailwind base;
- @tailwind components;
- @tailwind utilities;
+ @import "tailwindcss";
```

### 已替换的实用程序

Tailwind v4 移除了已弃用的工具类。请使用下方给出的替代方案。不透明度值仍为数字。

| 已弃用 | 替代项 |
|------------|-------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |

## 间距

对于同级元素之间的间距，使用 `gap` 工具类而不是外边距：

<!-- 间距工具类 -->
```html
<div class="flex gap-8">
    <div>Item 1</div>
    <div>Item 2</div>
</div>
```

## 深色模式

如果现有页面和组件支持深色模式，新页面和组件也必须以相同方式支持它，通常使用 `dark:` 变体：

<!-- 深色模式 -->
```html
<div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
    Content adapts to color scheme
</div>
```

## 常见模式

### Flexbox 布局

<!-- Flexbox 布局 -->
```html
<div class="flex items-center justify-between gap-4">
    <div>Left content</div>
    <div>Right content</div>
</div>
```

### 网格布局

<!-- 网格布局 -->
```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div>Card 1</div>
    <div>Card 2</div>
    <div>Card 3</div>
</div>
```

## 常见陷阱

- 使用已弃用的 v3 工具类（bg-opacity-*、flex-shrink-* 等）
- 使用 `@tailwind` 指令而不是 `@import "tailwindcss"`
- 尝试使用 `tailwind.config.js` 而不是 CSS `@theme` 指令
- 使用外边距在同级元素之间设置间距，而不是使用 gap 工具类
- 项目使用深色模式时忘记添加深色模式变体