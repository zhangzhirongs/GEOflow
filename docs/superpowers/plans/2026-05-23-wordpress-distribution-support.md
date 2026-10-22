# WordPress 分发支持实施计划

> **面向智能体工作者：** 必需子技能：使用 superpowers:subagent-driven-development（推荐）或 superpowers:executing-plans 逐项实现此计划任务。步骤使用复选框（`- [ ]`）语法进行跟踪。

**目标：** 将 WordPress REST API 添加为一等分发渠道，使 GEOFlow 能够配置、测试、发布、更新、删除和管理 WordPress 站点上的内容。

**架构：** 引入分发发布器接口，并根据 `distribution_channels.channel_type` 将每个渠道路由到相应的发布器。将现有 GEOFlow 目标站点智能体行为保留在 `geoflow_agent` 发布器之后，并新增一个 `wordpress_rest` 发布器，使用应用程序密码认证与 WordPress Core REST API 通信。WordPress 特有的非机密设置存放在 `distribution_channels.channel_config` 中，而应用程序密码仍加密保存在 `distribution_channel_secrets.secret_ciphertext` 中。

**技术栈：** Laravel 12、Blade 管理后台 UI、当前管理后台使用的 Tailwind 类、Eloquent、PostgreSQL JSON 列、Laravel HTTP 客户端、Redis 队列、PHPUnit 功能/单元测试、WordPress Core REST API。

---

## 范围

### 包含在首次实现中

- 在分发管理中添加渠道类型选择：
  - `geoflow_agent`：当前目标站点包以及已签名的智能体协议。
  - `wordpress_rest`：WordPress Core REST API 集成。
- 添加 WordPress 配置字段：
  - WordPress 站点 URL 或 REST API 基础 URL。
  - WordPress 用户名。
  - Application Password。
  - 默认帖子状态：`publish`、`draft`、`pending`、`private`。
  - 分类策略：`match_or_create`、`match_only`、`fixed`。
  - 选择固定策略时使用固定的分类 ID/名称。
  - 标签策略：`keywords_to_tags`、`disabled`。
  - 图片策略：`upload_to_media`、`keep_original`。
  - 内容格式：`html`。
- 添加 WordPress 健康检查：
  - 检测 `/wp-json`。
  - 通过 Application Password 进行身份验证。
  - 检查当前用户和最低内容权限。
- 添加 WordPress 内容操作：
  - 将文章发布到 `/wp/v2/posts`。
  - 通过已保存的 WordPress `post_id` 更新文章。
  - 通过保存的 WordPress `post_id` 删除远程文章。
  - 当 payload 包含 `content_base64` 时，将嵌入的本地图片上传到 `/wp/v2/media`。
  - 匹配或创建分类和标签。
  - 将支持的站点设置同步到 `/wp/v2/settings`。
- 调整管理界面：
  - 针对 WordPress 渠道，隐藏目标站点包和重写规则模块。
  - 显示 WordPress 连接指南和应用程序密码说明。
  - 保持 WordPress 作业的分发队列编辑/删除/重试操作正常工作。
- 添加测试，覆盖渠道创建、健康检查、发布、更新、删除、图片上传、分类/标签行为以及 UI 可见性。

### 已推迟

- WordPress 插件增强的 GEO/LLM 功能，例如 `llms.txt`、TXT 映射、schema 策略、SEO 插件字段和远程模板控制。
- WordPress.com OAuth 流程。首个实现面向自托管 WordPress 或暴露标准 `/wp-json/wp/v2/*` 路由和应用程序密码的 WordPress 环境。
- 完整的 Gutenberg 块生成。首次实现会发送由 GEOFlow Markdown 生成并经过清理的 HTML。
- WordPress 与 GEOFlow 双向同步。首个实现为 GEOFlow 到 WordPress。

---

## 文件映射

### 数据模型

- 修改 `database/migrations/2026_05_17_000000_create_distribution_management_tables.php`
  - 创建 `distribution_channels` 时添加 `channel_config` JSON 列。
  - 创建 `article_distributions` 时添加 `remote_meta` JSON 列。
- 创建 `database/migrations/2026_05_23_000000_add_wordpress_distribution_columns.php`
  - 如果缺失，添加 `distribution_channels.channel_config`。
  - 如果缺失，添加 `article_distributions.remote_meta`。
- 修改 `app/Models/DistributionChannel.php`
  - 将 `channel_config` 添加到 `$fillable`。
  - 将 `channel_config` 转换为数组。
  - 添加辅助函数：`channelType()`、`isGeoFlowAgent()`、`isWordPressRest()`、`resolvedChannelConfig()`、`wordpressRestBaseUrl()`。
- 修改 `app/Models/ArticleDistribution.php`
  - 将 `remote_meta` 添加到 `$fillable`。
  - 将 `remote_meta` 转换为数组。
  - 添加用于远程 WordPress 文章 ID 的辅助函数。

### 发布器层

- 创建 `app/Services/GeoFlow/DistributionPublisherInterface.php`
  - 通用发布器契约。
- 创建 `app/Services/GeoFlow/DistributionPublisherManager.php`
  - 通过 `channel_type` 选择发布者。
- 创建 `app/Services/GeoFlow/GeoFlowAgentPublisher.php`
  - 为当前智能体渠道封装现有的 `DistributionHttpClient` 行为。
- 创建 `app/Services/GeoFlow/WordPressRestPublisher.php`
  - 实现 WordPress REST 健康检查、发布、更新、删除、设置同步。
- 创建 `app/Services/GeoFlow/WordPressMediaSyncService.php`
  - 将 payload 图片上传到 WordPress 媒体库，并重写内容 HTML。
- 创建 `app/Services/GeoFlow/WordPressTaxonomySyncService.php`
  - 匹配或创建分类和标签。
- 修改 `app/Services/GeoFlow/DistributionOrchestrator.php`
  - 将直接调用 `DistributionHttpClient` 替换为 `DistributionPublisherManager`。
- 保留 `app/Services/GeoFlow/DistributionHttpClient.php`
  - 现有的已签名智能体客户端仍可供 `GeoFlowAgentPublisher` 使用。

### 管理控制器与界面

- 修改 `app/Http/Controllers/Admin/DistributionController.php`
  - 验证 `channel_type`。
  - 验证 WordPress 特定配置。
  - 将 WordPress 用户名存储在 `channel_config` 中。
  - 将应用程序密码加密存储在 `DistributionChannelSecret` 中。
  - 不要为 WordPress 生成 GEOFlow `gfsec_*` 密钥。
  - 调用发布器管理器进行健康检查和站点设置同步。
- 修改 `resources/views/admin/distribution/create.blade.php`
  - 添加渠道类型选择器。
  - 添加 WordPress 字段集。
  - 选择 WordPress 时，隐藏前端模式/目标包字段。
- 修改 `resources/views/admin/distribution/edit.blade.php`
  - 相同的条件字段集。
  - 禁止在创建后更改渠道类型，除非不存在任何作业。
- 修改 `resources/views/admin/distribution/show.blade.php`
  - 显示 WordPress 连接摘要。
  - 针对 WordPress，隐藏目标站点包和重写规则块。
  - 显示 WordPress 访问指南。
- 修改 `resources/views/admin/distribution/index.blade.php`
  - 显示渠道类型徽章。
- 修改 `resources/views/admin/distribution/_jobs-table.blade.php`
  - 保留远程编辑/删除/重试操作，但安全地标注远程 ID 和 URL。
- 修改 `lang/zh_CN/admin.php`、`lang/en/admin.php`、`lang/ja/admin.php`、`lang/es/admin.php`、`lang/ru/admin.php`、`lang/pt_BR/admin.php`
  - 添加 WordPress 渠道类型和校验的标签与消息。

### 测试

- 修改 `tests/Unit/DistributionSchemaMigrationTest.php`。
- 创建 `tests/Unit/DistributionPublisherManagerTest.php`。
- 创建 `tests/Unit/WordPressRestPublisherTest.php`。
- 创建 `tests/Unit/WordPressTaxonomySyncServiceTest.php`。
- 创建 `tests/Unit/WordPressMediaSyncServiceTest.php`。
- 扩展 `tests/Feature/AdminDistributionPageTest.php`。

### 文档

- 修改 `docs/distribution/unified-distribution-implementation-plan.md`。
- 修改 `docs/CHANGELOG.md` 和 `docs/CHANGELOG_en.md`。

---

## WordPress REST API 契约

### 身份验证

通过 HTTP Basic Auth 使用 WordPress 应用程序密码：

```php
Http::withBasicAuth($username, $applicationPassword)
    ->acceptJson()
    ->asJson();
```

应用程序密码不是 WordPress 登录密码。它在 WordPress 用户资料中创建，并且可以单独撤销。

### 端点解析

接受的输入示例：

- `https://example.com`
- `https://example.com/`
- `https://example.com/wp-json`
- `https://example.com/blog`
- `https://example.com/blog/wp-json`

规范化为：

```php
$base = rtrim($channel->endpoint_url, '/');
$restBase = str_ends_with($base, '/wp-json') ? $base : $base.'/wp-json';
```

Posts 端点：

```text
POST   {restBase}/wp/v2/posts
POST   {restBase}/wp/v2/posts/{post_id}
DELETE {restBase}/wp/v2/posts/{post_id}?force=false
```

媒体端点：

```text
POST {restBase}/wp/v2/media
```

类别和标签：

```text
GET  {restBase}/wp/v2/categories?slug={slug}
POST {restBase}/wp/v2/categories
GET  {restBase}/wp/v2/tags?slug={slug}
POST {restBase}/wp/v2/tags
```

设置：

```text
GET  {restBase}/wp/v2/settings
POST {restBase}/wp/v2/settings
```

---

## 任务

### 任务 1：为 WordPress 渠道配置和远程元数据添加 Schema 支持

**文件：**

- 修改：`database/migrations/2026_05_17_000000_create_distribution_management_tables.php`
- 创建：`database/migrations/2026_05_23_000000_add_wordpress_distribution_columns.php`
- 修改：`app/Models/DistributionChannel.php`
- 修改：`app/Models/ArticleDistribution.php`
- 修改：`tests/Unit/DistributionSchemaMigrationTest.php`

- [ ] **步骤 1：编写 schema 测试预期**

向 `tests/Unit/DistributionSchemaMigrationTest.php` 添加断言：

```php
public function test_distribution_schema_contains_wordpress_channel_columns(): void
{
    $this->assertTrue(Schema::hasColumn('distribution_channels', 'channel_config'));
    $this->assertTrue(Schema::hasColumn('article_distributions', 'remote_meta'));
}
```

- [ ] **第 2 步：运行 schema 测试并验证其在迁移变更前失败**

运行：

```bash
php artisan test tests/Unit/DistributionSchemaMigrationTest.php --filter=wordpress_channel_columns
```

实现前的预期：

```text
FAILED  Tests\Unit\DistributionSchemaMigrationTest
Failed asserting that false is true.
```

- [ ] **第 3 步：向初始创建迁移添加缺失的列**

在 `database/migrations/2026_05_17_000000_create_distribution_management_tables.php` 中，添加：

```php
$table->json('channel_config')->nullable();
```

在 `distribution_channels` 中 `site_settings` 之后。

添加：

```php
$table->json('remote_meta')->nullable();
```

在 `article_distributions` 中 `remote_url` 之后。

- [ ] **第 4 步：添加向前兼容的迁移**

创建 `database/migrations/2026_05_23_000000_add_wordpress_distribution_columns.php`：

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('distribution_channels') && ! Schema::hasColumn('distribution_channels', 'channel_config')) {
            Schema::table('distribution_channels', function (Blueprint $table): void {
                $table->json('channel_config')->nullable()->after('site_settings');
            });
        }

        if (Schema::hasTable('article_distributions') && ! Schema::hasColumn('article_distributions', 'remote_meta')) {
            Schema::table('article_distributions', function (Blueprint $table): void {
                $table->json('remote_meta')->nullable()->after('remote_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('article_distributions') && Schema::hasColumn('article_distributions', 'remote_meta')) {
            Schema::table('article_distributions', function (Blueprint $table): void {
                $table->dropColumn('remote_meta');
            });
        }

        if (Schema::hasTable('distribution_channels') && Schema::hasColumn('distribution_channels', 'channel_config')) {
            Schema::table('distribution_channels', function (Blueprint $table): void {
                $table->dropColumn('channel_config');
            });
        }
    }
};
```

- [ ] **第 5 步：更新模型**

在 `DistributionChannel` 中，将 `channel_config` 添加到 `$fillable` 和 casts 中：

```php
'channel_config',
```

```php
'channel_config' => 'array',
```

添加辅助函数：

```php
public function channelType(): string
{
    $type = (string) ($this->channel_type ?? 'geoflow_agent');

    return in_array($type, ['geoflow_agent', 'wordpress_rest'], true) ? $type : 'geoflow_agent';
}

public function isGeoFlowAgent(): bool
{
    return $this->channelType() === 'geoflow_agent';
}

public function isWordPressRest(): bool
{
    return $this->channelType() === 'wordpress_rest';
}

/**
 * @return array<string,mixed>
 */
public function resolvedChannelConfig(): array
{
    $stored = is_array($this->channel_config) ? $this->channel_config : [];

    return [
        'wordpress_username' => trim((string) ($stored['wordpress_username'] ?? '')),
        'wordpress_post_status' => in_array((string) ($stored['wordpress_post_status'] ?? 'publish'), ['publish', 'draft', 'pending', 'private'], true)
            ? (string) $stored['wordpress_post_status']
            : 'publish',
        'wordpress_category_strategy' => in_array((string) ($stored['wordpress_category_strategy'] ?? 'match_or_create'), ['match_or_create', 'match_only', 'fixed'], true)
            ? (string) $stored['wordpress_category_strategy']
            : 'match_or_create',
        'wordpress_fixed_category' => trim((string) ($stored['wordpress_fixed_category'] ?? '')),
        'wordpress_tag_strategy' => in_array((string) ($stored['wordpress_tag_strategy'] ?? 'keywords_to_tags'), ['keywords_to_tags', 'disabled'], true)
            ? (string) $stored['wordpress_tag_strategy']
            : 'keywords_to_tags',
        'wordpress_image_strategy' => in_array((string) ($stored['wordpress_image_strategy'] ?? 'upload_to_media'), ['upload_to_media', 'keep_original'], true)
            ? (string) $stored['wordpress_image_strategy']
            : 'upload_to_media',
        'wordpress_content_format' => 'html',
    ];
}

public function wordpressRestBaseUrl(): string
{
    $base = rtrim((string) $this->endpoint_url, '/');

    return str_ends_with($base, '/wp-json') ? $base : $base.'/wp-json';
}
```

在 `ArticleDistribution` 中，将 `remote_meta` 添加到 `$fillable`，转换为数组，并添加：

```php
public function wordpressPostId(): ?int
{
    if ($this->remote_id !== null && ctype_digit((string) $this->remote_id)) {
        return (int) $this->remote_id;
    }

    $meta = is_array($this->remote_meta) ? $this->remote_meta : [];
    $postId = $meta['wordpress_post_id'] ?? null;

    return is_numeric($postId) ? (int) $postId : null;
}
```

- [ ] **第 6 步：运行 schema 测试**

运行：

```bash
php artisan test tests/Unit/DistributionSchemaMigrationTest.php
```

预期：

```text
PASS
```

- [ ] **第7步：提交**

```bash
git add database/migrations/2026_05_17_000000_create_distribution_management_tables.php database/migrations/2026_05_23_000000_add_wordpress_distribution_columns.php app/Models/DistributionChannel.php app/Models/ArticleDistribution.php tests/Unit/DistributionSchemaMigrationTest.php
git commit -m "Add WordPress distribution schema support"
```

### 任务 2：引入 Distribution Publisher 接口和管理器

**文件：**

- 创建：`app/Services/GeoFlow/DistributionPublisherInterface.php`
- 创建：`app/Services/GeoFlow/DistributionPublisherManager.php`
- 创建：`app/Services/GeoFlow/GeoFlowAgentPublisher.php`
- 修改：`app/Services/GeoFlow/DistributionOrchestrator.php`
- 创建：`tests/Unit/DistributionPublisherManagerTest.php`

- [ ] **步骤 1：编写管理器测试**

创建 `tests/Unit/DistributionPublisherManagerTest.php`：

```php
<?php

namespace Tests\Unit;

use App\Models\DistributionChannel;
use App\Services\GeoFlow\DistributionPublisherManager;
use App\Services\GeoFlow\GeoFlowAgentPublisher;
use App\Services\GeoFlow\WordPressRestPublisher;
use Tests\TestCase;

class DistributionPublisherManagerTest extends TestCase
{
    public function test_it_resolves_geoflow_agent_publisher_by_default(): void
    {
        $channel = new DistributionChannel(['channel_type' => 'geoflow_agent']);
        $manager = app(DistributionPublisherManager::class);

        $this->assertInstanceOf(GeoFlowAgentPublisher::class, $manager->forChannel($channel));
    }

    public function test_it_resolves_wordpress_rest_publisher(): void
    {
        $channel = new DistributionChannel(['channel_type' => 'wordpress_rest']);
        $manager = app(DistributionPublisherManager::class);

        $this->assertInstanceOf(WordPressRestPublisher::class, $manager->forChannel($channel));
    }
}
```

此测试引用 `WordPressRestPublisher`，它是在任务 3 中创建的。如果先实现此任务，它可以一直保持失败，直到任务 3。

- [ ] **第 2 步：创建发布者接口**

创建 `app/Services/GeoFlow/DistributionPublisherInterface.php`：

```php
<?php

namespace App\Services\GeoFlow;

use App\Models\ArticleDistribution;
use App\Models\DistributionChannel;

interface DistributionPublisherInterface
{
    /**
     * @return array<string,mixed>
     */
    public function health(DistributionChannel $channel): array;

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    public function publish(ArticleDistribution $distribution, array $payload): array;

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    public function update(ArticleDistribution $distribution, array $payload): array;

    /**
     * @return array<string,mixed>
     */
    public function delete(ArticleDistribution $distribution): array;

    /**
     * @return array<string,mixed>
     */
    public function syncSiteSettings(DistributionChannel $channel): array;
}
```

- [ ] **第 3 步：包装现有智能体客户端**

创建 `app/Services/GeoFlow/GeoFlowAgentPublisher.php`：

```php
<?php

namespace App\Services\GeoFlow;

use App\Models\ArticleDistribution;
use App\Models\DistributionChannel;

class GeoFlowAgentPublisher implements DistributionPublisherInterface
{
    public function __construct(private readonly DistributionHttpClient $httpClient) {}

    public function health(DistributionChannel $channel): array
    {
        return $this->httpClient->health($channel);
    }

    public function publish(ArticleDistribution $distribution, array $payload): array
    {
        return $this->httpClient->send($distribution, $payload);
    }

    public function update(ArticleDistribution $distribution, array $payload): array
    {
        return $this->httpClient->updateArticle($distribution, $payload);
    }

    public function delete(ArticleDistribution $distribution): array
    {
        return $this->httpClient->deleteArticle($distribution);
    }

    public function syncSiteSettings(DistributionChannel $channel): array
    {
        return $this->httpClient->syncSiteSettings($channel);
    }
}
```

- [ ] **第 4 步：创建管理器**

创建 `app/Services/GeoFlow/DistributionPublisherManager.php`：

```php
<?php

namespace App\Services\GeoFlow;

use App\Models\DistributionChannel;
use RuntimeException;

class DistributionPublisherManager
{
    public function __construct(
        private readonly GeoFlowAgentPublisher $geoFlowAgentPublisher,
        private readonly WordPressRestPublisher $wordPressRestPublisher,
    ) {}

    public function forChannel(DistributionChannel $channel): DistributionPublisherInterface
    {
        return match ($channel->channelType()) {
            'geoflow_agent' => $this->geoFlowAgentPublisher,
            'wordpress_rest' => $this->wordPressRestPublisher,
            default => throw new RuntimeException('不支持的分发渠道类型：'.(string) $channel->channel_type),
        };
    }
}
```

- [ ] **第 5 步：更新编排器注入和调用**

将 `DistributionOrchestrator` 构造函数从：

```php
private readonly DistributionHttpClient $httpClient
```

至：

```php
private readonly DistributionPublisherManager $publisherManager
```

更改健康检查：

```php
return $this->publisherManager->forChannel($channel)->health($channel);
```

更改流程响应选择：

```php
$publisher = $this->publisherManager->forChannel($channel);
$response = match ((string) $distribution->action) {
    'update' => $publisher->update($distribution, $payload),
    'delete' => $publisher->delete($distribution),
    default => $publisher->publish($distribution, $payload),
};
```

更改即时操作响应：

```php
$publisher = $this->publisherManager->forChannel($channel);
$response = $action === 'delete'
    ? $publisher->delete($distribution)
    : $publisher->update($distribution, $payload);
```

- [ ] **第 6 步：运行现有分发测试**

运行：

```bash
php artisan test tests/Feature/AdminDistributionPageTest.php tests/Unit/DistributionQueueConfigurationTest.php tests/Unit/DistributionRetryPolicyTest.php
```

预期：

```text
PASS
```

- [ ] **第7步：提交**

```bash
git add app/Services/GeoFlow/DistributionPublisherInterface.php app/Services/GeoFlow/DistributionPublisherManager.php app/Services/GeoFlow/GeoFlowAgentPublisher.php app/Services/GeoFlow/DistributionOrchestrator.php tests/Unit/DistributionPublisherManagerTest.php
git commit -m "Introduce distribution publisher abstraction"
```

### 任务 3：实现 WordPress REST Publisher

**文件：**

- 创建：`app/Services/GeoFlow/WordPressRestPublisher.php`
- 创建：`tests/Unit/WordPressRestPublisherTest.php`
- 修改：`app/Services/GeoFlow/DistributionPublisherManager.php`

- [ ] **步骤 1：编写发布/更新/删除 HTTP 测试**

创建 `tests/Unit/WordPressRestPublisherTest.php`：

```php
<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\ArticleDistribution;
use App\Models\DistributionChannel;
use App\Models\DistributionChannelSecret;
use App\Services\GeoFlow\WordPressRestPublisher;
use App\Support\GeoFlow\ApiKeyCrypto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WordPressRestPublisherTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_publishes_article_to_wordpress_posts_endpoint(): void
    {
        Http::fake([
            'https://wp.example.com/wp-json/wp/v2/posts' => Http::response([
                'id' => 123,
                'link' => 'https://wp.example.com/hello-world/',
            ], 201),
        ]);

        [$channel, $distribution] = $this->makeDistribution();

        $result = app(WordPressRestPublisher::class)->publish($distribution, [
            'article' => [
                'title' => 'Hello World',
                'slug' => 'hello-world',
                'excerpt' => 'Short summary',
                'content_html' => '<p>Hello</p>',
                'keywords' => 'geo, ai',
                'meta_description' => 'Meta summary',
            ],
            'assets' => ['images' => []],
        ]);

        $this->assertSame('123', (string) $result['remote_id']);
        $this->assertSame('https://wp.example.com/hello-world/', $result['remote_url']);

        Http::assertSent(function ($request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://wp.example.com/wp-json/wp/v2/posts'
                && $request['title'] === 'Hello World'
                && $request['status'] === 'publish'
                && $request['content'] === '<p>Hello</p>';
        });
    }

    public function test_it_updates_existing_wordpress_post_id(): void
    {
        Http::fake([
            'https://wp.example.com/wp-json/wp/v2/posts/123' => Http::response([
                'id' => 123,
                'link' => 'https://wp.example.com/hello-world-updated/',
            ]),
        ]);

        [$channel, $distribution] = $this->makeDistribution(['remote_id' => '123']);

        $result = app(WordPressRestPublisher::class)->update($distribution, [
            'article' => [
                'title' => 'Hello Updated',
                'slug' => 'hello-world',
                'excerpt' => '',
                'content_html' => '<p>Updated</p>',
                'keywords' => '',
                'meta_description' => '',
            ],
            'assets' => ['images' => []],
        ]);

        $this->assertSame('123', (string) $result['remote_id']);
        $this->assertSame('https://wp.example.com/hello-world-updated/', $result['remote_url']);
    }

    public function test_it_deletes_existing_wordpress_post_id(): void
    {
        Http::fake([
            'https://wp.example.com/wp-json/wp/v2/posts/123*' => Http::response([
                'deleted' => false,
                'previous' => ['id' => 123],
            ]),
        ]);

        [$channel, $distribution] = $this->makeDistribution(['remote_id' => '123']);

        $result = app(WordPressRestPublisher::class)->delete($distribution);

        $this->assertSame('123', (string) $result['remote_id']);
        $this->assertTrue($result['deleted']);
    }

    public function test_health_uses_authenticated_current_user_endpoint(): void
    {
        Http::fake([
            'https://wp.example.com/wp-json/' => Http::response(['name' => 'WordPress']),
            'https://wp.example.com/wp-json/wp/v2/users/me*' => Http::response([
                'id' => 7,
                'name' => 'Editor',
                'capabilities' => ['edit_posts' => true, 'publish_posts' => true, 'upload_files' => true],
            ]),
        ]);

        [$channel] = $this->makeDistribution();

        $result = app(WordPressRestPublisher::class)->health($channel);

        $this->assertTrue($result['ok']);
        $this->assertSame('wordpress_rest', $result['channel_type']);
        $this->assertSame(7, $result['user_id']);
    }

    /**
     * @param  array<string,mixed>  $distributionOverrides
     * @return array{0:DistributionChannel,1:ArticleDistribution}
     */
    private function makeDistribution(array $distributionOverrides = []): array
    {
        $channel = DistributionChannel::query()->create([
            'name' => 'WP',
            'domain' => 'wp.example.com',
            'endpoint_url' => 'https://wp.example.com',
            'channel_type' => 'wordpress_rest',
            'channel_config' => [
                'wordpress_username' => 'editor',
                'wordpress_post_status' => 'publish',
                'wordpress_category_strategy' => 'match_or_create',
                'wordpress_tag_strategy' => 'keywords_to_tags',
                'wordpress_image_strategy' => 'keep_original',
            ],
            'status' => 'active',
        ]);

        DistributionChannelSecret::query()->create([
            'distribution_channel_id' => (int) $channel->id,
            'key_id' => 'wp_test',
            'secret_ciphertext' => app(ApiKeyCrypto::class)->encrypt('app password'),
            'status' => 'active',
            'scopes' => ['wordpress.rest'],
        ]);

        $article = Article::factory()->create([
            'title' => 'Hello World',
            'slug' => 'hello-world',
            'status' => 'published',
        ]);

        $distribution = ArticleDistribution::query()->create(array_merge([
            'article_id' => (int) $article->id,
            'distribution_channel_id' => (int) $channel->id,
            'action' => 'publish',
            'status' => 'queued',
            'idempotency_key' => 'wp-test-key',
        ], $distributionOverrides));

        return [$channel, $distribution];
    }
}
```

- [ ] **步骤 2：创建发布器实现**

创建 `app/Services/GeoFlow/WordPressRestPublisher.php`：

```php
<?php

namespace App\Services\GeoFlow;

use App\Models\ArticleDistribution;
use App\Models\DistributionChannel;
use App\Models\DistributionChannelSecret;
use App\Support\GeoFlow\ApiKeyCrypto;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WordPressRestPublisher implements DistributionPublisherInterface
{
    public function __construct(
        private readonly ApiKeyCrypto $apiKeyCrypto,
        private readonly WordPressMediaSyncService $mediaSyncService,
        private readonly WordPressTaxonomySyncService $taxonomySyncService,
    ) {}

    public function health(DistributionChannel $channel): array
    {
        $this->request($channel)->get($channel->wordpressRestBaseUrl())->throw();

        $response = $this->request($channel)
            ->get($channel->wordpressRestBaseUrl().'/wp/v2/users/me', ['context' => 'edit']);
        $this->throwIfFailed($response, 'WordPress 健康检查');
        $user = $response->json();
        $capabilities = is_array($user['capabilities'] ?? null) ? $user['capabilities'] : [];

        return [
            'ok' => true,
            'channel_type' => 'wordpress_rest',
            'rest_base_url' => $channel->wordpressRestBaseUrl(),
            'user_id' => (int) ($user['id'] ?? 0),
            'user_name' => (string) ($user['name'] ?? ''),
            'can_edit_posts' => (bool) ($capabilities['edit_posts'] ?? false),
            'can_publish_posts' => (bool) ($capabilities['publish_posts'] ?? false),
            'can_upload_files' => (bool) ($capabilities['upload_files'] ?? false),
        ];
    }

    public function publish(ArticleDistribution $distribution, array $payload): array
    {
        $distribution->loadMissing('channel');
        $channel = $this->channel($distribution);
        $postPayload = $this->postPayload($channel, $distribution, $payload);

        $response = $this->request($channel)->post($channel->wordpressRestBaseUrl().'/wp/v2/posts', $postPayload);
        $this->throwIfFailed($response, 'WordPress 文章发布');

        return $this->postResult($response);
    }

    public function update(ArticleDistribution $distribution, array $payload): array
    {
        $distribution->loadMissing('channel');
        $channel = $this->channel($distribution);
        $postId = $distribution->wordpressPostId();
        if (! $postId) {
            return $this->publish($distribution, $payload);
        }

        $response = $this->request($channel)
            ->post($channel->wordpressRestBaseUrl().'/wp/v2/posts/'.$postId, $this->postPayload($channel, $distribution, $payload));
        $this->throwIfFailed($response, 'WordPress 文章更新');

        return $this->postResult($response);
    }

    public function delete(ArticleDistribution $distribution): array
    {
        $distribution->loadMissing('channel');
        $channel = $this->channel($distribution);
        $postId = $distribution->wordpressPostId();
        if (! $postId) {
            return ['deleted' => true, 'remote_id' => null, 'remote_url' => null, 'message' => 'missing_remote_post_id'];
        }

        $response = $this->request($channel)
            ->delete($channel->wordpressRestBaseUrl().'/wp/v2/posts/'.$postId, ['force' => false]);
        $this->throwIfFailed($response, 'WordPress 文章删除');

        return ['deleted' => true, 'remote_id' => (string) $postId, 'remote_url' => null];
    }

    public function syncSiteSettings(DistributionChannel $channel): array
    {
        $settings = $channel->resolvedSiteSettings();
        $payload = [
            'title' => $settings['site_name'],
            'description' => $settings['site_description'],
            'posts_per_page' => $settings['per_page'],
        ];

        $response = $this->request($channel)->post($channel->wordpressRestBaseUrl().'/wp/v2/settings', $payload);
        $this->throwIfFailed($response, 'WordPress 站点设置同步');

        return ['ok' => true, 'settings' => $payload];
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function postPayload(DistributionChannel $channel, ArticleDistribution $distribution, array $payload): array
    {
        $article = is_array($payload['article'] ?? null) ? $payload['article'] : [];
        $config = $channel->resolvedChannelConfig();
        $contentHtml = (string) ($article['content_html'] ?? '');

        if ($config['wordpress_image_strategy'] === 'upload_to_media') {
            $contentHtml = $this->mediaSyncService->rewriteContentImages($channel, $payload, $contentHtml);
        }

        $postPayload = [
            'title' => (string) ($article['title'] ?? ''),
            'slug' => (string) ($article['slug'] ?? ''),
            'status' => (string) $config['wordpress_post_status'],
            'content' => $contentHtml,
            'excerpt' => (string) ($article['excerpt'] ?? ''),
        ];

        $categoryIds = $this->taxonomySyncService->categoryIds($channel, $payload);
        if ($categoryIds !== []) {
            $postPayload['categories'] = $categoryIds;
        }

        $tagIds = $this->taxonomySyncService->tagIds($channel, $payload);
        if ($tagIds !== []) {
            $postPayload['tags'] = $tagIds;
        }

        return $postPayload;
    }

    private function request(DistributionChannel $channel): PendingRequest
    {
        $channel->loadMissing('activeSecret');
        $config = $channel->resolvedChannelConfig();
        $username = (string) $config['wordpress_username'];
        $secret = $channel->activeSecret;
        if (! $secret instanceof DistributionChannelSecret || $username === '') {
            throw new RuntimeException('WordPress 渠道缺少用户名或 Application Password。');
        }

        $applicationPassword = $this->apiKeyCrypto->decrypt((string) $secret->secret_ciphertext);
        if ($applicationPassword === '') {
            throw new RuntimeException('WordPress Application Password 解密失败。');
        }

        return Http::timeout(30)->acceptJson()->asJson()->withBasicAuth($username, $applicationPassword);
    }

    private function channel(ArticleDistribution $distribution): DistributionChannel
    {
        if (! $distribution->channel instanceof DistributionChannel) {
            throw new RuntimeException('分发记录缺少 WordPress 渠道。');
        }

        return $distribution->channel;
    }

    private function throwIfFailed(Response $response, string $operation): void
    {
        if (! $response->failed()) {
            return;
        }

        $body = trim(strip_tags((string) $response->body()));
        $summary = mb_strlen($body) > 300 ? mb_substr($body, 0, 300).'...' : $body;

        throw new RuntimeException($operation.'失败：HTTP '.$response->status().($summary !== '' ? ' '.$summary : ''));
    }

    /**
     * @return array<string,mixed>
     */
    private function postResult(Response $response): array
    {
        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('WordPress 返回内容不是有效 JSON。');
        }

        return [
            'remote_id' => (string) ($json['id'] ?? ''),
            'remote_url' => (string) ($json['link'] ?? ''),
            'remote_meta' => [
                'wordpress_post_id' => (int) ($json['id'] ?? 0),
            ],
        ];
    }
}
```

- [ ] **第 3 步：运行测试**

运行：

```bash
php artisan test tests/Unit/WordPressRestPublisherTest.php tests/Unit/DistributionPublisherManagerTest.php
```

预期：

```text
PASS
```

- [ ] **步骤 4：提交**

```bash
git add app/Services/GeoFlow/WordPressRestPublisher.php tests/Unit/WordPressRestPublisherTest.php app/Services/GeoFlow/DistributionPublisherManager.php
git commit -m "Add WordPress REST distribution publisher"
```

### 任务 4：持久化发布者响应中的远程元数据

**文件：**

- 修改：`app/Services/GeoFlow/DistributionOrchestrator.php`
- 修改：`tests/Unit/WordPressRestPublisherTest.php`

- [ ] **步骤 1：向发布测试添加断言**

在 WordPress 发布器的发布集成路径中，或在新的编排器测试中，断言成功处理后 `remote_meta.wordpress_post_id` 已被存储：

```php
$distribution->refresh();
$this->assertSame(123, $distribution->remote_meta['wordpress_post_id'] ?? null);
```

- [ ] **第 2 步：更新编排器保存块**

在 `DistributionOrchestrator::process()` 和 `sendImmediateAction()` 中，从响应中合并 `remote_meta`：

```php
$existingMeta = is_array($distribution->remote_meta) ? $distribution->remote_meta : [];
$responseMeta = is_array($response['remote_meta'] ?? null) ? $response['remote_meta'] : [];
```

然后添加到 `forceFill`：

```php
'remote_meta' => array_replace($existingMeta, $responseMeta),
```

- [ ] **第 3 步：运行分发测试**

运行：

```bash
php artisan test tests/Feature/AdminDistributionPageTest.php tests/Unit/WordPressRestPublisherTest.php
```

预期：

```text
PASS
```

- [ ] **步骤 4：提交**

```bash
git add app/Services/GeoFlow/DistributionOrchestrator.php tests/Unit/WordPressRestPublisherTest.php
git commit -m "Persist distribution remote metadata"
```

### 任务 5：添加 WordPress 分类法同步

**文件：**

- 创建：`app/Services/GeoFlow/WordPressTaxonomySyncService.php`
- 创建：`tests/Unit/WordPressTaxonomySyncServiceTest.php`
- 修改：`app/Services/GeoFlow/WordPressRestPublisher.php`

- [ ] **步骤 1：编写分类法测试**

创建覆盖以下内容的测试：

```php
public function test_it_matches_existing_category_by_slug(): void
public function test_it_creates_category_when_strategy_is_match_or_create(): void
public function test_it_returns_empty_categories_when_match_only_misses(): void
public function test_it_converts_keywords_to_wordpress_tags(): void
public function test_it_skips_tags_when_tag_strategy_disabled(): void
```

使用 `Http::fake()` 来：

```text
GET  https://wp.example.com/wp-json/wp/v2/categories?slug=geo
POST https://wp.example.com/wp-json/wp/v2/categories
GET  https://wp.example.com/wp-json/wp/v2/tags?slug=ai
POST https://wp.example.com/wp-json/wp/v2/tags
```

- [ ] **第 2 步：实现分类法服务**

使用以下内容创建 `WordPressTaxonomySyncService`：

```php
public function categoryIds(DistributionChannel $channel, array $payload): array
public function tagIds(DistributionChannel $channel, array $payload): array
private function findTermId(DistributionChannel $channel, string $taxonomy, string $slug): ?int
private function createTermId(DistributionChannel $channel, string $taxonomy, string $name, string $slug): ?int
```

Slug 规范化：

```php
private function slug(string $value): string
{
    $slug = Str::slug($value);

    return $slug !== '' ? $slug : substr(hash('sha256', $value), 0, 12);
}
```

关键词拆分：

```php
preg_split('/[,，;；\n]+/u', $keywords)
```

- [ ] **第 3 步：将服务注入发布者**

`WordPressRestPublisher::postPayload()` 已经调用：

```php
$this->taxonomySyncService->categoryIds($channel, $payload);
$this->taxonomySyncService->tagIds($channel, $payload);
```

确保该服务使用与发布器相同的 Basic Auth 请求模式。如果重复变得明显，请在此任务中提取 `WordPressRestRequestFactory`，而不是复制凭据解析逻辑。

- [ ] **第 4 步：运行测试**

```bash
php artisan test tests/Unit/WordPressTaxonomySyncServiceTest.php tests/Unit/WordPressRestPublisherTest.php
```

预期：

```text
PASS
```

- [ ] **第5步：提交**

```bash
git add app/Services/GeoFlow/WordPressTaxonomySyncService.php app/Services/GeoFlow/WordPressRestPublisher.php tests/Unit/WordPressTaxonomySyncServiceTest.php tests/Unit/WordPressRestPublisherTest.php
git commit -m "Sync WordPress categories and tags"
```

### 任务 6：添加 WordPress 媒体上传和内容图片重写

**文件：**

- 创建：`app/Services/GeoFlow/WordPressMediaSyncService.php`
- 创建：`tests/Unit/WordPressMediaSyncServiceTest.php`
- 修改：`app/Services/GeoFlow/WordPressRestPublisher.php`

- [ ] **步骤 1：编写媒体同步测试**

封面：

```php
public function test_it_uploads_base64_image_asset_to_wordpress_media(): void
public function test_it_rewrites_content_html_image_src_to_wordpress_media_url(): void
public function test_it_keeps_original_src_when_asset_has_no_base64_content(): void
public function test_it_skips_upload_when_image_strategy_is_keep_original(): void
```

预期的模拟上传：

```php
Http::fake([
    'https://wp.example.com/wp-json/wp/v2/media' => Http::response([
        'id' => 456,
        'source_url' => 'https://wp.example.com/wp-content/uploads/image.jpg',
    ], 201),
]);
```

- [ ] **第 2 步：实现媒体同步服务**

服务应：

- 读取 `assets.images`。
- 仅上传具有 `content_base64` 的图片。
- 将二进制正文发送到 `/wp/v2/media`。
- 设置请求头：

```php
[
    'Content-Disposition' => 'attachment; filename="'.$filename.'"',
    'Content-Type' => $mimeType,
]
```

- 替换 `content_html` 中原始 `source_url` 的精确出现。
- 如果非关键图片上传失败，则返回未更改的 HTML，但尽可能在发布者响应中包含警告上下文。

- [ ] **第 3 步：运行测试**

```bash
php artisan test tests/Unit/WordPressMediaSyncServiceTest.php tests/Unit/WordPressRestPublisherTest.php
```

预期：

```text
PASS
```

- [ ] **步骤 4：提交**

```bash
git add app/Services/GeoFlow/WordPressMediaSyncService.php app/Services/GeoFlow/WordPressRestPublisher.php tests/Unit/WordPressMediaSyncServiceTest.php tests/Unit/WordPressRestPublisherTest.php
git commit -m "Upload WordPress media during distribution"
```

### 任务 7：为 WordPress 渠道添加管理表单

**文件：**

- 修改：`app/Http/Controllers/Admin/DistributionController.php`
- 修改：`resources/views/admin/distribution/create.blade.php`
- 修改：`resources/views/admin/distribution/edit.blade.php`
- 修改：`resources/views/admin/distribution/show.blade.php`
- 修改：`resources/views/admin/distribution/index.blade.php`
- 修改：`lang/zh_CN/admin.php`
- 修改：`lang/en/admin.php`
- 修改：`lang/ja/admin.php`
- 修改：`lang/es/admin.php`
- 修改：`lang/ru/admin.php`
- 修改：`lang/pt_BR/admin.php`
- 扩展：`tests/Feature/AdminDistributionPageTest.php`

- [ ] **步骤 1：编写功能测试**

添加测试：

```php
public function test_admin_can_create_wordpress_distribution_channel(): void
public function test_wordpress_distribution_channel_form_shows_wordpress_fields(): void
public function test_wordpress_distribution_detail_hides_target_site_package(): void
public function test_wordpress_distribution_detail_shows_connection_guide(): void
public function test_wordpress_distribution_channel_requires_application_password_on_create(): void
```

预期的数据库断言：

```php
$this->assertDatabaseHas('distribution_channels', [
    'name' => 'WordPress 站点',
    'channel_type' => 'wordpress_rest',
    'endpoint_url' => 'https://wp.example.com',
]);
```

预期的配置断言：

```php
$channel = DistributionChannel::query()->where('name', 'WordPress 站点')->firstOrFail();
$this->assertSame('editor', $channel->resolvedChannelConfig()['wordpress_username']);
```

- [ ] **第 2 步：验证并持久化渠道类型**

在 `validateChannel()` 中，添加：

```php
'channel_type' => ['nullable', 'string', 'in:geoflow_agent,wordpress_rest'],
'wordpress_username' => ['nullable', 'string', 'max:120'],
'wordpress_application_password' => ['nullable', 'string', 'max:255'],
'wordpress_post_status' => ['nullable', 'string', 'in:publish,draft,pending,private'],
'wordpress_category_strategy' => ['nullable', 'string', 'in:match_or_create,match_only,fixed'],
'wordpress_fixed_category' => ['nullable', 'string', 'max:120'],
'wordpress_tag_strategy' => ['nullable', 'string', 'in:keywords_to_tags,disabled'],
'wordpress_image_strategy' => ['nullable', 'string', 'in:upload_to_media,keep_original'],
```

在 WordPress 创建时要求用户名和应用程序密码：

```php
if (($payload['channel_type'] ?? 'geoflow_agent') === 'wordpress_rest') {
    if (! filled($payload['wordpress_username'] ?? null)) {
        throw ValidationException::withMessages(['wordpress_username' => __('admin.distribution.validation.wordpress_username')]);
    }
    if ($request->isMethod('post') && ! filled($payload['wordpress_application_password'] ?? null)) {
        throw ValidationException::withMessages(['wordpress_application_password' => __('admin.distribution.validation.wordpress_application_password')]);
    }
}
```

- [ ] **第 3 步：存储 WordPress 配置和密钥**

从 `store()` 和 `update()` 中的载荷设置 `channel_type`。

对于 WordPress 创建，使用以下命令创建机密：

```php
DistributionChannelSecret::query()->create([
    'distribution_channel_id' => (int) $channel->id,
    'key_id' => 'wp_'.Str::lower(Str::random(18)),
    'secret_ciphertext' => $this->apiKeyCrypto->encrypt((string) $payload['wordpress_application_password']),
    'status' => 'active',
    'scopes' => ['wordpress.rest'],
]);
```

对于 WordPress 更新，仅在 `wordpress_application_password` 已填写时才替换机密。

- [ ] **第 4 步：添加条件式 Blade 字段集**

在创建/编辑表单中：

- Channel 类型分段单选。
- 现有的 GEOFlow Agent 字段对 `geoflow_agent` 可见。
- 对 `wordpress_rest` 可见的 WordPress 字段。

使用简单的 data 属性和一小段内联脚本：

```html
data-channel-type-panel="geoflow_agent"
data-channel-type-panel="wordpress_rest"
```

```js
document.addEventListener('change', function (event) {
  if (!event.target.matches('[name="channel_type"]')) return;
  document.querySelectorAll('[data-channel-type-panel]').forEach(function (panel) {
    panel.classList.toggle('hidden', panel.dataset.channelTypePanel !== event.target.value);
  });
});
```

- [ ] **第 5 步：调整详情页模块**

在 `show.blade.php` 中：

```blade
@if ($channel->isGeoFlowAgent())
    {{-- target package, rewrite rules, Agent guide --}}
@endif

@if ($channel->isWordPressRest())
    {{-- WordPress guide --}}
@endif
```

WordPress 指南文案：

- 在 WordPress 用户资料中创建应用程序密码。
- 将用户名和应用程序密码粘贴到 GEOFlow 中。
- 点击健康检查。
- 先发布一篇测试文章作为草稿。

- [ ] **第 6 步：运行管理后台测试**

```bash
php artisan test tests/Feature/AdminDistributionPageTest.php
```

预期：

```text
PASS
```

- [ ] **第7步：提交**

```bash
git add app/Http/Controllers/Admin/DistributionController.php resources/views/admin/distribution/create.blade.php resources/views/admin/distribution/edit.blade.php resources/views/admin/distribution/show.blade.php resources/views/admin/distribution/index.blade.php lang/zh_CN/admin.php lang/en/admin.php lang/ja/admin.php lang/es/admin.php lang/ru/admin.php lang/pt_BR/admin.php tests/Feature/AdminDistributionPageTest.php
git commit -m "Add WordPress distribution admin UI"
```

### 任务 8：添加 WordPress 健康检查、设置同步和队列集成测试

**文件：**

- 扩展：`tests/Feature/AdminDistributionPageTest.php`
- 扩展：`tests/Unit/WordPressRestPublisherTest.php`
- 修改：`app/Http/Controllers/Admin/DistributionController.php`

- [ ] **步骤 1：添加健康功能测试**

功能测试应：

- 创建具有有效密钥的 WordPress 渠道。
- 模拟 `/wp-json/` 和 `/wp/v2/users/me`。
- POST `admin.distribution.health`。
- 断言 `last_health_status = ok`。

- [ ] **步骤 2：添加设置同步测试**

伪造：

```text
POST https://wp.example.com/wp-json/wp/v2/settings
```

断言请求包含：

```php
title
description
posts_per_page
```

- [ ] **第 3 步：添加队列处理测试**

创建绑定到 WordPress 渠道的任务，发布文章，调用 orchestrator 进程，并断言：

```php
$this->assertDatabaseHas('article_distributions', [
    'status' => 'synced',
    'remote_id' => '123',
    'remote_url' => 'https://wp.example.com/article/',
]);
```

- [ ] **第 4 步：运行测试**

```bash
php artisan test tests/Feature/AdminDistributionPageTest.php tests/Unit/WordPressRestPublisherTest.php
```

预期：

```text
PASS
```

- [ ] **第5步：提交**

```bash
git add tests/Feature/AdminDistributionPageTest.php tests/Unit/WordPressRestPublisherTest.php app/Http/Controllers/Admin/DistributionController.php
git commit -m "Cover WordPress distribution workflows"
```

### 任务 9：文档与最终回归

**文件：**

- 修改：`docs/distribution/unified-distribution-implementation-plan.md`
- 修改：`docs/CHANGELOG.md`
- 修改：`docs/CHANGELOG_en.md`

- [ ] **步骤 1：更新文档**

添加一个 WordPress 区块：

```markdown
## WordPress 渠道

GEOFlow 2.x 支持通过 WordPress Core REST API 将文章分发到 WordPress 站点。管理员需要在 WordPress 用户资料页创建 Application Password，并在 GEOFlow 分发渠道中选择“WordPress 站点”后填写站点地址、用户名和 Application Password。

首版支持发布、更新、删除、图片上传到媒体库、分类/标签同步和基础站点设置同步。`llms.txt`、TXT 地图和 Schema 深度控制属于后续 WordPress Connector 插件增强能力。
```

- [ ] **第 2 步：更新变更日志**

`docs/CHANGELOG.md`:

```markdown
## 2026-05-23

### 分发管理

- 新增 WordPress REST API 分发渠道设计与实现：
  - 支持 Application Password 鉴权。
  - 支持文章发布、更新、删除、图片上传、分类/标签同步和基础站点设置同步。
  - 分发渠道后台按 GEOFlow Agent 与 WordPress 站点展示不同配置和接入引导。
```

`docs/CHANGELOG_en.md`:

```markdown
## 2026-05-23

### Distribution Management

- Added WordPress REST API distribution channel support:
  - Supports Application Password authentication.
  - Supports post publish, update, delete, media upload, category/tag sync, and basic site settings sync.
  - Shows different configuration and onboarding guidance for GEOFlow Agent and WordPress channels.
```

- [ ] **第 3 步：运行聚焦回归测试**

```bash
php artisan test tests/Feature/AdminDistributionPageTest.php tests/Unit/DistributionQueueConfigurationTest.php tests/Unit/DistributionRetryPolicyTest.php tests/Unit/DistributionSchemaMigrationTest.php tests/Unit/DistributionPublisherManagerTest.php tests/Unit/WordPressRestPublisherTest.php tests/Unit/WordPressTaxonomySyncServiceTest.php tests/Unit/WordPressMediaSyncServiceTest.php
```

预期：

```text
PASS
```

- [ ] **第 4 步：运行代码风格和差异检查**

```bash
git diff --check
```

预期：无输出。

- [ ] **第5步：提交**

```bash
git add docs/distribution/unified-distribution-implementation-plan.md docs/CHANGELOG.md docs/CHANGELOG_en.md
git commit -m "Document WordPress distribution support"
```

---

## 推出说明

- 现有的 `geoflow_agent` 渠道必须继续完全像以前一样发布。
- WordPress 渠道不应显示目标站点包下载、重写规则或 GEOFlow Agent 机密指令。
- WordPress 应用程序密码在保存后绝不应显示。如果之后需要显示，请复用当前的超级管理员密码确认流程。
- 在首次真实 WordPress 测试中，使用默认文章状态 `draft`，以避免意外发布公开内容。
- 如果 WordPress 返回 `401` 或 `403`，显示关于应用程序密码、用户名、HTTPS 和用户能力的特定消息。
- 如果 WordPress 返回 `rest_cannot_create`，显示一条消息，说明该用户角色缺少 `edit_posts` 或 `publish_posts`。

## 自我审查

- 规范覆盖范围：涵盖渠道类型、配置、通信、内容管理、媒体、分类/标签、设置、UI、测试和文档。
- 占位符扫描：没有任何实现步骤依赖未指定的文件或未知路由。
- 类型一致性：`channel_type=wordpress_rest`、`channel_config`、`remote_meta` 和 `wordpressPostId()` 名称在 schema、model、publisher 和 tests 中保持一致。
