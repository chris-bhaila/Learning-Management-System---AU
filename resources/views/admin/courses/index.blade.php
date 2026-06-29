@extends('layouts.admin')

@section('title', 'All Courses')

@section('content')
@php
    $courses     ??= collect();
    $groups      ??= collect();
    $allTeachers = $courses->pluck('teacher')->filter()->unique('id')->values();
@endphp

<div x-data="{
    groupsOpen: false,
    view: 'list',
    editId: null,
    editName: '',
    editDesc: '',
    editErrors: { name: '' },

    check(value, rules) {
        const result = window.Iodine.assert(value ?? '', rules);
        return result.valid ? '' : result.error;
    },

    editGroup(id, name, desc) {
        this.editId   = id;
        this.editName = name;
        this.editDesc = desc ?? '';
        this.editErrors = { name: '' };
        this.view = 'edit';
        this.$nextTick(() => document.getElementById('admin-edit-group-name')?.focus());
    },

    cancelEdit() { this.view = 'list'; this.editId = null; this.editErrors = { name: '' }; },
    closeModal() { this.groupsOpen = false; this.view = 'list'; this.editId = null; },
}">

{{-- ─── Page Header ─── --}}
<div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3 lg:gap-4 mb-8">
    <div class="min-w-0">
        <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
            All Courses
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            {{ $courses->count() }} {{ Str::plural('course', $courses->count()) }} across all teachers
        </p>
    </div>
    <div class="flex items-center gap-2 lg:shrink-0">
        <button type="button" @click="groupsOpen = true"
                class="inline-flex items-center gap-2 px-4 py-2.5 border border-outline-variant/60
                       text-sm font-medium text-on-surface-variant rounded-[24px]
                       hover:bg-surface-container-low hover:text-primary
                       transition-colors duration-150 cursor-pointer">
            <span class="material-symbols-outlined text-[18px]">folder_managed</span>
            Manage Groups
        </button>
        <a href="{{ route('admin.courses.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-gold text-primary
                  text-sm font-semibold rounded-[24px] hover:bg-gold/90 transition-colors
                  duration-150 cursor-pointer">
            <span class="material-symbols-outlined text-[18px]">add</span>
            New Course
        </a>
    </div>
</div>


{{-- ─── Filters + Grid ─── --}}
<div class="animate-fade-up"
    x-data="{
        search: '',
        status: 'published',
        teacher: 'all',
        visibleCount: {{ $courses->count() }},
        matches(title, teacherName, courseStatus) {
            const searchOk  = this.search.trim() === ''
                || title.toLowerCase().includes(this.search.toLowerCase().trim())
                || teacherName.toLowerCase().includes(this.search.toLowerCase().trim());
            const statusOk  = this.status  === 'all' || courseStatus === this.status;
            const teacherOk = this.teacher === 'all' || teacherName  === this.teacher;
            return searchOk && statusOk && teacherOk;
        },
        clearFilters() {
            this.search = ''; this.status = 'published'; this.teacher = 'all';
        }
    }"
    x-effect="$nextTick(() => {
        visibleCount = Array.from($el.querySelectorAll('[data-card]'))
            .filter(el => el.style.display !== 'none').length;
    })"
>

    {{-- Search + Status + Teacher --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6">

        <div class="relative flex-1 max-w-md">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2
                         text-outline text-[18px] pointer-events-none">search</span>
            <input
                type="search"
                x-model="search"
                placeholder="Search by course or teacher…"
                class="w-full pl-10 pr-4 py-2.5 bg-surface-white border border-outline-variant/60
                       rounded-[16px] text-sm placeholder:text-outline
                       focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"
            >
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            @foreach([['All','all'],['Published','published'],['Draft','draft']] as [$label,$val])
            <button
                @click="status = '{{ $val }}'"
                :class="{
                    'bg-primary text-on-primary': status === '{{ $val }}',
                    'bg-surface-white border border-outline-variant/60 text-on-surface-variant hover:bg-surface-container-low': status !== '{{ $val }}'
                }"
                class="px-3.5 py-1.5 rounded-full text-xs font-medium transition-colors cursor-pointer"
            >{{ $label }}</button>
            @endforeach

            @if($allTeachers->isNotEmpty())
            <div class="relative">
                <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2
                             text-outline text-[15px] pointer-events-none">person</span>
                <select x-model="teacher"
                        class="pl-8 pr-7 py-1.5 bg-surface-white border border-outline-variant/60
                               rounded-full text-xs font-medium text-on-surface-variant
                               appearance-none cursor-pointer
                               focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                    <option value="all">All Teachers</option>
                    @foreach($allTeachers as $t)
                    <option value="{{ $t->name }}">{{ $t->name }}</option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined absolute right-2 top-1/2 -translate-y-1/2
                             text-outline text-[14px] pointer-events-none">expand_more</span>
            </div>
            @endif
        </div>

    </div>


    {{-- ─── Cards / Empty States ─── --}}
    @if($courses->isEmpty())

        <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] py-20
                    flex flex-col items-center gap-4 text-center">
            <div class="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center">
                <span class="material-symbols-outlined text-outline text-[32px]">library_books</span>
            </div>
            <div>
                <p class="text-base font-semibold text-on-surface">No courses yet</p>
                <p class="text-sm text-on-surface-variant mt-1">Courses will appear here once teachers create them.</p>
            </div>
        </div>

    @else

        {{-- Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($courses as $course)
            @php
                $published   = $course->is_published ?? false;
                $teacherName = $course->teacher?->name ?? '—';
            @endphp
            <x-course-card
                :course="$course"
                :show-route="route('admin.courses.show', $course)"
                :delete-route="route('admin.courses.destroy', $course)"
                :show-teacher="true"
                data-card
                x-show="matches(@js($course->title), @js($teacherName), '{{ $published ? 'published' : 'draft' }}')"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
            />
            @endforeach
        </div>

        {{-- No filter results --}}
        <div
            x-show="visibleCount === 0"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="bg-surface-white border border-outline-variant/40 rounded-[20px] py-16
                   flex flex-col items-center gap-3 text-center"
        >
            <div class="w-14 h-14 rounded-full bg-surface-container flex items-center justify-center">
                <span class="material-symbols-outlined text-outline text-[28px]">search_off</span>
            </div>
            <p class="text-sm font-semibold text-on-surface">No courses match your filters</p>
            <p class="text-xs text-on-surface-variant">Try adjusting your search or clearing the filters.</p>
            <button
                @click="clearFilters()"
                class="mt-1 px-4 py-2 border border-outline-variant/60 text-primary text-sm
                       font-medium rounded-[24px] hover:bg-surface-container-low transition-colors cursor-pointer">
                Clear filters
            </button>
        </div>

    @endif

</div>

{{-- ─── Groups Modal ─── --}}
<div x-show="groupsOpen"
     x-cloak
     @keydown.escape.window="closeModal()"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="absolute inset-0 bg-black/40" @click="closeModal()"></div>

    <div class="relative w-full max-w-2xl bg-surface-white rounded-[20px] shadow-2xl max-h-[85vh] flex flex-col"
         x-show="groupsOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/20 shrink-0">
            <div>
                <h2 class="text-base font-semibold text-on-surface" style="font-family: var(--font-display);">
                    <span x-show="view === 'list'">All Course Groups</span>
                    <span x-show="view === 'edit'" x-cloak>Edit Group</span>
                </h2>
                <p x-show="view === 'list'" class="text-xs text-on-surface-variant mt-0.5">
                    {{ $groups->count() }} {{ Str::plural('group', $groups->count()) }} across all teachers
                </p>
            </div>
            <button type="button" @click="closeModal()"
                    class="w-8 h-8 flex items-center justify-center rounded-full
                           text-on-surface-variant hover:bg-surface-container
                           transition-colors duration-150 cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto">

            {{-- LIST view --}}
            <div x-show="view === 'list'">
                @if($groups->isEmpty())
                    <div class="py-16 flex flex-col items-center gap-4 text-center px-6">
                        <div class="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center">
                            <span class="material-symbols-outlined text-outline text-[32px]">folder_open</span>
                        </div>
                        <div>
                            <p class="text-base font-semibold text-on-surface">No groups yet</p>
                            <p class="text-sm text-on-surface-variant mt-1">
                                Course groups will appear here once teachers create them.
                            </p>
                        </div>
                    </div>
                @else
                    <ul class="divide-y divide-outline-variant/20">
                        @foreach($groups as $group)
                        <li class="flex items-center gap-3 px-6 py-4
                                   hover:bg-surface-container-low/40 transition-colors duration-150">
                            <div class="w-9 h-9 rounded-full bg-surface-container flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-outline text-[18px]">folder</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-on-surface truncate">{{ $group->name }}</p>
                                @if($group->description)
                                    <p class="text-xs text-on-surface-variant truncate mt-0.5">{{ $group->description }}</p>
                                @endif
                                <p class="text-[11px] text-outline mt-0.5">
                                    {{ $group->courses_count }} {{ Str::plural('course', $group->courses_count) }}
                                </p>
                            </div>
                            <div class="min-w-[120px] shrink-0">
                                <p class="text-xs font-medium text-on-surface-variant truncate">
                                    {{ $group->teacher?->name ?? '—' }}
                                </p>
                                <p class="text-[10px] text-outline mt-0.5">Teacher</p>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <button type="button"
                                        @click="editGroup({{ $group->id }}, @js($group->name), @js($group->description ?? ''))"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-primary
                                               hover:text-gold transition-colors duration-150 cursor-pointer">
                                    <span class="material-symbols-outlined text-[14px]">edit</span>
                                    Edit
                                </button>
                                <button type="button"
                                        onclick="confirmDelete({{ Js::from($group->name) }}, document.getElementById('admin-delete-group-form-{{ $group->id }}'), 'Courses in this group will become unassigned, not deleted.')"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-error
                                               hover:text-error/70 transition-colors duration-150 cursor-pointer">
                                    <span class="material-symbols-outlined text-[14px]">delete</span>
                                    Delete
                                </button>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- EDIT view --}}
            <form x-show="view === 'edit'" x-cloak
                  method="POST"
                  :action="'/admin/groups/' + editId"
                  class="p-6"
                  @submit.prevent="
                      editErrors.name = check(document.getElementById('admin-edit-group-name').value, ['required', 'maxLength:255']);
                      if (!editErrors.name) $el.submit();
                  ">
                @csrf
                <input type="hidden" name="_method" value="PATCH">

                <div class="mb-5">
                    <label for="admin-edit-group-name"
                           class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                        Group Name <span class="text-error">*</span>
                    </label>
                    <input id="admin-edit-group-name" type="text" name="name"
                           x-model="editName"
                           @blur="editErrors.name = check($event.target.value, ['required', 'maxLength:255'])"
                           :class="editErrors.name ? 'border-error' : 'border-outline-variant/60'"
                           class="w-full px-4 py-2.5 bg-surface-white border rounded-[16px] text-sm
                                  focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                    <p x-show="editErrors.name" x-text="editErrors.name" x-cloak
                       class="mt-1.5 text-xs text-error"></p>
                </div>

                <div class="mb-6">
                    <label for="admin-edit-group-desc"
                           class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                        Description
                        <span class="ml-1 text-[10px] font-normal normal-case text-outline">(optional)</span>
                    </label>
                    <textarea id="admin-edit-group-desc" name="description" rows="3"
                              x-model="editDesc"
                              class="w-full px-4 py-2.5 bg-surface-white border border-outline-variant/60
                                     rounded-[16px] text-sm placeholder:text-outline resize-none
                                     focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="button" @click="cancelEdit()"
                            class="px-5 py-2.5 border border-outline-variant/60 text-sm font-medium
                                   text-on-surface-variant rounded-[24px]
                                   hover:bg-surface-container-low hover:text-primary
                                   transition-colors duration-150 cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gold text-primary
                                   text-sm font-semibold rounded-[24px] hover:bg-gold/90
                                   active:scale-[0.96] transition-all duration-150 cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">save</span>
                        Save Changes
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

{{-- Standalone group delete forms --}}
@foreach($groups as $group)
<form id="admin-delete-group-form-{{ $group->id }}" method="POST"
      action="{{ route('admin.groups.destroy', $group->id) }}" class="hidden">
    @csrf
    @method('DELETE')
</form>
@endforeach

</div>{{-- end outer x-data --}}
@endsection
