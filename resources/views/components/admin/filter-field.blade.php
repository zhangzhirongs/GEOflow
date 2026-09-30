@props([
    'label' => null,
    'width' => null,
])

{{-- 单个内联筛选字段：标签在左，控件在右 --}}
<div class="admin-filter-field">
    @if($label !== null)
        <label>{{ $label }}</label>
    @endif
    @if($width)
        <span class="admin-filter-field__control" style="width: {{ is_numeric($width) ? $width.'px' : $width }};">{{ $slot }}</span>
    @else
        {{ $slot }}
    @endif
</div>
