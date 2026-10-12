@props([
    'action' => null,
    'method' => 'GET',
    'resetUrl' => null,
    'searchLabel' => null,
    'resetLabel' => null,
    'moreLabel' => null,
    'showButtons' => true,
])

@php($hasAdvanced = isset($advanced))
@php($advancedId = 'admin-filter-advanced-'.\Illuminate\Support\Str::random(6))

{{-- 紧凑筛选栏：左侧内联筛选表单 + 右侧操作按钮；筛选项过多时可折叠进「更多筛选」 --}}
<div {{ $attributes->merge(['class' => 'admin-filter-bar']) }}>
    <div class="admin-filter-bar__row">
        <form method="{{ strtoupper($method) === 'GET' ? 'GET' : 'POST' }}"
              @if($action) action="{{ $action }}" @endif
              class="admin-filter-bar__form">
            @if(strtoupper($method) !== 'GET')
                @csrf
            @endif
            {{ $hidden ?? '' }}
            {{ $slot }}
            @if($hasAdvanced)
                <button type="button" class="admin-filter-btn admin-filter-btn--more"
                        data-filter-more-toggle aria-controls="{{ $advancedId }}" aria-expanded="false">
                    <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                    {{ $moreLabel ?? __('admin.button.more_filters') }}
                    <i data-lucide="chevron-down" class="w-4 h-4 admin-filter-more-caret"></i>
                </button>
            @endif
            @if($showButtons)
                <div class="admin-filter-actions">
                    <button type="submit" class="admin-filter-btn admin-filter-btn--primary">
                        <i data-lucide="search" class="w-4 h-4"></i>
                        {{ $searchLabel ?? __('admin.button.search') }}
                    </button>
                    @if($resetUrl)
                        <a href="{{ $resetUrl }}" class="admin-filter-btn admin-filter-btn--ghost">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                            {{ $resetLabel ?? __('admin.button.clear') }}
                        </a>
                    @endif
                </div>
            @endif
            @if($hasAdvanced)
                <div id="{{ $advancedId }}" class="admin-filter-advanced" data-filter-advanced>
                    {{ $advanced }}
                </div>
            @endif
        </form>
        @isset($actions)
            <div class="admin-filter-bar__actions">
                {{ $actions }}
            </div>
        @endisset
    </div>
</div>
