<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Task;
use App\Services\GeoFlow\ExecutorJobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminListPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Admin::query()->create([
            'username' => 'pagination_admin',
            'password' => 'secret-123',
            'role' => 'super_admin',
            'status' => 'active',
        ]), 'admin');
    }

    public function test_task_pages_and_health_polling_share_ten_row_boundaries(): void
    {
        foreach (range(1, 11) as $index) {
            Task::query()->create(['name' => 'Task '.$index, 'status' => 'paused']);
        }

        $this->get(route('admin.tasks.index'))
            ->assertOk()
            ->assertViewHas('tasks', fn ($tasks) => count($tasks) === 10)
            ->assertSee('aria-current="page"', false);

        $this->get(route('admin.tasks.index', ['page' => 2]))
            ->assertOk()
            ->assertViewHas('tasks', fn ($tasks) => count($tasks) === 1);

        $this->getJson(route('admin.tasks.health', ['page' => 2]))
            ->assertOk()
            ->assertJsonPath('pagination.per_page', 10)
            ->assertJsonCount(1, 'tasks');
    }

    public function test_executor_node_pagination_keeps_job_page_independent(): void
    {
        foreach (range(1, 11) as $index) {
            app(ExecutorJobService::class)->createNode(['name' => 'Node '.$index]);
        }

        $this->get(route('admin.executor-nodes.index', ['node_page' => 2, 'job_page' => 1]))
            ->assertOk()
            ->assertViewHas('nodes', fn ($nodes) => $nodes->count() === 1
                && $nodes->perPage() === 10 && $nodes->currentPage() === 2)
            ->assertViewHas('jobs', fn ($jobs) => $jobs->perPage() === 10
                && $jobs->currentPage() === 1)
            ->assertSee('node_page=1', false)
            ->assertSee('job_page=1', false);
    }

    public function test_article_page_size_is_fixed_even_with_an_old_page_size_in_the_url(): void
    {
        $this->get(route('admin.articles.index', ['per_page' => 100]))
            ->assertOk()
            ->assertViewHas('articles', fn ($articles) => $articles->perPage() === 10);

        $this->get(route('admin.manual-publications.index'))
            ->assertOk()
            ->assertViewHas('publications', fn ($publications) => $publications->perPage() === 10)
            ->assertSee('aria-disabled="true"', false);
    }
}
