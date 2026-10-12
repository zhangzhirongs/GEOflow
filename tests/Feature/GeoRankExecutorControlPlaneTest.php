<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\ExecutorJob;
use App\Models\ExecutorNode;
use App\Models\ManualPublicationAccount;
use App\Models\ManualPublicationPersona;
use App\Models\SocialPublication;
use App\Services\GeoFlow\ExecutorJobService;
use App\Services\GeoFlow\SocialPublicationOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeoRankExecutorControlPlaneTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_node_and_pairing_json_is_shown_once(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.executor-nodes.store'), [
                'name' => 'office-pc',
                'base_url' => 'http://127.0.0.1:8000',
            ]);

        $response->assertRedirect(route('admin.executor-nodes.index'));
        $pairing = session('georank_pairing');
        $this->assertIsArray($pairing);
        $this->assertSame('office-pc', $pairing['Slug']);
        $this->assertSame('remote', $pairing['Mode']);
        $this->assertSame('http://127.0.0.1:8000', $pairing['BaseUrl']);
        $this->assertNotEmpty($pairing['NodeKey']);

        $node = ExecutorNode::query()->firstOrFail();
        $this->assertSame(hash('sha256', $pairing['NodeKey']), $node->node_key_hash);
        $this->assertStringNotContainsString($pairing['NodeKey'], $node->getRawOriginal('node_key_hash'));
    }

    public function test_node_api_rejects_bad_key_and_secret_fields(): void
    {
        $created = app(ExecutorJobService::class)->createNode(['name' => 'office-pc', 'base_url' => 'http://127.0.0.1:8000']);
        $pairing = $created['pairing'];

        $this->postJson('/api/node/heartbeat', ['load' => 0], [
            'Authorization' => 'Bearer wrong',
            'X-GeoRank-Slug' => $pairing['Slug'],
        ])->assertUnauthorized()->assertJson(['error' => 'node_unauthorized']);

        $this->postJson('/api/node/heartbeat', ['load' => 0, 'cookies' => ['sid=1']], [
            'Authorization' => 'Bearer '.$pairing['NodeKey'],
            'X-GeoRank-Slug' => $pairing['Slug'],
        ])->assertStatus(422);
    }

    public function test_heartbeat_claim_and_report_login_job(): void
    {
        $admin = $this->admin();
        $persona = $this->persona($admin);
        $created = app(ExecutorJobService::class)->createNode(['name' => 'office-pc', 'base_url' => 'http://127.0.0.1:8000']);
        $pairing = $created['pairing'];
        $node = $created['node'];

        $account = ManualPublicationAccount::query()->create([
            'persona_id' => $persona->id,
            'platform' => ManualPublicationAccount::PLATFORM_XIAOHONGSHU,
            'account_name' => 'XHS',
            'executor_node_id' => $node->id,
            'auto_publish_enabled' => true,
            'is_active' => true,
            'created_by_admin_id' => $admin->id,
        ]);

        $headers = [
            'Authorization' => 'Bearer '.$pairing['NodeKey'],
            'X-GeoRank-Slug' => $pairing['Slug'],
        ];

        $this->postJson('/api/node/heartbeat', [
            'load' => 0,
            'maxConcurrency' => 1,
            'version' => '0.1.0',
            'sessions' => [],
        ], $headers)->assertOk()->assertJsonPath('accounts.0.id', (string) $account->id);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.manual-publications.settings.accounts.executor-login', ['accountId' => $account->id]))
            ->assertRedirect();

        $claim = $this->postJson('/api/node/jobs/claim', [], $headers);
        $claim->assertOk();
        $job = $claim->json('job');
        $this->assertSame('login', $job['type']);
        $this->assertSame('xiaohongshu', $job['platform']);
        $this->assertSame((string) $account->id, $job['accountId']);
        $this->assertArrayNotHasKey('cookies', $job);

        $this->postJson('/api/node/jobs/'.$job['id'].'/report', [
            'jobId' => $job['id'],
            'status' => 'succeeded',
            'sessionStatus' => 'ready',
            'logs' => ['login ok'],
        ], $headers)->assertOk();

        $account->refresh();
        $this->assertSame('ready', $account->session_status);
        $this->assertSame('succeeded', ExecutorJob::query()->firstOrFail()->status);
    }

    public function test_article_publish_enqueues_executor_job_instead_of_http(): void
    {
        $admin = $this->admin();
        $persona = $this->persona($admin);
        $created = app(ExecutorJobService::class)->createNode(['name' => 'office-pc']);
        $account = ManualPublicationAccount::query()->create([
            'persona_id' => $persona->id,
            'platform' => ManualPublicationAccount::PLATFORM_XIAOHONGSHU,
            'account_name' => 'XHS',
            'executor_node_id' => $created['node']->id,
            'auto_publish_enabled' => true,
            'is_active' => true,
            'created_by_admin_id' => $admin->id,
        ]);
        $article = $this->article();
        $publication = SocialPublication::query()->create([
            'article_id' => $article->id,
            'manual_publication_account_id' => $account->id,
            'platform' => 'xiaohongshu',
            'status' => 'queued',
            'attempt_count' => 0,
        ]);

        $ok = app(SocialPublicationOrchestrator::class)->process($publication);
        $this->assertTrue($ok);

        $job = ExecutorJob::query()->firstOrFail();
        $this->assertSame('publish', $job->type);
        $this->assertSame('queued', $job->status);
        $this->assertSame($publication->id, $job->social_publication_id);
        $this->assertSame('handoff', $job->recipe['publish']['mode'] ?? null);

        $publication->refresh();
        $this->assertSame('sending', $publication->status);

        app(ExecutorJobService::class)->report($created['node']->fresh(), (int) $job->id, [
            'status' => 'succeeded',
            'sessionStatus' => 'ready',
            'remoteUrl' => 'https://www.xiaohongshu.com/explore/abc',
        ]);
        $this->assertSame('synced', $publication->fresh()->status);
        $this->assertSame('https://www.xiaohongshu.com/explore/abc', $publication->fresh()->remote_url);
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'username' => uniqid('georank_admin_'),
            'password' => 'secret-123',
            'email' => uniqid('georank-admin-').'@example.com',
            'display_name' => 'GeoRank Admin',
            'role' => 'super_admin',
            'status' => 'active',
        ]);
    }

    private function persona(Admin $admin): ManualPublicationPersona
    {
        return ManualPublicationPersona::query()->create([
            'name' => 'GeoRank Persona',
            'disclosure_text' => 'GEOFlow',
            'created_by_admin_id' => $admin->id,
            'is_active' => true,
        ]);
    }

    private function article(): Article
    {
        $category = Category::query()->create(['name' => 'News', 'slug' => uniqid('news-')]);
        $author = Author::query()->create(['name' => 'Author']);

        return Article::query()->create([
            'title' => 'Published article',
            'slug' => uniqid('article-'),
            'excerpt' => 'excerpt',
            'content' => 'content',
            'category_id' => $category->id,
            'author_id' => $author->id,
            'status' => 'draft',
            'review_status' => 'approved',
        ]);
    }
}
