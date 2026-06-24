{{--
    x-card — consistent content-card container.
    Base: white bg, border, 20px radius, soft shadow.
    All additional classes (p-6, overflow-hidden, animate-fade-up, etc.) passed via class attribute.
    Shadow can be overridden by adding a shadow-[...] class — caller's class wins.
--}}
<div {{ $attributes->merge(['class' => 'bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)]']) }}>
    {{ $slot }}
</div>
