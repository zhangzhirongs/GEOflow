<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExecutorJob;
use App\Models\ExecutorNode;
use App\Services\GeoFlow\ExecutorJobService;
use App\Support\AdminWeb;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExecutorNodeController extends Controller
{
    public function __construct(private readonly ExecutorJobService $jobs) {}

    public function index(): View
    {
        $nodes = ExecutorNode::query()->orderByDesc('id')->paginate(20, ['*'], 'node_page')->withQueryString();
        foreach ($nodes as $node) {
            $fresh = $node->isFresh();
            if ((bool) $node->online !== $fresh) {
                $node->forceFill(['online' => $fresh])->save();
            }
        }

        return view('admin.executor-nodes.index', [
            'pageTitle' => __('georank.nav.executor_nodes'),
            'activeMenu' => 'articles',
            'adminSiteName' => AdminWeb::siteName(),
            'nodes' => $nodes,
            'jobs' => ExecutorJob::query()->with('account:id,account_name,platform')->orderByDesc('id')->paginate(30, ['*'], 'job_page')->withQueryString(),
            'pairing' => session('georank_pairing'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:80'],
            'max_concurrency' => ['nullable', 'integer', 'min:1', 'max:16'],
            'base_url' => ['nullable', 'url:http,https', 'max:1000'],
        ]);
        $created = $this->jobs->createNode($data, $request->user('admin')?->getAuthIdentifier());

        return redirect()
            ->route('admin.executor-nodes.index')
            ->with('message', __('georank.nodes.created'))
            ->with('georank_pairing', $created['pairing']);
    }

    public function resetKey(Request $request, int $nodeId): RedirectResponse
    {
        $node = ExecutorNode::query()->whereKey($nodeId)->firstOrFail();
        $pairing = $this->jobs->resetKey($node, $request->input('base_url'));

        return redirect()
            ->route('admin.executor-nodes.index')
            ->with('message', __('georank.nodes.key_reset'))
            ->with('georank_pairing', $pairing);
    }

    public function destroy(int $nodeId): RedirectResponse
    {
        ExecutorNode::query()->whereKey($nodeId)->firstOrFail()->delete();

        return back()->with('message', __('georank.nodes.deleted'));
    }
}
