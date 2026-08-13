<?php

namespace App\Services\GeoFlow;

use App\Models\ManualPublicationAccount;
use RuntimeException;

class GenericSocialPublishAdapter extends AbstractSocialPublishAdapter
{
    public function key(): string
    {
        return 'generic_http_api';
    }

    public function supports(ManualPublicationAccount $account): bool
    {
        return $account->platform === ManualPublicationAccount::PLATFORM_CUSTOM
            || trim((string) ($account->publish_adapter ?: '')) === $this->key();
    }

    public function publish(ManualPublicationAccount $account, array $payload): array
    {
        $endpoint = trim((string) ($account->publish_endpoint_url ?: ''));
        if ($endpoint === '') {
            throw new RuntimeException('通用发布账号缺少发布地址。');
        }

        $response = $this->sendJson($account, $endpoint, (string) ($account->publish_method ?: 'POST'), [
            'platform' => (string) ($account->platform ?: 'custom'),
            'mode' => 'generic_http_api',
            'login_identifier' => (string) ($account->publish_login_identifier ?? ''),
            'session_cookie_present' => $this->session($account) !== '',
            'account' => [
                'id' => (int) $account->id,
                'name' => (string) $account->account_name,
                'platform' => (string) $account->platform,
            ],
            'payload' => $payload,
        ], 20);

        return [
            'remote_id' => (string) ($response['id'] ?? $response['remote_id'] ?? ''),
            'remote_url' => (string) ($response['url'] ?? $response['remote_url'] ?? ''),
            'remote_meta' => ['platform_response' => $response],
        ];
    }
}
