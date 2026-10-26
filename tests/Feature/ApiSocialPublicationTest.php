<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ExecutorJob;
use App\Models\ManualPublicationAccount;
use App\Models\ManualPublicationPersona;
use App\Services\GeoFlow\ExecutorJobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSocialPublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_discover_platforms_and_accounts_without_secrets(): void
    {
        $admin = $this->admin();
        $persona = $this->persona($admin);
        $node = app(ExecutorJobService::class)->createNode(['name' => 'social-agent-node'])['node'];
        $account = ManualPublicationAccount::query()->create([
            'persona_id' => $persona->id,
            'platform' => ManualPublicationAccount::PLATFORM_XIAOHONGSHU,
            'account_name' => '品牌账号',
            'publish_secret_ciphertext' => 'do-not-return',
            'publish_session_ciphertext' => 'private-session',
            'executor_node_id' => $node->id,
            'is_active' => true,
        ]);
        $token = $admin->createToken('social-read', ['social:read'])->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/social-publication/platforms')
            ->assertOk()
            ->assertJsonFragment(['key' => 'xiaohongshu']);
        $this->withToken($token)->getJson('/api/v1/social-publication/accounts?platform=xiaohongshu')
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $account->id)
            ->assertJsonPath('data.items.0.can_enqueue', true)
            ->assertDontSee('private-session')
            ->assertDontSee('do-not-return');
    }

    public function test_agent_enqueues_publish_job_for_explicit_account(): void
    {
        $admin = $this->admin();
        $persona = $this->persona($admin);
        $node = app(ExecutorJobService::class)->createNode(['name' => 'social-publish-node'])['node'];
        $account = ManualPublicationAccount::query()->create([
            'persona_id' => $persona->id,
            'platform' => ManualPublicationAccount::PLATFORM_XIAOHONGSHU,
            'account_name' => '指定小红书账号',
            'executor_node_id' => $node->id,
            'auto_publish_enabled' => true,
            'is_active' => true,
        ]);
        $token = $admin->createToken('social-publish', ['social:publish'])->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/social-publication/jobs', [
            'account_id' => $account->id,
            'title' => '系统介绍',
            'content' => '这是一篇介绍公司系统的文章。',
            'images' => ['https://assets.example.test/cover.jpg'],
        ], ['X-Idempotency-Key' => 'social-job-test-001']);

        $response->assertStatus(202)
            ->assertJsonPath('data.account_id', $account->id)
            ->assertJsonPath('data.platform', 'xiaohongshu')
            ->assertJsonPath('data.job_state', 'queued')
            ->assertJsonPath('data.remote_publish_completed', false);

        $job = ExecutorJob::query()->findOrFail($response->json('data.job_id'));
        $this->assertSame((int) $account->id, (int) $job->manual_publication_account_id);
        $this->assertSame('publish', $job->type);
        $this->assertSame('queued', $job->status);
        $this->assertSame('系统介绍', $job->payload['title']);
        $this->assertFalse($job->payload['autoPublish']);

        $this->withToken($admin->createToken('social-read-job', ['social:read'])->plainTextToken)
            ->getJson('/api/v1/social-publication/jobs/'.$job->id)
            ->assertOk()
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.account_id', $account->id);
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'username' => uniqid('social_api_'),
            'password' => 'secret-123',
            'email' => uniqid('social-api-').'@example.test',
            'display_name' => 'Social API',
            'role' => 'super_admin',
            'status' => 'active',
        ]);
    }

    private function persona(Admin $admin): ManualPublicationPersona
    {
        return ManualPublicationPersona::query()->create([
            'name' => '品牌内容',
            'created_by_admin_id' => $admin->id,
            'is_active' => true,
        ]);
    }
}
