@extends('admin.layouts.app')

@section('content')
    <div class="px-4 sm:px-0">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div>
                    <h1 class="qdk-page-title">{{ __('admin.ai_source_providers.page_title') }}</h1>
                    <p class="qdk-page-sub">{{ __('admin.ai_source_providers.page_subtitle') }}</p>
                </div>
            </div>
            <button type="button" onclick="showCreateProviderModal()" class="qdk-btn qdk-btn-primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                {{ __('admin.ai_source_providers.create') }}
            </button>
        </div>

        <div class="mb-8 grid grid-cols-1 gap-5 md:grid-cols-4">
            <div class="qdk-stat">
                <div class="qdk-stat-icon"><i data-lucide="server" class="h-5 w-5"></i></div>
                <div>
                    <div class="qdk-stat-label">{{ __('admin.ai_source_providers.stats.total') }}</div>
                    <div class="qdk-stat-value">{{ (int) ($stats['provider_count'] ?? 0) }}</div>
                </div>
            </div>
            <div class="qdk-stat">
                <div class="qdk-stat-icon"><i data-lucide="check-circle" class="h-5 w-5"></i></div>
                <div>
                    <div class="qdk-stat-label">{{ __('admin.ai_source_providers.stats.active') }}</div>
                    <div class="qdk-stat-value">{{ (int) ($stats['active_provider_count'] ?? 0) }}</div>
                </div>
            </div>
            <div class="qdk-stat">
                <div class="qdk-stat-icon"><i data-lucide="activity" class="h-5 w-5"></i></div>
                <div>
                    <div class="qdk-stat-label">{{ __('admin.ai_source_providers.stats.today_usage') }}</div>
                    <div class="qdk-stat-value">{{ number_format((int) ($stats['provider_today_usage'] ?? 0)) }}</div>
                </div>
            </div>
            <div class="qdk-stat">
                <div class="qdk-stat-icon"><i data-lucide="alert-triangle" class="h-5 w-5"></i></div>
                <div>
                    <div class="qdk-stat-label">{{ __('admin.ai_source_providers.stats.failed_runs') }}</div>
                    <div class="qdk-stat-value">{{ number_format((int) ($stats['failed_runs'] ?? 0)) }}</div>
                </div>
            </div>
        </div>

        <div class="qdk-card mb-6">
            <div class="qdk-card-head">
                <h3 class="qdk-card-title">{{ __('admin.ai_source_providers.quick_config_title') }}</h3>
                <p class="qdk-card-sub">{{ __('admin.ai_source_providers.quick_config_desc') }}</p>
            </div>
            <div class="grid grid-cols-1 divide-y divide-gray-100 xl:grid-cols-2 xl:divide-x xl:divide-y-0">
                <form method="POST" action="{{ route('admin.ai-source-providers.model-bindings.upsert-api') }}" class="space-y-5 p-6">
                    @csrf
                    <input type="hidden" name="binding_type" value="deepseek">
                    <input type="hidden" id="deepseek_config_model_id" value="{{ (int) ($deepSeekApiConfig['id'] ?? 0) }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="braces" class="h-5 w-5 text-[color:var(--qdk-primary)]"></i>
                                <h4 class="text-base font-semibold text-gray-900">{{ __('admin.ai_source_providers.deepseek_config_title') }}</h4>
                            </div>
                            <p class="mt-1 text-sm text-gray-600">{{ __('admin.ai_source_providers.deepseek_config_desc') }}</p>
                        </div>
                        <span class="qdk-badge qdk-badge-gray shrink-0">JSON</span>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="deepseek_api_name" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_name') }}</label>
                            <input type="text" name="name" id="deepseek_api_name" required value="{{ $deepSeekApiConfig['name'] }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </div>
                        <div>
                            <label for="deepseek_api_model_id" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_model_id') }}</label>
                            <input type="text" name="model_id" id="deepseek_api_model_id" required value="{{ $deepSeekApiConfig['model_id'] }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('admin.ai_source_providers.placeholder_deepseek_model_id') }}">
                        </div>
                    </div>

                    <div>
                        <label for="deepseek_api_url" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_api_url') }}</label>
                        <input type="url" name="api_url" id="deepseek_api_url" required value="{{ $deepSeekApiConfig['api_url'] }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-1">
                            <label for="deepseek_api_key" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_api_key') }}</label>
                            <input type="password" name="api_key" id="deepseek_api_key" @if ((int) ($deepSeekApiConfig['id'] ?? 0) <= 0) required @endif class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">{{ __('admin.ai_source_providers.configured_key') }}: {{ $deepSeekApiConfig['masked_api_key'] }}</p>
                        </div>
                        <div>
                            <label for="deepseek_daily_limit" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_daily_limit') }}</label>
                            <input type="number" name="daily_limit" id="deepseek_daily_limit" min="0" value="{{ (int) ($deepSeekApiConfig['daily_limit'] ?? 0) }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </div>
                        <div>
                            <label for="deepseek_max_tokens" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_max_tokens') }}</label>
                            <input type="number" name="max_tokens" id="deepseek_max_tokens" min="1" value="{{ $deepSeekApiConfig['max_tokens'] }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">{{ __('admin.ai_source_providers.max_tokens_help') }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" onclick="testConfiguredModelBinding('deepseek', 'deepseek_config_model_id', 'deepseek-config-test-result', this)" class="qdk-btn qdk-btn-ghost qdk-btn-sm">
                            {{ __('admin.ai_source_providers.structured_test') }}
                        </button>
                        <button type="submit" class="qdk-btn qdk-btn-primary qdk-btn-sm">
                            {{ __('admin.ai_source_providers.save_api_config') }}
                        </button>
                    </div>
                    <div id="deepseek-config-test-result" class="text-xs"></div>
                </form>

                <form method="POST" action="{{ route('admin.ai-source-providers.model-bindings.upsert-api') }}" class="space-y-5 p-6">
                    @csrf
                    <input type="hidden" name="binding_type" value="ark">
                    <input type="hidden" id="ark_config_model_id" value="{{ (int) ($arkApiConfig['id'] ?? 0) }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="search-check" class="h-5 w-5 text-[color:var(--qdk-primary)]"></i>
                                <h4 class="text-base font-semibold text-gray-900">{{ __('admin.ai_source_providers.doubao_ark_config_title') }}</h4>
                            </div>
                            <p class="mt-1 text-sm text-gray-600">{{ __('admin.ai_source_providers.doubao_ark_config_desc') }}</p>
                        </div>
                        <span class="qdk-badge qdk-badge-gray shrink-0">Responses</span>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="ark_api_name" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_name') }}</label>
                            <input type="text" name="name" id="ark_api_name" required value="{{ $arkApiConfig['name'] }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </div>
                        <div>
                            <label for="ark_api_model_id" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_model_id') }}</label>
                            <input type="text" name="model_id" id="ark_api_model_id" required value="{{ $arkApiConfig['model_id'] }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('admin.ai_source_providers.placeholder_ark_model_id') }}">
                        </div>
                    </div>

                    <div>
                        <label for="ark_api_url" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_api_url') }}</label>
                        <input type="url" name="api_url" id="ark_api_url" required value="{{ $arkApiConfig['api_url'] }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-1">
                            <label for="ark_api_key" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_api_key') }}</label>
                            <input type="password" name="api_key" id="ark_api_key" @if ((int) ($arkApiConfig['id'] ?? 0) <= 0) required @endif class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ (int) ($arkApiConfig['id'] ?? 0) > 0 ? __('admin.ai_source_providers.placeholder_api_key_keep') : __('admin.ai_source_providers.placeholder_api_key') }}">
                            <p class="mt-1 text-xs text-gray-500">{{ __('admin.ai_source_providers.configured_key') }}: {{ $arkApiConfig['masked_api_key'] }}</p>
                        </div>
                        <div>
                            <label for="ark_daily_limit" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_daily_limit') }}</label>
                            <input type="number" name="daily_limit" id="ark_daily_limit" min="0" value="{{ (int) ($arkApiConfig['daily_limit'] ?? 0) }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                        </div>
                        <div>
                            <label for="ark_max_tokens" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_max_tokens') }}</label>
                            <input type="number" name="max_tokens" id="ark_max_tokens" min="1" value="{{ $arkApiConfig['max_tokens'] }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <p class="mt-1 text-xs text-gray-500">{{ __('admin.ai_source_providers.max_tokens_help') }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" onclick="testConfiguredModelBinding('ark', 'ark_config_model_id', 'ark-config-test-result', this)" class="qdk-btn qdk-btn-ghost qdk-btn-sm">
                            {{ __('admin.ai_source_providers.structured_test') }}
                        </button>
                        <button type="submit" class="qdk-btn qdk-btn-primary qdk-btn-sm">
                            {{ __('admin.ai_source_providers.save_api_config') }}
                        </button>
                    </div>
                    <div id="ark-config-test-result" class="text-xs"></div>
                </form>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="qdk-card lg:col-span-2">
                <div class="qdk-card-head">
                    <h3 class="qdk-card-title">{{ __('admin.ai_source_providers.search_list_title') }}</h3>
                    <p class="qdk-card-sub">{{ __('admin.ai_source_providers.search_list_desc') }}</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-[#fafbfc]">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.ai_source_providers.column.provider') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.ai_source_providers.column.options') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.ai_source_providers.column.usage') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.ai_source_providers.column.status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('admin.ai_source_providers.column.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                        @if (empty($providers))
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                    <i data-lucide="search-x" class="mx-auto mb-2 h-8 w-8 text-gray-400"></i>
                                    <p>{{ __('admin.ai_source_providers.empty') }}</p>
                                    <button type="button" onclick="showCreateProviderModal()" class="mt-2 text-[color:var(--qdk-primary)] hover:underline">
                                        {{ __('admin.ai_source_providers.add_first') }}
                                    </button>
                                </td>
                            </tr>
                        @else
                            @foreach ($providers as $provider)
                                <tr class="qdk-row">
                                    <td class="px-6 py-4 align-top">
                                        <div class="flex items-center gap-2">
                                            <div class="text-sm font-medium text-gray-900">{{ $provider['name'] }}</div>
                                            <span class="qdk-badge">
                                                {{ $provider['provider_label'] }}
                                            </span>
                                        </div>
                                        <div class="mt-1 max-w-sm truncate text-sm text-gray-500">{{ $provider['endpoint_url'] }}</div>
                                        <div class="mt-1 text-xs text-gray-400">{{ __('admin.ai_source_providers.api_key_mask') }}: {{ $provider['masked_api_key'] }}</div>
                                    </td>
                                    <td class="px-6 py-4 align-top text-sm text-gray-700">
                                        <div>{{ __('admin.ai_source_providers.option_count', ['count' => (int) ($provider['metadata']['count'] ?? 10)]) }}</div>
                                        <div>{{ __('admin.ai_source_providers.option_summary') }}: {{ ! empty($provider['metadata']['need_summary']) ? __('admin.common.yes') : __('admin.common.no') }}</div>
                                        <div>{{ __('admin.ai_source_providers.option_content') }}: {{ ! empty($provider['metadata']['need_content']) ? __('admin.common.yes') : __('admin.common.no') }}</div>
                                    </td>
                                    <td class="px-6 py-4 align-top text-sm text-gray-900">
                                        @if ((int) $provider['daily_limit'] > 0)
                                            <div>{{ (int) $provider['used_today'] }} / {{ (int) $provider['daily_limit'] }}</div>
                                            <div class="text-xs text-gray-500">{{ __('admin.ai_source_providers.limit_today') }}</div>
                                        @else
                                            <div class="text-[#10894e]">{{ __('admin.ai_source_providers.limit_unlimited') }}</div>
                                        @endif
                                        <div class="mt-1 text-xs text-gray-500">{{ __('admin.ai_source_providers.total_used', ['count' => number_format((int) $provider['total_used'])]) }}</div>
                                    </td>
                                    <td class="px-6 py-4 align-top">
                                        @if ($provider['status'] === 'active')
                                            <span class="qdk-badge qdk-badge-green">{{ __('admin.ai_source_providers.status_active') }}</span>
                                        @else
                                            <span class="qdk-badge" style="background:#fde8e8;color:#c53030;">{{ __('admin.ai_source_providers.status_inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 align-top text-sm font-medium">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <button type="button" onclick="testSourceProvider({{ (int) $provider['id'] }}, this)" class="text-[#10894e] hover:underline">{{ __('admin.ai_source_providers.test') }}</button>
                                            <button type="button" onclick='editProvider(@json($provider, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP))' class="text-[color:var(--qdk-primary)] hover:underline">{{ __('admin.ai_source_providers.edit') }}</button>
                                            <button type="button" onclick="deleteProvider({{ (int) $provider['id'] }}, @js($provider['name']))" class="text-red-600 hover:underline">{{ __('admin.ai_source_providers.delete') }}</button>
                                        </div>
                                        <div id="provider-test-result-{{ (int) $provider['id'] }}" class="mt-2 max-w-xs whitespace-normal text-xs"></div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="qdk-card">
                <div class="qdk-card-head">
                    <h3 class="qdk-card-title">{{ __('admin.ai_source_providers.model_bindings_title') }}</h3>
                    <p class="qdk-card-sub">{{ __('admin.ai_source_providers.model_bindings_desc') }}</p>
                </div>
                <form method="POST" action="{{ route('admin.ai-source-providers.model-bindings') }}" class="space-y-5 px-6 py-5">
                    @csrf
                    <div>
                        <label for="ark_model_id" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.ark_model') }}</label>
                        <select name="ark_model_id" id="ark_model_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="0">{{ __('admin.ai_source_providers.model_none') }}</option>
                            @foreach ($chatModels as $model)
                                <option value="{{ (int) $model['id'] }}" @selected((int) $arkModelId === (int) $model['id'])>
                                    {{ $model['name'].' ('.$model['model_id'].')' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="mt-2 flex items-center justify-between gap-3">
                            <p class="text-xs text-gray-500">{{ __('admin.ai_source_providers.ark_model_help') }}</p>
                            <button type="button" onclick="testModelBinding('ark', 'ark_model_id', 'ark-model-test-result', this)" class="shrink-0 text-xs font-medium text-[color:var(--qdk-primary)] hover:underline">{{ __('admin.ai_source_providers.test') }}</button>
                        </div>
                        <div id="ark-model-test-result" class="mt-2 text-xs"></div>
                    </div>

                    <div>
                        <label for="deepseek_model_id" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.deepseek_model') }}</label>
                        <select name="deepseek_model_id" id="deepseek_model_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="0">{{ __('admin.ai_source_providers.model_none') }}</option>
                            @foreach ($chatModels as $model)
                                <option value="{{ (int) $model['id'] }}" @selected((int) $deepSeekModelId === (int) $model['id'])>
                                    {{ $model['name'].' ('.$model['model_id'].')' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="mt-2 flex items-center justify-between gap-3">
                            <p class="text-xs text-gray-500">{{ __('admin.ai_source_providers.deepseek_model_help') }}</p>
                            <button type="button" onclick="testModelBinding('deepseek', 'deepseek_model_id', 'deepseek-model-test-result', this)" class="shrink-0 text-xs font-medium text-[color:var(--qdk-primary)] hover:underline">{{ __('admin.ai_source_providers.test') }}</button>
                        </div>
                        <div id="deepseek-model-test-result" class="mt-2 text-xs"></div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="qdk-btn qdk-btn-primary qdk-btn-sm">
                            {{ __('admin.ai_source_providers.save_bindings') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="providerModal" class="fixed inset-0 z-50 hidden h-full w-full overflow-y-auto bg-gray-600/50">
        <div class="relative top-10 mx-auto w-11/12 rounded-md border bg-white p-5 shadow-lg md:w-3/4 lg:w-1/2">
            <div class="mt-3">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900" id="providerModalTitle">{{ __('admin.ai_source_providers.modal_create') }}</h3>
                    <button type="button" onclick="closeProviderModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="h-6 w-6"></i>
                    </button>
                </div>

                <form id="providerForm" method="POST" action="{{ route('admin.ai-source-providers.store') }}" class="space-y-6">
                    @csrf
                    <input type="hidden" name="_method" id="providerFormMethod" value="POST">

                    <div class="rounded-md border border-[#d6e2ff] bg-[color:var(--qdk-primary-weak)] px-4 py-3">
                        <div class="flex items-center gap-2 text-sm font-medium text-[color:var(--qdk-primary)]">
                            <i data-lucide="search-check" class="h-4 w-4"></i>
                            {{ __('admin.ai_source_providers.provider.doubao_search_custom') }}
                        </div>
                        <p class="mt-1 text-xs text-[#3f5fb0]">{{ __('admin.ai_source_providers.doubao_custom_hint') }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label for="provider_name" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_name') }}</label>
                            <input type="text" name="name" id="provider_name" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('admin.ai_source_providers.placeholder_name') }}">
                        </div>
                        <div>
                            <label for="provider_daily_limit" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_daily_limit') }}</label>
                            <input type="number" name="daily_limit" id="provider_daily_limit" min="0" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" value="0">
                        </div>
                    </div>

                    <div>
                        <label for="provider_endpoint_url" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_endpoint_url') }}</label>
                        <input type="url" name="endpoint_url" id="provider_endpoint_url" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" value="{{ $defaultDoubaoEndpoint }}" placeholder="{{ $defaultDoubaoEndpoint }}">
                    </div>

                    <div>
                        <label for="provider_api_key" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_api_key') }}</label>
                        <input type="password" name="api_key" id="provider_api_key" required class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('admin.ai_source_providers.placeholder_api_key') }}">
                        <p id="providerApiKeyHelp" class="mt-1 text-xs text-gray-500">{{ __('admin.ai_source_providers.api_key_help_create') }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div>
                            <label for="provider_count" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_count') }}</label>
                            <input type="number" name="count" id="provider_count" min="1" max="20" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" value="10">
                        </div>
                        <div>
                            <label for="provider_content_formats" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_content_formats') }}</label>
                            <select name="content_formats" id="provider_content_formats" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="Markdown">Markdown</option>
                                <option value="Text">Text</option>
                            </select>
                        </div>
                        <div id="providerStatusField" class="hidden">
                            <label for="provider_status" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_status') }}</label>
                            <select name="status" id="provider_status" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="active">{{ __('admin.ai_source_providers.status_active') }}</option>
                                <option value="inactive">{{ __('admin.ai_source_providers.status_inactive') }}</option>
                            </select>
                        </div>
                    </div>

                    <input type="hidden" name="search_type" value="web">
                    <input type="hidden" name="need_summary" value="0">
                    <input type="hidden" name="need_content" value="0">
                    <input type="hidden" name="need_url" value="0">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700">
                            <input type="checkbox" name="need_summary" id="provider_need_summary" value="1" checked class="rounded border-gray-300 text-[color:var(--qdk-primary)]">
                            {{ __('admin.ai_source_providers.field_need_summary') }}
                        </label>
                        <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700">
                            <input type="checkbox" name="need_content" id="provider_need_content" value="1" checked class="rounded border-gray-300 text-[color:var(--qdk-primary)]">
                            {{ __('admin.ai_source_providers.field_need_content') }}
                        </label>
                        <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700">
                            <input type="checkbox" name="need_url" id="provider_need_url" value="1" checked class="rounded border-gray-300 text-[color:var(--qdk-primary)]">
                            {{ __('admin.ai_source_providers.field_need_url') }}
                        </label>
                    </div>

                    <div>
                        <label for="provider_auth_info_level" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_auth_info_level') }}</label>
                        <input type="text" name="auth_info_level" id="provider_auth_info_level" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('admin.ai_source_providers.placeholder_auth_info_level') }}">
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label for="provider_sites" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_sites') }}</label>
                            <textarea name="sites" id="provider_sites" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('admin.ai_source_providers.placeholder_sites') }}"></textarea>
                        </div>
                        <div>
                            <label for="provider_block_hosts" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_source_providers.field_block_hosts') }}</label>
                            <textarea name="block_hosts" id="provider_block_hosts" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="{{ __('admin.ai_source_providers.placeholder_block_hosts') }}"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" onclick="closeProviderModal()" class="qdk-btn qdk-btn-ghost">
                            {{ __('admin.button.cancel') }}
                        </button>
                        <button type="submit" class="qdk-btn qdk-btn-primary">
                            {{ __('admin.button.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const SOURCE_PROVIDER_I18N = {
            modalCreate: @json(__('admin.ai_source_providers.modal_create')),
            modalEdit: @json(__('admin.ai_source_providers.modal_edit')),
            apiKeyPlaceholder: @json(__('admin.ai_source_providers.placeholder_api_key')),
            apiKeyPlaceholderKeep: @json(__('admin.ai_source_providers.placeholder_api_key_keep')),
            apiKeyHelpCreate: @json(__('admin.ai_source_providers.api_key_help_create')),
            apiKeyHelpEdit: @json(__('admin.ai_source_providers.api_key_help_edit')),
            confirmDelete: @json(__('admin.ai_source_providers.confirm_delete', ['name' => '__NAME__'])),
            test: @json(__('admin.ai_source_providers.test')),
            testing: @json(__('admin.ai_source_providers.testing')),
            testSuccessPrefix: @json(__('admin.ai_source_providers.test_success_prefix')),
            testFailedPrefix: @json(__('admin.ai_source_providers.test_failed_prefix')),
            testNetworkError: @json(__('admin.ai_source_providers.test_network_error')),
            selectModelFirst: @json(__('admin.ai_source_providers.select_model_first')),
            saveApiBeforeTest: @json(__('admin.ai_source_providers.save_api_before_test')),
        };
        const SOURCE_PROVIDER_ROUTES = {
            store: @json(route('admin.ai-source-providers.store')),
            update: @json(\App\Support\AdminWeb::routePath('admin.ai-source-providers.update', ['providerId' => '__PROVIDER_ID__'])),
            delete: @json(\App\Support\AdminWeb::routePath('admin.ai-source-providers.delete', ['providerId' => '__PROVIDER_ID__'])),
            test: @json(\App\Support\AdminWeb::routePath('admin.ai-source-providers.test', ['providerId' => '__PROVIDER_ID__'])),
            modelTest: @json(route('admin.ai-source-providers.model-bindings.test')),
        };
        const DEFAULT_DOUBAO_ENDPOINT = @json($defaultDoubaoEndpoint);

        function showCreateProviderModal() {
            document.getElementById('providerModalTitle').textContent = SOURCE_PROVIDER_I18N.modalCreate;
            document.getElementById('providerForm').action = SOURCE_PROVIDER_ROUTES.store;
            document.getElementById('providerFormMethod').value = 'POST';
            document.getElementById('providerForm').reset();
            document.getElementById('provider_endpoint_url').value = DEFAULT_DOUBAO_ENDPOINT;
            document.getElementById('provider_api_key').required = true;
            document.getElementById('provider_api_key').placeholder = SOURCE_PROVIDER_I18N.apiKeyPlaceholder;
            document.getElementById('providerApiKeyHelp').textContent = SOURCE_PROVIDER_I18N.apiKeyHelpCreate;
            document.getElementById('provider_count').value = 10;
            document.getElementById('provider_daily_limit').value = 0;
            document.getElementById('provider_need_summary').checked = true;
            document.getElementById('provider_need_content').checked = true;
            document.getElementById('provider_need_url').checked = true;
            document.getElementById('provider_content_formats').value = 'Markdown';
            document.getElementById('providerStatusField').classList.add('hidden');
            document.getElementById('providerModal').classList.remove('hidden');
        }

        function editProvider(provider) {
            const metadata = provider.metadata || {};
            document.getElementById('providerModalTitle').textContent = SOURCE_PROVIDER_I18N.modalEdit;
            document.getElementById('providerForm').action = SOURCE_PROVIDER_ROUTES.update.replace('__PROVIDER_ID__', String(provider.id));
            document.getElementById('providerFormMethod').value = 'PUT';
            document.getElementById('provider_name').value = provider.name || '';
            document.getElementById('provider_endpoint_url').value = provider.endpoint_url || DEFAULT_DOUBAO_ENDPOINT;
            document.getElementById('provider_api_key').value = '';
            document.getElementById('provider_api_key').required = false;
            document.getElementById('provider_api_key').placeholder = SOURCE_PROVIDER_I18N.apiKeyPlaceholderKeep;
            document.getElementById('providerApiKeyHelp').textContent = SOURCE_PROVIDER_I18N.apiKeyHelpEdit;
            document.getElementById('provider_daily_limit').value = provider.daily_limit || 0;
            document.getElementById('provider_count').value = metadata.count || 10;
            document.getElementById('provider_content_formats').value = metadata.content_formats || 'Markdown';
            document.getElementById('provider_need_summary').checked = Boolean(metadata.need_summary);
            document.getElementById('provider_need_content').checked = Boolean(metadata.need_content);
            document.getElementById('provider_need_url').checked = metadata.need_url !== false;
            document.getElementById('provider_auth_info_level').value = metadata.auth_info_level || '';
            document.getElementById('provider_sites').value = provider.sites_text || '';
            document.getElementById('provider_block_hosts').value = provider.block_hosts_text || '';
            document.getElementById('provider_status').value = provider.status || 'active';
            document.getElementById('providerStatusField').classList.remove('hidden');
            document.getElementById('providerModal').classList.remove('hidden');
        }

        function closeProviderModal() {
            document.getElementById('providerModal').classList.add('hidden');
        }

        function deleteProvider(id, name) {
            if (!confirm(SOURCE_PROVIDER_I18N.confirmDelete.replace('__NAME__', name))) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = SOURCE_PROVIDER_ROUTES.delete.replace('__PROVIDER_ID__', String(id));
            form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}">`;
            document.body.appendChild(form);
            form.submit();
        }

        async function testSourceProvider(id, button) {
            const resultEl = document.getElementById(`provider-test-result-${id}`);
            await postTestRequest(
                SOURCE_PROVIDER_ROUTES.test.replace('__PROVIDER_ID__', String(id)),
                {},
                resultEl,
                button,
            );
        }

        async function testConfiguredModelBinding(bindingType, inputId, resultId, button) {
            const modelId = Number(document.getElementById(inputId).value || 0);
            const resultEl = document.getElementById(resultId);
            if (modelId <= 0) {
                resultEl.textContent = SOURCE_PROVIDER_I18N.saveApiBeforeTest;
                resultEl.className = 'text-xs text-amber-700';
                return;
            }

            await postTestRequest(
                SOURCE_PROVIDER_ROUTES.modelTest,
                {binding_type: bindingType, model_id: modelId},
                resultEl,
                button,
            );
        }

        async function testModelBinding(bindingType, selectId, resultId, button) {
            const modelId = Number(document.getElementById(selectId).value || 0);
            const resultEl = document.getElementById(resultId);
            if (modelId <= 0) {
                resultEl.textContent = SOURCE_PROVIDER_I18N.selectModelFirst;
                resultEl.className = 'mt-2 text-xs text-amber-700';
                return;
            }

            await postTestRequest(
                SOURCE_PROVIDER_ROUTES.modelTest,
                {binding_type: bindingType, model_id: modelId},
                resultEl,
                button,
            );
        }

        async function postTestRequest(url, payload, resultEl, button) {
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = SOURCE_PROVIDER_I18N.testing;
            resultEl.textContent = '';
            resultEl.className = 'mt-2 text-xs text-gray-500';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify(payload),
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    const sourceCount = data.meta && typeof data.meta.source_count === 'number' ? ` (${data.meta.source_count})` : '';
                    const structured = data.meta && data.meta.structured_output ? ` ${JSON.stringify(data.meta.structured_output).slice(0, 180)}` : sourceCount;
                    resultEl.textContent = SOURCE_PROVIDER_I18N.testSuccessPrefix + data.message + structured;
                    resultEl.className = 'mt-2 break-all text-xs text-emerald-700';
                } else {
                    resultEl.textContent = SOURCE_PROVIDER_I18N.testFailedPrefix + (data.message || response.statusText);
                    resultEl.className = 'mt-2 break-all text-xs text-red-700';
                }
            } catch (error) {
                resultEl.textContent = SOURCE_PROVIDER_I18N.testNetworkError;
                resultEl.className = 'mt-2 break-all text-xs text-red-700';
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        }
    </script>
@endpush
