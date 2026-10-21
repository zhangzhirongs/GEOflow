@extends('admin.layouts.app')

@section('content')
    @php
        $knowledgeBases = (int) ($stats['knowledge_bases'] ?? 0);
        $knowledgeChunks = (int) ($stats['knowledge_chunks'] ?? 0);
        $vectorizedChunks = (int) ($stats['vectorized_chunks'] ?? 0);
        $vectorProgress = $knowledgeChunks > 0 ? min(100, (int) round(($vectorizedChunks / max(1, $knowledgeChunks)) * 100)) : 0;
        $foundationCards = [
            [
                'title' => '关键词',
                'icon' => 'key',
                'tone' => 'bg-blue-50 text-blue-600',
                'href' => route('admin.keyword-libraries.index'),
                'metrics' => [
                    '关键词数量' => __('admin.materials.unit_items', ['count' => (int) $stats['total_keywords']]),
                ],
            ],
            [
                'title' => '文章标题',
                'icon' => 'type',
                'tone' => 'bg-emerald-50 text-emerald-600',
                'href' => route('admin.title-libraries.index'),
                'metrics' => [
                    '标题数量' => __('admin.materials.unit_items', ['count' => (int) $stats['total_titles']]),
                ],
            ],
            [
                'title' => '图片',
                'icon' => 'image',
                'tone' => 'bg-purple-50 text-purple-600',
                'href' => route('admin.image-libraries.index'),
                'metrics' => [
                    '图片数量' => __('admin.materials.unit_images', ['count' => (int) $stats['total_images']]),
                ],
            ],
            [
                'title' => '作者',
                'icon' => 'users',
                'tone' => 'bg-indigo-50 text-indigo-600',
                'href' => route('admin.authors.index'),
                'metrics' => [
                    '作者数量' => __('admin.materials.author_count', ['count' => (int) $stats['authors']]),
                ],
            ],
        ];
    @endphp

    <div class="px-4 sm:px-0">
        <div class="mb-5">
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.materials.heading') }}</h1>
        </div>

        <section class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4 lg:px-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">知识库</h2>
                    </div>
                    <div class="flex flex-wrap gap-2">
                            <a href="{{ route('admin.knowledge-bases.create') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-700">
                                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                                {{ __('admin.materials.knowledge_hub_create') }}
                            </a>
                            <a href="{{ route('admin.knowledge-bases.index') }}" class="inline-flex items-center justify-center whitespace-nowrap rounded-md border border-orange-200 bg-white px-4 py-2 text-sm font-semibold text-orange-700 hover:bg-orange-50">
                                <i data-lucide="database" class="mr-2 h-4 w-4"></i>
                                {{ __('admin.materials.manage_knowledge_bases') }}
                            </a>
                    </div>
                </div>
            </div>

            <div class="px-6 py-6 lg:px-8">
                <div class="grid grid-cols-2 gap-x-8 gap-y-6 lg:grid-cols-3">
                    <div>
                        <div class="text-sm font-medium text-gray-500">{{ __('admin.materials.knowledge_base_count') }}</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ $knowledgeBases }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">资料片段</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">{{ $knowledgeChunks }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">已处理片段</div>
                        <div class="mt-2 text-3xl font-bold text-emerald-700">{{ $vectorizedChunks }}</div>
                    </div>
                </div>

                <div class="mt-7">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-gray-700">资料处理进度</span>
                        <span class="font-semibold text-gray-900">{{ $vectorizedChunks }} / {{ $knowledgeChunks }}</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-2 rounded-full bg-orange-500" style="width: {{ $vectorProgress }}%"></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-8">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-gray-900">常用素材</h2>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($foundationCards as $card)
                    <a href="{{ $card['href'] }}" class="block rounded-lg border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-md {{ $card['tone'] }}">
                                <i data-lucide="{{ $card['icon'] }}" class="h-6 w-6"></i>
                            </div>
                            <i data-lucide="arrow-up-right" class="h-5 w-5 text-gray-300"></i>
                        </div>
                        <h3 class="mt-4 text-base font-semibold text-gray-900">{{ $card['title'] }}</h3>
                        <div class="mt-3">
                            @foreach ($card['metrics'] as $label => $value)
                                <div class="flex items-center justify-between gap-4 text-sm">
                                    <span class="text-gray-500">{{ $label }}</span>
                                    <span class="shrink-0 font-semibold text-gray-900">{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        @if ($canManageProtectedWorkflows)
        <section class="mb-8 overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
            <div class="p-6 lg:p-8">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">从网页添加资料</h2>
                </div>

                <form method="POST" action="{{ route('admin.url-import.store') }}" class="mt-7">
                    @csrf
                    <label for="quick_url_import_url" class="sr-only">網頁地址</label>
                    <div class="flex flex-col gap-3 lg:flex-row">
                        <input
                            id="quick_url_import_url"
                            name="url"
                            type="text"
                            required
                            value="{{ old('url') }}"
                            placeholder="粘贴网页地址"
                            class="block min-h-11 w-full rounded-md border-gray-300 px-4 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                        @foreach (['knowledge', 'keywords', 'titles'] as $output)
                            <input type="hidden" name="outputs[]" value="{{ $output }}">
                        @endforeach
                        <button type="submit" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-md border border-transparent bg-blue-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                            <i data-lucide="globe" class="mr-2 h-4 w-4"></i>
                            开始采集
                        </button>
                    </div>
                    @error('url')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </form>

                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.url-import.history') }}" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-800">
                        <i data-lucide="history" class="mr-2 h-4 w-4"></i>
                        {{ __('admin.materials.url_import_history') }}
                    </a>
                </div>
            </div>
        </section>
        @endif
    </div>
@endsection
