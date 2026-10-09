<?php

namespace Tests\Unit;

use App\Models\AdminActivityLog;
use App\Support\AdminActivityLogPresenter;
use Tests\TestCase;

class AdminActivityLogPresenterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('zh_CN');
    }

    public function test_it_translates_create_admin_action_page_target_and_details(): void
    {
        $log = new AdminActivityLog([
            'action' => 'admin.admin-users.store:submit',
            'request_method' => 'POST',
            'page' => 'create',
            'target_type' => 'admin',
            'target_id' => 2,
            'details' => json_encode([
                'username' => 'editor_01',
                'display_name' => '内容运营A',
                'status' => 'active',
                'password' => '[redacted]',
                '_token' => 'abc',
                'action' => 'submit',
                'bio' => '[text:12 chars]',
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $presented = AdminActivityLogPresenter::present($log);

        $this->assertSame('创建', $presented['action']);
        $this->assertSame('用户管理', $presented['page']);
        $this->assertSame('提交', $presented['method']);
        $this->assertSame('管理员 #2', $presented['target']);
        $this->assertStringContainsString('用户名：editor_01', $presented['details']);
        $this->assertStringContainsString('显示名称：内容运营A', $presented['details']);
        $this->assertStringContainsString('状态：启用', $presented['details']);
        $this->assertStringContainsString('密码：已隐藏', $presented['details']);
        $this->assertStringContainsString('简介：文本（12字）', $presented['details']);
        $this->assertStringNotContainsString('_token', $presented['details']);
        $this->assertStringNotContainsString('admin.admin-users.store', $presented['action']);
    }

    public function test_it_translates_login_and_empty_target_details(): void
    {
        $log = new AdminActivityLog([
            'action' => 'auth:login',
            'request_method' => 'POST',
            'page' => 'login',
            'target_type' => '',
            'details' => '',
        ]);

        $presented = AdminActivityLogPresenter::present($log);

        $this->assertSame('登录', $presented['action']);
        $this->assertSame('登录', $presented['page']);
        $this->assertSame('提交', $presented['method']);
        $this->assertSame('-', $presented['target']);
        $this->assertSame('-', $presented['details']);
    }
}
