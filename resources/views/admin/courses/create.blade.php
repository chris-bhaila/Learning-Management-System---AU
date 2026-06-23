@extends('layouts.admin')

@section('title', 'Create Course')

@section('topbar-actions')
    <a href="{{ route('admin.courses.index') }}"
       class="inline-flex items-center gap-1.5 px-4 py-2 border border-outline-variant/60
              text-sm font-medium text-on-surface-variant rounded-[24px]
              hover:bg-surface-container-low hover:text-primary transition-colors cursor-pointer">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Back to Courses
    </a>
@endsection

@section('content')
@php
    $groupsByTeacher = $groups
        ->groupBy('teacher_id')
        ->map(fn($g) => $g->map(fn($grp) => ['id' => $grp->id, 'name' => $grp->name])->values());
@endphp

{{-- ─── Page Header ─── --}}
<div>
    <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
        Create Course
    </h1>
    <p class="mt-1 text-sm text-on-surface-variant">
        Set up a new course and assign it to a teacher.
    </p>
</div>

{{-- ─── Server-side error summary (shown on POST redirect back) ─── --}}
@if($errors->any())
    <div class="bg-error-container border border-error/30 text-on-error-container rounded-[16px] p-4 flex gap-3">
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
    method="POST"
    action="{{ route('admin.courses.store') }}"
    @submit="onSubmit"
    x-data="{
        teacherId: '{{ old('teacher_id', '') }}',
        groupId:   '{{ old('group_id', '') }}',
        published: {{ old('is_published', '0') === '1' ? 'true' : 'false' }},
        groupsByTeacher: @js($groupsByTeacher),

        {{-- Pre-seed errors from server so inline messages show on POST redirect back --}}
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

        onTitleBlur(value) {
            this.errors.title = this.check(value, ['required', 'maxLength:255']);
        },

        onTeacherChange(event) {
            this.groupId = '';
            this.errors.teacher_id = this.check(event.target.value, ['required']);
        },

        onSubmit(event) {
            this.errors.title      = this.check(document.getElementById('title').value, ['required', 'maxLength:255']);
            this.errors.teacher_id = this.check(this.teacherId, ['required']);

            if (Object.values(this.errors).some(Boolean)) {
                event.preventDefault();
            }
        },
    }"
>
    @csrf

    {{-- ═══ SECTION 1 — Course Details ═══ --}}
    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] p-6 animate-fade-up">

        <h2 class="text-sm font-semibold text-on-surface mb-5" style="font-family: var(--font-display);">
            Course Details
        </h2>

        {{-- Title --}}
        <div class="mb-5">
            <label for="title"
                   class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                Course Title <span class="text-error">*</span>
            </label>
            <input
                id="title"
                type="text"
                name="title"
                value="{{ old('title') }}"
                placeholder="e.g. Introduction to Algebra"
                @blur="onTitleBlur($event.target.value)"
                :class="errors.title ? 'border-error' : 'border-outline-variant/60'"
                class="w-full px-4 py-2.5 bg-surface-white border rounded-[16px] text-sm
                       placeholder:text-outline
                       focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
            >
            <p x-show="errors.title" x-text="errors.title"
               class="mt-1.5 text-xs text-error" x-cloak></p>
        </div>

        {{-- Description (TipTap) — optional, no client-side validation needed --}}
        <div>
            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                Description
            </label>

            {{-- Toolbar --}}
            <div class="flex items-center gap-0.5 px-3 py-2 bg-surface-container-low
                        border border-outline-variant/60 rounded-t-[16px] border-b-0 flex-wrap">

                <button type="button" onclick="tiptap.chain().focus().toggleBold().run()"
                        title="Bold"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer font-bold text-sm">
                    B
                </button>
                <button type="button" onclick="tiptap.chain().focus().toggleItalic().run()"
                        title="Italic"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer italic text-sm">
                    I
                </button>
                <button type="button" onclick="tiptap.chain().focus().toggleStrike().run()"
                        title="Strikethrough"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer line-through text-sm">
                    S
                </button>

                <div class="w-px h-5 bg-outline-variant/60 mx-1"></div>

                <button type="button"
                        onclick="tiptap.chain().focus().toggleHeading({ level: 2 }).run()"
                        title="Heading 2"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer text-xs font-bold">
                    H2
                </button>
                <button type="button"
                        onclick="tiptap.chain().focus().toggleHeading({ level: 3 }).run()"
                        title="Heading 3"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer text-xs font-bold">
                    H3
                </button>

                <div class="w-px h-5 bg-outline-variant/60 mx-1"></div>

                <button type="button"
                        onclick="tiptap.chain().focus().toggleBulletList().run()"
                        title="Bullet list"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">format_list_bulleted</span>
                </button>
                <button type="button"
                        onclick="tiptap.chain().focus().toggleOrderedList().run()"
                        title="Ordered list"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">format_list_numbered</span>
                </button>

                <div class="w-px h-5 bg-outline-variant/60 mx-1"></div>

                <button type="button"
                        onclick="tiptap.chain().focus().toggleBlockquote().run()"
                        title="Blockquote"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">format_quote</span>
                </button>
                <button type="button"
                        onclick="tiptap.chain().focus().setHorizontalRule().run()"
                        title="Divider"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">horizontal_rule</span>
                </button>

                <div class="w-px h-5 bg-outline-variant/60 mx-1"></div>

                <button type="button"
                        onclick="tiptap.chain().focus().undo().run()"
                        title="Undo"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">undo</span>
                </button>
                <button type="button"
                        onclick="tiptap.chain().focus().redo().run()"
                        title="Redo"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">redo</span>
                </button>
            </div>

            {{-- Editor area --}}
            <div id="tiptap-editor"
                 class="w-full min-h-[200px] px-4 py-3 bg-surface-white border border-outline-variant/60
                        rounded-b-[16px] text-sm text-on-surface
                        focus-within:border-primary focus-within:ring-1 focus-within:ring-primary
                        prose prose-sm max-w-none
                        [&_.ProseMirror]:outline-none [&_.ProseMirror]:min-h-[160px]">
            </div>

            {{-- Hidden field synced by TipTap --}}
            <input type="hidden" name="description" id="description-hidden"
                   value="{{ old('description') }}">

            @error('description')
                <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- ═══ SECTION 2 — Teacher & Group ═══ --}}
    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] p-6 animate-fade-up">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

            {{-- Teacher --}}
            <div>
                <label for="teacher_id"
                       class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                    Teacher <span class="text-error">*</span>
                </label>
                <div class="relative">
                    <select
                        id="teacher_id"
                        name="teacher_id"
                        x-model="teacherId"
                        @change="onTeacherChange($event)"
                        :class="errors.teacher_id ? 'border-error' : 'border-outline-variant/60'"
                        class="w-full appearance-none pl-4 pr-10 py-2.5 bg-surface-white border rounded-[16px]
                               text-sm text-on-surface cursor-pointer
                               focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                    >
                        <option value="">Select a teacher…</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}"
                                    {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                {{ $teacher->name }}
                            </option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2
                                 text-outline text-[18px] pointer-events-none">
                        expand_more
                    </span>
                </div>
                <p x-show="errors.teacher_id" x-text="errors.teacher_id"
                   class="mt-1.5 text-xs text-error" x-cloak></p>
            </div>

            {{-- Course Group — optional, no client-side validation --}}
            <div>
                <label for="group_id"
                       class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                    Course Group
                    <span class="ml-1 text-[10px] font-normal normal-case text-outline">(optional)</span>
                </label>
                <div class="relative">
                    <select
                        id="group_id"
                        name="group_id"
                        x-model="groupId"
                        :disabled="!teacherId || filteredGroups.length === 0"
                        class="w-full appearance-none pl-4 pr-10 py-2.5 bg-surface-white border border-outline-variant/60
                               rounded-[16px] text-sm text-on-surface cursor-pointer
                               disabled:opacity-50 disabled:cursor-not-allowed
                               focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary
                               {{ $errors->has('group_id') ? 'border-error' : '' }}"
                    >
                        <option value="">
                            <span x-text="teacherId
                                ? (filteredGroups.length ? 'No group' : 'No groups for this teacher')
                                : 'Select a teacher first'">
                                Select a teacher first
                            </span>
                        </option>
                        <template x-for="group in filteredGroups" :key="group.id">
                            <option :value="group.id" :selected="groupId == group.id"
                                    x-text="group.name"></option>
                        </template>
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2
                                 text-outline text-[18px] pointer-events-none">
                        expand_more
                    </span>
                </div>
                @error('group_id')
                    <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>

        </div>
    </div>

    {{-- ═══ SECTION 3 — Settings ═══ --}}
    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] p-6 animate-fade-up">

        <h2 class="text-sm font-semibold text-on-surface mb-5" style="font-family: var(--font-display);">
            Settings
        </h2>

        {{-- Publish toggle — boolean, no validation needed --}}
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-on-surface">Publish course</p>
                <p class="text-xs text-on-surface-variant mt-0.5">
                    Published courses are visible to enrolled students. Drafts are hidden.
                </p>
            </div>

            <button
                type="button"
                @click="published = !published"
                :class="published
                    ? 'bg-gold border-gold/80'
                    : 'bg-surface-container-high border-outline-variant/60'"
                class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full border
                       transition-colors duration-200 cursor-pointer focus:outline-none
                       focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                role="switch"
                :aria-checked="published.toString()"
            >
                <span
                    :class="published ? 'translate-x-6' : 'translate-x-1'"
                    class="inline-block h-5 w-5 rounded-full bg-surface-white shadow
                           transition-transform duration-200">
                </span>
            </button>

            <input type="hidden" name="is_published" :value="published ? '1' : '0'">
        </div>
    </div>

    {{-- ═══ ACTIONS ═══ --}}
    <div class="flex items-center justify-end gap-3 pt-2">
        <a href="{{ route('admin.courses.index') }}"
           class="px-5 py-2.5 border border-outline-variant/60 text-sm font-medium text-on-surface-variant
                  rounded-[24px] hover:bg-surface-container-low hover:text-primary transition-colors cursor-pointer">
            Cancel
        </a>
        <button type="submit"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-gold text-primary
                       text-sm font-semibold rounded-[24px] hover:bg-gold/90 transition-colors cursor-pointer
                       focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gold">
            <span class="material-symbols-outlined text-[18px]">add</span>
            Save Course
        </button>
    </div>

</form>
@endsection

@push('scripts')
<script type="module">
import { Editor } from 'https://esm.sh/@tiptap/core@2'
import StarterKit from 'https://esm.sh/@tiptap/starter-kit@2'

const editorEl = document.getElementById('tiptap-editor')
const hiddenEl = document.getElementById('description-hidden')

const editor = new Editor({
    element: editorEl,
    extensions: [StarterKit],
    content: hiddenEl.value || '',
    editorProps: {
        attributes: { class: 'outline-none min-h-[160px]' },
    },
    onUpdate({ editor }) {
        hiddenEl.value = editor.getHTML()
    },
})

window.tiptap = editor
</script>
@endpush
