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
        $nodes = ExecutorNode::query()->orderByDesc('id')->get();
        foreach ($nodes as $node) {
            if ($node->online && ! $node->isFresh()) {
                $node->forceFill(['online' => false])->save();
            }
        }

        return view('admin.executor-nodes.index', [
            'pageTitle' => __('georank.nav.executor_nodes'),
            'activeMenu' => 'articles',
            'adminSiteName' => AdminWeb::siteName(),
            'nodes' => ExecutorNode::query()->orderByDesc('id')->get(),
            'jobs' => ExecutorJob::query()->with('account:id,account_name,platform')->orderByDesc('id')->limit(30)->get(),
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
