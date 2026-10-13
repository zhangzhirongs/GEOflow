<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGrowthAiProbeRequest;
use App\Models\Admin;
use App\Models\AiGrowthProbe;
use App\Services\Admin\Analytics\GrowthAiProbeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class GrowthAiProbeController extends Controller
{
    public function __construct(private readonly GrowthAiProbeService $probes) {}

    public function store(StoreGrowthAiProbeRequest $request): RedirectResponse
    {
        $admin = $request->user('admin');
        if (! $admin instanceof Admin) {
            abort(403);
        }

        $question = (string) $request->validated('question');
        $providerType = (string) $request->validated('provider_type');

        try {
            $probe = $this->probes->queue($question, $admin, $providerType);
        } catch (RuntimeException $exception) {
            $errorKey = $this->probes->configured() && $providerType !== AiGrowthProbe::CHANNEL_AUTO
                ? 'provider_type'
                : 'question';

            return redirect()
                ->route('admin.analytics')
                ->withInput()
                ->withErrors([$errorKey => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.analytics', ['probe' => $probe->uuid])
            ->with('message', __('admin.analytics.overview.probe.queued'));
    }

    public function show(AiGrowthProbe $probe): JsonResponse
    {
        return response()->json($this->probes->present($probe));
    }
}
