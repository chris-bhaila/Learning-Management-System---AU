@props(['hiddenInputId', 'minHeightClass' => 'min-h-[200px]'])

{{-- Reusable per-session WYSIWYG ⇄ raw HTML toggle for TipTap editors.
     Wraps the toolbar + #tiptap-editor markup passed as the slot.
     Always starts in WYSIWYG mode; never persists raw mode across reloads. --}}
<div x-data="rawHtmlToggle(@js($hiddenInputId))" class="relative"
     @reset-raw-html.window="raw = false; error = ''">

    {{-- Sliding toggle inset top-right — squircle thumb carries the icon and
         slides from left (WYSIWYG) to right (raw HTML) inside a pill track. --}}
    <button type="button"
            @click="toggle()"
            :class="raw ? 'bg-gold border-gold/80' : 'bg-surface-container-low border-outline-variant/40'"
            class="absolute top-2 right-2 z-20 inline-flex items-center w-14 h-8 p-1
                   rounded-md border transition-colors duration-200 cursor-pointer
                   focus:outline-none"
            role="switch" :aria-checked="raw.toString()"
            title="Edit raw HTML"
            aria-label="Edit raw HTML">
        <span :class="raw ? 'translate-x-6 bg-surface-white text-primary' : 'translate-x-0 bg-surface-white text-on-surface-variant border border-outline-variant/60'"
              class="w-6 h-6 flex items-center justify-center rounded-md shadow-sm
                     transition-all duration-200">
            <span class="font-mono text-[10px] font-bold leading-none" aria-hidden="true">&lt;/&gt;</span>
        </span>
    </button>

    <div x-show="!raw">
        {{ $slot }}
    </div>

    <div x-show="raw" x-cloak>
        <textarea
            x-ref="rawTextarea"
            @input="onInput($event.target.value)"
            spellcheck="false"
            class="w-full {{ $minHeightClass }} px-4 py-3 bg-surface-white border border-outline-variant/60
                   rounded-[16px] text-sm text-on-surface font-mono leading-relaxed resize-y
                   focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
        ></textarea>
        <p x-show="error" x-text="error" x-cloak class="mt-1.5 text-xs text-error"></p>
    </div>

</div>
