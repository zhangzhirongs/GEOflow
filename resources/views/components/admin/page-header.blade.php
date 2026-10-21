@props([
    'title' => '',
    'subtitle' => null,
    'backUrl' => null,
    'backLabel' => null,
])

{{-- 页面头：30px 主标题 + 灰色副标题 + 右侧操作区，全站统一 --}}
<div {{ $attributes->merge(['class' => 'admin-page-head']) }}>
    <div class="admin-page-head__copy">
        @if($backUrl)
            <div class="mb-3 flex items-center gap-3">
                <a href="{{ $backUrl }}" class="qdk-back" title="{{ $backLabel ?? __('admin.button.back') }}" aria-label="{{ $backLabel ?? __('admin.button.back') }}">
                    <i data-lucide="arrow-left" class="nav-icon"></i>
                </a>
                @isset($meta)
                    <span class="text-[13px] text-gray-500">{{ $meta }}</span>
                @endisset
            </div>
        @endif
        <h1 class="admin-page-title">{{ $title }}</h1>
        @if($subtitle)
            <p class="admin-page-sub">{{ $subtitle }}</p>
        @endif
        @isset($below)
            <div class="mt-3">{{ $below }}</div>
        @endisset
    </div>
    @isset($actions)
        <div class="admin-page-head__actions">{{ $actions }}</div>
    @endisset
</div>
