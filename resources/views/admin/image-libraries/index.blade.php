@extends('admin.layouts.app')

@php
    $formatSize = static function (int $bytes): string {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    };
@endphp

@section('content')
    <div class="px-4 sm:px-0">
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.materials.index') }}" class="qdk-back">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <div>
                    <h1 class="qdk-page-title">{{ __('admin.image_libraries.heading') }}</h1>
                    <p class="qdk-page-sub">{{ __('admin.image_libraries.subtitle') }}</p>
                </div>
            </div>
            <button type="button" onclick="showCreateModal()" class="qdk-btn qdk-btn-primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                {{ __('admin.image_libraries.create') }}
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-8">
            <div class="qdk-stat">
                <div class="qdk-stat-icon"><i data-lucide="folder" class="h-5 w-5"></i></div>
                <div>
                    <div class="qdk-stat-label">{{ __('admin.image_libraries.total') }}</div>
                    <div class="qdk-stat-value">{{ (int) ($stats['total_libraries'] ?? 0) }}</div>
                </div>
            </div>
            <div class="qdk-stat">
                <div class="qdk-stat-icon"><i data-lucide="image" class="h-5 w-5"></i></div>
                <div>
                    <div class="qdk-stat-label">{{ __('admin.image_libraries.total_images') }}</div>
                    <div class="qdk-stat-value">{{ (int) ($stats['total_images'] ?? 0) }}</div>
                </div>
            </div>
            <div class="qdk-stat">
                <div class="qdk-stat-icon"><i data-lucide="hard-drive" class="h-5 w-5"></i></div>
                <div>
                    <div class="qdk-stat-label">{{ __('admin.image_libraries.storage') }}</div>
                    <div class="qdk-stat-value">{{ $formatSize((int) ($stats['total_size'] ?? 0)) }}</div>
                </div>
            </div>
            <div class="qdk-stat">
                <div class="qdk-stat-icon"><i data-lucide="trending-up" class="h-5 w-5"></i></div>
                <div>
                    <div class="qdk-stat-label">{{ __('admin.common.avg_per_library') }}</div>
                    <div class="qdk-stat-value">{{ (float) ($stats['avg_images'] ?? 0) }}</div>
                </div>
            </div>
        </div>

        <div class="qdk-card">
            <div class="qdk-card-head">
                <h3 class="qdk-card-title">{{ __('admin.image_libraries.list_title') }}</h3>
            </div>

            @if (empty($libraries))
                <div class="qdk-empty">
                    <i data-lucide="folder-plus" class="qdk-empty-icon"></i>
                    <h3 class="qdk-empty-title">{{ __('admin.image_libraries.empty') }}</h3>
                    <p class="qdk-empty-sub mb-4">{{ __('admin.image_libraries.empty_desc') }}</p>
                    <button type="button" onclick="showCreateModal()" class="qdk-btn qdk-btn-primary">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        {{ __('admin.image_libraries.create') }}
                    </button>
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach ($libraries as $library)
                        <div class="qdk-row px-5 py-4">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="text-sm font-semibold">
                                            <a href="{{ route('admin.image-libraries.detail', ['libraryId' => (int) $library['id']]) }}" class="qdk-link">
                                                {{ $library['name'] }}
                                            </a>
                                        </h4>
                                        <span class="qdk-badge">
                                            {{ __('admin.image_libraries.image_count', ['count' => (int) $library['actual_count']]) }}
                                        </span>
                                        @if ((int) ($library['total_size'] ?? 0) > 0)
                                            <span class="qdk-badge qdk-badge-gray">
                                                {{ $formatSize((int) $library['total_size']) }}
                                            </span>
                                        @endif
                                    </div>
                                    @if ($library['description'] !== '')
                                        <p class="mt-1 text-sm text-gray-500 truncate">{{ $library['description'] }}</p>
                                    @endif
                                    <div class="mt-1.5 flex items-center gap-4 text-xs text-gray-400">
                                        <span>{{ __('admin.image_libraries.created_at', ['value' => $library['created_at'] ? \Illuminate\Support\Carbon::parse($library['created_at'])->format('Y-m-d H:i') : '-']) }}</span>
                                        <span>{{ __('admin.image_libraries.updated_at', ['value' => $library['updated_at'] ? \Illuminate\Support\Carbon::parse($library['updated_at'])->format('Y-m-d H:i') : '-']) }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <a href="{{ route('admin.image-libraries.detail', ['libraryId' => (int) $library['id']]) }}" class="qdk-btn qdk-btn-primary qdk-btn-sm">
                                        <i data-lucide="upload" class="w-4 h-4"></i>
                                        {{ __('admin.image_libraries.upload_images') }}
                                    </a>
                                    <a href="{{ route('admin.image-libraries.detail', ['libraryId' => (int) $library['id']]) }}" class="qdk-btn qdk-btn-ghost qdk-btn-sm">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                        {{ __('admin.button.view') }}
                                    </a>
                                    <form method="POST" action="{{ route('admin.image-libraries.delete', ['libraryId' => (int) $library['id']]) }}" onsubmit="return confirm(@js(__('admin.image_libraries.confirm_delete', ['name' => $library['name']])));" class="inline-block">
                                        @csrf
                                        <button type="submit" class="qdk-btn qdk-btn-danger qdk-btn-sm">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            {{ __('admin.button.delete') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div id="create-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('admin.image_libraries.modal_create') }}</h3>
                <form method="POST" action="{{ route('admin.image-libraries.store') }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.image_libraries.field_name') }}</label>
                            <input type="text" name="name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" placeholder="{{ __('admin.image_libraries.placeholder_name') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('admin.image_libraries.field_description') }}</label>
                            <textarea name="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" placeholder="{{ __('admin.image_libraries.placeholder_description') }}"></textarea>
                        </div>
                        <div class="text-sm text-gray-500">
                            <p class="mb-2">{{ __('admin.image_libraries.supported_formats') }}</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>JPEG/JPG</li>
                                <li>PNG</li>
                                <li>GIF</li>
                                <li>WebP</li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="hideCreateModal()" class="qdk-btn qdk-btn-ghost">
                            {{ __('admin.button.cancel') }}
                        </button>
                        <button type="submit" class="qdk-btn qdk-btn-primary">
                            {{ __('admin.button.create') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function showCreateModal() {
            document.getElementById('create-modal').classList.remove('hidden');
        }

        function hideCreateModal() {
            document.getElementById('create-modal').classList.add('hidden');
        }

        window.onclick = function (event) {
            const createModal = document.getElementById('create-modal');
            if (event.target === createModal) {
                hideCreateModal();
            }
        };
    </script>
@endpush
