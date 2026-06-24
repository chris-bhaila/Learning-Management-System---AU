@extends($layout)

@section('title', $unit->title)

@section('topbar-actions')
    <a href="{{ $backRoute }}"
       class="inline-flex items-center gap-1.5 px-4 py-2 border border-outline-variant/60
              text-sm font-medium text-on-surface-variant rounded-[24px]
              hover:bg-surface-container-low hover:text-primary transition-colors cursor-pointer">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Back to Course
    </a>
@endsection

@section('content')
@php
    $courseName = $unit->course?->title ?? '—';
@endphp

<div
    x-data="{
        editing: false,
        submitting: false,
        titleShake: false,

        errors: {
            title: '{{ addslashes($errors->first('title')) }}',
        },

        check(value, rules) {
            const result = window.Iodine.assert(value ?? '', rules);
            return result.valid ? '' : result.error;
        },

        onTitleBlur(value) {
            this.errors.title = this.check(value, ['required', 'maxLength:255']);
        },

        shake(flag) {
            this[flag] = false;
            this.$nextTick(() => {
                this[flag] = true;
                setTimeout(() => this[flag] = false, 450);
            });
        },

        onSubmit(event) {
            this.errors.title = this.check(document.getElementById('edit-title').value, ['required', 'maxLength:255']);
            if (this.errors.title) {
                event.preventDefault();
                this.shake('titleShake');
                return;
            }
            this.submitting = true;
        },

        cancelEdit() {
            this.editing = false;
            this.errors  = { title: '' };
            document.getElementById('edit-title').value = @js($unit->title);
            if (window.tiptap) {
                window.tiptap.commands.setContent(@js($unit->content ?? ''), false);
                document.getElementById('edit-content').value = @js($unit->content ?? '');
            }
        },

        init() {
            if ({{ $errors->any() ? 'true' : 'false' }}) {
                this.editing = true;
            }
        },
    }"
    class="space-y-6"
>

{{-- ─── Server validation errors ─── --}}
@if($errors->any())
    <div role="alert" class="bg-error-container border border-error/30 text-on-error-container rounded-[16px] p-4 flex gap-3">
        <span class="material-symbols-outlined text-error text-[20px] shrink-0 mt-0.5">error</span>
        <div>
            <p class="text-sm font-semibold mb-1">Please fix the following errors:</p>
            <ul class="text-sm list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<form
    id="edit-unit-form"
    method="POST"
    action="{{ $updateRoute }}"
    @submit="onSubmit"
    class="space-y-6"
>
    @csrf
    @method('PATCH')

    {{-- Label shown above flex row only in edit mode --}}
    <label x-show="editing" x-cloak for="edit-title"
           class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
        Unit Title <span class="text-error">*</span>
    </label>

    {{-- ─── Header row: title + action buttons ─── --}}
    <div class="flex flex-wrap items-start gap-4">
        <div class="min-w-0 w-full sm:flex-1">

            {{-- View mode --}}
            <div x-show="!editing"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1">
                <div class="flex items-center gap-2 flex-wrap mb-2">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium
                                 bg-surface-container text-on-surface-variant">
                        <span class="material-symbols-outlined text-[14px]">menu_book</span>
                        Unit {{ $unit->order ?? '—' }}
                    </span>
                </div>
                <h1 class="text-2xl font-bold text-primary leading-tight break-words"
                    style="font-family: var(--font-display);">
                    {{ $unit->title }}
                </h1>
                <p class="mt-1.5 text-sm text-on-surface-variant flex items-center gap-1.5 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 min-w-0">
                        <span class="material-symbols-outlined text-[16px] shrink-0">library_books</span>
                        <a href="{{ $backRoute }}"
                           class="truncate hover:text-primary transition-colors cursor-pointer">
                            {{ $courseName }}
                        </a>
                    </span>
                    <span class="text-outline-variant/60 shrink-0">·</span>
                    <span class="inline-flex items-center gap-1.5 shrink-0">
                        <span class="material-symbols-outlined text-[16px]">schedule</span>
                        Created {{ $unit->created_at->diffForHumans() }}
                    </span>
                </p>
            </div>

            {{-- Edit mode --}}
            <div x-show="editing" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1">
                <input
                    id="edit-title"
                    type="text"
                    name="title"
                    value="{{ old('title', $unit->title) }}"
                    @blur="onTitleBlur($event.target.value)"
                    :class="[errors.title ? 'border-error' : 'border-outline-variant/60', { 'animate-shake': titleShake }]"
                    class="w-full px-4 py-2.5 bg-surface-white border rounded-[16px] text-sm
                           focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                >
                <p x-show="errors.title" x-text="errors.title"
                   x-transition:enter="transition ease-out duration-150"
                   x-transition:enter-start="opacity-0 -translate-y-1"
                   x-transition:enter-end="opacity-100 translate-y-0"
                   class="mt-1.5 text-xs text-error" x-cloak></p>
            </div>
        </div>

        {{-- Buttons --}}
        <div class="flex items-center gap-2 shrink-0 self-center">

            {{-- View: Edit + Delete --}}
            <button
                type="button"
                x-show="!editing"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="editing = true"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-gold text-primary
                       text-sm font-semibold rounded-[24px] hover:bg-gold/90
                       active:scale-[0.96] transition-all duration-150 cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">edit</span>
                Edit Unit
            </button>

            <div x-show="!editing"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
                <form method="POST" action="{{ $destroyRoute }}">
                    @csrf
                    @method('DELETE')
                    <button type="button"
                            onclick="confirmDelete({{ Js::from($unit->title) }}, this.closest('form'))"
                            class="inline-flex items-center gap-2 px-4 py-2.5
                                   border border-error/40 text-error text-sm font-medium rounded-[24px]
                                   hover:bg-error/5 active:scale-[0.96] transition-all duration-150 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">delete</span>
                        Delete
                    </button>
                </form>
            </div>

            {{-- Edit mode: Cancel + Save --}}
            <div class="flex items-center gap-2"
                 x-show="editing"
                 x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
                <button
                    type="button"
                    @click="cancelEdit()"
                    class="px-4 py-2.5 border border-outline-variant/60 text-sm font-medium
                           text-on-surface-variant rounded-[24px]
                           hover:bg-surface-container-low hover:text-primary
                           active:scale-[0.96] transition-all duration-150 cursor-pointer">
                    Cancel
                </button>
                <button
                    type="submit"
                    form="edit-unit-form"
                    :disabled="submitting"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gold text-primary
                           text-sm font-semibold rounded-[24px] hover:bg-gold/90
                           active:scale-[0.96] transition-all duration-150 cursor-pointer
                           disabled:opacity-60 disabled:cursor-not-allowed disabled:active:scale-100">
                    <span class="material-symbols-outlined text-[18px]"
                          :class="submitting ? 'animate-spin' : ''"
                          x-text="submitting ? 'progress_activity' : 'save'">save</span>
                    <span x-text="submitting ? 'Saving…' : 'Save'">Save</span>
                </button>
            </div>

        </div>
    </div>

    {{-- ─── Two-column grid ─── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 animate-fade-up">

        {{-- ═══ MAIN COLUMN ═══ --}}
        <div class="lg:col-span-2 min-w-0 flex flex-col gap-5">

            {{-- Content card --}}
            <div class="bg-surface-white border border-outline-variant/40 rounded-[20px]
                        shadow-[0px_1px_4px_rgba(30,42,74,0.06)] p-6 overflow-hidden">

                <p class="text-sm font-semibold text-on-surface mb-4" style="font-family: var(--font-display);">
                    Content
                </p>

                {{-- View --}}
                <div x-show="!editing"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1">
                    @if($unit->content)
                        <div x-data="{ expanded: false, overflows: false }"
                             x-init="$nextTick(() => { overflows = $refs.contentBody.scrollHeight > 220 })">
                            <div x-ref="contentBody"
                                 :class="!expanded && overflows ? 'max-h-[220px]' : ''"
                                 class="relative overflow-hidden rich-text text-on-surface break-words">
                                {!! $unit->content !!}
                                <div x-show="!expanded && overflows"
                                     class="absolute bottom-0 left-0 right-0 h-14
                                            bg-gradient-to-t from-surface-white to-transparent
                                            pointer-events-none">
                                </div>
                            </div>
                            <button x-show="overflows" x-cloak
                                    type="button"
                                    @click="expanded = !expanded"
                                    class="mt-2 inline-flex items-center gap-0.5 text-xs font-medium
                                           text-primary hover:text-gold transition-colors cursor-pointer">
                                <span x-text="expanded ? 'Show less' : 'Show more'">Show more</span>
                                <span class="material-symbols-outlined text-[14px]"
                                      :style="expanded ? 'transform:rotate(180deg)' : ''"
                                      style="transition:transform 200ms ease">expand_more</span>
                            </button>
                        </div>
                    @else
                        <p class="text-sm text-outline italic">No content provided.</p>
                    @endif
                </div>

                {{-- Edit: TipTap --}}
                <div x-show="editing" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1">

                    {{-- Toolbar --}}
                    <div class="flex items-center gap-0.5 px-3 py-2 bg-surface-container-low
                                border border-outline-variant/60 rounded-t-[16px] border-b-0 flex-wrap">
                        <button type="button" onclick="tiptap.chain().focus().toggleBold().run()" title="Bold"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-primary active:scale-90 transition-all duration-100 cursor-pointer font-bold text-sm">B</button>
                        <button type="button" onclick="tiptap.chain().focus().toggleItalic().run()" title="Italic"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-primary transition-colors cursor-pointer italic text-sm">I</button>
                        <button type="button" onclick="tiptap.chain().focus().toggleStrike().run()" title="Strikethrough"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-primary active:scale-90 transition-all duration-100 cursor-pointer line-through text-sm">S</button>
                        <div class="w-px h-5 bg-outline-variant/60 mx-1"></div>
                        <button type="button" onclick="tiptap.chain().focus().toggleHeading({ level: 2 }).run()" title="Heading 2"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-primary active:scale-90 transition-all duration-100 cursor-pointer text-xs font-bold">H2</button>
                        <button type="button" onclick="tiptap.chain().focus().toggleHeading({ level: 3 }).run()" title="Heading 3"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-primary active:scale-90 transition-all duration-100 cursor-pointer text-xs font-bold">H3</button>
                        <div class="w-px h-5 bg-outline-variant/60 mx-1"></div>
                        <button type="button" onclick="tiptap.chain().focus().toggleBulletList().run()" title="Bullet list"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-primary active:scale-90 transition-all duration-100 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">format_list_bulleted</span>
                        </button>
                        <button type="button" onclick="tiptap.chain().focus().toggleOrderedList().run()" title="Ordered list"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-primary active:scale-90 transition-all duration-100 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">format_list_numbered</span>
                        </button>
                        <div class="w-px h-5 bg-outline-variant/60 mx-1"></div>
                        <button type="button" onclick="tiptap.chain().focus().toggleBlockquote().run()" title="Blockquote"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-primary active:scale-90 transition-all duration-100 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">format_quote</span>
                        </button>
                        <div class="w-px h-5 bg-outline-variant/60 mx-1"></div>
                        <button type="button" onclick="tiptap.chain().focus().undo().run()" title="Undo"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-primary active:scale-90 transition-all duration-100 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">undo</span>
                        </button>
                        <button type="button" onclick="tiptap.chain().focus().redo().run()" title="Redo"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-primary active:scale-90 transition-all duration-100 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">redo</span>
                        </button>
                    </div>

                    <div id="tiptap-editor"
                         class="w-full min-h-[300px] px-4 py-3 bg-surface-white border border-outline-variant/60
                                rounded-b-[16px] text-sm text-on-surface
                                focus-within:border-primary focus-within:ring-1 focus-within:ring-primary
                                prose prose-sm max-w-none
                                [&_.ProseMirror]:outline-none [&_.ProseMirror]:min-h-[260px]">
                    </div>

                    <input type="hidden" name="content" id="edit-content"
                           value="{{ old('content', $unit->content) }}">

                    @error('content')
                        <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Attachments --}}
            <div class="bg-surface-white border border-outline-variant/40 rounded-[20px]
                        shadow-[0px_1px_4px_rgba(30,42,74,0.06)] overflow-hidden">

                <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-outline-variant/20">
                    <p class="text-sm font-semibold text-on-surface" style="font-family: var(--font-display);">
                        Attachments
                    </p>
                    <span class="text-xs text-on-surface-variant">
                        {{ $unit->files->count() }} {{ Str::plural('file', $unit->files->count()) }}
                    </span>
                </div>

                @if($unit->files->isEmpty())
                    <div class="py-10 flex flex-col items-center gap-2 text-center px-4">
                        <span class="material-symbols-outlined text-outline text-[28px] animate-float">attach_file</span>
                        <p class="text-xs text-on-surface-variant">No files attached to this unit.</p>
                    </div>
                @else
                    <ul class="divide-y divide-outline-variant/20">
                        @foreach($unit->files as $file)
                            <li class="flex items-center gap-3 px-6 py-3.5 min-w-0
                                       hover:bg-surface-container-low/40 transition-colors duration-200">
                                <span class="material-symbols-outlined text-outline text-[20px] shrink-0">description</span>
                                <span class="flex-1 text-sm text-on-surface truncate min-w-0">
                                    {{ $file->original_name ?? $file->filename }}
                                </span>
                                <a href="{{ route('files.download', $file->id) }}"
                                   class="shrink-0 inline-flex items-center gap-1 text-xs font-medium
                                          text-primary hover:text-gold transition-colors cursor-pointer">
                                    <span class="material-symbols-outlined text-[14px]">download</span>
                                    Download
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

        </div>{{-- end main --}}

        {{-- ═══ SIDEBAR ═══ --}}
        <div class="min-w-0 flex flex-col gap-5">

            <div class="bg-surface-white border border-outline-variant/40 rounded-[20px]
                        shadow-[0px_1px_4px_rgba(30,42,74,0.06)] p-6">
                <div class="flex flex-col gap-4">

                    {{-- Course --}}
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-surface-container flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-outline text-[18px]">library_books</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] text-outline font-medium uppercase tracking-wide">Course</p>
                            <a href="{{ $backRoute }}"
                               class="text-sm font-medium text-on-surface hover:text-gold
                                      transition-colors truncate block cursor-pointer">
                                {{ $courseName }}
                            </a>
                        </div>
                    </div>

                    {{-- Position --}}
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-surface-container flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-outline text-[18px]">format_list_numbered</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] text-outline font-medium uppercase tracking-wide">Position</p>
                            <p class="text-sm text-on-surface">Unit {{ $unit->order ?? '—' }}</p>
                        </div>
                    </div>

                    {{-- Created --}}
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-surface-container flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-outline text-[18px]">schedule</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] text-outline font-medium uppercase tracking-wide">Created</p>
                            <p class="text-sm text-on-surface">{{ $unit->created_at->format('M j, Y') }}</p>
                        </div>
                    </div>

                </div>
            </div>

        </div>{{-- end sidebar --}}

    </div>{{-- end grid --}}

</form>

</div>{{-- end x-data --}}
@endsection

@push('scripts')
<script type="module">
import { Editor } from 'https://esm.sh/@tiptap/core@2'
import StarterKit from 'https://esm.sh/@tiptap/starter-kit@2'

const editorEl = document.getElementById('tiptap-editor')
const hiddenEl = document.getElementById('edit-content')

window.tiptap = new Editor({
    element: editorEl,
    extensions: [StarterKit],
    content: hiddenEl?.value || '',
    editorProps: {
        attributes: { class: 'outline-none min-h-[260px]' },
    },
    onUpdate({ editor }) {
        if (hiddenEl) hiddenEl.value = editor.getHTML()
    },
})
</script>
@endpush
