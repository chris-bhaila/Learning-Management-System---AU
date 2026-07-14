{{--
    Shared "Event Details" modal for an Activity row. Requires an ancestor element with
    x-data="logDetailModal()" (defined below) and a trigger elsewhere on the page calling
    open({ event, eventLabel, eventBadge, eventIcon, subjectType, subjectName, causerName,
    causerRole, timestamp, timeAgo, diff }) — see admin/logs/index.blade.php and
    admin/dashboard.blade.php for the two current callers.
--}}
<div x-show="isOpen"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-primary/40 backdrop-blur-sm" @click="close()"></div>

    {{-- Modal card --}}
    <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto z-10"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-2">

        <x-card class="p-0 overflow-hidden">

            {{-- Header --}}
            <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-outline-variant/30">
                <div class="flex items-center gap-3">
                    <template x-if="entry">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                              :class="entry.eventBadge">
                            <span class="material-symbols-outlined text-[13px]" x-text="entry.eventIcon"></span>
                            <span x-text="entry.eventLabel"></span>
                        </span>
                    </template>
                    <h2 class="text-base font-semibold text-primary" style="font-family: var(--font-display);">
                        Event Details
                    </h2>
                </div>
                <button type="button" @click="close()"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-outline
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <template x-if="entry">
                <div class="p-6 space-y-5">

                    {{-- Meta --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <div>
                                <p class="text-[10px] font-semibold text-outline uppercase tracking-wide mb-0.5">Subject</p>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <template x-if="entry.subjectType">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-semibold
                                                     bg-surface-container-high text-on-surface-variant"
                                              x-text="entry.subjectType"></span>
                                    </template>
                                    <span class="text-sm text-on-surface font-medium" x-text="entry.subjectName ?? '—'"></span>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] font-semibold text-outline uppercase tracking-wide mb-0.5">Caused By</p>
                                <template x-if="entry.causerName">
                                    <div>
                                        <p class="text-sm text-on-surface font-medium" x-text="entry.causerName"></p>
                                        <p class="text-[11px] text-outline" x-text="entry.causerRole ?? ''"></p>
                                    </div>
                                </template>
                                <template x-if="!entry.causerName">
                                    <span class="inline-flex items-center gap-1 text-sm text-outline italic">
                                        <span class="material-symbols-outlined text-[14px]">computer</span>
                                        System
                                    </span>
                                </template>
                            </div>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-outline uppercase tracking-wide mb-0.5">Timestamp</p>
                            <p class="text-sm text-on-surface font-medium" x-text="entry.timestamp"></p>
                            <p class="text-[11px] text-outline mt-0.5" x-text="entry.timeAgo"></p>
                        </div>
                    </div>

                    {{-- Diff table --}}
                    <div>
                        <p class="text-[10px] font-semibold text-outline uppercase tracking-wide mb-2"
                           x-text="entry.event === 'created' ? 'Initial values'
                                  : entry.event === 'deleted' ? 'Last known values'
                                  : entry.event === 'restored' ? 'Restored values'
                                  : 'Field changes'">
                        </p>

                        {{-- No diff data --}}
                        <template x-if="!entry.diff || entry.diff.length === 0">
                            <p class="text-sm text-outline italic py-3 text-center">
                                No field data recorded for this event.
                            </p>
                        </template>

                        {{-- Updated: 3-column diff (Before → After) --}}
                        <template x-if="entry.diff && entry.diff.length > 0 && entry.event === 'updated'">
                            <div class="rounded-[12px] border border-outline-variant/40 overflow-hidden text-xs">
                                <div class="grid grid-cols-3 px-4 py-2.5 bg-surface-container
                                            text-[10px] font-semibold tracking-widest text-outline uppercase">
                                    <span>Field</span>
                                    <span class="text-error">Before</span>
                                    <span class="text-emerald-700">After</span>
                                </div>
                                <template x-for="row in entry.diff" :key="row.label">
                                    <div class="grid grid-cols-3 px-4 py-3 border-t border-outline-variant/20
                                                hover:bg-surface-container/50 transition-colors">
                                        <span class="text-primary font-semibold" x-text="row.label"></span>
                                        <span class="text-error pr-3 break-words"
                                              x-text="row.old ?? '—'"></span>
                                        <span class="text-emerald-700 break-words"
                                              x-text="row.new ?? '—'"></span>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- Created / Restored: 2-column (Field + Value) --}}
                        <template x-if="entry.diff && entry.diff.length > 0 && (entry.event === 'created' || entry.event === 'restored')">
                            <div class="rounded-[12px] border border-outline-variant/40 overflow-hidden text-xs">
                                <div class="grid grid-cols-2 px-4 py-2.5 bg-surface-container
                                            text-[10px] font-semibold tracking-widest text-outline uppercase">
                                    <span>Field</span>
                                    <span class="text-emerald-700">Value</span>
                                </div>
                                <template x-for="row in entry.diff" :key="row.label">
                                    <div class="grid grid-cols-2 px-4 py-3 border-t border-outline-variant/20
                                                hover:bg-surface-container/50 transition-colors">
                                        <span class="text-primary font-semibold" x-text="row.label"></span>
                                        <span class="text-emerald-700 break-words"
                                              x-text="row.new ?? '—'"></span>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- Deleted: 2-column (Field + Last value) --}}
                        <template x-if="entry.diff && entry.diff.length > 0 && entry.event === 'deleted'">
                            <div class="rounded-[12px] border border-outline-variant/40 overflow-hidden text-xs">
                                <div class="grid grid-cols-2 px-4 py-2.5 bg-surface-container
                                            text-[10px] font-semibold tracking-widest text-outline uppercase">
                                    <span>Field</span>
                                    <span class="text-error">Last value</span>
                                </div>
                                <template x-for="row in entry.diff" :key="row.label">
                                    <div class="grid grid-cols-2 px-4 py-3 border-t border-outline-variant/20
                                                hover:bg-surface-container/50 transition-colors">
                                        <span class="text-primary font-semibold" x-text="row.label"></span>
                                        <span class="text-error break-words" x-text="row.old ?? '—'"></span>
                                    </div>
                                </template>
                            </div>
                        </template>

                    </div>
                </div>
            </template>

            {{-- Footer --}}
            <div class="flex justify-end px-6 py-4 border-t border-outline-variant/20 bg-surface-container-low/30">
                <x-button variant="secondary" @click="close()">Close</x-button>
            </div>

        </x-card>
    </div>
</div>

@push('scripts')
<script>
    function logDetailModal() {
        return {
            isOpen: false,
            entry: null,
            open(data) {
                this.entry = data;
                this.isOpen = true;
                document.body.style.overflow = 'hidden';
            },
            close() {
                this.isOpen = false;
                this.entry = null;
                document.body.style.overflow = '';
            },
        };
    }
</script>
@endpush
