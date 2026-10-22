@php
    $editing = isset($account) && $account !== null;
    $formKey = $editing ? (string) $account->id : 'new';
    $hasOldInput = (string) old('_account_form_id') === $formKey;
    $value = static fn (string $name, mixed $default = '') => $hasOldInput
        ? old($name, $default)
        : ($editing ? ($account->{$name} ?? $default) : $default);
    $fieldId = static fn (string $name): string => 'account-'.$formKey.'-'.$name;
@endphp
<input type="hidden" name="_account_form_id" value="{{ $formKey }}">
<input type="hidden" name="persona_id" value="{{ $value('persona_id', $personas->first()->id ?? '') }}">
<div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
    <div>
        <label for="{{ $fieldId('platform') }}" class="block text-sm font-medium text-gray-700">{{ __('admin.manual_publications.field.platform') }} *</label>
        <select id="{{ $fieldId('platform') }}" name="platform" required class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm">
            @foreach($platforms as $platform)
                <option value="{{ $platform }}" @selected($value('platform', $platforms[0] ?? '') === $platform)>{{ __('admin.manual_publications.platform.'.$platform) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="{{ $fieldId('account_name') }}" class="block text-sm font-medium text-gray-700">{{ __('admin.manual_publications.field.account') }} *</label>
        <input id="{{ $fieldId('account_name') }}" name="account_name" value="{{ $value('account_name') }}" required maxlength="160" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm">
    </div>
    <div>
        <label for="{{ $fieldId('profile_url') }}" class="block text-sm font-medium text-gray-700">{{ __('admin.manual_publications.settings.profile_url') }}</label>
        <input id="{{ $fieldId('profile_url') }}" type="url" name="profile_url" value="{{ $value('profile_url') }}" maxlength="1000" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm">
    </div>
    <div>
        <label for="{{ $fieldId('publish_adapter') }}" class="block text-sm font-medium text-gray-700">{{ __('georank.publish.adapter_label') }}</label>
        <select id="{{ $fieldId('publish_adapter') }}" name="publish_adapter" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">{{ __('georank.publish.adapter_auto') }}</option>
            @foreach($publishAdapters as $key => $label)
                <option value="{{ $key }}" @selected($value('publish_adapter') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="{{ $fieldId('executor_node_id') }}" class="block text-sm font-medium text-gray-700">{{ __('georank.accounts.executor_node') }}</label>
        <select id="{{ $fieldId('executor_node_id') }}" name="executor_node_id" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">{{ __('georank.accounts.executor_node_none') }}</option>
            @foreach($executorNodes as $node)
                <option value="{{ $node->id }}" @selected((string) $value('executor_node_id') === (string) $node->id)>{{ $node->name }} ({{ $node->slug }})</option>
            @endforeach
            @if($editing && $account->executorNode && ! $executorNodes->contains('id', $account->executor_node_id))
                <option value="{{ $account->executor_node_id }}" @selected((string) $value('executor_node_id') === (string) $account->executor_node_id)>{{ $account->executorNode->name }} ({{ $account->executorNode->slug }})</option>
            @endif
        </select>
    </div>
    <div class="flex flex-wrap items-center gap-4 md:pt-6">
        @foreach(['auto_publish_enabled' => __('georank.publish.auto_publish'), 'is_active' => __('admin.manual_publications.settings.active')] as $name => $label)
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="hidden" name="{{ $name }}" value="0">
                <input type="checkbox" name="{{ $name }}" value="1" @checked((bool) $value($name, $name === 'is_active')) class="rounded border-gray-300 text-blue-600">
                {{ $label }}
            </label>
        @endforeach
    </div>
    <div class="md:col-span-2 xl:col-span-3">
        <label for="{{ $fieldId('notes') }}" class="block text-sm font-medium text-gray-700">{{ __('admin.manual_publications.settings.notes') }}</label>
        <textarea id="{{ $fieldId('notes') }}" name="notes" rows="2" maxlength="5000" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm">{{ $value('notes') }}</textarea>
    </div>
</div>
<details class="mt-4 rounded-lg border border-gray-200" @if($hasOldInput || $value('auto_publish_enabled', false)) open @endif>
    <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-gray-700">{{ __('admin.manual_publications.settings.connection_settings') }}</summary>
    <div class="grid grid-cols-1 gap-4 border-t border-gray-200 p-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach([
            'publish_endpoint_url' => [__('georank.publish.endpoint_url'), 'url', 1000],
            'publish_secret_key_id' => [__('georank.publish.gateway_account_id'), 'text', 120],
            'publish_login_identifier' => [__('georank.publish.login_identifier'), 'text', 160],
            'publish_auth_header_name' => [__('georank.publish.auth_header_name'), 'text', 120],
            'publish_basic_username' => [__('georank.publish.basic_username'), 'text', 160],
        ] as $name => [$label, $type, $max])
            <div>
                <label for="{{ $fieldId($name) }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label>
                <input id="{{ $fieldId($name) }}" type="{{ $type }}" name="{{ $name }}" value="{{ $value($name) }}" maxlength="{{ $max }}" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm">
            </div>
        @endforeach
        @foreach(['publish_method' => [$publishMethods, __('georank.publish.method'), 'POST'], 'publish_auth_type' => [$publishAuthTypes, __('georank.publish.auth_type'), 'none']] as $name => [$options, $label, $default])
            <div>
                <label for="{{ $fieldId($name) }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label>
                <select id="{{ $fieldId($name) }}" name="{{ $name }}" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm">
                    @foreach($options as $option)
                        <option value="{{ $option }}" @selected($value($name, $default) === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
        <div>
            <label for="{{ $fieldId('publish_secret') }}" class="block text-sm font-medium text-gray-700">{{ __('georank.publish.secret') }}</label>
            <input id="{{ $fieldId('publish_secret') }}" name="publish_secret" type="password" autocomplete="new-password" maxlength="10000" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm" @if($editing) placeholder="{{ __('georank.publish.secret_ph_edit') }}" @endif>
        </div>
        <div class="md:col-span-2 xl:col-span-3">
            <label for="{{ $fieldId('publish_session') }}" class="block text-sm font-medium text-gray-700">{{ __('georank.publish.session') }}</label>
            <textarea id="{{ $fieldId('publish_session') }}" name="publish_session" rows="2" maxlength="10000" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __($editing ? 'georank.publish.session_ph_edit' : 'georank.publish.session_ph') }}"></textarea>
        </div>
    </div>
</details>
