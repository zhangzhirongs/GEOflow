<?php

namespace App\Services\GeoFlow;

use App\Models\ManualPublicationAccount;
use RuntimeException;

class SocialPublishAdapterManager
{
    /**
     * @param  array<int, SocialPublishAdapterInterface>  $adapters
     */
    public function __construct(private readonly iterable $adapters) {}

    public function defaultAdapterKey(ManualPublicationAccount $account): string
    {
        return match ($account->platform) {
            ManualPublicationAccount::PLATFORM_ZHIHU => 'zhihu_login_session',
            ManualPublicationAccount::PLATFORM_XIAOHONGSHU => 'xiaohongshu_login_session',
            ManualPublicationAccount::PLATFORM_BILIBILI => 'bilibili_login_session',
            default => 'generic_http_api',
        };
    }

    public function forAccount(ManualPublicationAccount $account): SocialPublishAdapterInterface
    {
        $adapterKey = trim((string) ($account->publish_adapter ?: '')) ?: $this->defaultAdapterKey($account);

        foreach ($this->adapters as $adapter) {
            if ($adapter->key() === $adapterKey || $adapter->supports($account)) {
                return $adapter;
            }
        }

        throw new RuntimeException('未找到可用的社交发布适配器。');
    }
}
