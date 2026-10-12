@extends('admin.layouts.app')

@section('content')
<div class="px-4 sm:px-0">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('georank.nodes.title') }}</h1>
            <p class="mt-1 text-sm leading-6 text-gray-600">{{ __('georank.nodes.subtitle') }}</p>
        </div>
        <a href="{{ route('admin.manual-publications.settings.index') }}" class="inline-flex w-fit items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            {{ __('georank.nodes.to_accounts') }}
        </a>
    </div>

    @if(session('georank_pairing'))
        @php($pairing = session('georank_pairing'))
        <div class="mt-6 rounded-lg border border-amber-300 bg-amber-50 p-5">
            <div class="text-sm font-semibold text-amber-900">{{ __('georank.nodes.pairing_once') }}</div>
            <dl class="mt-3 grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                @foreach(['Slug' => 'Slug', 'BaseUrl' => __('georank.nodes.base_url'), 'MaxConcurrency' => __('georank.nodes.concurrency'), 'Mode' => __('georank.nodes.mode')] as $key => $label)
                    @if(isset($pairing[$key]))
                        <div class="flex items-center justify-between gap-3 border-b border-amber-200/70 py-1">
                            <dt class="text-xs font-medium text-amber-800">{{ $label }}</dt>
                            <dd class="truncate font-mono text-xs text-amber-950">{{ $pairing[$key] }}</dd>
                        </div>
                    @endif
                @endforeach
                <div class="flex items-center justify-between gap-3 border-b border-amber-200/70 py-1 sm:col-span-2">
                    <dt class="text-xs font-medium text-amber-800">NodeKey</dt>
                    <dd class="truncate font-mono text-xs text-amber-950">{{ $pairing['NodeKey'] ?? '' }}</dd>
                </div>
            </dl>
            <textarea id="georank-pairing-json" readonly class="mt-3 w-full rounded-md border border-amber-200 bg-white p-3 font-mono text-xs text-amber-950" rows="6">{{ json_encode($pairing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</textarea>
            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('georank-pairing-json').value)" class="mt-3 rounded-md bg-amber-600 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-700">{{ __('georank.nodes.copy') }}</button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.executor-nodes.store') }}" class="mt-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        <h2 class="text-lg font-semibold text-gray-900">{{ __('georank.nodes.create') }}</h2>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div><label class="block text-sm font-medium text-gray-700">{{ __('georank.nodes.name') }} *</label><input name="name" required maxlength="160" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="office-pc"></div>
            <div><label class="block text-sm font-medium text-gray-700">Slug</label><input name="slug" maxlength="80" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="office-pc"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('georank.nodes.base_url') }}</label><input type="url" name="base_url" value="{{ rtrim(config('app.url'), '/') }}" maxlength="1000" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('georank.nodes.concurrency') }}</label><input type="number" min="1" max="16" name="max_concurrency" value="1" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm"></div>
        </div>
        <p class="mt-2 text-xs text-gray-500">{{ __('georank.nodes.base_url_help') }}</p>
        <button class="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('georank.nodes.create_btn') }}</button>
    </form>

    <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">{{ __('georank.nodes.name') }}</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">{{ __('georank.nodes.status') }}</th>
                    <th class="px-4 py-3">{{ __('georank.nodes.heartbeat') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($nodes as $node)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $node->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $node->slug }}</td>
                        <td class="px-4 py-3">
                            @if($node->isFresh())
                                <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">{{ __('georank.nodes.online') }}</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500">{{ __('georank.nodes.offline') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $node->last_heartbeat_at?->diffForHumans() ?: '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.executor-nodes.reset-key', ['nodeId' => $node->id]) }}" class="inline">@csrf<button class="text-xs font-semibold text-blue-700 hover:underline">{{ __('georank.nodes.reset_key') }}</button></form>
                            <form method="POST" action="{{ route('admin.executor-nodes.destroy', ['nodeId' => $node->id]) }}" class="inline" onsubmit="return confirm('{{ __('georank.nodes.confirm_delete') }}')">@csrf @method('DELETE')<button class="ml-3 text-xs font-semibold text-red-600 hover:underline">{{ __('georank.nodes.delete') }}</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('georank.nodes.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-900">{{ __('georank.jobs.recent') }}</div>
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">{{ __('georank.jobs.type') }}</th>
                    <th class="px-4 py-3">{{ __('georank.jobs.account') }}</th>
                    <th class="px-4 py-3">{{ __('georank.jobs.status') }}</th>
                    <th class="px-4 py-3">{{ __('georank.jobs.error') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($jobs as $job)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">{{ $job->id }}</td>
                        <td class="px-4 py-3">{{ $job->type }} / {{ $job->platform }}</td>
                        <td class="px-4 py-3">{{ $job->account?->account_name ?: $job->manual_publication_account_id }}</td>
                        <td class="px-4 py-3">{{ $job->status }}</td>
                        <td class="px-4 py-3 text-xs text-red-600">{{ $job->error }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('georank.jobs.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
