@props([
    'title' => null,
    'subtitle' => null,
    'eyebrow' => null,
    'flush' => false,
])

{{-- 卡片容器：白底 + 1px 描边 + 8px 圆角，标题 16px/600 --}}
<section {{ $attributes->merge(['class' => 'admin-card']) }}>
    @if($title || isset($actions))
        <div class="admin-card__head">
            <div class="min-w-0">
                @if($eyebrow)
                    <p class="mb-1 text-[12px] font-semibold uppercase tracking-[0.14em] text-blue-600">{{ $eyebrow }}</p>
                @endif
                @if($title)
                    <h2 class="qdk-card-title">{{ $title }}</h2>
                @endif
                @if($subtitle)
                    <p class="qdk-card-sub">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    @if($flush)
        {{ $slot }}
    @else
        <div class="admin-card__body">{{ $slot }}</div>
    @endif

    @isset($footer)
        <div class="admin-card__foot">{{ $footer }}</div>
    @endisset
</section>
