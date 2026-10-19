@extends('admin.layouts.app')

@section('content')
<div class="px-4 sm:px-0">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.manual_publications.settings.title') }}</h1>
            <p class="mt-1 text-sm leading-6 text-gray-600">{{ __('admin.manual_publications.settings.subtitle') }}</p>
        </div>
        <a href="{{ route('admin.manual-publications.index') }}" class="inline-flex w-fit items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>{{ __('admin.manual_publications.button.back') }}
        </a>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <section class="space-y-5">
            <div class="rounded-lg border border-blue-100 bg-blue-50 p-5 text-sm text-blue-900">
                {{ __('admin.manual_publications.settings.identity_auto_managed') }}
            </div>
            {{-- Publishing identities are managed automatically from the logged-in account. --}}
            <form method="POST" action="{{ route('admin.manual-publications.settings.personas.store') }}" class="hidden">
                @csrf
                <h2 class="text-lg font-semibold text-gray-900">{{ __('admin.manual_publications.settings.new_persona') }}</h2>
                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><label class="block text-sm font-medium text-gray-700">{{ __('admin.manual_publications.settings.name') }} *</label><input name="name" required maxlength="120" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm"></div>
                    <div><label class="block text-sm font-medium text-gray-700">{{ __('admin.manual_publications.settings.tone') }}</label><input name="tone" maxlength="120" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm"></div>
                    <div><label class="block text-sm font-medium text-gray-700">{{ __('admin.manual_publications.settings.domain') }}</label><input name="domain" maxlength="255" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm"></div>
                    <div class="sm:col-span-2"><label class="block text-sm font-medium text-gray-700">{{ __('admin.manual_publications.settings.bio') }}</label><textarea name="bio" rows="3" maxlength="5000" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm"></textarea></div>
                    <div class="sm:col-span-2"><label class="block text-sm font-medium text-gray-700">{{ __('admin.manual_publications.settings.disclosure') }}</label><textarea name="disclosure_text" rows="3" maxlength="2000" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm"></textarea></div>
                </div>
                <input type="hidden" name="is_active" value="1">
                <button class="mt-5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('admin.manual_publications.settings.save_persona') }}</button>
            </form>

            @foreach($personas as $persona)
                <form method="POST" action="{{ route('admin.manual-publications.settings.personas.update', ['personaId' => $persona->id]) }}" class="hidden">
                    @csrf @method('PUT')
                    <div class="flex items-center justify-between gap-3"><h3 class="font-semibold text-gray-900">#{{ $persona->id }} · {{ $persona->name }}</h3><span class="text-xs text-gray-500">{{ __('admin.manual_publications.settings.account_count', ['count' => $persona->accounts_count]) }}</span></div>
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <input name="name" value="{{ $persona->name }}" required maxlength="120" class="rounded-md border-gray-300 text-sm shadow-sm">
                        <input name="tone" value="{{ $persona->tone }}" maxlength="120" class="rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('admin.manual_publications.settings.tone') }}">
                        <input name="domain" value="{{ $persona->domain }}" maxlength="255" class="rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('admin.manual_publications.settings.domain') }}">
                        <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($persona->is_active) class="rounded border-gray-300 text-blue-600">{{ __('admin.manual_publications.settings.active') }}</label>
                        <textarea name="bio" rows="2" maxlength="5000" class="sm:col-span-2 rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('admin.manual_publications.settings.bio') }}">{{ $persona->bio }}</textarea>
                        <textarea name="disclosure_text" rows="2" maxlength="2000" class="sm:col-span-2 rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('admin.manual_publications.settings.disclosure') }}">{{ $persona->disclosure_text }}</textarea>
                    </div>
                    <button class="mt-4 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">{{ __('admin.manual_publications.button.save') }}</button>
                </form>
            @endforeach
        </section>

        <section class="space-y-5">
            <form method="POST" action="{{ route('admin.manual-publications.settings.accounts.store') }}" class="rounded-lg border border-purple-200 bg-white p-6 shadow-sm ring-1 ring-purple-50">
                @csrf
                <div class="flex items-start justify-between gap-3"><div><h2 class="text-lg font-semibold text-gray-900">{{ __('admin.manual_publications.settings.new_account') }}</h2><p class="mt-1 text-xs text-gray-500">账号用于登录、保持会话并提交发布任务。</p></div><span class="rounded-full bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700">新账号</span></div>
                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <input type="hidden" name="persona_id" value="{{ $personas->first()->id ?? '' }}">
                    <div><label class="block text-sm font-medium text-gray-700">{{ __('admin.manual_publications.field.platform') }} *</label><select name="platform" required class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm">@foreach($platforms as $platform)<option value="{{ $platform }}">{{ __('admin.manual_publications.platform.'.$platform) }}</option>@endforeach</select></div>
                    <div><label class="block text-sm font-medium text-gray-700">{{ __('georank.publish.adapter_label') }}</label><select name="publish_adapter" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm"><option value="">{{ __('georank.publish.adapter_auto') }}</option>@foreach($publishAdapters as $adapterKey => $adapterLabel)<option value="{{ $adapterKey }}">{{ $adapterLabel }}</option>@endforeach</select></div>
                    <div><label class="block text-sm font-medium text-gray-700">{{ __('admin.manual_publications.field.account') }} *</label><input name="account_name" required maxlength="160" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm"></div>
                    <div><label class="block text-sm font-medium text-gray-700">{{ __('admin.manual_publications.field.custom_platform') }}</label><input name="custom_platform" maxlength="120" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm"></div>
                    <div><label class="block text-sm font-medium text-gray-700">{{ __('georank.publish.gateway_account_id') }}</label><input name="publish_secret_key_id" maxlength="120" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('georank.publish.gateway_account_id_ph') }}"></div>
                    <div class="sm:col-span-2"><label class="block text-sm font-medium text-gray-700">{{ __('admin.manual_publications.settings.profile_url') }}</label><input type="url" name="profile_url" maxlength="1000" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm"></div>
                    <div class="sm:col-span-2"><label class="block text-sm font-medium text-gray-700">{{ __('admin.manual_publications.settings.notes') }}</label><textarea name="notes" rows="3" maxlength="5000" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm"></textarea></div>
                    <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700"><input type="hidden" name="auto_publish_enabled" value="0"><input type="checkbox" name="auto_publish_enabled" value="1" class="rounded border-gray-300 text-blue-600">{{ __('georank.publish.auto_publish') }}</label>
                    <div class="sm:col-span-2"><label class="block text-sm font-medium text-gray-700">{{ __('georank.accounts.executor_node') }}</label>
                    <select name="executor_node_id" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm">
                        <option value="">{{ __('georank.accounts.executor_node_none') }}</option>
                        @foreach(($executorNodes ?? []) as $node)
                            <option value="{{ $node->id }}">{{ $node->name }} ({{ $node->slug }})</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">{{ __('georank.accounts.executor_node_help') }}</p>
                    </div>
                    <div><label class="block text-sm font-medium text-gray-700">{{ __('georank.publish.endpoint_url') }}</label><input type="url" name="publish_endpoint_url" maxlength="1000" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="http://host.docker.internal:8787/xhs/publish"></div>
                    <div><label class="block text-sm font-medium text-gray-700">{{ __('georank.publish.method') }}</label><select name="publish_method" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm">@foreach($publishMethods as $method)<option value="{{ $method }}">{{ $method }}</option>@endforeach</select></div>
                    <div><label class="block text-sm font-medium text-gray-700">{{ __('georank.publish.auth_type') }}</label><select name="publish_auth_type" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm">@foreach($publishAuthTypes as $authType)<option value="{{ $authType }}">{{ $authType }}</option>@endforeach</select></div>
                    <div><label class="block text-sm font-medium text-gray-700">{{ __('georank.publish.login_identifier') }}</label><input name="publish_login_identifier" maxlength="160" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('georank.publish.login_identifier_ph') }}"></div>
                    <div><label class="block text-sm font-medium text-gray-700">{{ __('georank.publish.auth_header_name') }}</label><input name="publish_auth_header_name" maxlength="120" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm"></div>
                    <div><label class="block text-sm font-medium text-gray-700">{{ __('georank.publish.basic_username') }}</label><input name="publish_basic_username" maxlength="160" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm"></div>
                    <div class="sm:col-span-2"><label class="block text-sm font-medium text-gray-700">{{ __('georank.publish.secret') }}</label><input name="publish_secret" type="password" maxlength="10000" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm"></div>
                    <div class="sm:col-span-2"><label class="block text-sm font-medium text-gray-700">{{ __('georank.publish.session') }}</label><textarea name="publish_session" rows="2" maxlength="10000" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('georank.publish.session_ph') }}"></textarea></div>
                </div>
                <input type="hidden" name="is_active" value="1">
                <button class="mt-5 rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700">{{ __('admin.manual_publications.settings.save_account') }}</button>
            </form>

            @foreach($accounts as $account)
                @php($profile = is_array($account->publish_profile_json) ? $account->publish_profile_json : [])
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-gray-300 hover:shadow-md">
                    <form method="POST" action="{{ route('admin.manual-publications.settings.accounts.update', ['accountId' => $account->id]) }}">
                        @csrf @method('PUT')
                        <h3 class="font-semibold text-gray-900">#{{ $account->id }} · {{ $account->account_name }}</h3>
                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <input type="hidden" name="persona_id" value="{{ $account->persona_id }}">
                            <select name="platform" required class="rounded-md border-gray-300 text-sm shadow-sm">@foreach($platforms as $platform)<option value="{{ $platform }}" @selected($account->platform === $platform)>{{ __('admin.manual_publications.platform.'.$platform) }}</option>@endforeach</select>
                            <select name="publish_adapter" class="rounded-md border-gray-300 text-sm shadow-sm"><option value="">{{ __('georank.publish.adapter_auto') }}</option>@foreach($publishAdapters as $adapterKey => $adapterLabel)<option value="{{ $adapterKey }}" @selected($account->publish_adapter === $adapterKey)>{{ $adapterLabel }}</option>@endforeach</select>
                            <input name="account_name" value="{{ $account->account_name }}" required maxlength="160" class="rounded-md border-gray-300 text-sm shadow-sm">
                            <input name="custom_platform" value="{{ $account->custom_platform }}" maxlength="120" class="rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('admin.manual_publications.field.custom_platform') }}">
                            <input name="publish_secret_key_id" value="{{ $account->publish_secret_key_id }}" maxlength="120" class="rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('georank.publish.gateway_account_id') }}">
                            <input type="url" name="profile_url" value="{{ $account->profile_url }}" maxlength="1000" class="sm:col-span-2 rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('admin.manual_publications.settings.profile_url') }}">
                            <textarea name="notes" rows="2" maxlength="5000" class="sm:col-span-2 rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('admin.manual_publications.settings.notes') }}">{{ $account->notes }}</textarea>
                            <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700"><input type="hidden" name="auto_publish_enabled" value="0"><input type="checkbox" name="auto_publish_enabled" value="1" @checked($account->auto_publish_enabled) class="rounded border-gray-300 text-blue-600">{{ __('georank.publish.auto_publish') }}</label>
                            <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">{{ __('georank.accounts.executor_node') }}</label>
                            <select name="executor_node_id" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">{{ __('georank.accounts.executor_node_none') }}</option>
                                @foreach(($executorNodes ?? []) as $node)
                                    <option value="{{ $node->id }}" @selected((int) $account->executor_node_id === (int) $node->id)>{{ $node->name }} ({{ $node->slug }})</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">{{ __('georank.session_status') }}: {{ $account->sessionStatusLabel() }} @if($account->executorNode) / {{ $account->executorNode->slug }} @endif</p>
                        </div>
                            <input type="url" name="publish_endpoint_url" value="{{ $account->publish_endpoint_url }}" maxlength="1000" class="rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('georank.publish.endpoint_url') }}">
                            <select name="publish_method" class="rounded-md border-gray-300 text-sm shadow-sm">@foreach($publishMethods as $method)<option value="{{ $method }}" @selected($account->publish_method === $method)>{{ $method }}</option>@endforeach</select>
                            <select name="publish_auth_type" class="rounded-md border-gray-300 text-sm shadow-sm">@foreach($publishAuthTypes as $authType)<option value="{{ $authType }}" @selected($account->publish_auth_type === $authType)>{{ $authType }}</option>@endforeach</select>
                            <input name="publish_login_identifier" value="{{ $account->publish_login_identifier }}" maxlength="160" class="rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('georank.publish.login_identifier') }}">
                            <input name="publish_auth_header_name" value="{{ $account->publish_auth_header_name }}" maxlength="120" class="rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('georank.publish.auth_header_name') }}">
                            <input name="publish_basic_username" value="{{ $account->publish_basic_username }}" maxlength="160" class="rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('georank.publish.basic_username') }}">
                            <input name="publish_secret" type="password" maxlength="10000" class="sm:col-span-2 rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('georank.publish.secret_ph_edit') }}">
                            <textarea name="publish_session" rows="2" maxlength="10000" class="sm:col-span-2 rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('georank.publish.session_ph_edit') }}"></textarea>
                            <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($account->is_active) class="rounded border-gray-300 text-blue-600">{{ __('admin.manual_publications.settings.active') }}</label>
                        </div>
                        <button class="mt-4 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">{{ __('admin.manual_publications.button.save') }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.manual-publications.settings.accounts.destroy', ['accountId' => $account->id]) }}" class="mt-2" onsubmit="return confirm('{{ __('admin.manual_publications.settings.confirm_account_delete') }}')">
                        @csrf @method('DELETE')
                        <button class="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">{{ __('admin.button.delete') }}</button>
                    </form>
                    @if($account->executor_node_id)
                        <div class="mt-4 flex flex-col gap-3 rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-900 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="font-semibold">{{ __('georank.accounts.executor_box') }}</div>
                                <div class="mt-1 text-xs">{{ __('georank.session_status') }}: {{ $account->sessionStatusLabel() }}</div>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('admin.manual-publications.settings.accounts.executor-login', ['accountId' => $account->id]) }}">@csrf<button class="rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">{{ __('georank.accounts.login') }}</button></form>
                                <form method="POST" action="{{ route('admin.manual-publications.settings.accounts.executor-check', ['accountId' => $account->id]) }}">@csrf<button class="rounded-md border border-blue-200 bg-white px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">{{ __('georank.accounts.check') }}</button></form>
                            </div>
                        </div>
                    @endif
                    @if($account->platform === \App\Models\ManualPublicationAccount::PLATFORM_XIAOHONGSHU && ! $account->executor_node_id)
                        <div class="mt-4 flex flex-col gap-3 rounded-lg border border-red-100 bg-red-50 p-3 text-sm text-red-800 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="font-semibold">{{ __('georank.publish.xhs_box') }}</div>
                                <div class="mt-1 text-xs">{{ __('georank.publish.status_label') }}：{{ $profile['gateway_login_status'] ?? __('georank.publish.status_unchecked') }} @if(!empty($profile['gateway_last_checked_at'])) · {{ $profile['gateway_last_checked_at'] }} @endif</div>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('admin.manual-publications.settings.accounts.xiaohongshu-login.start', ['accountId' => $account->id]) }}">@csrf<button class="rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700">{{ __('georank.publish.login_refresh') }}</button></form>
                                <form method="POST" action="{{ route('admin.manual-publications.settings.accounts.xiaohongshu-login.status', ['accountId' => $account->id]) }}">@csrf<button class="rounded-md border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">{{ __('georank.publish.check_status') }}</button></form>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
            <div class="mt-4">{{ $accounts->links() }}</div>
        </section>
    </div>
</div>
@endsection
