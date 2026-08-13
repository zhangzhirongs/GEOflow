<?php

namespace Tests\Feature;

use App\Jobs\ProcessSocialPublicationJob;
use App\Models\Admin;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\ManualPublicationAccount;
use App\Models\ManualPublicationPersona;
use App\Support\GeoFlow\ApiKeyCrypto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SocialPublicationPoolTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_save_social_account_pool_with_encrypted_secret(): void
    {
        $admin = $this->admin();
        $persona = $this->persona($admin);

        $this->actingAs($admin, 'admin')->post(route('admin.manual-publications.settings.accounts.store'), [
            'persona_id' => $persona->id,
            'platform' => ManualPublicationAccount::PLATFORM_ZHIHU,
            'account_name' => 'Zhihu Pool',
            'auto_publish_enabled' => '1',
            'publish_endpoint_url' => 'https://publisher.example/api/publish',
            'publish_method' => 'POST',
            'publish_auth_type' => 'bearer',
            'publish_secret_key_id' => 'zhihu-001',
            'publish_secret' => 'plain-secret-token',
            'is_active' => '1',
        ])->assertRedirect();

        $account = ManualPublicationAccount::query()->firstOrFail();
        $this->assertSame('zhihu-001', $account->publish_secret_key_id);
        $this->assertNotSame('plain-secret-token', (string) $account->getRawOriginal('publish_secret_ciphertext'));
        $this->assertSame('plain-secret-token', app(ApiKeyCrypto::class)->decrypt((string) $account->getRawOriginal('publish_secret_ciphertext')));
    }

    public function test_social_account_update_keeps_existing_session_when_blank(): void
    {
        $admin = $this->admin();
        $persona = $this->persona($admin);
        $account = ManualPublicationAccount::query()->create([
            'persona_id' => $persona->id,
            'platform' => ManualPublicationAccount::PLATFORM_ZHIHU,
            'account_name' => 'Zhihu Pool',
            'auto_publish_enabled' => true,
            'publish_endpoint_url' => 'https://publisher.example/api/publish',
            'publish_method' => 'POST',
            'publish_auth_type' => 'none',
            'publish_secret_key_id' => 'zhihu-001',
            'publish_secret_ciphertext' => app(ApiKeyCrypto::class)->encrypt('plain-secret-token'),
            'publish_session_ciphertext' => app(ApiKeyCrypto::class)->encrypt('session-cookie=abc'),
            'is_active' => true,
            'created_by_admin_id' => $admin->id,
        ]);

        $this->actingAs($admin, 'admin')->put(route('admin.manual-publications.settings.accounts.update', ['accountId' => $account->id]), [
            'persona_id' => $persona->id,
            'platform' => ManualPublicationAccount::PLATFORM_ZHIHU,
            'account_name' => 'Zhihu Pool Updated',
            'auto_publish_enabled' => '1',
            'publish_endpoint_url' => 'https://publisher.example/api/publish',
            'publish_method' => 'POST',
            'publish_auth_type' => 'none',
            'publish_secret_key_id' => 'zhihu-002',
            'publish_secret' => '',
            'publish_session' => '',
            'is_active' => '1',
        ])->assertRedirect();

        $account->refresh();
        $this->assertSame('session-cookie=abc', app(ApiKeyCrypto::class)->decrypt((string) $account->getRawOriginal('publish_session_ciphertext')));
        $this->assertSame('zhihu_login_session', $account->publish_adapter);
    }

    public function test_custom_platform_defaults_to_generic_adapter(): void
    {
        $admin = $this->admin();
        $persona = $this->persona($admin);

        $this->actingAs($admin, 'admin')->post(route('admin.manual-publications.settings.accounts.store'), [
            'persona_id' => $persona->id,
            'platform' => ManualPublicationAccount::PLATFORM_CUSTOM,
            'account_name' => 'Generic Pool',
            'custom_platform' => 'forum',
            'auto_publish_enabled' => '1',
            'publish_endpoint_url' => 'https://publisher.example/api/publish',
            'publish_secret_key_id' => 'generic-001',
            'publish_secret' => 'plain-secret-token',
            'is_active' => '1',
        ])->assertRedirect();

        $account = ManualPublicationAccount::query()->firstOrFail();
        $this->assertSame('generic_http_api', $account->publish_adapter);
    }

    public function test_xiaohongshu_gateway_account_can_be_saved_without_manual_session_or_secret(): void
    {
        $admin = $this->admin();
        $persona = $this->persona($admin);

        $this->actingAs($admin, 'admin')->post(route('admin.manual-publications.settings.accounts.store'), [
            'persona_id' => $persona->id,
            'platform' => ManualPublicationAccount::PLATFORM_XIAOHONGSHU,
            'account_name' => 'XHS Pool',
            'auto_publish_enabled' => '1',
            'publish_endpoint_url' => 'http://host.docker.internal:8787/xhs/publish',
            'publish_login_identifier' => 'xhs-user',
            'publish_secret_key_id' => 'xhs-account-001',
            'publish_secret' => '',
            'publish_session' => '',
            'is_active' => '1',
        ])->assertRedirect();

        $account = ManualPublicationAccount::query()->firstOrFail();
        $this->assertSame('xiaohongshu_login_session', $account->publish_adapter);
        $this->assertSame('xhs-account-001', $account->publish_secret_key_id);
        $this->assertTrue((bool) ($account->publish_profile_json['gateway_managed_session'] ?? false));
        $this->assertNull($account->getRawOriginal('publish_session_ciphertext'));
        $this->assertNull($account->getRawOriginal('publish_secret_ciphertext'));
    }

    public function test_published_article_queues_social_publication_job_for_enabled_accounts(): void
    {
        Queue::fake();

        $admin = $this->admin();
        $persona = $this->persona($admin);
        ManualPublicationAccount::query()->create([
            'persona_id' => $persona->id,
            'platform' => ManualPublicationAccount::PLATFORM_ZHIHU,
            'account_name' => 'Zhihu Pool',
            'auto_publish_enabled' => true,
            'publish_endpoint_url' => 'https://publisher.example/api/publish',
            'publish_method' => 'POST',
            'publish_auth_type' => 'none',
            'publish_secret_key_id' => 'zhihu-001',
            'publish_secret_ciphertext' => app(ApiKeyCrypto::class)->encrypt('plain-secret-token'),
            'is_active' => true,
            'created_by_admin_id' => $admin->id,
        ]);

        $article = $this->article();
        app(\App\Services\GeoFlow\ArticleGeoFlowService::class)->publishArticle((int) $article->id, (int) $admin->id);

        Queue::assertPushed(ProcessSocialPublicationJob::class);
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'username' => uniqid('social_admin_'),
            'password' => 'secret-123',
            'email' => uniqid('social-admin-').'@example.com',
            'display_name' => 'Social Admin',
            'role' => 'super_admin',
            'status' => 'active',
        ]);
    }

    private function persona(Admin $admin): ManualPublicationPersona
    {
        return ManualPublicationPersona::query()->create([
            'name' => 'Social Persona',
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
