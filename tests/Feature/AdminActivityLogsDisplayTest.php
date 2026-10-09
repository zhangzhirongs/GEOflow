<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActivityLogsDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_log_page_renders_chinese_action_page_target_and_details(): void
    {
        app()->setLocale('zh_CN');

        $admin = Admin::query()->create([
            'username' => 'root_admin',
            'password' => 'secret-123',
            'email' => 'root-admin@example.com',
            'display_name' => 'Root Admin',
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        AdminActivityLog::query()->create([
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
            'admin_role' => 'super_admin',
            'action' => 'admin.admin-users.store:submit',
            'request_method' => 'POST',
            'page' => 'create',
            'target_type' => 'admin',
            'target_id' => 9,
            'ip_address' => '127.0.0.1',
            'details' => json_encode([
                'username' => 'editor_01',
                'password' => '[redacted]',
                '_token' => 'secret-token',
                'action' => 'submit',
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.admin-activity-logs'))
            ->assertOk()
            ->assertDontSee('admin.admin-users.store:submit', false)
            ->assertDontSee('secret-token', false)
            ->assertSee('创建', false)
            ->assertSee('用户管理', false)
            ->assertSee('管理员 #9', false)
            ->assertSee('用户名：editor_01', false)
            ->assertSee('已隐藏', false);
    }
}
