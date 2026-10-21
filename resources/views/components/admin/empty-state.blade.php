@props([
    'icon' => 'inbox',
    'title' => '',
    'description' => null,
])

{{-- 空状态：图标 + 标题 + 说明 + 可选操作，禁止留白不提示 --}}
<div {{ $attributes->merge(['class' => 'admin-empty']) }}>
    <div class="admin-empty__icon">
        <i data-lucide="{{ $icon }}" class="nav-icon"></i>
    </div>
    <h3 class="qdk-empty-title">{{ $title }}</h3>
    @if($description)
        <p class="qdk-empty-sub">{{ $description }}</p>
    @endif
    @isset($actions)
        <div class="mt-5 flex flex-wrap items-center justify-center gap-2">{{ $actions }}</div>
    @endisset
</div>
