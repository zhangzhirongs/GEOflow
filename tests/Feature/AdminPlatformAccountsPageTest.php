<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ManualPublicationAccount;
use App\Models\ManualPublicationPersona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPlatformAccountsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounts_are_listed_with_independent_edit_forms_and_ten_row_pagination(): void
    {
        $this->signIn();
        $persona = ManualPublicationPersona::query()->create(['name' => 'Publisher', 'is_active' => true]);
        foreach (range(1, 11) as $index) {
            ManualPublicationAccount::query()->create([
                'persona_id' => $persona->id,
                'platform' => ManualPublicationAccount::PLATFORM_XIAOHONGSHU,
                'account_name' => sprintf('Account %02d', $index),
                'is_active' => true,
            ]);
        }

        $this->get(route('admin.manual-publications.settings.index'))
            ->assertOk()
            ->assertSee('data-account-create-form', false)
            ->assertSee('data-account-list', false)
            ->assertSee('data-account-edit-form="1"', false)
            ->assertSee('aria-controls="account-editor-1" aria-expanded="false"', false)
            ->assertViewHas('accounts', fn ($accounts) => $accounts->count() === 10);

        $this->get(route('admin.manual-publications.settings.index', ['account_page' => 2]))
            ->assertOk()
            ->assertSee('Account 11')
            ->assertDontSee('Account 01')
            ->assertViewHas('accounts', fn ($accounts) => $accounts->count() === 1);
    }

    public function test_account_can_be_created_and_updated_through_existing_settings_routes(): void
    {
        $this->signIn();
        $payload = [
            'platform' => ManualPublicationAccount::PLATFORM_XIAOHONGSHU,
            'account_name' => 'Original account',
            'is_active' => '1',
            'auto_publish_enabled' => '0',
        ];
        $url = route('admin.manual-publications.settings.index');

        $this->from($url)->post(route('admin.manual-publications.settings.accounts.store'), $payload)
            ->assertRedirect($url)->assertSessionHasNoErrors();
        $account = ManualPublicationAccount::query()->firstOrFail();

        $this->from($url)->put(route('admin.manual-publications.settings.accounts.update', ['accountId' => $account->id]), array_replace($payload, [
            'account_name' => 'Edited account',
            'is_active' => '0',
            '_account_form_id' => (string) $account->id,
        ]))->assertRedirect($url)->assertSessionHasNoErrors();

        $this->assertSame('Edited account', $account->fresh()->account_name);
        $this->assertFalse((bool) $account->fresh()->is_active);
        $this->get($url)->assertOk()->assertSee('Edited account');
    }

    private function signIn(): void
    {
        $this->actingAs(Admin::query()->create([
            'username' => 'platform_accounts_admin',
            'password' => 'secret-123',
            'role' => 'super_admin',
            'status' => 'active',
        ]), 'admin');
    }
}
