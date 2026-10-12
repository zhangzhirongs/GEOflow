<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExecutorNode;
use App\Services\GeoFlow\ExecutorJobService;
use App\Support\GeoRank\WireGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class GeoRankNodeController extends Controller
{
    public function __construct(private readonly ExecutorJobService $jobs) {}

    public function heartbeat(Request $request): JsonResponse
    {
        $node = $this->node($request);
        $payload = $request->json()->all() ?: [];

        return response()->json($this->jobs->heartbeat(
            $node,
            $payload,
            $request->ip(),
        ));
    }

    public function claim(Request $request): JsonResponse
    {
        $job = $this->jobs->claim($this->node($request));
        if (! $job) {
            return response()->json(['job' => null]);
        }

        return response()->json([
            'job' => WireGuard::stripSecrets($job->toNodeJob()),
        ]);
    }

    public function report(Request $request, int $id): JsonResponse
    {
        try {
            $job = $this->jobs->report($this->node($request), $id, $request->json()->all() ?: []);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }

        return response()->json([
            'job' => WireGuard::stripSecrets($job->toNodeJob()),
        ]);
    }

    private function node(Request $request): ExecutorNode
    {
        $node = $request->attributes->get('georank_node');
        if (! $node instanceof ExecutorNode) {
            abort(401, 'node_unauthorized');
        }

        return $node;
    }
}
