<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminLoginCsrf;
use App\Models\Admin;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginCsrfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(AdminLoginCsrf::class, fn () => new class($this->app, $this->app->make(Encrypter::class)) extends AdminLoginCsrf
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
    }

    public function test_login_page_cannot_be_cached(): void
    {
        $response = $this->get(route('admin.login'))->assertOk();

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_expired_login_returns_a_fresh_form_without_replaying_credentials(): void
    {
        $this->withSession(['_token' => 'current-token'])
            ->post(route('admin.login.attempt'), [
                '_token' => 'expired-token',
                'username' => 'attempted-user',
                'password' => 'must-not-be-retained',
            ])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('session')
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest('admin');
        $this->get(route('admin.login'))->assertOk()
            ->assertSee(__('admin.login.error.session_expired'))
            ->assertDontSee('must-not-be-retained');
    }

    public function test_current_token_reaches_form_validation(): void
    {
        $this->withSession(['_token' => 'current-token'])
            ->postJson(route('admin.login.attempt'), ['_token' => 'current-token'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username', 'password']);
    }

    public function test_current_token_allows_successful_login(): void
    {
        $admin = Admin::query()->create([
            'username' => 'csrf-admin',
            'password' => 'correct-test-password',
            'email' => 'csrf-admin@example.com',
            'display_name' => 'CSRF Admin',
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $this->withSession(['_token' => 'current-token'])
            ->post(route('admin.login.attempt'), [
                '_token' => 'current-token',
                'username' => 'csrf-admin',
                'password' => 'correct-test-password',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_json_requests_and_other_forms_still_reject_invalid_tokens(): void
    {
        $this->postJson(route('admin.login.attempt'), ['_token' => 'expired-token'])
            ->assertStatus(419);

        $this->post(route('admin.logout'), ['_token' => 'expired-token'])
            ->assertStatus(419);
    }
}
