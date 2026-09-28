@extends('admin.layouts.app')

@section('content')
    <div class="px-4 sm:px-0">
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.title-libraries.index') }}" class="qdk-back">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <div>
                    <h1 class="qdk-page-title">{{ $library->name }}</h1>
                    <p class="qdk-page-sub">{{ __('admin.title_detail.subtitle') }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="showImportModal()" class="qdk-btn qdk-btn-ghost">
                    <i data-lucide="upload" class="w-4 h-4"></i>
                    {{ __('admin.title_detail.import_batch') }}
                </button>
                <button type="button" onclick="showAddModal()" class="qdk-btn qdk-btn-primary">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    {{ __('admin.title_detail.add_title') }}
                </button>
                <a href="{{ route('admin.title-libraries.ai-generate', ['libraryId' => (int) $library->id]) }}" class="qdk-btn qdk-btn-sm" style="background:#efeafe;color:#6d5efc;height:38px;">
                    <i data-lucide="zap" class="w-4 h-4"></i>
                    {{ __('admin.title_detail.ai_generate') }}
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
            <div class="qdk-stat">
                <div class="qdk-stat-icon"><i data-lucide="list" class="h-5 w-5"></i></div>
                <div>
                    <div class="qdk-stat-label">{{ __('admin.title_detail.total_titles') }}</div>
                    <div class="qdk-stat-value">{{ $titles->total() }}</div>
                </div>
            </div>
            <div class="qdk-stat">
                <div class="qdk-stat-icon"><i data-lucide="calendar" class="h-5 w-5"></i></div>
                <div>
                    <div class="qdk-stat-label">{{ __('admin.title_detail.created_date') }}</div>
                    <div class="qdk-stat-value">{{ optional($library->created_at)->format('Y-m-d') ?? '-' }}</div>
                </div>
            </div>
            <div class="qdk-stat">
                <div class="qdk-stat-icon"><i data-lucide="trending-up" class="h-5 w-5"></i></div>
                <div>
                    <div class="qdk-stat-label">{{ __('admin.title_detail.usage_total') }}</div>
                    <div class="qdk-stat-value">{{ $usageTotal }}</div>
                </div>
            </div>
        </div>

        <div class="qdk-card">
            <div class="qdk-card-head">
                <h3 class="qdk-card-title">{{ __('admin.title_detail.list_title') }}</h3>
            </div>

            @if ($titles->isEmpty())
                <div class="qdk-empty">
                    <i data-lucide="list" class="qdk-empty-icon"></i>
                    <h3 class="qdk-empty-title">{{ __('admin.title_detail.empty') }}</h3>
                    <p class="qdk-empty-sub mb-4">{{ __('admin.title_detail.empty_desc') }}</p>
                    <div class="flex justify-center gap-2">
                        <button type="button" onclick="showAddModal()" class="qdk-btn qdk-btn-primary">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            {{ __('admin.title_detail.add_title') }}
                        </button>
                        <button type="button" onclick="showImportModal()" class="qdk-btn qdk-btn-ghost">
                            <i data-lucide="upload" class="w-4 h-4"></i>
                            {{ __('admin.title_detail.import_batch') }}
                        </button>
                    </div>
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach ($titles as $title)
                        <div class="qdk-row px-5 py-4">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="text-sm font-semibold text-gray-900 break-all">{{ $title->title }}</h4>
                                        @if ((bool) $title->is_ai_generated)
                                            <span class="qdk-badge" style="background:#efeafe;color:#6d5efc;">
                                                <i data-lucide="zap" class="w-3 h-3"></i>
                                                {{ __('admin.title_detail.ai_badge') }}
                                            </span>
                                        @endif
                                        @if ((string) ($title->keyword ?? '') !== '')
                                            <span class="qdk-badge qdk-badge-gray">
                                                {{ $title->keyword }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="mt-1.5 flex items-center gap-4 text-xs text-gray-400">
                                        <span>{{ __('admin.title_detail.usage_count', ['count' => (int) ($title->used_count ?? 0)]) }}</span>
                                        <span>{{ __('admin.title_detail.created_at', ['value' => optional($title->created_at)->format('Y-m-d H:i') ?? '-']) }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <button type="button" onclick="deleteTitle({{ (int) $title->id }}, @js($title->title))" class="qdk-btn qdk-btn-danger qdk-btn-sm">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        {{ __('admin.button.delete') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($titles->lastPage() > 1)
                    <div class="px-5 py-4 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600">
                                {{ __('admin.title_detail.pagination', ['start' => $titles->firstItem(), 'end' => $titles->lastItem(), 'total' => $titles->total()]) }}
                            </div>
                            <div>
                                {{ $titles->links() }}
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('admin.title-libraries.titles.delete', ['libraryId' => (int) $library->id]) }}" id="delete-title-form" class="hidden">
        @csrf
        <input type="hidden" name="title_ids[]" id="delete-title-id" value="">
    </form>

    <div id="add-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('admin.title_detail.modal_add') }}</h3>
                <form method="POST" action="{{ route('admin.title-libraries.titles.store', ['libraryId' => (int) $library->id]) }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.title_detail.field_title') }}</label>
                            <input type="text" name="title" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" placeholder="{{ __('admin.title_detail.placeholder_title') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.title_detail.field_keyword') }}</label>
                            <input type="text" name="keyword" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" placeholder="{{ __('admin.title_detail.placeholder_keyword') }}">
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="hideAddModal()" class="qdk-btn qdk-btn-ghost">
                            {{ __('admin.button.cancel') }}
                        </button>
                        <button type="submit" class="qdk-btn qdk-btn-primary">
                            {{ __('admin.button.add') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="import-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-2/3 max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('admin.title_detail.modal_import') }}</h3>
                <form method="POST" action="{{ route('admin.title-libraries.import', ['libraryId' => (int) $library->id]) }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.title_detail.field_titles') }}</label>
                            <textarea name="titles_text" rows="10" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" placeholder="{{ __('admin.title_detail.placeholder_titles') }}"></textarea>
                        </div>
                        <div class="text-sm text-gray-500">
                            <p class="mb-2">{{ __('admin.title_detail.import_format_title') }}</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>{{ __('admin.title_detail.import_format_line') }}</li>
                                <li>{{ __('admin.title_detail.import_format_pipe') }}</li>
                                <li>{{ __('admin.title_detail.import_format_dedupe') }}</li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="hideImportModal()" class="qdk-btn qdk-btn-ghost">
                            {{ __('admin.button.cancel') }}
                        </button>
                        <button type="submit" class="qdk-btn qdk-btn-primary">
                            {{ __('admin.title_detail.import_button') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function showAddModal() {
            document.getElementById('add-modal').classList.remove('hidden');
        }

        function hideAddModal() {
            document.getElementById('add-modal').classList.add('hidden');
        }

        function showImportModal() {
            document.getElementById('import-modal').classList.remove('hidden');
        }

        function hideImportModal() {
            document.getElementById('import-modal').classList.add('hidden');
        }

        function deleteTitle(titleId, titleName) {
            const confirmed = confirm(@json(__('admin.title_detail.confirm_delete', ['name' => '{name}'])).replace('{name}', titleName));
            if (!confirmed) {
                return;
            }

            document.getElementById('delete-title-id').value = String(titleId);
            document.getElementById('delete-title-form').submit();
        }

        window.onclick = function (event) {
            const addModal = document.getElementById('add-modal');
            const importModal = document.getElementById('import-modal');

            if (event.target === addModal) {
                hideAddModal();
            }

            if (event.target === importModal) {
                hideImportModal();
            }
        };
    </script>
@endpush
