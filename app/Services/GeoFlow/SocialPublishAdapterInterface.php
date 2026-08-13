<?php

namespace App\Services\GeoFlow;

use App\Models\ManualPublicationAccount;

interface SocialPublishAdapterInterface
{
    public function key(): string;

    public function supports(ManualPublicationAccount $account): bool;

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    public function publish(ManualPublicationAccount $account, array $payload): array;
}
