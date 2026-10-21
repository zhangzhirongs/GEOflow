@props([
    'tone' => 'neutral',
    'label' => null,
    'dot' => true,
])

@php
    $toneMap = [
        'success' => 'qdk-badge-green',
        'approved' => 'qdk-badge-green',
        'published' => 'qdk-badge-green',
        'working' => 'qdk-badge-blue',
        'info' => 'qdk-badge-blue',
        'pending' => 'qdk-badge-amber',
        'warning' => 'qdk-badge-amber',
        'danger' => 'qdk-badge-red',
        'error' => 'qdk-badge-red',
        'violet' => 'qdk-badge-violet',
        'neutral' => 'qdk-badge-gray',
        'draft' => 'qdk-badge-gray',
    ];
    $toneClass = $toneMap[$tone] ?? 'qdk-badge-gray';
@endphp

{{-- 状态标签：浅底 + 深字 + 语义色点，禁止纯文字着色 --}}
<span {{ $attributes->merge(['class' => 'qdk-badge '.$toneClass.($dot ? '' : ' admin-tag--plain')]) }}>
    {{ $label ?? $slot }}
</span>
