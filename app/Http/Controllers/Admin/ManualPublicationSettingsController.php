<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveManualPublicationAccountRequest;
use App\Http\Requests\Admin\SaveManualPublicationPersonaRequest;
use App\Models\ManualPublicationAccount;
use App\Models\ManualPublicationPersona;
use App\Support\GeoFlow\ApiKeyCrypto;
use App\Support\AdminWeb;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ManualPublicationSettingsController extends Controller
{
    public function __construct(private readonly ApiKeyCrypto $apiKeyCrypto) {}

    public function index(): View
    {
        return view('admin.manual-publications.settings', [
            'pageTitle' => __('admin.manual_publications.settings.title'),
            'activeMenu' => 'articles',
            'adminSiteName' => AdminWeb::siteName(),
            'personas' => ManualPublicationPersona::query()->withCount('accounts')->orderByDesc('is_active')->orderBy('name')->get(),
            'accounts' => ManualPublicationAccount::query()->with('persona:id,name')->orderByDesc('is_active')->orderBy('account_name')->get(),
            'platforms' => ManualPublicationAccount::PLATFORMS,
            'publishMethods' => ['POST', 'PUT', 'PATCH'],
            'publishAuthTypes' => ['none', 'bearer', 'basic', 'header_key'],
            'publishAdapters' => [
                'zhihu_login_session' => '知乎登录态发布',
                'xiaohongshu_login_session' => '小红书登录态发布',
                'bilibili_login_session' => 'B站登录态发布',
                'generic_http_api' => '通用 HTTP 发布',
            ],
        ]);
    }

    public function storePersona(SaveManualPublicationPersonaRequest $request): RedirectResponse
    {
        ManualPublicationPersona::query()->create($this->personaPayload($request) + [
            'created_by_admin_id' => $request->user('admin')?->getAuthIdentifier(),
        ]);

        return back()->with('message', __('admin.manual_publications.settings.persona_saved'));
    }

    public function updatePersona(SaveManualPublicationPersonaRequest $request, int $personaId): RedirectResponse
    {
        ManualPublicationPersona::query()->whereKey($personaId)->firstOrFail()->update($this->personaPayload($request));

        return back()->with('message', __('admin.manual_publications.settings.persona_saved'));
    }

    public function storeAccount(SaveManualPublicationAccountRequest $request): RedirectResponse
    {
        ManualPublicationAccount::query()->create($this->accountPayload($request) + [
            'created_by_admin_id' => $request->user('admin')?->getAuthIdentifier(),
        ]);

        return back()->with('message', __('admin.manual_publications.settings.account_saved'));
    }

    public function updateAccount(SaveManualPublicationAccountRequest $request, int $accountId): RedirectResponse
    {
        $account = ManualPublicationAccount::query()->whereKey($accountId)->firstOrFail();
        $payload = $this->accountPayload($request);
        if ($payload['publish_secret_ciphertext'] === null) {
            $payload['publish_secret_ciphertext'] = $account->getRawOriginal('publish_secret_ciphertext');
        }
        if ($payload['publish_session_ciphertext'] === null) {
            $payload['publish_session_ciphertext'] = $account->getRawOriginal('publish_session_ciphertext');
        }

        $account->update($payload);

        return back()->with('message', __('admin.manual_publications.settings.account_saved'));
    }

    /** @return array<string, mixed> */
    private function personaPayload(SaveManualPublicationPersonaRequest $request): array
    {
        $data = $request->validated();

        return [
            'name' => trim((string) $data['name']),
            'bio' => trim((string) ($data['bio'] ?? '')) ?: null,
            'tone' => trim((string) ($data['tone'] ?? '')) ?: null,
            'domain' => trim((string) ($data['domain'] ?? '')) ?: null,
            'disclosure_text' => trim((string) ($data['disclosure_text'] ?? '')) ?: null,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    /** @return array<string, mixed> */
    private function accountPayload(SaveManualPublicationAccountRequest $request): array
    {
        $data = $request->validated();
        $secret = trim((string) ($data['publish_secret'] ?? ''));
        $session = trim((string) ($data['publish_session'] ?? ''));
        $platform = (string) $data['platform'];
        $publishAdapter = trim((string) ($data['publish_adapter'] ?? ''));

        if ($publishAdapter === '') {
            $publishAdapter = match ($platform) {
                ManualPublicationAccount::PLATFORM_ZHIHU => 'zhihu_login_session',
                ManualPublicationAccount::PLATFORM_XIAOHONGSHU => 'xiaohongshu_login_session',
                ManualPublicationAccount::PLATFORM_BILIBILI => 'bilibili_login_session',
                default => 'generic_http_api',
            };
        }

        return [
            'persona_id' => (int) $data['persona_id'],
            'platform' => $platform,
            'publish_adapter' => $publishAdapter,
            'custom_platform' => trim((string) ($data['custom_platform'] ?? '')) ?: null,
            'account_name' => trim((string) $data['account_name']),
            'profile_url' => trim((string) ($data['profile_url'] ?? '')) ?: null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'auto_publish_enabled' => $request->boolean('auto_publish_enabled'),
            'publish_endpoint_url' => trim((string) ($data['publish_endpoint_url'] ?? '')) ?: null,
            'publish_method' => strtoupper((string) ($data['publish_method'] ?? 'POST')),
            'publish_auth_type' => (string) ($data['publish_auth_type'] ?? 'none'),
            'publish_login_identifier' => trim((string) ($data['publish_login_identifier'] ?? '')) ?: null,
            'publish_auth_header_name' => trim((string) ($data['publish_auth_header_name'] ?? '')) ?: null,
            'publish_basic_username' => trim((string) ($data['publish_basic_username'] ?? '')) ?: null,
            'publish_secret_key_id' => trim((string) ($data['publish_secret_key_id'] ?? '')) ?: null,
            'publish_secret_ciphertext' => $secret !== '' ? $this->apiKeyCrypto->encrypt($secret) : null,
            'publish_session_ciphertext' => $session !== '' ? $this->apiKeyCrypto->encrypt($session) : null,
            'publish_profile_json' => [
                'platform' => (string) $data['platform'],
                'login_identifier' => trim((string) ($data['publish_login_identifier'] ?? '')) ?: null,
                'adapter' => trim((string) ($data['publish_adapter'] ?? '')) ?: null,
            ],
            'publish_status' => 'idle',
            'publish_attempt_count' => 0,
            'last_published_at' => null,
            'next_publish_at' => null,
            'last_publish_status' => null,
            'last_publish_error' => null,
            'last_remote_id' => null,
            'last_remote_url' => null,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
