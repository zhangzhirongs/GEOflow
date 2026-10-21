<header class="mb-5">
    <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-950">{{ $title }}</h1>
        </div>
        <button type="button" onclick="location.reload()" class="inline-flex min-h-10 w-fit items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 transition duration-[120ms] motion-reduce:transition-none hover:bg-gray-50 active:scale-[.98] motion-reduce:active:scale-100">
            <i data-lucide="refresh-cw" class="mr-2 h-4 w-4"></i>
            {{ __('admin.analytics.refresh') }}
        </button>
    </div>
    @include('admin.analytics._navigation')
</header>
