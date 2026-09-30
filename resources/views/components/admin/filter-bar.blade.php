@props([
    'action' => null,
    'method' => 'GET',
    'resetUrl' => null,
    'searchLabel' => null,
    'resetLabel' => null,
    'showButtons' => true,
])

{{-- 紧凑筛选栏：左侧内联筛选表单 + 右侧操作按钮（参考 CSWG 任务列表筛选栏） --}}
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
        </form>
        @isset($actions)
            <div class="admin-filter-bar__actions">
                {{ $actions }}
            </div>
        @endisset
    </div>
</div>
