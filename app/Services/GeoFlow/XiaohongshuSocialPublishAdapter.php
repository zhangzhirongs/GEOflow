<?php

namespace App\Services\GeoFlow;

use App\Models\ManualPublicationAccount;
use RuntimeException;

class XiaohongshuSocialPublishAdapter extends AbstractSocialPublishAdapter
{
    public function key(): string
    {
        return 'xiaohongshu_login_session';
    }

    public function supports(ManualPublicationAccount $account): bool
    {
        return $account->platform === ManualPublicationAccount::PLATFORM_XIAOHONGSHU;
    }

    public function publish(ManualPublicationAccount $account, array $payload): array
    {
        $endpoint = trim((string) ($account->publish_endpoint_url ?: ''));
        if ($endpoint === '') {
            throw new RuntimeException('小红书账号缺少发布网关地址。');
        }

        $gatewayAccountId = trim((string) ($account->publish_secret_key_id ?: ''));
        if ($gatewayAccountId === '') {
            $gatewayAccountId = 'geoflow-account-'.$account->id;
        }

        $response = $this->sendJson($account, $endpoint, (string) ($account->publish_method ?: 'POST'), [
            'platform' => 'xiaohongshu',
            'mode' => 'gateway_managed_session',
            'gateway_account_id' => $gatewayAccountId,
            'login_identifier' => (string) ($account->publish_login_identifier ?? ''),
            'account' => [
                'id' => (int) $account->id,
                'name' => (string) $account->account_name,
            ],
            'payload' => $payload,
        ], 30);

        return [
            'remote_id' => (string) ($response['note_id'] ?? $response['id'] ?? $response['remote_id'] ?? ''),
            'remote_url' => (string) ($response['url'] ?? $response['remote_url'] ?? ''),
            'remote_meta' => ['platform_response' => $response],
        ];
    }
}
