@props([
    'length' => 9,
    'groups' => [3, 3, 3],
    'name'   => 'token_value',
])

@php
    $boxes = [];
    $idx   = 0;
    foreach ($groups as $gIdx => $size) {
        for ($j = 0; $j < $size; $j++) {
            $boxes[] = ['group' => $gIdx, 'index' => $idx++, 'last_in_group' => $j === $size - 1];
        }
    }
    $totalGroups = count($groups);
@endphp

<div
    x-data="{
        values:  Array({{ $length }}).fill(''),
        length:  {{ $length }},
        valid:   'ABCDEFGHJKMNPQRSTUVWXYZ23456789',

        get combined() { return this.values.join(''); },

        boxes() {
            console.log('$el is:', this.$el, this.$el?.outerHTML?.slice(0, 150));
            return Array.from(this.$el.querySelectorAll('[data-token-box]'));
        },

        // Sanitize whatever lands in the box, keep at most one valid char, advance.
        handleInput(index, event) {
            const raw   = event.target.value;
            const chars = [...raw.toUpperCase()].filter(c => this.valid.includes(c));

            if (!chars.length) {
                this.values[index] = '';
                return;
            }

            // Last typed char wins (covers overtype-on-select case)
            const char = chars[chars.length - 1];
            this.values[index] = char;

            if (index < this.length - 1) {
                this.$nextTick(() => this.boxes()[index + 1]?.focus());
            }
        },

        handleKeydown(index, event) {
            if (event.key === 'Backspace') {
                if (this.values[index] !== '') {
                    this.values[index] = '';
                    // stay on this box — default behavior already clears it via x-model
                } else if (index > 0) {
                    event.preventDefault();
                    this.values[index - 1] = '';
                    this.$nextTick(() => this.boxes()[index - 1]?.focus());
                }
            } else if (event.key === 'ArrowLeft') {
                event.preventDefault();
                if (index > 0) this.boxes()[index - 1]?.focus();
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                if (index < this.length - 1) this.boxes()[index + 1]?.focus();
            }
        },

        handleInput(index, event) {
            const raw   = event.target.value;
            const chars = [...raw.toUpperCase()].filter(c => this.valid.includes(c));
            console.log('handleInput fired', { index, raw, chars });

            if (!chars.length) {
                this.values[index] = '';
                return;
            }

            const char = chars[chars.length - 1];
            this.values[index] = char;
            console.log('about to advance', { from: index, to: index + 1, length: this.length });

            if (index < this.length - 1) {
                this.$nextTick(() => {
                    const all = this.boxes();
                    console.log('total boxes found:', all.length, 'expected length:', this.length, 'index+1:', index + 1);
                    all[index + 1]?.focus();
                });
            }
        },

        handlePaste(event) {
            event.preventDefault();
            const raw   = event.clipboardData?.getData('text') ?? '';
            const chars = [...raw.toUpperCase()]
                .filter(c => this.valid.includes(c))
                .slice(0, this.length);

            chars.forEach((c, i) => { this.values[i] = c; });

            this.$nextTick(() => {
                const nextIdx = Math.min(chars.length, this.length - 1);
                this.boxes()[nextIdx]?.focus();
            });
        },
    }"
    class="flex items-center gap-2 flex-wrap"
    {{ $attributes }}
>
    <input type="hidden" name="{{ $name }}" :value="combined">

    @php $prevGroup = -1; @endphp
    @foreach($boxes as $box)

        @if($box['group'] !== $prevGroup && $prevGroup !== -1)
            <span class="text-sm font-mono font-bold text-outline-variant select-none" aria-hidden="true">–</span>
        @endif
        @php $prevGroup = $box['group']; @endphp

        <input
            type="text"
            inputmode="text"
            autocomplete="off"
            maxlength="1"
            data-token-box
            x-model="values[{{ $box['index'] }}]"
            @input="handleInput({{ $box['index'] }}, $event)"
            @keydown="handleKeydown({{ $box['index'] }}, $event)"
            @paste="handlePaste($event)"
            @focus="$event.target.select()"
            placeholder="·"
            aria-label="Token character {{ $box['index'] + 1 }} of {{ $length }}"
            class="w-11 h-12 sm:w-12 sm:h-13 text-center text-lg font-bold font-mono
                   text-primary bg-surface-white
                   border-2 border-outline-variant/60 rounded-[16px]
                   placeholder:text-outline-variant/40
                   focus:border-primary focus:ring-2 focus:ring-primary/15 focus:outline-none
                   transition-all duration-150 uppercase cursor-text caret-primary
                   hover:border-outline-variant"
            style="width: 2.75rem; height: 3rem;"
        >
    @endforeach
</div>