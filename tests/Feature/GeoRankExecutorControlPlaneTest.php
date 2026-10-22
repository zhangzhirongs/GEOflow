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
use App\Services\GeoFlow\GeoRankRecipeCatalog;
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

    public function test_expired_login_releases_slot_and_late_report_cannot_overwrite_timeout(): void
    {
        [$jobs, $node, $account] = $this->sessionTaskContext();
        $first = $jobs->enqueueLogin($account);
        $first->forceFill(['recipe' => ['login' => ['timeoutMs' => 300000]]])->save();
        $claimed = $jobs->claim($node);
        $this->assertNotEmpty($claimed->recipe['login']['successCookieNames']);
        $retry = $jobs->enqueueLogin($account);
        $this->travel(359)->seconds();
        $this->assertNull($jobs->claim($node));
        $this->assertSame('running', $first->fresh()->status);
        $this->travel(2)->seconds();
        $this->assertSame($retry->id, $jobs->claim($node)->id);
        $this->assertSame('failed', $first->fresh()->status);
        $this->assertNotNull($first->fresh()->finished_at);
        $this->assertNotEmpty($first->fresh()->error);
        $jobs->report($node, (int) $first->id, ['status' => 'succeeded', 'sessionStatus' => 'ready']);
        $this->assertSame('failed', $first->fresh()->status);
        $this->assertSame('login_in_progress', $account->fresh()->session_status);
        $this->assertSame(1, $node->fresh()->fail);
        $this->assertSame(0, $jobs->expireSessionJobs($node));
    }

    public function test_session_check_timeout_is_recovered_by_heartbeat(): void
    {
        [$jobs, $node, $account] = $this->sessionTaskContext();
        $job = $jobs->enqueueCheck($account);
        $jobs->claim($node);
        $this->travel(89)->seconds();
        $this->assertSame(0, $jobs->expireSessionJobs($node));
        $this->travel(2)->seconds();
        $jobs->heartbeat($node, ['load' => 0]);
        $this->assertSame('failed', $job->fresh()->status);
        $this->assertSame('unknown', $account->fresh()->session_status);
        $this->assertSame(1, $node->fresh()->fail);
    }

    public function test_successful_report_is_idempotent_and_not_recovered_as_expired(): void
    {
        [$jobs, $node, $account] = $this->sessionTaskContext();
        $job = $jobs->enqueueLogin($account);
        $jobs->claim($node);
        $report = ['status' => 'succeeded', 'sessionStatus' => 'ready'];
        $jobs->report($node, (int) $job->id, $report);
        $jobs->report($node, (int) $job->id, $report);
        $this->travel(10)->minutes();
        $this->assertSame(0, $jobs->expireSessionJobs());
        $this->assertSame('succeeded', $job->fresh()->status);
        $this->assertSame(1, $node->fresh()->done);
        $this->assertSame('ready', $account->fresh()->session_status);
    }

    public function test_session_recovery_does_not_expire_publish_jobs(): void
    {
        [$jobs, $node, $account] = $this->sessionTaskContext();
        $job = $jobs->enqueueLogin($account);
        $jobs->claim($node);
        $job->forceFill(['type' => 'publish'])->save();
        $this->travel(1)->hours();
        $this->assertSame(0, $jobs->expireSessionJobs());
        $this->assertSame('running', $job->fresh()->status);
    }

    public function test_every_platform_has_login_detection_and_check_credentials(): void
    {
        $catalog = app(GeoRankRecipeCatalog::class);
        foreach (ManualPublicationAccount::PLATFORMS as $platform) {
            $recipe = $catalog->forPlatform($platform);
            $this->assertNotEmpty($recipe['login']['successCookieNames'], $platform);
            $this->assertSame($recipe['login']['successCookieNames'], $recipe['check']['loggedInCookieNames'], $platform);
            $this->assertLessThanOrEqual(300000, $recipe['login']['timeoutMs'], $platform);
            $this->assertLessThanOrEqual(30000, $recipe['check']['timeoutMs'], $platform);
        }
        $this->assertSame(['SESSDATA'], $catalog->forPlatform('bilibili')['check']['loggedInCookieNames']);
        $this->assertArrayNotHasKey('successUrlIncludes', $catalog->forPlatform('csdn')['login']);
    }

    public function test_all_real_platforms_release_timed_out_login_and_check_slots(): void
    {
        [$jobs, $node, $account] = $this->sessionTaskContext();
        foreach (ManualPublicationAccount::PLATFORMS as $platform) {
            if ($platform === 'local_demo') {
                continue;
            }
            $account->forceFill(['platform' => $platform])->save();
            foreach (['login', 'check'] as $type) {
                $job = $type === 'login' ? $jobs->enqueueLogin($account) : $jobs->enqueueCheck($account);
                $this->assertSame($job->id, $jobs->claim($node)->id, $platform.' '.$type);
                $this->travel($type === 'login' ? 361 : 91)->seconds();
                $this->assertSame(1, $jobs->expireSessionJobs($node), $platform.' '.$type);
                $this->assertSame('failed', $job->fresh()->status);
                $this->assertNotNull($job->fresh()->finished_at);
            }
        }
    }

    private function sessionTaskContext(): array
    {
        $admin = $this->admin();
        $persona = $this->persona($admin);
        $jobs = app(ExecutorJobService::class);
        $node = $jobs->createNode(['name' => 'timeout-test'])['node'];
        $account = ManualPublicationAccount::query()->create([
            'persona_id' => $persona->id,
            'platform' => 'toutiao',
            'account_name' => 'Toutiao',
            'executor_node_id' => $node->id,
            'is_active' => true,
            'created_by_admin_id' => $admin->id,
        ]);

        return [$jobs, $node, $account];
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
