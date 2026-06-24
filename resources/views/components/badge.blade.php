@props([
    'variant' => 'default',  // published | draft | active | expired | admin | teacher | student | default
    'dot'     => false,      // show coloured dot indicator
    'icon'    => null,       // material-symbols icon name (optional)
])

@php
$variantCls = match($variant) {
    'published', 'active'  => 'bg-gold/20 text-primary',
    'draft', 'expired'     => 'bg-surface-container text-on-surface-variant',
    'admin'                => 'bg-primary-container text-on-primary',
    'teacher'              => 'bg-gold/20 text-on-gold',
    'student'              => 'bg-surface-container text-on-surface-variant',
    default                => 'bg-surface-container text-on-surface-variant',
};

$dotCls = match($variant) {
    'published', 'active'  => 'bg-gold',
    default                => 'bg-outline-variant',
};

$cls = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium ' . $variantCls;
@endphp

<span {{ $attributes->merge(['class' => $cls]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotCls }}" aria-hidden="true"></span>
    @endif
    @if($icon)
        <span class="material-symbols-outlined text-[12px]" aria-hidden="true">{{ $icon }}</span>
    @endif
    {{ $slot }}
</span>
