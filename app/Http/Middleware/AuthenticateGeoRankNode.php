<?php

namespace App\Http\Middleware;

use App\Models\ExecutorNode;
use App\Services\GeoFlow\ExecutorJobService;
use App\Support\GeoRank\WireGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateGeoRankNode
{
    public function __construct(private readonly ExecutorJobService $jobs) {}

    public function handle(Request $request, Closure $next): Response
    {
        $slug = trim((string) $request->header('X-GeoRank-Slug', ''));
        $nodeKey = trim((string) $request->bearerToken());
        $node = $this->jobs->authenticate($slug, $nodeKey);
        if (! $node instanceof ExecutorNode) {
            return response()->json(['error' => 'node_unauthorized'], 401);
        }

        try {
            WireGuard::assertNoSecrets($request->json()->all() ?: []);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $request->attributes->set('georank_node', $node);

        return $next($request);
    }
}
