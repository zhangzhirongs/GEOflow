<?php

namespace App\Services\GeoFlow;

use App\Models\ManualPublicationAccount;
use RuntimeException;

class GeoRankRemoteSocialPublishAdapter extends AbstractSocialPublishAdapter
{
    public function key(): string
    {
        return 'georank_remote';
    }

    public function supports(ManualPublicationAccount $account): bool
    {
        return $account->publish_adapter === $this->key();
    }

    public function publish(ManualPublicationAccount $account, array $payload): array
    {
        $base = rtrim(trim((string) $account->publish_endpoint_url), '/');
        $remoteAccountId = trim((string) $account->publish_secret_key_id);
        $token = $this->secret($account);
        if ($base === '' || $remoteAccountId === '' || $token === '') {
            throw new RuntimeException('GeoRank integration URL, account ID, or token is missing.');
        }

        $article = is_array($payload['article'] ?? null) ? $payload['article'] : [];
        $response = $this->sendJson(
            $account,
            $base.'/api/integrations/geoflow/publish',
            'POST',
            [
                'accountId' => $remoteAccountId,
                'payload' => [
                    'title' => (string) ($article['title'] ?? $payload['title'] ?? ''),
                    'content' => (string) ($article['content'] ?? $payload['content'] ?? ''),
                    'excerpt' => (string) ($article['excerpt'] ?? $payload['excerpt'] ?? ''),
                    'images' => is_array($article['images'] ?? null) ? array_values($article['images']) : [],
                    'autoPublish' => false,
                ],
            ],
            30,
            ['Authorization' => 'Bearer '.$token],
        );

        $job = is_array($response['job'] ?? null) ? $response['job'] : [];
        if (empty($job['id'])) {
            throw new RuntimeException('GeoRank did not return a publish job ID.');
        }

        return [
            'pending' => true,
            'remote_id' => '',
            'remote_url' => '',
            'remote_meta' => ['georank_job_id' => (string) $job['id'], 'status' => (string) ($job['status'] ?? 'queued')],
        ];
    }
}
