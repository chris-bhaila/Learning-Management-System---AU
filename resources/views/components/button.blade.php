@props([
    'variant' => 'primary',   // primary | primary-dark | secondary | danger | ghost
    'size'    => 'md',        // md | sm | icon
    'href'    => null,        // renders <a> when set, otherwise <button>
    'type'    => 'button',    // button | submit | reset
    'icon'    => null,        // material-symbols icon name (optional shorthand)
])

@php
$base = 'inline-flex items-center justify-center gap-2 cursor-pointer transition-all duration-150 focus:outline-none';

$sizes = [
    'md'   => 'px-5 py-2.5 text-sm rounded-[24px]',
    'sm'   => 'px-3 py-1.5 text-xs rounded-[24px]',
    'icon' => 'w-8 h-8 rounded-[12px]',
];

$variants = [
    'primary' => 'bg-gold text-primary font-semibold
                  hover:bg-gold/90 active:scale-[0.96]
                  disabled:opacity-60 disabled:cursor-not-allowed disabled:active:scale-100',

    'primary-dark' => 'bg-primary text-on-primary font-semibold
                       hover:opacity-90 active:scale-[0.99]
                       disabled:opacity-60 disabled:cursor-not-allowed',

    'secondary' => 'border border-outline-variant/60 text-on-surface-variant font-medium
                    hover:bg-surface-container-low hover:text-primary',

    'danger' => 'border border-error/40 text-error font-medium
                 hover:bg-error/5 active:scale-[0.96]',

    'ghost' => 'text-on-surface-variant font-medium
                hover:bg-surface-container hover:text-primary',
];

$iconSize = match($size) {
    'sm'   => 'text-[14px]',
    'icon' => 'text-[18px]',
    default => 'text-[18px]',
};

$cls = trim($base . ' ' . ($sizes[$size] ?? $sizes['md']) . ' ' . ($variants[$variant] ?? $variants['primary']));
@endphp

@if($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => $cls]) }}>
    @if($icon)
        <span class="material-symbols-outlined {{ $iconSize }}" aria-hidden="true">{{ $icon }}</span>
    @endif
    {{ $slot }}
</a>
@else
<button type="{{ $type }}" {{ $attributes->merge(['class' => $cls]) }}>
    @if($icon)
        <span class="material-symbols-outlined {{ $iconSize }}" aria-hidden="true">{{ $icon }}</span>
    @endif
    {{ $slot }}
</button>
@endif
