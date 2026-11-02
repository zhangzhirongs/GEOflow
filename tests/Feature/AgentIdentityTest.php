<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_and_unconfigured_requests_fail_closed(): void
    {
        $this->withSession(['url.intended' => '/geo_admin/ai-configurator'])
            ->get(route('agent.identity'))->assertUnauthorized()
            ->assertSessionHas('url.intended', '/geo_admin/ai-configurator');
        $admin = $this->admin();
        config(['geoflow.agent_sso_secret' => '']);
        $this->actingAs($admin, 'admin')->withSession([Admin::AUTH_VERSION_SESSION_KEY => 1])
            ->get(route('agent.identity'))->assertStatus(503);
    }

    public function test_signed_identity_uses_stable_id_role_and_session(): void
    {
        $secret = str_repeat('a', 40);
        config(['geoflow.agent_sso_secret' => $secret]);
        $admin = $this->admin();
        $response = $this->actingAs($admin, 'admin')
            ->withSession([Admin::AUTH_VERSION_SESSION_KEY => 1])
            ->withHeaders(['X-Agent-Original-URI' => '/agent/api/auth/me', 'X-Agent-Original-Method' => 'GET'])
            ->get(route('agent.identity'))->assertOk();
        $encoded = $response->headers->get('X-Agent-Identity');
        $this->assertSame(hash_hmac('sha256', $encoded, $secret), $response->headers->get('X-Agent-Signature'));
        $claims = json_decode(base64_decode(strtr($encoded, '-_', '+/')), true);
        $this->assertSame((string) $admin->id, $claims['sub']);
        $this->assertFalse($claims['admin']);
        $this->assertSame('/agent/api/auth/me', $claims['target']);
        $this->assertSame('GET', $claims['method']);
        $this->assertGreaterThan(time(), $claims['exp']);
        $this->assertLessThanOrEqual(time() + 15, $claims['exp']);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_disabled_and_revoked_admin_sessions_are_rejected(): void
    {
        config(['geoflow.agent_sso_secret' => str_repeat('a', 40)]);
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->withSession([Admin::AUTH_VERSION_SESSION_KEY => 99])
            ->get(route('agent.identity'))->assertUnauthorized();
        $admin->update(['status' => 'disabled']);
        $this->actingAs($admin, 'admin')->withSession([Admin::AUTH_VERSION_SESSION_KEY => 1])
            ->get(route('agent.identity'))->assertUnauthorized();
    }

    public function test_super_admin_identity_stops_working_after_geoflow_logout(): void
    {
        config(['geoflow.agent_sso_secret' => str_repeat('a', 40)]);
        $admin = $this->admin();
        $admin->update(['role' => 'super_admin']);
        $response = $this->actingAs($admin, 'admin')
            ->withSession([Admin::AUTH_VERSION_SESSION_KEY => 1])
            ->get(route('agent.identity'))->assertOk();
        $claims = json_decode(base64_decode(strtr($response->headers->get('X-Agent-Identity'), '-_', '+/')), true);
        $this->assertTrue($claims['admin']);

        $this->post(route('admin.logout'))->assertRedirect();
        $this->get(route('agent.identity'))->assertUnauthorized();
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'username' => 'sso-test', 'password' => 'test-password-123',
            'email' => 'sso@example.com', 'display_name' => 'SSO',
            'role' => 'admin', 'status' => 'active',
        ]);
    }
}
