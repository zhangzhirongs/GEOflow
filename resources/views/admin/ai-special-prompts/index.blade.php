@extends('admin.layouts.app')

@section('content')
    <div class="px-4 sm:px-0">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="qdk-page-title">{{ __('admin.ai_special.heading') }}</h1>
            </div>
        </div>

        <div class="space-y-6">
            <div class="qdk-card">
                <div class="qdk-card-head">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:var(--qdk-primary-weak);color:var(--qdk-primary);">
                                <i data-lucide="key" class="w-5 h-5"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="qdk-card-title">{{ __('admin.ai_special.keyword_title') }}</h3>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-6">
                    <form method="POST" action="{{ route('admin.ai-special-prompts.keyword') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="keyword_content" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_special.keyword_field') }}</label>
                            <textarea name="keyword_content" id="keyword_content" rows="8" required
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"
                                      placeholder="{{ __('admin.ai_special.keyword_placeholder') }}">{{ $keywordPromptContent }}</textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="qdk-btn qdk-btn-primary">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                {{ __('admin.ai_special.keyword_save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="qdk-card">
                <div class="qdk-card-head">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:#efeafe;color:#6d5efc;">
                                <i data-lucide="file-text" class="w-5 h-5"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="qdk-card-title">{{ __('admin.ai_special.description_title') }}</h3>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-6">
                    <form method="POST" action="{{ route('admin.ai-special-prompts.description') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="description_content" class="block text-sm font-medium text-gray-700">{{ __('admin.ai_special.description_field') }}</label>
                            <textarea name="description_content" id="description_content" rows="8" required
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"
                                      placeholder="{{ __('admin.ai_special.description_placeholder') }}">{{ $descriptionPromptContent }}</textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="qdk-btn qdk-btn-primary">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                {{ __('admin.ai_special.description_save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
@endpush
