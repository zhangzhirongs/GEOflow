<?php

namespace Tests\Unit;

use App\Models\ManualPublicationAccount;
use App\Services\GeoFlow\BilibiliSocialPublishAdapter;
use App\Services\GeoFlow\GenericSocialPublishAdapter;
use App\Services\GeoFlow\SocialPublishAdapterManager;
use App\Services\GeoFlow\XiaohongshuSocialPublishAdapter;
use App\Services\GeoFlow\ZhihuSocialPublishAdapter;
use Tests\TestCase;

class SocialPublishAdapterManagerTest extends TestCase
{
    public function test_it_resolves_platform_specific_adapter(): void
    {
        $manager = app(SocialPublishAdapterManager::class);

        $this->assertInstanceOf(ZhihuSocialPublishAdapter::class, $manager->forAccount($this->account(ManualPublicationAccount::PLATFORM_ZHIHU)));
        $this->assertInstanceOf(XiaohongshuSocialPublishAdapter::class, $manager->forAccount($this->account(ManualPublicationAccount::PLATFORM_XIAOHONGSHU)));
        $this->assertInstanceOf(BilibiliSocialPublishAdapter::class, $manager->forAccount($this->account(ManualPublicationAccount::PLATFORM_BILIBILI)));
        $this->assertInstanceOf(GenericSocialPublishAdapter::class, $manager->forAccount($this->account(ManualPublicationAccount::PLATFORM_CUSTOM)));
    }

    public function test_it_uses_explicit_adapter_when_configured(): void
    {
        $manager = app(SocialPublishAdapterManager::class);

        $account = $this->account(ManualPublicationAccount::PLATFORM_CUSTOM);
        $account->publish_adapter = 'generic_http_api';

        $this->assertInstanceOf(GenericSocialPublishAdapter::class, $manager->forAccount($account));
    }

    private function account(string $platform): ManualPublicationAccount
    {
        return new ManualPublicationAccount([
            'platform' => $platform,
        ]);
    }
}
