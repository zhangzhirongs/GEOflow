<?php

namespace App\Services\GeoFlow;

use App\Models\ManualPublicationAccount;
use App\Services\Outbound\SafeOutboundHttpClient;
use App\Support\GeoFlow\ApiKeyCrypto;
use Illuminate\Http\Client\Factory;
use RuntimeException;

abstract class AbstractSocialPublishAdapter implements SocialPublishAdapterInterface
{
    public function __construct(
        protected readonly ApiKeyCrypto $apiKeyCrypto,
        protected readonly SafeOutboundHttpClient $safeHttp,
        protected readonly Factory $http,
    ) {}

    protected function secret(ManualPublicationAccount $account): string
    {
        return $this->apiKeyCrypto->decrypt($account->autoPublishSecret());
    }

    protected function session(ManualPublicationAccount $account): string
    {
        return $this->apiKeyCrypto->decrypt($account->autoPublishSession());
    }

    protected function commonHeaders(ManualPublicationAccount $account): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'GEOFlow Social Publisher',
        ];

        $secret = $this->secret($account);
        $authType = (string) $account->publish_auth_type;
        if ($authType === 'bearer' && $secret !== '') {
            $headers['Authorization'] = 'Bearer '.$secret;
        } elseif ($authType === 'basic' && $secret !== '') {
            $username = trim((string) $account->publish_basic_username);
            if ($username === '') {
                throw new RuntimeException('Basic 登录缺少用户名。');
            }
            $headers['Authorization'] = 'Basic '.base64_encode($username.':'.$secret);
        } elseif ($authType === 'header_key' && $secret !== '') {
            $headerName = trim((string) $account->publish_auth_header_name);
            if ($headerName === '') {
                throw new RuntimeException('Header 登录缺少头名称。');
            }
            $headers[$headerName] = $secret;
        }

        $session = $this->session($account);
        if ($session !== '') {
            $headers['Cookie'] = $session;
        }

        return $headers;
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    protected function sendJson(ManualPublicationAccount $account, string $endpoint, string $method, array $payload, int $timeout = 20): array
    {
        $request = $this->http->timeout($timeout)
            ->connectTimeout(5)
            ->acceptJson()
            ->asJson()
            ->withHeaders($this->commonHeaders($account));

        $response = $this->safeHttp->send(
            $request,
            strtoupper($method),
            $endpoint,
            $payload,
            (int) config('geoflow.outbound_json_max_bytes', 4 * 1024 * 1024)
        );

        if ($response->failed()) {
            throw new RuntimeException('社交平台发布失败，HTTP '.$response->status());
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }
}
