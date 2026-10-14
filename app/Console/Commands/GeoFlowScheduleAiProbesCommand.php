<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\AiGrowthProbe;
use App\Models\SiteSetting;
use App\Services\Admin\Analytics\GrowthAiProbeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GeoFlowScheduleAiProbesCommand extends Command
{
    protected $signature = 'geoflow:ai-probes:schedule';
    protected $description = 'Queue the configured recurring AI analysis question';

    public function handle(GrowthAiProbeService $probes): int
    {
        $settings = SiteSetting::query()->whereIn('setting_key', [
            'growth_ai_schedule_admin_id',
            'growth_ai_schedule_enabled', 'growth_ai_schedule_question',
            'growth_ai_schedule_provider', 'growth_ai_schedule_interval_minutes',
            'growth_ai_schedule_next_run_at',
        ])->pluck('setting_value', 'setting_key');
        if ((string) $settings->get('growth_ai_schedule_enabled', '0') !== '1' || trim((string) $settings->get('growth_ai_schedule_question')) === '') {
            return self::SUCCESS;
        }
        $next = $settings->get('growth_ai_schedule_next_run_at');
        if ($next && now()->lt($next)) return self::SUCCESS;
        $lock = Cache::lock('geoflow:ai-probes:schedule', 120);
        if (! $lock->get()) return self::SUCCESS;
        try {
            if (AiGrowthProbe::query()->where('question', trim((string) $settings->get('growth_ai_schedule_question')))->whereIn('status', [AiGrowthProbe::STATUS_QUEUED, AiGrowthProbe::STATUS_RUNNING])->exists()) return self::SUCCESS;
            $admin = Admin::query()->whereKey((int) $settings->get('growth_ai_schedule_admin_id'))->first();
            if (! $admin || ! $probes->configured()) return self::SUCCESS;
            $probes->queue((string) $settings->get('growth_ai_schedule_question'), $admin, (string) $settings->get('growth_ai_schedule_provider', AiGrowthProbe::CHANNEL_AUTO));
            SiteSetting::query()->updateOrCreate(['setting_key' => 'growth_ai_schedule_next_run_at'], ['setting_value' => now()->addMinutes(max(1, (int) $settings->get('growth_ai_schedule_interval_minutes', 60)))->toDateTimeString()]);
        } finally { $lock->release(); }
        return self::SUCCESS;
    }
}
