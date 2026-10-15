<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateGrowthAiScheduleRequest;
use App\Services\Admin\Analytics\GrowthAiProbeService;
use App\Services\Admin\Analytics\GrowthOverviewService;
use App\Support\AdminWeb;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    private const TRAFFIC_QUERY_KEYS = [
        'log_preset',
        'log_date_from',
        'log_date_to',
        'log_traffic_type',
        'log_source',
        'traffic_type',
    ];

    private const CONTENT_QUERY_KEYS = [
        'preset',
        'date_from',
        'date_to',
        'task_id',
        'category_id',
        'article_id',
    ];

    public function __construct(
        private readonly GrowthOverviewService $overview,
        private readonly GrowthAiProbeService $probes,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->hasAny(self::TRAFFIC_QUERY_KEYS)) {
            return redirect()->route('admin.analytics.traffic', $request->query());
        }

        if ($request->filled('channel_id')) {
            if ($request->user('admin')?->canManageProtectedWorkflows() !== true) {
                abort(403);
            }

            return redirect()->route('admin.analytics.distribution', $request->query());
        }

        if ($request->hasAny(self::CONTENT_QUERY_KEYS)) {
            return redirect()->route('admin.analytics.content', $request->query());
        }

        $canManageProtectedWorkflows = auth('admin')->user()?->canManageProtectedWorkflows() === true;
        $growthProbes = $this->probes->recent();

        return view('admin.analytics.index', [
            'pageTitle' => __('admin.analytics.page_title'),
            'activeMenu' => 'analytics',
            'analyticsPage' => 'overview',
            'adminSiteName' => AdminWeb::siteName(),
            'canManageProtectedWorkflows' => $canManageProtectedWorkflows,
            'overview' => $this->overview->snapshot($canManageProtectedWorkflows),
            'growthProbes' => $growthProbes,
            'growthProbeData' => $growthProbes->mapWithKeys(fn ($probe): array => [
                $probe->uuid => $this->probes->present($probe),
            ])->all(),
            'growthProbeConfigured' => $this->probes->configured(),
            'growthProbeChannels' => $this->probes->channels(),
            'growthAiSchedule' => $this->probes->scheduleSettings(),
        ]);
    }

    public function updateAiSchedule(UpdateGrowthAiScheduleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['admin_id'] = $request->user('admin')->id;
        if (! $this->probes->configured() || ! in_array($data['provider_type'], array_column($this->probes->channels(), 'value'), true)) {
            return redirect()->route('admin.analytics')->withErrors(['provider_type' => __('admin.analytics.overview.probe.errors.channel_unavailable')])->withInput();
        }
        $this->probes->saveSchedule($data);

        return redirect()->route('admin.analytics')->with('message', 'AI 自动分析计划已保存');
    }
}
