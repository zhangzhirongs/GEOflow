<?php

namespace App\Services\GeoFlow;

use App\Models\ManualPublicationAccount;
use App\Models\SocialPublication;
use RuntimeException;

class XiaohongshuSocialPublishAdapter extends AbstractSocialPublishAdapter
{
    public function __construct(
        \App\Support\GeoFlow\ApiKeyCrypto $apiKeyCrypto,
        \App\Services\Outbound\SafeOutboundHttpClient $safeHttp,
        \Illuminate\Http\Client\Factory $http,
        private readonly ExecutorJobService $executorJobs,
    ) {
        parent::__construct($apiKeyCrypto, $safeHttp, $http);
    }

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
        if ($account->executor_node_id) {
            $article = is_array($payload['article'] ?? null) ? $payload['article'] : [];
            $job = $this->executorJobs->enqueuePublish($account, [
                'title' => (string) ($article['title'] ?? $payload['title'] ?? ''),
                'content' => (string) ($article['content'] ?? $payload['content'] ?? ''),
                'excerpt' => (string) ($article['excerpt'] ?? $payload['excerpt'] ?? ''),
                'images' => is_array($article['images'] ?? null) ? array_values($article['images']) : [],
                'autoPublish' => false,
            ], $this->relatedPublication($payload));

            return [
                'pending' => true,
                'remote_id' => '',
                'remote_url' => '',
                'remote_meta' => [
                    'executor_job_id' => $job->id,
                    'mode' => 'executor_handoff',
                ],
            ];
        }

        $endpoint = trim((string) ($account->publish_endpoint_url ?: ''));
        if ($endpoint === '') {
            throw new RuntimeException('xiaohongshu account is missing executor node or publish endpoint');
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
            'asset_base_url' => rtrim((string) config('app.url'), '/'),
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

    /**
     * @param  array<string,mixed>  $payload
     */
    private function relatedPublication(array $payload): ?SocialPublication
    {
        $id = (int) ($payload['social_publication_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        return SocialPublication::query()->whereKey($id)->first();
    }
}
