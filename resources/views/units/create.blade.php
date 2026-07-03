@extends($layout)

@section('title', 'Add Unit — ' . $course->title)

@section('topbar-actions')
    <x-button variant="secondary" href="{{ $backRoute }}" icon="arrow_back">Back to Course</x-button>
@endsection

@section('content')

{{-- ─── Page Header ─── --}}
<div>
    <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1">
        {{ $course->title }}
    </p>
    <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
        Add Unit
    </h1>
    <p class="mt-1 text-sm text-on-surface-variant">
        The new unit will be appended to the end of the course and can be reordered after.
    </p>
</div>

{{-- ─── Server-side error summary ─── --}}
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
    action="{{ $storeRoute }}"
    enctype="multipart/form-data"
    @submit="onSubmit"
    x-data="{
        submitting: false,
        errors: {
            title: '{{ addslashes($errors->first('title')) }}',
        },

        fileList: [],
        fileErrors: [],
        allowedExts: ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','png','jpg','jpeg','zip'],
        maxBytes: 20971520,

        onFilesChange(event) {
            const inputs = Array.from(event.target.files);
            this.fileErrors = [];
            this.fileList = [];
            for (const file of inputs) {
                const ext = file.name.split('.').pop().toLowerCase();
                if (!this.allowedExts.includes(ext)) {
                    this.fileErrors = ['File type not allowed. Accepted: PDF, Word, Excel, PowerPoint, text, images, ZIP.'];
                    event.target.value = '';
                    return;
                }
                if (file.size > this.maxBytes) {
                    this.fileErrors = ['Each file must be 20 MB or smaller.'];
                    event.target.value = '';
                    return;
                }
                this.fileList.push({ name: file.name, size: file.size });
            }
        },

        clearFiles() {
            this.fileList = [];
            this.fileErrors = [];
            this.$refs.fileInput.value = '';
        },

        formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        },

        check(value, rules) {
            const result = window.Iodine.assert(value ?? '', rules);
            return result.valid ? '' : result.error;
        },

        onTitleBlur(value) {
            this.errors.title = this.check(value, ['required', 'maxLength:255']);
        },

        onSubmit(event) {
            this.errors.title = this.check(document.getElementById('unit-title').value, ['required', 'maxLength:255']);
            if (this.errors.title) {
                event.preventDefault();
                return;
            }
            history.replaceState(null, '', @js($backRoute));
            this.submitting = true;
        },
    }"
    class="space-y-4"
>
    @csrf

    {{-- ─── Unit Details ─── --}}
    <x-card class="p-6 animate-fade-up">

        <h2 class="text-sm font-semibold text-on-surface mb-5" style="font-family: var(--font-display);">
            Unit Details
        </h2>

        {{-- Title --}}
        <div class="mb-6">
            <label for="unit-title"
                   class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                Title <span class="text-error">*</span>
            </label>
            <input
                id="unit-title"
                type="text"
                name="title"
                value="{{ old('title') }}"
                placeholder="e.g. Introduction to the Topic"
                @blur="onTitleBlur($event.target.value)"
                :class="errors.title ? 'border-error' : 'border-outline-variant/60'"
                class="w-full px-4 py-2.5 bg-surface-white border rounded-[16px] text-sm
                       placeholder:text-outline
                       focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary
                       transition-colors"
            >
            <p x-show="errors.title" x-text="errors.title"
               x-transition:enter="transition ease-out duration-150"
               x-transition:enter-start="opacity-0 -translate-y-1"
               x-transition:enter-end="opacity-100 translate-y-0"
               class="mt-1.5 text-xs text-error" x-cloak></p>
        </div>

        {{-- Content (TipTap) --}}
        <div>
            <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                Content
                <span class="ml-1 text-[10px] font-normal normal-case text-outline">(optional)</span>
            </label>

            <x-editor-raw-toggle hidden-input-id="content-hidden" min-height-class="min-h-[300px]">
            {{-- Toolbar --}}
            <div class="flex items-center gap-0.5 px-3 py-2 bg-surface-container-low
                        border border-outline-variant/60 rounded-t-[16px] border-b-0 flex-wrap">
                <button type="button" onclick="tiptap.chain().focus().toggleBold().run()" title="Bold"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer font-bold text-sm">B</button>
                <button type="button" onclick="tiptap.chain().focus().toggleItalic().run()" title="Italic"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer italic text-sm">I</button>
                <button type="button" onclick="tiptap.chain().focus().toggleStrike().run()" title="Strikethrough"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer line-through text-sm">S</button>
                <div class="w-px h-5 bg-outline-variant/60 mx-1"></div>
                <button type="button" onclick="tiptap.chain().focus().toggleHeading({ level: 2 }).run()" title="Heading 2"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer text-xs font-bold">H2</button>
                <button type="button" onclick="tiptap.chain().focus().toggleHeading({ level: 3 }).run()" title="Heading 3"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer text-xs font-bold">H3</button>
                <div class="w-px h-5 bg-outline-variant/60 mx-1"></div>
                <button type="button" onclick="tiptap.chain().focus().toggleBulletList().run()" title="Bullet list"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">format_list_bulleted</span>
                </button>
                <button type="button" onclick="tiptap.chain().focus().toggleOrderedList().run()" title="Ordered list"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">format_list_numbered</span>
                </button>
                <div class="w-px h-5 bg-outline-variant/60 mx-1"></div>
                <button type="button" onclick="tiptap.chain().focus().toggleBlockquote().run()" title="Blockquote"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">format_quote</span>
                </button>
                <div class="w-px h-5 bg-outline-variant/60 mx-1"></div>
                <button type="button" onclick="tiptap.chain().focus().undo().run()" title="Undo"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">undo</span>
                </button>
                <button type="button" onclick="tiptap.chain().focus().redo().run()" title="Redo"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-on-surface-variant
                               hover:bg-surface-container hover:text-primary transition-colors cursor-pointer">
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
            </x-editor-raw-toggle>

            <input type="hidden" name="content" id="content-hidden" value="{{ old('content') }}">

            @error('content')
                <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
            @enderror
        </div>

    </x-card>

    {{-- ─── Attachments ─── --}}
    <x-card class="p-6">

        <h2 class="text-sm font-semibold text-on-surface mb-1" style="font-family: var(--font-display);">
            Attachments
            <span class="ml-1 text-[10px] font-normal normal-case text-outline">(optional)</span>
        </h2>
        <p class="text-xs text-on-surface-variant mb-4">
            PDF, Word, Excel, PowerPoint, images, text files, or ZIP — up to 20 MB each.
        </p>

        <label for="unit-file-upload"
               class="flex flex-col items-center justify-center gap-2 w-full px-4 py-8
                      border-2 border-dashed border-outline-variant/60 rounded-[16px]
                      cursor-pointer hover:border-primary hover:bg-surface-container-low
                      transition-colors duration-150 group">
            <span class="material-symbols-outlined text-[28px] text-outline group-hover:text-primary transition-colors">
                upload_file
            </span>
            <span class="text-sm text-on-surface-variant group-hover:text-on-surface transition-colors">
                Click to select files
            </span>
            <input
                id="unit-file-upload"
                type="file"
                name="files[]"
                multiple
                x-ref="fileInput"
                @change="onFilesChange($event)"
                class="hidden"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.png,.jpg,.jpeg,.zip"
            >
        </label>

        <div x-show="fileErrors.length > 0"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="mt-3" x-cloak>
            <template x-for="err in fileErrors" :key="err">
                <p class="text-xs text-error" x-text="err"></p>
            </template>
        </div>

        <div x-show="fileList.length > 0"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="mt-4" x-cloak>
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide">
                    Selected (<span x-text="fileList.length"></span>)
                </p>
                <button type="button"
                        @click="clearFiles()"
                        class="text-xs text-on-surface-variant hover:text-error transition-colors cursor-pointer">
                    Clear all
                </button>
            </div>
            <ul class="space-y-1.5">
                <template x-for="(file, index) in fileList" :key="index">
                    <li class="flex items-center gap-2 px-3 py-2 bg-surface-container-low rounded-[12px]">
                        <span class="material-symbols-outlined text-[18px] text-outline shrink-0">description</span>
                        <span class="text-sm text-on-surface min-w-0 truncate" x-text="file.name"></span>
                        <span class="text-xs text-on-surface-variant shrink-0 ml-auto" x-text="formatSize(file.size)"></span>
                    </li>
                </template>
            </ul>
        </div>

    </x-card>

    {{-- ─── Actions ─── --}}
    <div class="flex items-center justify-end gap-3 pt-2">
        <x-button variant="secondary" href="{{ $backRoute }}">Cancel</x-button>
        <x-button variant="primary" type="submit" x-bind:disabled="submitting">
            <span class="material-symbols-outlined text-[18px]"
                  :class="submitting ? 'animate-spin' : ''"
                  x-text="submitting ? 'progress_activity' : 'add'">add</span>
            <span x-text="submitting ? 'Saving…' : 'Add Unit'">Add Unit</span>
        </x-button>
    </div>

</form>
@endsection

@push('scripts')
<script type="module">
import { Editor } from 'https://esm.sh/@tiptap/core@2'
import StarterKit from 'https://esm.sh/@tiptap/starter-kit@2'

const editorEl = document.getElementById('tiptap-editor')
const hiddenEl = document.getElementById('content-hidden')

window.tiptap = new Editor({
    element: editorEl,
    extensions: [StarterKit],
    content: hiddenEl.value || '',
    editorProps: {
        attributes: { class: 'outline-none min-h-[260px]' },
    },
    onUpdate({ editor }) {
        hiddenEl.value = editor.getHTML()
    },
})
</script>
@endpush
