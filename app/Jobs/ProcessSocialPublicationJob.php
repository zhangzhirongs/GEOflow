<?php

namespace App\Jobs;

use App\Models\SocialPublication;
use App\Services\GeoFlow\DistributionRetryPolicy;
use App\Services\GeoFlow\SocialPublicationOrchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessSocialPublicationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 90;

    public function __construct(private readonly int $socialPublicationId) {}

    public function handle(SocialPublicationOrchestrator $orchestrator, DistributionRetryPolicy $retryPolicy): void
    {
        $publication = SocialPublication::query()->whereKey($this->socialPublicationId)->first();
        if (! $publication) {
            return;
        }

        try {
            if (! $orchestrator->process($publication)) {
                return;
            }
        } catch (Throwable $e) {
            $publication = SocialPublication::query()->whereKey($this->socialPublicationId)->first();
            if (! $publication) {
                return;
            }

            $account = $publication->account;
            $attemptCount = (int) $publication->attempt_count;
            $maxAttempts = 3;
            $shouldRetry = $retryPolicy->shouldRetry($e, $attemptCount, $maxAttempts);
            $retryAt = $shouldRetry ? $retryPolicy->retryAt($attemptCount) : null;

            $publication->forceFill([
                'status' => $shouldRetry ? 'queued' : 'failed',
                'last_error_message' => mb_substr($e->getMessage(), 0, 1000),
                'last_attempt_at' => now(),
                'next_retry_at' => $retryAt,
            ])->save();

            if ($account) {
                $account->forceFill([
                    'publish_status' => $shouldRetry ? 'queued' : 'failed',
                    'next_publish_at' => $retryAt,
                    'last_publish_status' => $shouldRetry ? 'queued' : 'failed',
                    'last_publish_error' => mb_substr($e->getMessage(), 0, 1000),
                ])->save();
            }

            if ($shouldRetry) {
                self::dispatch((int) $publication->id)
                    ->onQueue('distribution')
                    ->delay($retryAt);
            }
        }
    }
}
