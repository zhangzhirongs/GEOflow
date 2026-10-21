@props([
    'label' => '',
    'value' => null,
    'meta' => null,
    'icon' => null,
    'href' => null,
    'tone' => 'default',
])

@php
    $toneMap = [
        'default' => 'bg-blue-50 text-blue-600',
        'blue' => 'bg-blue-50 text-blue-600',
        'success' => 'bg-emerald-50 text-emerald-600',
        'warning' => 'bg-amber-50 text-amber-600',
        'danger' => 'bg-red-50 text-red-600',
        'neutral' => 'bg-gray-100 text-gray-500',
    ];
    $iconClass = $toneMap[$tone] ?? $toneMap['default'];
@endphp

{{-- 指标卡：13px 标签 + 28px 等宽数字 + 12px 辅助信息，全站统一 --}}
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'admin-metric']) }}>
@else
    <div {{ $attributes->merge(['class' => 'admin-metric']) }}>
@endif
    <div class="admin-metric__head">
        <span class="truncate">{{ $label }}</span>
        @if($icon)
            <span class="admin-metric__icon {{ $iconClass }}">
                <i data-lucide="{{ $icon }}" class="nav-icon"></i>
            </span>
        @endif
    </div>
    <div class="admin-metric__value">{{ is_numeric($value) ? number_format((int) $value) : $value }}</div>
    @if($meta)
        <div class="admin-metric__meta truncate">{{ $meta }}</div>
    @endif
@if($href)
    </a>
@else
    </div>
@endif
