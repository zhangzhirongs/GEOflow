@extends('admin.layouts.app')

@section('content')
<div class="space-y-5 px-4 sm:px-0">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.manual_publications.settings.title') }}</h1>
        <a href="{{ route('admin.manual-publications.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>{{ __('admin.manual_publications.button.back') }}
        </a>
    </div>
    <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">{{ __('admin.manual_publications.settings.identity_auto_managed') }}</div>
    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <h2 class="border-b border-gray-200 px-5 py-4 text-base font-semibold text-gray-900">{{ __('admin.manual_publications.settings.new_account') }}</h2>
        <form method="POST" action="{{ route('admin.manual-publications.settings.accounts.store') }}" class="p-5" data-account-create-form>
            @csrf
            @include('admin.manual-publications._account-fields', ['account' => null])
            <div class="mt-4 flex justify-end">
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('admin.manual_publications.settings.save_account') }}</button>
            </div>
        </form>
    </section>
    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm" data-account-list>
        <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <h2 class="text-base font-semibold text-gray-900">{{ __('admin.manual_publications.settings.account_list') }}</h2>
            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">{{ $accounts->total() }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500">
                    <tr>
                        <th scope="col" class="px-5 py-3">{{ __('admin.manual_publications.field.account') }}</th>
                        <th scope="col" class="px-5 py-3">{{ __('admin.manual_publications.field.platform') }}</th>
                        <th scope="col" class="px-5 py-3">{{ __('georank.accounts.executor_node') }}</th>
                        <th scope="col" class="px-5 py-3">{{ __('georank.session_status') }}</th>
                        <th scope="col" class="px-5 py-3">{{ __('账号是否启用') }}</th>
                        <th scope="col" class="px-5 py-3 text-right">{{ __('是否自动发布') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($accounts as $account)
                        @php($editorOpen = (string) old('_account_form_id') === (string) $account->id)
                        <tr data-account-row="{{ $account->id }}">
                            <td class="px-5 py-4"><div class="font-medium text-gray-900">{{ $account->account_name }}</div><span class="text-xs text-gray-500">#{{ $account->id }}</span></td>
                            <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ __('admin.manual_publications.platform.'.$account->platform) }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $account->executorNode?->name ?? '?' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $account->sessionStatusLabel() }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span @class(['rounded-full px-2.5 py-1 text-xs font-medium', 'bg-emerald-50 text-emerald-700' => $account->is_active, 'bg-gray-100 text-gray-500' => ! $account->is_active])>{{ $account->is_active ? __('admin.lead_forms.status.active') : __('admin.lead_forms.status.inactive') }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-3">
                                    <button type="button" data-account-edit="{{ $account->id }}" aria-controls="account-editor-{{ $account->id }}" aria-expanded="{{ $editorOpen ? 'true' : 'false' }}" class="font-medium text-blue-600 hover:text-blue-800">{{ __('admin.button.edit') }}</button>
                                    <form method="POST" action="{{ route('admin.manual-publications.settings.accounts.destroy', ['accountId' => $account->id]) }}" onsubmit="return confirm(@js(__('admin.manual_publications.settings.confirm_account_delete')))">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">{{ __('admin.button.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr id="account-editor-{{ $account->id }}" @class(['hidden' => ! $editorOpen])>
                            <td colspan="6" class="bg-gray-50 p-5">
                                <form method="POST" action="{{ route('admin.manual-publications.settings.accounts.update', ['accountId' => $account->id]) }}" class="rounded-lg border border-gray-200 bg-white p-5" data-account-edit-form="{{ $account->id }}">
                                    @csrf @method('PUT')
                                    <h3 class="mb-4 font-semibold text-gray-900">{{ __('admin.button.edit') }} . {{ $account->account_name }}</h3>
                                    @include('admin.manual-publications._account-fields', ['account' => $account])
                                    <div class="mt-4 flex justify-end gap-3">
                                        <button type="button" data-account-edit="{{ $account->id }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('admin.button.cancel') }}</button>
                                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('admin.manual_publications.button.save') }}</button>
                                    </div>
                                </form>
                                @php($profile = is_array($account->publish_profile_json) ? $account->publish_profile_json : [])
                                @include('admin.manual-publications._account-session-actions')
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">{{ __('admin.manual_publications.settings.accounts_empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-5 py-4">{{ $accounts->onEachSide(1)->links('pagination.admin') }}</div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-account-edit]').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.dataset.accountEdit;
                const row = document.getElementById(`account-editor-${id}`);
                const open = row.classList.contains('hidden');
                row.classList.toggle('hidden', !open);
                document.querySelectorAll(`[data-account-edit="${id}"][aria-controls]`).forEach(toggle => {
                    toggle.setAttribute('aria-expanded', String(open));
                });
                if (open) row.querySelector('select, input:not([type="hidden"])')?.focus();
            });
        });
    });
</script>
@endpush
