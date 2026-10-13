<?php

namespace App\Jobs;

use App\Models\AiGrowthProbe;
use App\Services\Admin\Analytics\GrowthAiAnswerCollector;
use App\Services\Admin\Analytics\GrowthAiProbeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

class ProcessGrowthAiProbeJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 180;

    public bool $failOnTimeout = true;

    public function __construct(private readonly int $probeId) {}

    public function handle(GrowthAiProbeService $probes): void
    {
        $probe = AiGrowthProbe::query()->whereKey($this->probeId)->first();
        if (! $probe) {
            return;
        }

        $probes->process($probe);
    }

    public function failed(?Throwable $exception = null): void
    {
        $probe = AiGrowthProbe::query()->whereKey($this->probeId)->first();
        if (! $probe || $probe->status === AiGrowthProbe::STATUS_COMPLETED) {
            return;
        }

        $exception ??= new RuntimeException(__('admin.analytics.overview.probe.errors.queue_stopped'));
        $message = app(GrowthAiAnswerCollector::class)->humanize($exception);
        $probe->forceFill([
            'status' => AiGrowthProbe::STATUS_FAILED,
            'error_message' => mb_substr($message !== '' ? $message : __('admin.analytics.overview.probe.errors.queue_stopped'), 0, 1000, 'UTF-8'),
            'completed_at' => now(),
        ])->save();
    }
}
