<?php

namespace App\Services\GeoFlow;

use App\Models\ManualPublicationAccount;
use App\Services\Outbound\SafeOutboundHttpClient;
use Illuminate\Http\Client\Factory;
use RuntimeException;

class SocialGatewayClient
{
    public function __construct(
        private readonly SafeOutboundHttpClient $safeHttp,
        private readonly Factory $http,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function startXiaohongshuLogin(ManualPublicationAccount $account): array
    {
        return $this->sendGatewayRequest($account, $this->gatewayUrl($account, 'login/start'));
    }

    /**
     * @return array<string,mixed>
     */
    public function xiaohongshuLoginStatus(ManualPublicationAccount $account): array
    {
        return $this->sendGatewayRequest($account, $this->gatewayUrl($account, 'login/status'));
    }

    private function gatewayUrl(ManualPublicationAccount $account, string $action): string
    {
        $endpoint = trim((string) $account->publish_endpoint_url);
        if ($endpoint === '') {
            throw new RuntimeException('请先为账号配置小红书发布网关地址。');
        }

        if (! str_ends_with($endpoint, '/publish')) {
            throw new RuntimeException('小红书发布地址应以 /publish 结尾，例如 http://host.docker.internal:8787/xhs/publish。');
        }

        return substr($endpoint, 0, -strlen('/publish')).'/'.$action;
    }

    /**
     * @return array<string,mixed>
     */
    private function sendGatewayRequest(ManualPublicationAccount $account, string $url): array
    {
        $request = $this->http->timeout(20)
            ->connectTimeout(5)
            ->acceptJson()
            ->asJson();

        $response = $this->safeHttp->send($request, 'POST', $url, [
            'account_id' => (string) ($account->publish_secret_key_id ?: 'geoflow-account-'.$account->id),
            'login_identifier' => (string) ($account->publish_login_identifier ?: $account->account_name),
            'account_name' => (string) $account->account_name,
        ], (int) config('geoflow.outbound_json_max_bytes', 4 * 1024 * 1024));

        if ($response->failed()) {
            throw new RuntimeException('小红书登录网关请求失败，HTTP '.$response->status());
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }
}
