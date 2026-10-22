<laravel-boost-guidelines>
=== 基础规则 ===

# Laravel Boost 指南

Laravel Boost 指南由 Laravel 维护者专门为此应用策划。应严格遵循这些指南，以确保在构建 Laravel 应用时获得最佳体验。

## 基础背景

此应用程序是一个 Laravel 应用程序，其主要 Laravel 生态系统包及版本如下。你是所有这些方面的专家。确保遵守这些特定包和版本。

- php - 8.4
- laravel/ai (AI) - v0
- laravel/framework (LARAVEL) - v12
- laravel/horizon (HORIZON) - v5
- laravel/prompts (PROMPTS) - v0
- laravel/reverb (REVERB) - v1
- laravel/sanctum (SANCTUM) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- laravel-echo (ECHO) - v2
- tailwindcss (TAILWINDCSS) - v4

## 技能激活

此项目具有可用的领域特定技能。每当你在该领域工作时，你必须激活相关技能——不要等到陷入困境才行动。

- `ai-sdk-development` — 在使用 ai-sdk（Laravel 官方第一方 AI SDK）时触发。在构建、编辑 AI 代理、聊天机器人、文本生成、图像生成、音频/TTS、转录/STT、嵌入、RAG、向量存储、重排序、结构化输出、流式传输、对话记忆、工具、队列、广播，以及跨 OpenAI、Anthropic、Gemini、Azure、Groq、xAI、DeepSeek、Mistral、Ollama、ElevenLabs、Cohere、Jina 和 VoyageAI 的提供程序故障转移时激活。当用户提及 ai-sdk、`Laravel\Ai\` 命名空间或本项目的 AI 功能时调用 — 不用于直接使用的 Prism PHP 或其他 AI 包。
- `laravel-best-practices` — 每当编写、审查或重构 Laravel PHP 代码时，应用此技能。这包括创建或修改控制器、模型、迁移、表单请求、策略、作业、计划命令、服务类和 Eloquent 查询。触发条件包括 N+1 与查询性能问题、缓存策略、授权与安全模式、验证、错误处理、队列与作业配置、路由定义以及架构决策。也用于 Laravel 代码审查和重构现有 Laravel 代码以遵循最佳实践。涵盖任何涉及 Laravel 后端 PHP 代码模式的任务。
- `configuring-horizon` — 当用户在 Laravel 上下文中按名称提及 Horizon 时，使用此技能。涵盖完整的 Horizon 生命周期：安装 Horizon（horizon:install、Sail 设置）、配置 config/horizon.php（supervisor 块、队列分配、平衡策略、minProcesses/maxProcesses）、修复仪表板（通过 Gate::define viewHorizon 授权、空白指标、horizon:snapshot 调度）以及排查生产问题（工作进程崩溃、超时链顺序、LongWaitDetected 通知、waits 配置）。还涵盖作业标记与静默。不适用于未使用 Horizon 的通用 Laravel 队列、SQS 或数据库驱动、独立 Redis 设置、Linux supervisord、Telescope 或作业批处理。
- `tailwindcss-development` — 当用户消息以任何形式包含 'tailwind' 时始终调用。也适用于：构建响应式网格布局（多列卡片网格、产品网格）、flex/grid 页面结构（带侧边栏的仪表盘、固定顶栏、移动端切换导航）、为 UI 组件添加样式（卡片、表格、导航栏、定价区块、表单、输入框、徽章）、添加深色模式变体、修复间距或排版，以及 Tailwind v3/v4 相关工作。核心用例：在 HTML 模板（Blade、JSX、Vue）中编写或修复 Tailwind 工具类。跳过：后端 PHP 逻辑、数据库查询、API 路由、不含 HTML/CSS 组件的 JavaScript、CSS 文件审查、构建工具配置和原生 CSS。

## 约定

- 你必须遵循此应用程序中使用的所有现有代码约定。创建或编辑文件时，请检查同级文件以了解正确的结构、方法和命名。
- 为变量和方法使用描述性名称。例如，`isRegisteredForDiscounts`，而不是 `discount()`。
- 在编写新组件之前，检查是否有现有组件可复用。

## 验证脚本

- 当测试已覆盖该功能并证明其可用时，不要创建验证脚本或使用 tinker。单元测试和功能测试更重要。

## 应用结构与架构

- 遵循现有目录结构；未经批准不要创建新的基础文件夹。
- 未经批准，不要更改应用程序的依赖项。

## 前端打包

- 如果用户在 UI 中看不到前端更改的反映，这可能意味着他们需要运行 `npm run build`、`npm run dev` 或 `composer run dev`。询问他们。

## 文档文件

- 仅当用户明确要求时，你才应创建文档文件。

## 回复

- 解释要简洁——关注重要内容，而不是解释显而易见的细节。

=== boost 规则 ===

# Laravel Boost

## 工具

- Laravel Boost 是一个 MCP 服务器，其工具专为此应用设计。优先使用 Boost 工具，而不是 shell 命令或文件读取等手动替代方式。
- 使用 `database-query` 对数据库运行只读查询，而不是在 tinker 中编写原始 SQL。
- 在编写迁移或模型之前，使用 `database-schema` 检查表结构。
- 使用 `get-absolute-url` 解析项目 URL 的正确协议、域名和端口。与用户分享 URL 之前始终使用此方法。
- 使用 `browser-logs` 读取浏览器日志、错误和异常。只有最近的日志有用，忽略旧条目。

## 搜索文档（重要）

- 在进行代码更改之前，始终使用 `search-docs`。不要跳过此步骤。它会根据已安装的包自动返回特定版本文档。
- 当你知道哪些包相关时，传递 `packages` 数组来限定结果范围。
- 使用多个宽泛的、基于主题的查询：`['rate limiting', 'routing rate limiting', 'routing']`。预期最相关的结果会最先出现。
- 不要将包名添加到查询中，因为包信息已经共享。使用`test resource table`，而不是`filament 4 test resource table`。

### 搜索语法

1. 使用词语实现自动词干化的 AND 逻辑：`rate limit` 同时匹配 "rate" 和 "limit"。
2. 使用`"quoted phrases"`进行精确位置匹配：`"infinite scroll"`要求词语按顺序相邻。
3. 为混合查询组合单词和短语：`middleware "rate limit"`。
4. 使用多个查询实现 OR 逻辑：`queries=["authentication", "middleware"]`。

## Artisan

- 直接通过命令行运行 Artisan 命令（例如，`php artisan route:list`）。使用 `php artisan list` 发现可用命令，使用 `php artisan [command] --help` 检查参数。
- 使用 `php artisan route:list` 检查路由。使用以下方式筛选：`--method=GET`、`--name=users`、`--path=api`、`--except-vendor`、`--only-vendor`。
- 使用点号表示法读取配置值：`php artisan config:show app.name`、`php artisan config:show database.default`。或直接从 `config/` 目录读取配置文件。
- 要检查环境变量，请直接读取 `.env` 文件。

## Tinker

- 在应用上下文中执行 PHP 以调试和测试代码。未经用户批准不要创建模型，优先使用基于工厂的测试。优先使用现有 Artisan 命令，而不是自定义 tinker 代码。
- 始终使用单引号以防止 shell 展开：`php artisan tinker --execute 'Your::code();'`
  - PHP 字符串内部使用双引号：`php artisan tinker --execute 'User::where("active", true)->count();'`

=== php 规则 ===

# PHP

- 控制结构始终使用花括号，即使主体只有一行也是如此。
- 使用 PHP 8 构造函数属性提升：`public function __construct(public GitHub $github) { }`。除非构造函数是私有的，否则不要留下空的零参数 `__construct()` 方法。
- 为所有方法参数使用显式返回类型声明和类型提示：`function isAccessible(User $user, ?string $path = null): bool`
- Enum 键使用 TitleCase：`FavoritePerson`、`BestLake`、`Monthly`。
- 优先使用 PHPDoc 块，而不是行内注释。仅为异常复杂的逻辑添加行内注释。
- 在 PHPDoc 块中使用数组形状类型定义。

=== 部署规则 ===

# 部署

- Laravel 可使用 [Laravel Cloud](https://cloud.laravel.com/) 部署，这是部署和扩展生产 Laravel 应用最快的方式。

=== tests 规则 ===

# 测试强制执行

- 每项更改都必须通过程序化方式进行测试。编写新测试或更新现有测试，然后运行受影响的测试以确保它们通过。
- 运行确保代码质量和速度所需的最少量测试。使用 `php artisan test --compact`，并指定具体文件名或过滤器。

=== laravel/core 规则 ===

# 按 Laravel 的方式做事

- 使用 `php artisan make:` 命令创建新文件（即迁移、控制器、模型等）。你可以使用 `php artisan list` 列出可用的 Artisan 命令，并使用 `php artisan [command] --help` 检查其参数。
- 如果正在创建通用 PHP 类，请使用 `php artisan make:class`。
- 向所有 Artisan 命令传递 `--no-interaction`，以确保它们无需用户输入即可运行。还应传递正确的 `--options`，以确保行为正确。

### 模型创建

- 创建新模型时，也要为它们创建有用的工厂和填充器。使用 `php artisan make:model --help` 检查可用选项，询问用户是否还需要其他内容。

## API 与 Eloquent 资源

- 对于 API，默认使用 Eloquent API Resources 和 API 版本控制；除非现有 API 路由不这样做，此时应遵循现有应用约定。

## URL 生成

- 生成指向其他页面的链接时，优先使用命名路由和 `route()` 函数。

## 测试

- 为测试创建模型时，使用这些模型的工厂。在手动设置模型之前，检查工厂是否有可用的自定义状态。
- Faker：使用诸如 `$this->faker->word()` 或 `fake()->randomDigit()` 的方法。遵循现有约定，决定使用 `$this->faker` 还是 `fake()`。
- 创建测试时，利用 `php artisan make:test [options] {name}` 创建功能测试，并传入 `--unit` 创建单元测试。大多数测试应为功能测试。

## Vite 错误

- 如果收到“Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest”错误，可以运行 `npm run build`，或请用户运行 `npm run dev` 或 `composer run dev`。

=== laravel/v12 规则 ===

# Laravel 12

- 关键：务必始终使用 `search-docs` 工具获取特定版本的 Laravel 文档和更新的代码示例。
- 自 Laravel 11 起，Laravel 采用了新的精简文件结构，本项目使用该结构。

## Laravel 12 结构

- 在 Laravel 12 中，中间件不再在 `app/Http/Kernel.php` 中注册。
- 中间件在 `bootstrap/app.php` 中使用 `Application::configure()->withMiddleware()` 以声明方式配置。
- `bootstrap/app.php` 是用于注册中间件、异常和路由文件的文件。
- `bootstrap/providers.php` 包含应用特定的服务提供者。
- `app/Console/Kernel.php` 文件已不存在；请使用 `bootstrap/app.php` 或 `routes/console.php` 进行控制台配置。
- `app/Console/Commands/` 中的控制台命令会自动可用，无需手动注册。

## 数据库

- 修改列时，迁移必须包含该列先前定义的所有属性。否则，这些属性将被删除并丢失。
- Laravel 12 原生支持限制预加载记录，无需外部包：`$query->latest()->limit(10);`。

### 模型

- 类型转换可以且很可能应设置在模型的 `casts()` 方法中，而不是 `$casts` 属性中。遵循其他模型的现有约定。

=== pint/core 规则 ===

# Laravel Pint 代码格式化工具

- 如果你修改了任何 PHP 文件，必须在最终确定更改前运行 `vendor/bin/pint --dirty --format agent`，以确保你的代码符合项目预期的风格。
- 不要运行`vendor/bin/pint --test --format agent`，只需运行`vendor/bin/pint --format agent`即可修复任何格式问题。

=== phpunit/core 规则 ===

# PHPUnit

- 此应用程序使用 PHPUnit 进行测试。所有测试都必须编写为 PHPUnit 类。使用 `php artisan make:test --phpunit {name}` 创建新测试。
- 如果看到使用“Pest”的测试，请将其转换为 PHPUnit。
- 每次更新测试后，运行该单个测试。
- 当与你的功能相关的测试通过后，询问用户是否也希望运行整个测试套件，以确保所有内容仍然通过。
- 测试应覆盖所有正常路径、失败路径和边界情况。
- 未经批准，你不得从 tests 目录中移除任何测试或测试文件。这些不是临时文件或辅助文件；它们是应用程序的核心。

## 运行测试

- 在最终确定之前，使用适当的过滤器运行最少量的测试。
- 要运行所有测试：`php artisan test --compact`。
- 要运行文件中的所有测试：`php artisan test --compact tests/Feature/ExampleTest.php`。
- 要按特定测试名称过滤：`php artisan test --compact --filter=testName`（建议在对相关文件进行更改后使用）。

</laravel-boost-guidelines>
