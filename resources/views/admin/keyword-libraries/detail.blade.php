@extends('admin.layouts.app')

@section('content')
    <div class="px-4 sm:px-0">
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.keyword-libraries.index') }}" class="qdk-back">
                        <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    </a>
                    <div>
                        <h1 class="qdk-page-title">{{ $library->name }}</h1>
                        <p class="qdk-page-sub">{{ $library->description !== '' ? $library->description : __('admin.keyword_detail.no_description') }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="showEditModal()" class="qdk-btn qdk-btn-ghost qdk-btn-sm">
                        <i data-lucide="edit" class="w-4 h-4"></i>
                        {{ __('admin.keyword_detail.edit_info') }}
                    </button>
                    <button type="button" onclick="showAddModal()" class="qdk-btn qdk-btn-primary">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        {{ __('admin.keyword_detail.add_keyword') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">
            <div class="qdk-stat">
                <div class="qdk-stat-icon"><i data-lucide="key" class="h-5 w-5"></i></div>
                <div>
                    <div class="qdk-stat-label">{{ __('admin.keyword_detail.total_keywords') }}</div>
                    <div class="qdk-stat-value">{{ $keywords->total() }}</div>
                </div>
            </div>
            <div class="qdk-stat">
                <div class="qdk-stat-icon"><i data-lucide="trending-up" class="h-5 w-5"></i></div>
                <div>
                    <div class="qdk-stat-label">{{ __('admin.keyword_detail.usage_total') }}</div>
                    <div class="qdk-stat-value">{{ $usageTotal }}</div>
                </div>
            </div>
            <div class="qdk-stat">
                <div class="qdk-stat-icon"><i data-lucide="calendar" class="h-5 w-5"></i></div>
                <div>
                    <div class="qdk-stat-label">{{ __('admin.keyword_detail.created_date') }}</div>
                    <div class="qdk-stat-value">{{ optional($library->created_at)->format('m-d') ?? '-' }}</div>
                </div>
            </div>
            <div class="qdk-stat">
                <div class="qdk-stat-icon"><i data-lucide="clock" class="h-5 w-5"></i></div>
                <div>
                    <div class="qdk-stat-label">{{ __('admin.keyword_detail.updated_date') }}</div>
                    <div class="qdk-stat-value">{{ optional($library->updated_at)->format('m-d') ?? '-' }}</div>
                </div>
            </div>
        </div>

        <div class="qdk-card mb-6">
            <div class="px-5 py-4">
                <div class="flex items-center justify-between gap-4">
                    <form method="GET" class="flex items-center gap-3 flex-1">
                        <div class="flex-1 max-w-md">
                            <input type="text" name="search" value="{{ $search }}"
                                placeholder="{{ __('admin.keyword_detail.search_placeholder') }}"
                                class="block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <button type="submit" class="qdk-btn qdk-btn-primary">
                            <i data-lucide="search" class="w-4 h-4"></i>
                            {{ __('admin.button.search') }}
                        </button>
                        <a href="{{ route('admin.keyword-libraries.detail', ['libraryId' => (int) $library->id]) }}" class="qdk-btn qdk-btn-ghost">
                            <i data-lucide="x" class="w-4 h-4"></i>
                            {{ __('admin.button.clear') }}
                        </a>
                    </form>
                    <button type="button" onclick="toggleBatchActions()" class="qdk-btn qdk-btn-ghost qdk-btn-sm">
                        <i data-lucide="check-square" class="w-4 h-4"></i>
                        {{ __('admin.keyword_detail.batch_actions') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="qdk-card">
            <div class="qdk-card-head">
                <h3 class="qdk-card-title">
                    {{ __('admin.keyword_detail.list_title') }}
                    <span class="qdk-card-sub">{{ __('admin.keyword_detail.list_total', ['count' => $keywords->total()]) }}</span>
                </h3>
            </div>

            @if ($keywords->isEmpty())
                <div class="qdk-empty">
                    <i data-lucide="search" class="qdk-empty-icon"></i>
                    <h3 class="qdk-empty-title">{{ __('admin.keyword_detail.empty') }}</h3>
                    <p class="qdk-empty-sub mb-4">{{ $search !== '' ? __('admin.keyword_detail.empty_search') : __('admin.keyword_detail.empty_desc') }}</p>
                    @if ($search === '')
                        <button type="button" onclick="showAddModal()" class="qdk-btn qdk-btn-primary">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            {{ __('admin.keyword_detail.add_keyword') }}
                        </button>
                    @endif
                </div>
            @else
                <div id="batch-actions" class="hidden px-5 py-3 bg-[#fafbfc] border-b border-gray-100">
                    <form method="POST" action="{{ route('admin.keyword-libraries.keywords.delete', ['libraryId' => (int) $library->id]) }}" id="batch-form">
                        @csrf
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-600" id="selected-keyword-count">{{ __('admin.keyword_detail.selected_count', ['count' => 0]) }}</span>
                            <button type="submit" class="qdk-btn qdk-btn-danger qdk-btn-sm">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                {{ __('admin.keyword_detail.delete_selected') }}
                            </button>
                            <button type="button" onclick="toggleBatchActions()" class="qdk-btn qdk-btn-ghost qdk-btn-sm">
                                {{ __('admin.button.cancel') }}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="px-5 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                        @foreach ($keywords as $keyword)
                            <div class="group flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-[#fafbfc]">
                                <div class="flex items-center space-x-2 min-w-0">
                                    <input type="checkbox" form="batch-form" name="keyword_ids[]" value="{{ (int) $keyword->id }}" class="keyword-checkbox hidden rounded border-gray-300 text-[color:var(--qdk-primary)] shadow-sm">
                                    <span class="text-sm text-gray-900 break-all">{{ $keyword->keyword }}</span>
                                </div>
                                <button type="button" onclick="deleteKeyword({{ (int) $keyword->id }}, @js($keyword->keyword))" class="text-red-600 hover:text-red-800 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if ($keywords->lastPage() > 1)
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                {{ __('admin.keyword_detail.pagination', ['start' => $keywords->firstItem(), 'end' => $keywords->lastItem(), 'total' => $keywords->total()]) }}
                            </div>
                            <div>
                                {{ $keywords->links() }}
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('admin.keyword-libraries.keywords.delete', ['libraryId' => (int) $library->id]) }}" id="single-delete-form" class="hidden">
        @csrf
        <input type="hidden" name="keyword_ids[]" id="single-delete-keyword-id" value="">
    </form>

    <div id="add-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('admin.keyword_detail.modal_add') }}</h3>
                <form method="POST" action="{{ route('admin.keyword-libraries.keywords.store', ['libraryId' => (int) $library->id]) }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.keyword_detail.field_keyword') }}</label>
                            <input type="text" name="keyword" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" placeholder="{{ __('admin.keyword_detail.placeholder_keyword') }}">
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

    <div id="edit-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('admin.keyword_detail.modal_edit') }}</h3>
                <form method="POST" action="{{ route('admin.keyword-libraries.detail.update', ['libraryId' => (int) $library->id]) }}">
                    @csrf
                    @method('PUT')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.keyword_detail.field_name') }}</label>
                            <input type="text" name="name" required value="{{ old('name', (string) $library->name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.keyword_detail.field_description') }}</label>
                            <textarea name="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">{{ old('description', (string) ($library->description ?? '')) }}</textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-between space-x-3">
                        <button type="button" onclick="showImportModal()" class="qdk-btn qdk-btn-sm" style="background:var(--qdk-primary-weak);color:var(--qdk-primary);">
                            {{ __('admin.button.import') }}
                        </button>
                        <div class="space-x-3">
                            <button type="button" onclick="hideEditModal()" class="qdk-btn qdk-btn-ghost">
                                {{ __('admin.button.cancel') }}
                            </button>
                            <button type="submit" class="qdk-btn qdk-btn-primary">
                                {{ __('admin.button.save') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="import-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-2/3 max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('admin.keyword_libraries.modal_import') }} <span class="text-[color:var(--qdk-primary)]">{{ $library->name }}</span></h3>
                <form method="POST" action="{{ route('admin.keyword-libraries.import', ['libraryId' => (int) $library->id]) }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.keyword_libraries.field_keywords') }}</label>
                            <textarea name="keywords_text" rows="10" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" placeholder="{{ __('admin.keyword_libraries.placeholder_keywords') }}"></textarea>
                        </div>
                        <div class="text-sm text-gray-500">
                            <p class="mb-2">{{ __('admin.keyword_libraries.format_title') }}</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>{{ __('admin.keyword_libraries.format_line') }}</li>
                                <li>{{ __('admin.keyword_libraries.format_comma') }}</li>
                                <li>{{ __('admin.keyword_libraries.format_dedupe') }}</li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="hideImportModal()" class="qdk-btn qdk-btn-ghost">
                            {{ __('admin.button.cancel') }}
                        </button>
                        <button type="submit" class="qdk-btn qdk-btn-primary">
                            {{ __('admin.keyword_libraries.import_button') }}
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

        function showEditModal() {
            document.getElementById('edit-modal').classList.remove('hidden');
        }

        function hideEditModal() {
            document.getElementById('edit-modal').classList.add('hidden');
        }

        function showImportModal() {
            document.getElementById('import-modal').classList.remove('hidden');
        }

        function hideImportModal() {
            document.getElementById('import-modal').classList.add('hidden');
        }

        function toggleBatchActions() {
            const batchActions = document.getElementById('batch-actions');
            const checkboxes = document.querySelectorAll('.keyword-checkbox');
            const isHidden = batchActions.classList.contains('hidden');

            if (isHidden) {
                batchActions.classList.remove('hidden');
                checkboxes.forEach((checkbox) => checkbox.classList.remove('hidden'));
            } else {
                batchActions.classList.add('hidden');
                checkboxes.forEach((checkbox) => {
                    checkbox.classList.add('hidden');
                    checkbox.checked = false;
                });
                updateSelectedCount();
            }
        }

        function updateSelectedCount() {
            const selected = document.querySelectorAll('.keyword-checkbox:checked').length;
            const text = @json(__('admin.keyword_detail.selected_count', ['count' => '{count}'])).replace('{count}', String(selected));
            const counter = document.getElementById('selected-keyword-count');
            if (counter) {
                counter.textContent = text;
            }
        }

        function deleteKeyword(keywordId, keywordName) {
            const confirmed = confirm(@json(__('admin.keyword_detail.confirm_delete_keyword', ['name' => '{name}'])).replace('{name}', keywordName));
            if (!confirmed) {
                return;
            }

            document.getElementById('single-delete-keyword-id').value = String(keywordId);
            document.getElementById('single-delete-form').submit();
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.keyword-checkbox').forEach((checkbox) => {
                checkbox.addEventListener('change', updateSelectedCount);
            });

            const batchForm = document.getElementById('batch-form');
            if (batchForm) {
                batchForm.addEventListener('submit', function (event) {
                    const selected = document.querySelectorAll('.keyword-checkbox:checked').length;
                    if (selected <= 0) {
                        event.preventDefault();
                        alert(@json(__('admin.keyword_detail.error.select_required')));
                        return;
                    }

                    const confirmed = confirm(@json(__('admin.keyword_detail.confirm_delete_selected', ['count' => '{count}'])).replace('{count}', String(selected)));
                    if (!confirmed) {
                        event.preventDefault();
                    }
                });
            }
        });

        window.onclick = function (event) {
            const addModal = document.getElementById('add-modal');
            const editModal = document.getElementById('edit-modal');
            const importModal = document.getElementById('import-modal');

            if (event.target === addModal) {
                hideAddModal();
            }
            if (event.target === editModal) {
                hideEditModal();
            }
            if (event.target === importModal) {
                hideImportModal();
            }
        };
    </script>
@endpush
