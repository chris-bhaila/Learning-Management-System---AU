{{--
    Enroll with Token modal — shared by Dashboard and My Courses pages.

    Issue 1 fix: The inner panel lives in <template x-if="open"> rather than
    being always in the DOM behind x-show. This ensures the x-token-input
    components are only initialized while the modal is actually visible —
    never in a hidden/zero-size container. Each close destroys them; each
    open creates fresh instances with values = Array(N).fill('').

    Standard form POST to student.enroll. Re-opens on server error via
    old('_modal') === 'enroll', matching the existing admin modal pattern.
    Success closes the modal (full page reload) and the flash toast fires.

    Trigger:  openEnrollModal()  (defined in @push('scripts') below)
--}}
@php
    $reopenModal = old('_modal') === 'enroll';
    $savedType   = old('_token_type', 'class');
    $tokenError  = $reopenModal ? ($errors->first('token_value') ?? '') : '';
@endphp

{{-- ── Backdrop (x-show handles the fade, x-if on the panel handles initialization) ── --}}
<div
    x-data="{
        open:       {{ $reopenModal ? 'true' : 'false' }},
        tokenType:  '{{ $savedType }}',
        submitting: false,
        errorMsg:   @js($tokenError),
        shaking:    false,
        _opener:    null,

        init() {
            if (this.open) {
                document.body.classList.add('overflow-hidden');
                this.lockBg();
                // Error-reopen path: panel x-if stamps synchronously since open=true,
                // but inner tokenType x-if needs an extra tick to stamp the input.
                this.$nextTick(() => {
                    this.$nextTick(() => {
                        this.$el.querySelector('[data-token-box]')?.focus();
                        if (this.errorMsg) this.triggerShake();
                    });
                });
            }
        },

        setType(type) {
            if (this.tokenType === type) return;
            this.tokenType = type;
            this.errorMsg  = '';
            this.shaking   = false;
            // Focus first box of the newly rendered input after x-if swaps it in
            this.$nextTick(() => {
                this.$nextTick(() => {
                    this.$el.querySelector('[data-token-box]')?.focus();
                });
            });
        },

        triggerShake() {
            this.shaking = false;
            this.$nextTick(() => { this.shaking = true; });
            setTimeout(() => { this.shaking = false; }, 450);
        },

        onSubmit(event) {
            const hidden = this.$el.querySelector('input[name=token_value]');
            if (!hidden || !hidden.value.trim()) {
                this.errorMsg = 'Please enter your token.';
                this.triggerShake();
                event.preventDefault();
                return;
            }
            this.submitting = true;
        },

        lockBg() {
            const s = document.getElementById('sidebar');
            const m = document.getElementById('main-content');
            if (s) s.inert = true;
            if (m) m.inert = true;
        },

        unlockBg() {
            const s = document.getElementById('sidebar');
            const m = document.getElementById('main-content');
            if (s) s.inert = false;
            if (m) m.inert = false;
        },

        close() {
            this.open       = false;
            this.errorMsg   = '';
            this.shaking    = false;
            this.submitting = false;
            document.body.classList.remove('overflow-hidden');
            this.unlockBg();
            // Return focus to whichever element opened the modal
            const target = this._opener;
            setTimeout(() => { target?.focus(); }, 200);
        },

        // Focus trap — keeps Tab cycling within the modal while open
        trapFocus(event) {
            const sel = 'button:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]';
            const focusable = [...this.$el.querySelectorAll(sel)].filter(el => el.offsetParent !== null && el.tabIndex >= 0);
            if (!focusable.length) return;

            const first  = focusable[0];
            const last   = focusable[focusable.length - 1];
            const active = document.activeElement;

            event.preventDefault();

            if (event.shiftKey) {
                if (active === first || !this.$el.contains(active)) {
                    last.focus();
                } else {
                    const idx = focusable.indexOf(active);
                    focusable[Math.max(0, idx - 1)]?.focus();
                }
            } else {
                if (active === last || !this.$el.contains(active)) {
                    first.focus();
                } else {
                    const idx = focusable.indexOf(active);
                    focusable[idx + 1]?.focus();
                }
            }
        },
    }"
    x-show="open"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @open-enroll-modal.window="
        _opener = document.activeElement;
        open = true;
        document.body.classList.add('overflow-hidden');
        lockBg();
        $nextTick(() => $nextTick(() => { $el.querySelector('[data-token-box]')?.focus(); }))
    "
    @keydown.escape.window="if(open) close()"
    @keydown.tab="if(open) trapFocus($event)"
    @click.self="close()"
    role="dialog"
    aria-modal="true"
    aria-labelledby="enrollModalTitle"
    class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
>
    {{--
        Inner panel lives in x-if="open" so that x-token-input components
        are created fresh on each modal open and destroyed on close.
        x-transition on the root div inside the template plays the scale
        animation — Alpine v3 honours x-transition on x-if stamped roots.
    --}}
    <template x-if="open">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-1"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-1"
            class="w-full max-w-xl"
        >
            <x-card class="overflow-hidden">

                {{-- ── Header ── --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/30">
                    <div>
                        <h3 id="enrollModalTitle"
                            class="text-base font-semibold text-primary"
                            style="font-family: var(--font-display);">
                            Enroll with Token
                        </h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">
                            Enter the token your teacher shared with you.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="close()"
                        class="w-8 h-8 flex items-center justify-center rounded-[12px] cursor-pointer
                               text-on-surface-variant hover:bg-surface-container hover:text-primary
                               transition-colors duration-150 shrink-0 ml-3">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                {{-- ── Form ── --}}
                <form
                    method="POST"
                    action="{{ route('student.enroll') }}"
                    @submit="onSubmit"
                    novalidate
                >
                    @csrf
                    <input type="hidden" name="_modal"      value="enroll">
                    <input type="hidden" name="_token_type" :value="tokenType">

                    <div class="px-6 py-5 space-y-5">

                        {{-- ── Class / Course toggle ── --}}
                        <div class="flex items-center gap-1 p-1 bg-surface-container rounded-[16px]">
                            <button
                                type="button"
                                @click="setType('class')"
                                :class="tokenType === 'class'
                                    ? 'bg-surface-white text-primary shadow-sm'
                                    : 'text-on-surface-variant hover:text-primary'"
                                class="flex-1 flex items-center justify-center gap-1.5
                                       px-4 py-2 rounded-[12px] text-sm font-medium
                                       transition-all duration-150 cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[16px]">group</span>
                                Class
                            </button>
                            <button
                                type="button"
                                @click="setType('course')"
                                :class="tokenType === 'course'
                                    ? 'bg-surface-white text-primary shadow-sm'
                                    : 'text-on-surface-variant hover:text-primary'"
                                class="flex-1 flex items-center justify-center gap-1.5
                                       px-4 py-2 rounded-[12px] text-sm font-medium
                                       transition-all duration-150 cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[16px]">library_books</span>
                                Course
                            </button>
                        </div>

                        {{-- ── Token input ── --}}
                        {{--
                            x-if destroys/recreates the token-input component on toggle,
                            giving a natural values-array reset. Only one hidden
                            input[name=token_value] exists in the DOM at any time.
                        --}}
                        <div>
                            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-3 text-center">
                                Token
                            </p>

                            {{-- Centered wrapper; overflow-x-auto as safety net on very narrow screens --}}
                            <div :class="{ 'animate-shake': shaking }"
                                 class="flex justify-center overflow-x-auto pb-1">
                                <template x-if="tokenType === 'class'">
                                    <div>
                                        <x-token-input :length="9" :groups="[3,3,3]" name="token_value" />
                                    </div>
                                </template>
                                <template x-if="tokenType === 'course'">
                                    <div>
                                        <x-token-input :length="6" :groups="[2,2,2]" name="token_value" />
                                    </div>
                                </template>
                            </div>

                            {{-- Error --}}
                            <div x-show="errorMsg" x-cloak class="mt-3 flex justify-center" aria-live="assertive">
                                <p class="text-xs font-medium text-error flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px] shrink-0">error</span>
                                    <span x-text="errorMsg"></span>
                                </p>
                            </div>
                        </div>

                        {{-- ── Contextual hint ── --}}
                        <div class="text-xs text-on-surface-variant text-center">
                            <p x-show="tokenType === 'class'">
                                <span class="font-semibold text-on-surface">Class token (9 chars)</span>
                                — joins you to a teacher's class. Do this before enrolling in courses.
                            </p>
                            <p x-show="tokenType === 'course'" x-cloak>
                                <span class="font-semibold text-on-surface">Course token (6 chars)</span>
                                — enrolls you in a specific course. You must be in the class first.
                            </p>
                        </div>

                    </div>

                    {{-- ── Footer ── --}}
                    <div class="flex items-center justify-end gap-3 px-6 py-4
                                border-t border-outline-variant/30 bg-surface-container-low/40">
                        <x-button type="button" variant="secondary" @click="close()">
                            Cancel
                        </x-button>
                        <x-button
                            type="submit"
                            variant="primary"
                            x-bind:disabled="submitting"
                            class="min-w-[110px] justify-center"
                        >
                            <span x-show="!submitting" class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[16px]">vpn_key</span>
                                Enroll
                            </span>
                            <span x-show="submitting" x-cloak class="flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin shrink-0"
                                     fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor"
                                          d="M4 12a8 8 0 018-8v8H4z"/>
                                </svg>
                                Enrolling…
                            </span>
                        </x-button>
                    </div>

                </form>
            </x-card>
        </div>
    </template>

</div>

@push('scripts')
<script>
    function openEnrollModal() {
        window.dispatchEvent(new CustomEvent('open-enroll-modal'));
    }
</script>
@endpush
