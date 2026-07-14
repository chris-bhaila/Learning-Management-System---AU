@extends('layouts.admin')

@section('title', $course->title)

@section('topbar-actions')
    <a href="{{ route('admin.courses.index') }}"
       class="inline-flex items-center gap-1.5 px-4 py-2 border border-outline-variant/60
              text-sm font-medium text-on-surface-variant rounded-[24px]
              hover:bg-surface-container-low hover:text-primary transition-colors cursor-pointer">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        All Courses
    </a>
@endsection


@section('content')
@php
    $groupsByTeacher = $groups
        ->groupBy('teacher_id')
        ->map(fn($g) => $g->map(fn($grp) => ['id' => $grp->id, 'name' => $grp->name])->values());

    $published   = $course->is_published ?? false;
    $teacherName = $course->teacher?->name ?? '—';
    $groupName   = $course->group?->name ?? null;
@endphp

<div
    x-data="{
        editing: false,
        submitting: false,
        titleShake: false,
        teacherShake: false,

        teacherId: '{{ $course->teacher_id }}',
        groupId:   '{{ $course->group_id ?? '' }}',
        published: {{ $course->is_published ? 'true' : 'false' }},
        groupsByTeacher: @js($groupsByTeacher),

        errors: {
            title:      '{{ addslashes($errors->first('title')) }}',
            teacher_id: '{{ addslashes($errors->first('teacher_id')) }}',
        },

        get filteredGroups() {
            return this.groupsByTeacher[this.teacherId] ?? [];
        },

        check(value, rules) {
            const result = window.Iodine.assert(value ?? '', rules);
            return result.valid ? '' : result.error;
        },

        shake(flag) {
            this[flag] = false;
            this.$nextTick(() => {
                this[flag] = true;
                setTimeout(() => this[flag] = false, 450);
            });
        },

        onTitleBlur(value) {
            this.errors.title = this.check(value, ['required', 'maxLength:255']);
        },

        onTeacherChange(event) {
            this.groupId = '';
            this.errors.teacher_id = this.check(event.target.value, ['required']);
        },

        onSubmit(event) {
            this.errors.title      = this.check(document.getElementById('edit-title').value, ['required', 'maxLength:255']);
            this.errors.teacher_id = this.check(this.teacherId, ['required']);
            if (Object.values(this.errors).some(Boolean)) {
                event.preventDefault();
                if (this.errors.title)      this.shake('titleShake');
                if (this.errors.teacher_id) this.shake('teacherShake');
                return;
            }
            this.submitting = true;
        },

        cancelEdit() {
            this.editing     = false;
            this.teacherId   = '{{ $course->teacher_id }}';
            this.groupId     = '{{ $course->group_id ?? '' }}';
            this.published   = {{ $course->is_published ? 'true' : 'false' }};
            this.errors      = { title: '', teacher_id: '' };
            document.getElementById('edit-title').value = @js($course->title);
            if (window.tiptap) {
                window.tiptap.commands.setContent(@js($course->description ?? ''), false);
                document.getElementById('edit-description').value = @js($course->description ?? '');
            }
            window.dispatchEvent(new CustomEvent('reset-raw-html'));
        },

        init() {
            if ({{ $errors->any() ? 'true' : 'false' }}) {
                this.editing = true;
            }
        },
    }"
    class="space-y-6"
>

{{-- ─── Server validation error summary ─── --}}
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

{{-- ══════════════════════════════════════════
     EDITABLE FORM (title · description · settings)
══════════════════════════════════════════ --}}
<form
    id="edit-course-form"
    method="POST"
    action="{{ route('admin.courses.update', $course->id) }}"
    @submit="onSubmit"
    class="space-y-6"
>
    @csrf
    @method('PATCH')

    {{-- ─── Page Header / Title ─── --}}
    {{-- Label lives outside the flex row so only the input participates in vertical alignment --}}
    <label x-show="editing" x-cloak for="edit-title"
           class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
        Course Title <span class="text-error">*</span>
    </label>

    <div class="flex flex-wrap items-start gap-4 mb-4">
        <div class="min-w-0 w-full sm:flex-1">

            {{-- View: course title heading --}}
            <div x-show="!editing"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1">
                <div class="flex items-center gap-3 flex-wrap mb-3">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium
                                 {{ $published ? 'bg-gold/20 text-primary' : 'bg-surface-container text-on-surface-variant' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $published ? 'bg-gold' : 'bg-outline-variant' }}"></span>
                        {{ $published ? 'Published' : 'Draft' }}
                    </span>
                    @if($groupName)
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium
                                     bg-surface-container text-on-surface-variant">
                            <span class="material-symbols-outlined text-[14px]">folder</span>
                            {{ $groupName }}
                        </span>
                    @endif
                </div>
                <h1 class="text-2xl font-bold text-primary leading-tight break-words mb-2"
                    style="font-family: var(--font-display);">
                    {{ $course->title }}
                </h1>
                <p class="text-sm text-on-surface-variant flex items-center gap-1.5 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 min-w-0">
                        <span class="material-symbols-outlined text-[16px] shrink-0">person</span>
                        <span class="truncate">{{ $teacherName }}</span>
                    </span>
                    <span class="text-outline-variant/60">·</span>
                    <span class="inline-flex items-center gap-1.5 shrink-0">
                        <span class="material-symbols-outlined text-[16px]">schedule</span>
                        Created {{ $course->created_at->diffForHumans() }}
                    </span>
                </p>
            </div>

            {{-- Edit: title input (label is above the flex row, so only the input aligns with buttons) --}}
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
                    value="{{ old('title', $course->title) }}"
                    @blur="onTitleBlur($event.target.value)"
                    :class="[errors.title ? 'border-error' : 'border-outline-variant/60', { 'animate-shake': titleShake }]"
                    class="w-full px-4 py-2.5 bg-surface-white border rounded-[16px] text-sm
                           focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                >
                <p x-show="errors.title" x-text="errors.title"
                   x-transition:enter="transition ease-out duration-150"
                   x-transition:enter-start="opacity-0 -translate-y-1"
                   x-transition:enter-end="opacity-100 translate-y-0"
                   x-transition:leave="transition ease-in duration-100"
                   x-transition:leave-start="opacity-100"
                   x-transition:leave-end="opacity-0"
                   class="mt-1.5 text-xs text-error" x-cloak></p>
            </div>

        </div>

        {{-- Action buttons — inside x-data scope so Alpine can toggle them --}}
        <div class="flex items-center gap-2 shrink-0 self-center">

            {{-- View mode --}}
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
                Edit Course
            </button>
            <button
                type="button"
                x-show="!editing"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                onclick="confirmDelete(
                    {{ Js::from($course->title) }},
                    document.getElementById('delete-course-form'),
                    'This permanently deletes all its units, revokes its tokens, and removes every student\'s enrollment. This cannot be undone.'
                )"
                class="inline-flex items-center gap-2 px-5 py-2.5 border border-error/40 text-error
                       text-sm font-semibold rounded-[24px] hover:bg-error hover:text-white hover:border-error
                       active:scale-[0.96] transition-all duration-150 cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">delete</span>
                Delete Course
            </button>

            {{-- Edit mode --}}
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
                    form="edit-course-form"
                    :disabled="submitting"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gold text-primary
                           text-sm font-semibold rounded-[24px] hover:bg-gold/90
                           active:scale-[0.96] transition-all duration-150 cursor-pointer
                           disabled:opacity-60 disabled:cursor-not-allowed disabled:active:scale-100">
                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true"
                          :class="submitting ? 'animate-spin' : ''"
                          x-text="submitting ? 'progress_activity' : 'save'">save</span>
                    <span x-text="submitting ? 'Saving…' : 'Save'">Save</span>
                </button>
            </div>

        </div>
    </div>

    {{-- ─── Two-column layout ─── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 animate-fade-up">

        {{-- ═══ MAIN COLUMN ═══ --}}
        <div class="contents lg:col-span-2 lg:flex lg:flex-col lg:gap-5 lg:min-w-0">

            {{-- Description card --}}
            <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] order-2 p-6 mt-4 overflow-hidden">

                <p class="text-sm font-semibold text-on-surface mb-4" style="font-family: var(--font-display);">
                    Description
                </p>

                {{-- View: rendered HTML --}}
                <div x-show="!editing"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1">
                    @if($course->description)
                        <div x-data="{ expanded: false, overflows: false }"
                             x-init="$nextTick(() => { overflows = $refs.contentBody.scrollHeight > 220 })">
                            <div x-ref="contentBody"
                                 :class="!expanded && overflows ? 'max-h-[220px]' : ''"
                                 class="relative overflow-hidden rich-text text-on-surface break-words">
                                {!! $course->description !!}
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
                        <p class="text-sm text-outline italic">No description provided.</p>
                    @endif
                </div>

                {{-- Edit: TipTap editor --}}
                <div x-show="editing" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1">

                    <x-editor-raw-toggle hidden-input-id="edit-description">
                    {{-- Toolbar --}}
                    <div class="flex items-center gap-0.5 px-3 py-2 bg-surface-container-low
                                border border-outline-variant/60 rounded-t-[16px] border-b-0 flex-wrap">
                        <button type="button" onclick="tiptap.chain().focus().toggleBold().run()" aria-label="Bold"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                                       hover:bg-surface-container hover:text-primary active:scale-90 transition-all duration-100 cursor-pointer font-bold text-sm">
                            B
                        </button>
                        <button type="button" onclick="tiptap.chain().focus().toggleItalic().run()" aria-label="Italic"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                                       hover:bg-surface-container hover:text-primary transition-colors cursor-pointer italic text-sm">
                            I
                        </button>
                        <button type="button" onclick="tiptap.chain().focus().toggleStrike().run()" aria-label="Strikethrough"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                                       hover:bg-surface-container hover:text-primary active:scale-90 transition-all duration-100 cursor-pointer line-through text-sm">
                            S
                        </button>
                        <div class="w-px h-5 bg-outline-variant/60 mx-1" role="separator"></div>
                        <button type="button" onclick="tiptap.chain().focus().toggleHeading({ level: 2 }).run()" aria-label="Heading 2"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                                       hover:bg-surface-container hover:text-primary active:scale-90 transition-all duration-100 cursor-pointer text-xs font-bold">
                            H2
                        </button>
                        <button type="button" onclick="tiptap.chain().focus().toggleHeading({ level: 3 }).run()" aria-label="Heading 3"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                                       hover:bg-surface-container hover:text-primary active:scale-90 transition-all duration-100 cursor-pointer text-xs font-bold">
                            H3
                        </button>
                        <div class="w-px h-5 bg-outline-variant/60 mx-1" role="separator"></div>
                        <button type="button" onclick="tiptap.chain().focus().toggleBulletList().run()" aria-label="Bullet list"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                                       hover:bg-surface-container hover:text-primary active:scale-90 transition-all duration-100 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">format_list_bulleted</span>
                        </button>
                        <button type="button" onclick="tiptap.chain().focus().toggleOrderedList().run()" aria-label="Ordered list"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                                       hover:bg-surface-container hover:text-primary active:scale-90 transition-all duration-100 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">format_list_numbered</span>
                        </button>
                        <div class="w-px h-5 bg-outline-variant/60 mx-1" role="separator"></div>
                        <button type="button" onclick="tiptap.chain().focus().toggleBlockquote().run()" aria-label="Blockquote"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                                       hover:bg-surface-container hover:text-primary active:scale-90 transition-all duration-100 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">format_quote</span>
                        </button>
                        <div class="w-px h-5 bg-outline-variant/60 mx-1" role="separator"></div>
                        <button type="button" onclick="tiptap.chain().focus().undo().run()" aria-label="Undo"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                                       hover:bg-surface-container hover:text-primary active:scale-90 transition-all duration-100 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">undo</span>
                        </button>
                        <button type="button" onclick="tiptap.chain().focus().redo().run()" aria-label="Redo"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                                       hover:bg-surface-container hover:text-primary active:scale-90 transition-all duration-100 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">redo</span>
                        </button>
                    </div>

                    <div id="tiptap-editor"
                         class="w-full min-h-[200px] px-4 py-3 bg-surface-white border border-outline-variant/60
                                rounded-b-[16px] text-sm text-on-surface
                                focus-within:border-primary focus-within:ring-1 focus-within:ring-primary
                                prose prose-sm max-w-none
                                [&_.ProseMirror]:outline-none [&_.ProseMirror]:min-h-[160px]">
                    </div>
                    </x-editor-raw-toggle>

                    <input type="hidden" name="description" id="edit-description"
                           value="{{ old('description', $course->description) }}">

                    @error('description')
                        <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- ─── Units ─── --}}
            <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] order-3 overflow-hidden">

                <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-outline-variant/20">
                    <p class="text-sm font-semibold text-on-surface" style="font-family: var(--font-display);">
                        Units
                    </p>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-on-surface-variant">
                            {{ $course->units->count() }} {{ Str::plural('unit', $course->units->count()) }}
                        </span>
                        <a href="{{ route('admin.units.create', $course->id) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gold text-primary
                                  text-xs font-semibold rounded-[24px] hover:bg-gold/90
                                  active:scale-[0.96] transition-all duration-150 cursor-pointer">
                            <span class="material-symbols-outlined text-[14px]">add</span>
                            Add Unit
                        </a>
                    </div>
                </div>

                @if($course->units->isEmpty())
                    <div class="py-12 flex flex-col items-center gap-3 text-center">
                        <div class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center animate-float">
                            <span class="material-symbols-outlined text-outline text-[24px]">menu_book</span>
                        </div>
                        <p class="text-sm font-semibold text-on-surface">No units yet</p>
                        <p class="text-xs text-on-surface-variant">Add the first unit to start building this course.</p>
                        <a href="{{ route('admin.units.create', $course->id) }}"
                           class="mt-1 inline-flex items-center gap-1.5 px-4 py-2 bg-gold text-primary
                                  text-sm font-semibold rounded-[24px] hover:bg-gold/90 transition-colors cursor-pointer">
                            <span class="material-symbols-outlined text-[16px]">add</span>
                            Add Unit
                        </a>
                    </div>
                @else
                    <ul class="divide-y divide-outline-variant/20">
                        @foreach($course->units as $unit)
                            <li class="flex items-center gap-4 px-6 py-3.5 hover:bg-surface-container-low/40 transition-colors duration-200">

                                {{-- Order badge --}}
                                <span class="w-7 h-7 rounded-full bg-surface-container text-on-surface-variant
                                             text-xs font-semibold flex items-center justify-center shrink-0"
                                      aria-hidden="true">
                                    {{ $unit->order }}
                                </span>

                                {{-- Title --}}
                                <a href="{{ route('admin.units.show', $unit->id) }}"
                                   class="flex-1 text-sm font-medium text-on-surface truncate min-w-0
                                          hover:text-gold transition-colors cursor-pointer">
                                    {{ $unit->title }}
                                </a>

                                {{-- Publish toggle badge --}}
                                <button type="button"
                                        title="{{ $unit->is_published ? 'Mark as draft' : 'Publish unit' }}"
                                        onclick="document.getElementById('publish-unit-form-{{ $unit->id }}').requestSubmit()"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px]
                                               font-medium shrink-0 transition-colors duration-150 cursor-pointer
                                               {{ $unit->is_published
                                                    ? 'bg-gold/20 text-primary hover:bg-gold/30'
                                                    : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $unit->is_published ? 'bg-gold' : 'bg-outline-variant' }}"></span>
                                    {{ $unit->is_published ? 'Published' : 'Draft' }}
                                </button>

                                {{-- Delete --}}
                                <button type="button"
                                        onclick="confirmDelete({{ Js::from($unit->title) }}, document.getElementById('delete-unit-form-{{ $unit->id }}'))"
                                        aria-label="Delete unit {{ $unit->title }}"
                                        class="inline-flex items-center justify-center w-8 h-8 shrink-0
                                               border border-error/40 rounded-[12px] text-error
                                               hover:bg-error/5 transition-colors cursor-pointer">
                                    <span class="material-symbols-outlined text-[16px]" aria-hidden="true">delete</span>
                                </button>

                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- ─── Attachments ─── --}}
            <div
                x-data="{
                    fileSelected: false,
                    fileName: '',
                    fileError: '',
                    uploading: false,
                    allowedExts: ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','png','jpg','jpeg','zip'],
                    maxBytes: 20971520,
                    onFileChange(event) {
                        const file = event.target.files[0];
                        if (!file) { this.fileSelected = false; this.fileName = ''; this.fileError = ''; return; }
                        const ext = file.name.split('.').pop().toLowerCase();
                        if (!this.allowedExts.includes(ext)) {
                            this.fileError = 'File type not allowed. Accepted: PDF, Word, Excel, PowerPoint, text, images, ZIP.';
                            this.fileSelected = false;
                            event.target.value = '';
                            return;
                        }
                        if (file.size > this.maxBytes) {
                            this.fileError = 'File must be 20 MB or smaller.';
                            this.fileSelected = false;
                            event.target.value = '';
                            return;
                        }
                        this.fileError = '';
                        this.fileSelected = true;
                        this.fileName = file.name;
                    },
                    onUploadSubmit() {
                        if (!this.fileSelected) {
                            this.fileError = 'Please select a file first.';
                            return;
                        }
                        this.uploading = true;
                        document.getElementById('upload-admin-course-file-form').requestSubmit();
                    },
                }"
                class="bg-surface-white border border-outline-variant/40 rounded-[20px]
                       shadow-[0px_1px_4px_rgba(30,42,74,0.06)] order-4 overflow-hidden">

                <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-outline-variant/20">
                    <p class="text-sm font-semibold text-on-surface" style="font-family: var(--font-display);">
                        Attachments
                    </p>
                    <span class="text-xs text-on-surface-variant">
                        {{ $course->files->count() }} {{ Str::plural('file', $course->files->count()) }}
                    </span>
                </div>

                {{-- Upload section --}}
                <div class="px-6 py-4 border-b border-outline-variant/20 bg-surface-container-low/40">
                    @if($errors->has('file'))
                        <div class="mb-3 flex items-start gap-2 text-xs text-error">
                            <span class="material-symbols-outlined text-[14px] mt-0.5 shrink-0">error</span>
                            {{ $errors->first('file') }}
                        </div>
                    @endif
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                        <label for="admin-course-file-input"
                               class="flex-1 min-w-0 inline-flex items-center gap-2 px-4 py-2.5 w-full
                                      border border-outline-variant/60 bg-surface-white rounded-[16px]
                                      text-sm text-on-surface-variant cursor-pointer
                                      hover:border-primary/50 hover:bg-surface-container-low
                                      transition-colors duration-150 overflow-hidden">
                            <span class="material-symbols-outlined text-[18px] text-outline shrink-0">upload_file</span>
                            <span class="truncate" x-text="fileName || 'Choose file…'"></span>
                        </label>
                        <input id="admin-course-file-input" type="file" name="file"
                               form="upload-admin-course-file-form"
                               @change="onFileChange"
                               class="sr-only"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.png,.jpg,.jpeg,.zip">
                        <button type="button"
                                @click="onUploadSubmit"
                                :disabled="!fileSelected || uploading"
                                class="shrink-0 inline-flex items-center justify-center gap-2 px-4 py-2.5
                                       bg-primary text-white text-sm font-semibold rounded-[24px]
                                       hover:bg-primary-container active:scale-[0.96]
                                       transition-all duration-150 cursor-pointer
                                       disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100">
                            <span class="material-symbols-outlined text-[16px]"
                                  :class="uploading ? 'animate-spin' : ''"
                                  x-text="uploading ? 'progress_activity' : 'upload'">upload</span>
                            <span x-text="uploading ? 'Uploading…' : 'Upload File'">Upload File</span>
                        </button>
                    </div>
                    <p x-show="fileError" x-text="fileError" x-cloak
                       class="mt-2 text-xs text-error transition-all duration-150"></p>
                    <p class="mt-1.5 text-[11px] text-outline">PDF, Word, Excel, PowerPoint, images, text, ZIP · max 20 MB</p>
                </div>

                @if($course->files->isEmpty())
                    <div class="py-10 flex flex-col items-center gap-2 text-center px-4">
                        <span class="material-symbols-outlined text-outline text-[28px] animate-float">attach_file</span>
                        <p class="text-sm font-medium text-on-surface">No files attached yet</p>
                        <p class="text-xs text-on-surface-variant">Upload a file above to attach it to this course.</p>
                    </div>
                @else
                    @include('partials.attachment-list', [
                        'groupedFiles' => $course->filesGroupedByDate(),
                        'canDelete' => true,
                        'deleteFormPrefix' => 'delete-admin-course-file-form',
                    ])
                @endif
            </div>

        </div>{{-- end main column --}}

        {{-- ═══ SIDEBAR ═══ --}}
        <div class="contents lg:flex lg:flex-col lg:gap-5 lg:min-w-0">

            {{-- ─── Course info card ─── --}}
            <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] order-1 p-6">

                {{-- View --}}
                <div x-show="!editing" class="flex flex-col gap-3"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1">

                    {{-- Teacher --}}
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-primary-container flex items-center justify-center shrink-0">
                            <span class="text-xs font-semibold text-on-primary"
                                  style="font-family: var(--font-display);">
                                {{ strtoupper(substr($teacherName, 0, 2)) }}
                            </span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] text-outline font-medium uppercase tracking-wide">Teacher</p>
                            <p class="text-sm font-medium text-on-surface truncate">{{ $teacherName }}</p>
                        </div>
                    </div>

                    {{-- Group --}}
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-surface-container flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-outline text-[18px]">folder</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] text-outline font-medium uppercase tracking-wide">Group</p>
                            <p class="text-sm text-on-surface truncate">{{ $groupName ?? '—' }}</p>
                        </div>
                    </div>

                    {{-- Published status --}}
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full {{ $published ? 'bg-gold/10' : 'bg-surface-container' }} flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[18px] {{ $published ? 'text-gold' : 'text-outline' }}">
                                {{ $published ? 'visibility' : 'visibility_off' }}
                            </span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] text-outline font-medium uppercase tracking-wide">Status</p>
                            <p class="text-sm font-medium {{ $published ? 'text-primary' : 'text-on-surface-variant' }}">
                                {{ $published ? 'Published' : 'Draft' }}
                            </p>
                        </div>
                    </div>

                </div>

                {{-- Edit --}}
                <div x-show="editing" x-cloak class="flex flex-col gap-4"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1">

                    {{-- Teacher dropdown --}}
                    <div>
                        <label for="edit-teacher"
                               class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                            Teacher <span class="text-error">*</span>
                        </label>
                        <div class="relative">
                            <select
                                id="edit-teacher"
                                name="teacher_id"
                                x-model="teacherId"
                                @change="onTeacherChange($event)"
                                :class="[errors.teacher_id ? 'border-error' : 'border-outline-variant/60', { 'animate-shake': teacherShake }]"
                                class="w-full appearance-none pl-4 pr-10 py-2.5 bg-surface-white border
                                       rounded-[16px] text-sm text-on-surface cursor-pointer
                                       focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                            >
                                <option value="">Select a teacher…</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}"
                                            {{ old('teacher_id', $course->teacher_id) == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2
                                         text-outline text-[18px] pointer-events-none">expand_more</span>
                        </div>
                        <p x-show="errors.teacher_id" x-text="errors.teacher_id"
                           x-transition:enter="transition ease-out duration-150"
                           x-transition:enter-start="opacity-0 -translate-y-1"
                           x-transition:enter-end="opacity-100 translate-y-0"
                           x-transition:leave="transition ease-in duration-100"
                           x-transition:leave-start="opacity-100"
                           x-transition:leave-end="opacity-0"
                           class="mt-1.5 text-xs text-error" x-cloak></p>
                    </div>

                    {{-- Group dropdown --}}
                    <div>
                        <label for="edit-group"
                               class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                            Course Group
                            <span class="ml-1 text-[10px] font-normal normal-case text-outline">(optional)</span>
                        </label>
                        <div class="relative">
                            <select
                                id="edit-group"
                                name="group_id"
                                x-model="groupId"
                                :disabled="!teacherId || filteredGroups.length === 0"
                                class="w-full appearance-none pl-4 pr-10 py-2.5 bg-surface-white border
                                       border-outline-variant/60 rounded-[16px] text-sm text-on-surface cursor-pointer
                                       disabled:opacity-50 disabled:cursor-not-allowed
                                       focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                            >
                                <option value="">
                                    <span x-text="teacherId
                                        ? (filteredGroups.length ? 'No group' : 'No groups for this teacher')
                                        : 'Select a teacher first'">No group</span>
                                </option>
                                <template x-for="group in filteredGroups" :key="group.id">
                                    <option :value="group.id" :selected="groupId == group.id"
                                            x-text="group.name"></option>
                                </template>
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2
                                         text-outline text-[18px] pointer-events-none">expand_more</span>
                        </div>
                    </div>

                    {{-- Published toggle --}}
                    <div class="flex items-center justify-between gap-3 pt-1">
                        <div>
                            <p class="text-sm font-medium text-on-surface">Published</p>
                            <p class="text-xs text-on-surface-variant mt-0.5">Visible to enrolled students</p>
                        </div>
                        <button
                            type="button"
                            @click="published = !published"
                            :class="published ? 'bg-gold border-gold/80' : 'bg-surface-container-high border-outline-variant/60'"
                            class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full border
                                   transition-colors duration-200 cursor-pointer focus:outline-none
                                   focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                            role="switch" :aria-checked="published.toString()"
                        >
                            <span :class="published ? 'translate-x-6' : 'translate-x-1'"
                                  class="inline-block h-5 w-5 rounded-full bg-surface-white shadow
                                         transition-transform duration-200"></span>
                        </button>
                        <input type="hidden" name="is_published" :value="published ? '1' : '0'">
                    </div>

                </div>
            </div>

            {{-- ─── Tokens ─── --}}
            <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] order-5 overflow-hidden">

                <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-outline-variant/20">
                    <p class="text-sm font-semibold text-on-surface" style="font-family: var(--font-display);">
                        Course Tokens
                    </p>
                    <span class="text-xs text-on-surface-variant">
                        {{ $course->tokens->count() }} total
                    </span>
                </div>

                @if($course->tokens->isEmpty())
                    <div class="py-10 flex flex-col items-center gap-2 text-center px-4">
                        <span class="material-symbols-outlined text-outline text-[28px] animate-float">key</span>
                        <p class="text-xs text-on-surface-variant">No tokens generated yet.</p>
                    </div>
                @else
                    <ul class="divide-y divide-outline-variant/20">
                        @foreach($course->tokens as $token)
                        @php
                            $expired       = $token->isExpired();
                            $usesRemaining = max(0, $token->max_uses - $token->uses_count);
                        @endphp
                        <li class="px-6 py-3.5 flex flex-col gap-1 min-w-0 hover:bg-surface-container-low/40 transition-colors duration-200">
                            <div class="flex items-center justify-between gap-2 min-w-0">
                                <code class="text-xs font-mono text-primary bg-surface-container
                                             px-2 py-0.5 rounded-lg tracking-wide min-w-0 truncate">
                                    {{ $token->token_value }}
                                </code>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold shrink-0
                                             {{ $expired ? 'bg-surface-container text-on-surface-variant' : 'bg-gold/20 text-primary' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $expired ? 'bg-outline-variant' : 'bg-gold' }}"></span>
                                    {{ $expired ? 'Expired' : 'Active' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 text-[11px] text-on-surface-variant flex-wrap">
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[13px]">schedule</span>
                                    Expires {{ $token->expires_at->format('M j, Y g:i A') }}
                                </span>
                                <span class="text-outline-variant/60">·</span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[13px]">group</span>
                                    {{ $usesRemaining }} / {{ $token->max_uses }} remaining
                                </span>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- ─── Enrolled Students ─── --}}
            <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] order-6 overflow-hidden">

                <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-outline-variant/20">
                    <p class="text-sm font-semibold text-on-surface" style="font-family: var(--font-display);">
                        Enrolled Students
                    </p>
                    <span class="text-xs text-on-surface-variant">
                        {{ $course->students->count() }} {{ Str::plural('student', $course->students->count()) }}
                    </span>
                </div>

                @if($course->students->isEmpty())
                    <div class="py-10 flex flex-col items-center gap-2 text-center px-4">
                        <span class="material-symbols-outlined text-outline text-[28px] animate-float">group</span>
                        <p class="text-xs text-on-surface-variant">No students enrolled yet.</p>
                    </div>
                @else
                    <ul class="divide-y divide-outline-variant/20 max-h-72 overflow-y-auto">
                        @foreach($course->students as $student)
                            <li class="flex items-center gap-3 px-6 py-3 hover:bg-surface-container-low/40 transition-colors duration-200">
                                <div class="w-7 h-7 rounded-full bg-surface-container flex items-center justify-center shrink-0">
                                    <span class="text-[10px] font-semibold text-on-surface-variant"
                                          style="font-family: var(--font-display);">
                                        {{ strtoupper(substr($student->name, 0, 2)) }}
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-on-surface truncate">{{ $student->name }}</p>
                                    <p class="text-[11px] text-on-surface-variant truncate">{{ $student->email }}</p>
                                </div>
                                <span class="text-[10px] text-outline shrink-0">
                                    {{ $student->pivot->enrolled_at ? \Carbon\Carbon::parse($student->pivot->enrolled_at)->format('M j') : '—' }}
                                </span>
                                <button
                                    type="button"
                                    title="Remove from course"
                                    onclick="confirmDestructive(
                                        {{ Js::from('Remove ' . $student->name . ' from ' . $course->title . '?') }},
                                        {{ Js::from('They will lose access to this course only — their class enrollment and other courses are unaffected.') }},
                                        document.getElementById('remove-student-form-{{ $student->id }}'),
                                        'Remove'
                                    )"
                                    class="w-7 h-7 shrink-0 inline-flex items-center justify-center rounded-lg cursor-pointer
                                           text-on-surface-variant hover:bg-error-container hover:text-error
                                           transition-colors duration-150">
                                    <span class="material-symbols-outlined text-[16px]">person_remove</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

        </div>{{-- end sidebar --}}

    </div>{{-- end grid --}}

</form>

{{-- Standalone delete-course form — outside #edit-course-form, same reason as every other
     standalone form on this page (prevents _method spoofing from polluting the PATCH submission). --}}
<form id="delete-course-form" method="POST" action="{{ route('admin.courses.destroy', $course->id) }}" class="hidden">
    @csrf
    @method('DELETE')
</form>

{{-- Standalone remove-student forms — outside #edit-course-form, same reason as the unit
     delete/publish forms below (prevents _method spoofing from polluting the PATCH submission). --}}
@foreach($course->students as $student)
<form id="remove-student-form-{{ $student->id }}" method="POST"
      action="{{ route('admin.courses.students.remove', [$course->id, $student->id]) }}" class="hidden">
    @csrf
    @method('PATCH')
</form>
@endforeach

{{-- Standalone unit delete forms — outside #edit-course-form to prevent _method=DELETE polluting the PATCH submission --}}
@foreach($course->units as $unit)
<form id="delete-unit-form-{{ $unit->id }}" method="POST" action="{{ route('admin.units.destroy', $unit->id) }}" class="hidden">
    @csrf
    @method('DELETE')
</form>
@endforeach

{{-- Standalone unit publish-toggle forms — each submits PATCH with is_published flipped --}}
@foreach($course->units as $unit)
<form id="publish-unit-form-{{ $unit->id }}" method="POST" action="{{ route('admin.units.update', $unit->id) }}" class="hidden">
    @csrf
    @method('PATCH')
    <input type="hidden" name="is_published" value="{{ $unit->is_published ? '0' : '1' }}">
</form>
@endforeach

{{-- Standalone course file upload form — outside #edit-course-form; file input and button reference this via form= attribute. --}}
<form id="upload-admin-course-file-form" method="POST" action="{{ route('files.store') }}"
      enctype="multipart/form-data" class="hidden">
    @csrf
    <input type="hidden" name="fileable_type" value="App\Models\Course">
    <input type="hidden" name="fileable_id" value="{{ $course->id }}">
</form>

{{-- Standalone per-file delete forms for course attachments. --}}
@foreach($course->files as $file)
<form id="delete-admin-course-file-form-{{ $file->id }}" method="POST"
      action="{{ route('files.destroy', $file->id) }}" class="hidden">
    @csrf
    @method('DELETE')
</form>
@endforeach

</div>{{-- end x-data --}}
@endsection

@push('scripts')
<script type="module">
import { Editor } from 'https://esm.sh/@tiptap/core@2'
import StarterKit from 'https://esm.sh/@tiptap/starter-kit@2'

const editorEl  = document.getElementById('tiptap-editor')
const hiddenEl  = document.getElementById('edit-description')

window.tiptap = new Editor({
    element: editorEl,
    extensions: [StarterKit],
    content: hiddenEl?.value || '',
    editorProps: {
        attributes: { class: 'outline-none min-h-[160px]' },
    },
    onUpdate({ editor }) {
        if (hiddenEl) hiddenEl.value = editor.getHTML()
    },
})
</script>
@endpush
