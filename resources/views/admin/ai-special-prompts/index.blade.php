@extends('admin.layouts.app')

@section('content')
    <div class="px-4 sm:px-0">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="qdk-page-title">{{ __('admin.ai_special.heading') }}</h1>
                <p class="qdk-page-sub">{{ __('admin.ai_special.subtitle') }}</p>
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
                            <p class="qdk-card-sub">{{ __('admin.ai_special.keyword_subtitle') }}</p>
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
                            <p class="mt-2 text-sm text-gray-500">{{ __('admin.ai_special.keyword_help') }}</p>
                            <p class="mt-1 text-xs text-gray-500">{!! __('admin.ai_special.variable_help') !!}</p>
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
                            <p class="qdk-card-sub">{{ __('admin.ai_special.description_subtitle') }}</p>
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
                            <p class="mt-2 text-sm text-gray-500">{{ __('admin.ai_special.description_help') }}</p>
                            <p class="mt-1 text-xs text-gray-500">{!! __('admin.ai_special.variable_help') !!}</p>
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

        <div class="mt-8 rounded-lg border border-[#d6e2ff] bg-[color:var(--qdk-primary-weak)] p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i data-lucide="info" class="h-5 w-5 text-[color:var(--qdk-primary)]"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-[color:var(--qdk-primary)]">{{ __('admin.ai_special.help_title') }}</h3>
                    <div class="mt-2 text-sm text-[#3f5fb0]">
                        <ul class="list-disc list-inside space-y-1">
                            <li>{{ __('admin.ai_special.help_keyword') }}</li>
                            <li>{{ __('admin.ai_special.help_description') }}</li>
                            <li>{{ __('admin.ai_special.help_variables') }}</li>
                            <li>{{ __('admin.ai_special.help_auto_apply') }}</li>
                        </ul>
                    </div>
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
