{{--
    Enroll with Token modal — shared by Dashboard and My Courses pages.
    Single plain text input handles both class (9-char) and course (6-char) tokens.
    The backend determines token type automatically from the submitted value.
--}}
@php
    $reopenModal   = old('_modal') === 'enroll'
                        || $errors->has('token_value')
                        || session()->has('enroll_success');
    $oldToken      = old('token_value', '');
    $tokenError    = $errors->first('token_value') ?? '';
    $enrollSuccess = session('enroll_success') ?? '';
@endphp

{{-- ── Backdrop ── --}}
<div
    x-data="{
        open:        {{ $reopenModal ? 'true' : 'false' }},
        tokenType:   'class',
        tokenValue:  @js(strtoupper($oldToken)),
        submitting:  false,
        errorMsg:    @js($tokenError),
        successMsg:  @js($enrollSuccess),
        shaking:     false,
        _opener:     null,

        init() {
            if (this.open) {
                document.body.classList.add('overflow-hidden');
                this.lockBg();
                this.$nextTick(() => {
                    this.$nextTick(() => {
                        if (this.successMsg) {
                            setTimeout(() => this.close(), 2500);
                        } else {
                            this.$el.querySelector('#tokenInput')?.focus();
                            if (this.errorMsg) this.triggerShake();
                        }
                    });
                });
            }
        },

        triggerShake() {
            this.shaking = false;
            this.$nextTick(() => { this.shaking = true; });
            setTimeout(() => { this.shaking = false; }, 450);
        },

        onSubmit(event) {
            const val = this.tokenValue.trim();
            if (!val) {
                this.errorMsg = 'Please enter your token.';
                this.triggerShake();
                event.preventDefault();
                return;
            }
            if (val.length < 6 || val.length > 9) {
                this.errorMsg = 'Tokens are 6 characters (course) or 9 characters (class).';
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
            this.successMsg = '';
            this.shaking    = false;
            this.submitting = false;
            document.body.classList.remove('overflow-hidden');
            this.unlockBg();
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
        tokenType = 'class';
        tokenValue = '';
        errorMsg = '';
        successMsg = '';
        submitting = false;
        document.body.classList.add('overflow-hidden');
        lockBg();
        $nextTick(() => $nextTick(() => { $el.querySelector('#tokenInput')?.focus(); }))
    "
    @keydown.escape.window="if(open) close()"
    @keydown.tab="if(open) trapFocus($event)"
    @click.self="close()"
    role="dialog"
    aria-modal="true"
    aria-labelledby="enrollModalTitle"
    class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
>
    <template x-if="open">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-1"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-1"
            class="w-full max-w-md"
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
                    <input type="hidden" name="_modal" value="enroll">

                    {{-- ── Success state ── --}}
                    <div x-show="successMsg" x-cloak
                         class="px-6 py-8 flex flex-col items-center gap-3 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-gold/20 flex items-center justify-center">
                            <span class="material-symbols-outlined text-[28px] text-primary">check_circle</span>
                        </div>
                        <p class="text-sm font-semibold text-on-surface" style="font-family: var(--font-display);"
                           x-text="successMsg"></p>
                        <p class="text-xs text-on-surface-variant">Closing in a moment…</p>
                    </div>

                    <div x-show="!successMsg" class="px-6 py-5 space-y-4">

                        {{-- ── Class / Course toggle ── --}}
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="tokenType = 'class'; errorMsg = ''; $nextTick(() => $el.closest('form').querySelector('#tokenInput').focus())"
                                :class="tokenType === 'class'
                                    ? 'bg-gold text-primary shadow-sm'
                                    : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high'"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-medium
                                       cursor-pointer transition-all duration-150"
                            >
                                <span class="material-symbols-outlined text-[16px]">group</span>
                                Class
                            </button>
                            <button
                                type="button"
                                @click="tokenType = 'course'; errorMsg = ''; $nextTick(() => $el.closest('form').querySelector('#tokenInput').focus())"
                                :class="tokenType === 'course'
                                    ? 'bg-gold text-primary shadow-sm'
                                    : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high'"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-medium
                                       cursor-pointer transition-all duration-150"
                            >
                                <span class="material-symbols-outlined text-[16px]">library_books</span>
                                Course
                            </button>
                        </div>

                        {{-- ── Token input ── --}}
                        <div>
                            <label for="tokenInput"
                                   class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-2">
                                Token
                            </label>

                            <div :class="{ 'animate-shake': shaking }">
                                <input
                                    id="tokenInput"
                                    type="text"
                                    name="token_value"
                                    autocomplete="off"
                                    :maxlength="tokenType === 'class' ? 9 : 6"
                                    :placeholder="tokenType === 'class' ? 'e.g. AX1BY2CZ3' : 'e.g. AX1BY2'"
                                    x-model="tokenValue"
                                    @input="tokenValue = $event.target.value.toUpperCase(); errorMsg = ''"
                                    class="w-full px-4 py-2.5 text-base font-mono font-semibold tracking-widest uppercase
                                           text-primary bg-surface-white
                                           border border-outline-variant/60 rounded-[16px]
                                           placeholder:text-outline-variant/40 placeholder:font-normal placeholder:tracking-normal
                                           focus:border-primary focus:ring-2 focus:ring-primary/15 focus:outline-none
                                           transition-all duration-150"
                                    data-iodine-rules='["required","minLength:6","maxLength:9","regExp:^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]+$"]'
                                >
                            </div>

                            {{-- Error --}}
                            <div x-show="errorMsg" x-cloak class="mt-2" aria-live="assertive">
                                <p class="text-xs font-medium text-error flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px] shrink-0">error</span>
                                    <span x-text="errorMsg"></span>
                                </p>
                            </div>
                        </div>

                        {{-- ── Contextual hint ── --}}
                        <p class="text-xs text-on-surface-variant" x-show="tokenType === 'class'">
                            <span class="font-semibold text-on-surface">Class token (9 characters)</span> — joins you to a teacher's class. Do this before enrolling in courses.
                        </p>
                        <p class="text-xs text-on-surface-variant" x-show="tokenType === 'course'" x-cloak>
                            <span class="font-semibold text-on-surface">Course token (6 characters)</span> — enrolls you in a specific course. You must be in the class first.
                        </p>

                    </div>{{-- end !successMsg body --}}

                    {{-- ── Footer ── --}}
                    <div x-show="!successMsg"
                         class="flex items-center justify-end gap-3 px-6 py-4
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
