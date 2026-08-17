<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AiModel;
use App\Models\Author;
use App\Models\Category;
use App\Models\KnowledgeBase;
use App\Models\Prompt;
use App\Models\TitleLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAiConfiguratorWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_ai_configurator_workbench(): void
    {
        $this->seedWorkbenchCatalog();

        $response = $this->actingAs($this->createAdmin(), 'admin')
            ->get(route('admin.ai.configurator'));

        $response
            ->assertOk()
            ->assertSee(__('admin.ai_configurator.intent_title'))
            ->assertSee(__('admin.ai_configurator.skills.task.title'))
            ->assertSee(__('admin.ai_configurator.skills.article.title'))
            ->assertSee(route('admin.ai.configurator.resolve'), false);
    }

    public function test_workbench_resolves_article_requests_to_prefilled_article_form(): void
    {
        $this->seedWorkbenchCatalog();

        $response = $this->actingAs($this->createAdmin(), 'admin')
            ->postJson(route('admin.ai.configurator.resolve'), [
                'input' => '生成一篇关于 AI 搜索可见性的文章草稿',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('skill.key', 'article-builder');

        $redirectUrl = (string) $response->json('redirect_url');
        $this->assertStringContainsString(route('admin.articles.create'), $redirectUrl);
        $this->assertStringContainsString('title=', $redirectUrl);
        $this->assertStringContainsString('is_ai_generated=1', $redirectUrl);
    }

    public function test_workbench_resolves_task_requests_to_prefilled_task_form(): void
    {
        $this->seedWorkbenchCatalog();

        $response = $this->actingAs($this->createAdmin(), 'admin')
            ->postJson(route('admin.ai.configurator.resolve'), [
                'input' => '创建一个自动发文任务，每小时生成内容',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('skill.key', 'task-builder')
            ->assertJsonPath('prefill.status', 'paused')
            ->assertJsonPath('prefill.publish_scope', 'local_only');

        $this->assertStringContainsString(route('admin.tasks.create'), (string) $response->json('redirect_url'));
    }

    public function test_create_pages_accept_workbench_prefill_queries(): void
    {
        $this->seedWorkbenchCatalog();
        $admin = $this->createAdmin();

        $task = $this->actingAs($admin, 'admin')
            ->get(route('admin.tasks.create', [
                'task_name' => '智能工作台任务',
                'status' => 'active',
                'article_limit' => 3,
            ]))
            ->assertOk()
            ->viewData('taskForm');

        $this->assertSame('智能工作台任务', (string) ($task['task_name'] ?? ''));
        $this->assertSame('active', (string) ($task['status'] ?? ''));
        $this->assertSame(3, (int) ($task['article_limit'] ?? 0));

        $article = $this->actingAs($admin, 'admin')
            ->get(route('admin.articles.create', [
                'title' => '工作台文章标题',
                'content' => '# 工作台文章标题',
                'keywords' => 'workbench, geoflow',
            ]))
            ->assertOk()
            ->viewData('articleForm');

        $this->assertSame('工作台文章标题', (string) ($article['title'] ?? ''));
        $this->assertSame('# 工作台文章标题', (string) ($article['content'] ?? ''));
        $this->assertSame('workbench, geoflow', (string) ($article['keywords'] ?? ''));
    }

    private function createAdmin(): Admin
    {
        return Admin::query()->create([
            'username' => 'ai_workbench_admin',
            'password' => 'secret-123',
            'email' => 'ai-workbench@example.com',
            'display_name' => 'AI Workbench Admin',
            'role' => 'super_admin',
            'status' => 'active',
        ]);
    }

    private function seedWorkbenchCatalog(): void
    {
        Category::query()->create([
            'name' => 'AI Visibility',
            'slug' => 'ai-visibility',
            'sort_order' => 1,
        ]);

        Author::query()->create([
            'name' => 'Workbench Author',
            'email' => 'workbench-author@example.com',
        ]);

        AiModel::query()->create([
            'name' => 'Workbench Chat',
            'version' => 'test',
            'api_key' => '',
            'model_id' => 'workbench-chat',
            'model_type' => 'chat',
            'api_url' => 'https://ai.test',
            'failover_priority' => 100,
            'daily_limit' => 0,
            'used_today' => 0,
            'total_used' => 0,
            'status' => 'active',
        ]);

        Prompt::query()->create([
            'name' => 'Workbench Prompt',
            'type' => 'content',
            'content' => 'Write about {{title}}.',
        ]);

        TitleLibrary::query()->create([
            'name' => 'Workbench Titles',
            'description' => 'Titles for workbench tests',
            'title_count' => 0,
        ]);

        KnowledgeBase::query()->create([
            'name' => 'Workbench Knowledge',
            'content' => 'AI visibility knowledge.',
            'character_count' => 24,
        ]);
    }
}
