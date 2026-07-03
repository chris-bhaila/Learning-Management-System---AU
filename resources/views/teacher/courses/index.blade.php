@extends('layouts.teacher')

@section('title', 'My Courses')

@section('content')
@php
    $courses          ??= collect();
    $recentCourses    ??= collect();
    $coursesByGroup   ??= collect();
    $ungroupedCourses ??= collect();
    $groups           ??= collect();
@endphp

<div x-data="{
    groupsOpen: false,
    view: 'list',
    editId: null,
    editName: '',
    editDesc: '',
    addErrors: { name: '', desc: '' },
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
        this.$nextTick(() => document.getElementById('edit-group-name')?.focus());
    },

    cancelAdd()  { this.view = 'list'; this.addErrors  = { name: '', desc: '' }; },
    cancelEdit() { this.view = 'list'; this.editId = null; this.editErrors = { name: '' }; },
    closeModal() { this.groupsOpen = false; this.view = 'list'; this.editId = null; },
}">

{{-- ─── Page Header ─── --}}
<div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3 lg:gap-4 mb-8">
    <div class="min-w-0">
        <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
            My Courses
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            {{ $courses->count() }} {{ Str::plural('course', $courses->count()) }} in your library
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
        <a href="{{ route('teacher.courses.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-gold text-primary
                  text-sm font-semibold rounded-[24px] hover:bg-gold/90 transition-colors
                  duration-150 cursor-pointer">
            <span class="material-symbols-outlined text-[18px]">add</span>
            New Course
        </a>
    </div>
</div>


@if($courses->isEmpty())

{{-- No courses at all --}}
<div class="bg-surface-white border border-outline-variant/40 rounded-[20px] py-20
            flex flex-col items-center gap-4 text-center">
    <div class="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center">
        <span class="material-symbols-outlined text-outline text-[32px]">library_books</span>
    </div>
    <div>
        <p class="text-base font-semibold text-on-surface">No courses yet</p>
        <p class="text-sm text-on-surface-variant mt-1">Create your first course to get started.</p>
    </div>
    <a href="{{ route('teacher.courses.create') }}"
       class="mt-1 inline-flex items-center gap-1.5 px-5 py-2.5 bg-gold text-primary
              text-sm font-semibold rounded-[24px] hover:bg-gold/90 transition-colors cursor-pointer">
        <span class="material-symbols-outlined text-[18px]">add</span>
        Add Course
    </a>
</div>

@else

{{-- ─── Sections ─── --}}
<div class="animate-fade-up"
     x-data="{
         search: '',
         status: 'all',
         openSections: {},
         visibleCount: {{ $courses->count() }},

         matches(title, courseStatus) {
             const searchOk = this.search.trim() === ''
                 || title.toLowerCase().includes(this.search.toLowerCase().trim());
             const statusOk = this.status === 'all' || courseStatus === this.status;
             return searchOk && statusOk;
         },

         isOpen(id)  { return this.openSections[id] !== false; },
         toggle(id)  { this.openSections[id] = !this.isOpen(id); },
         clearFilters() { this.search = ''; this.status = 'all'; },
     }"
     x-effect="$nextTick(() => {
         visibleCount = Array.from($el.querySelectorAll('[data-card]'))
             .filter(el => el.style.display !== 'none').length;
     })"
>

    {{-- Search + Status filter bar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-8">
        <div class="relative flex-1 max-w-md">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2
                         text-outline text-[18px] pointer-events-none">search</span>
            <input type="search" x-model="search"
                   placeholder="Search courses…"
                   class="w-full pl-10 pr-4 py-2.5 bg-surface-white border border-outline-variant/60
                          rounded-[16px] text-sm placeholder:text-outline
                          focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
        </div>
        <div class="flex items-center gap-2">
            @foreach([['All','all'],['Published','published'],['Draft','draft']] as [$label,$val])
            <button @click="status = '{{ $val }}'"
                    :class="{
                        'bg-primary text-on-primary': status === '{{ $val }}',
                        'bg-surface-white border border-outline-variant/60 text-on-surface-variant hover:bg-surface-container-low': status !== '{{ $val }}'
                    }"
                    class="px-3.5 py-1.5 rounded-full text-xs font-medium transition-colors cursor-pointer">
                {{ $label }}
            </button>
            @endforeach
        </div>
    </div>

    {{-- ─── Recently Added ─── --}}
    @if($recentCourses->isNotEmpty())
    <section class="mb-10">
        <div class="flex items-center gap-2 mb-4">
            <span class="material-symbols-outlined text-[18px] text-gold">schedule</span>
            <h2 class="text-sm font-semibold text-on-surface" style="font-family: var(--font-display);">
                Recently Added
            </h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($recentCourses as $course)
            @php $published = $course->is_published ?? false; @endphp
            <x-course-card
                :course="$course"
                :show-route="route('teacher.courses.show', $course)"
                :delete-route="route('teacher.courses.destroy', $course)"
                :show-teacher="false"
                data-card
                x-show="matches({{ Js::from($course->title) }}, '{{ $published ? 'published' : 'draft' }}')"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
            />
            @endforeach
        </div>
    </section>
    @endif

    {{-- ─── Group sections ─── --}}
    @foreach($coursesByGroup as $groupId => $groupCourses)
    @php
        $sectionId = 'group-' . $groupId;
        $groupObj  = $groupCourses->first()->group;
    @endphp
    <section class="mb-6">
        <button type="button"
                @click="toggle('{{ $sectionId }}')"
                class="w-full flex items-center justify-between gap-3 py-3 px-1
                       border-b border-outline-variant/30 hover:border-outline-variant/60
                       transition-colors duration-150 cursor-pointer">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="material-symbols-outlined text-[18px] text-outline shrink-0">folder</span>
                <span class="text-sm font-semibold text-on-surface truncate min-w-0"
                      style="font-family: var(--font-display);">
                    {{ $groupObj->name }}
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full
                             bg-surface-container text-[11px] font-medium text-on-surface-variant shrink-0">
                    {{ $groupCourses->count() }}
                </span>
            </div>
            <span class="material-symbols-outlined text-[18px] text-outline shrink-0
                         transition-transform duration-200"
                  :class="{ '-rotate-90': !isOpen('{{ $sectionId }}') }">
                expand_more
            </span>
        </button>

        <div x-show="isOpen('{{ $sectionId }}')"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="pt-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                @foreach($groupCourses as $course)
                @php $published = $course->is_published ?? false; @endphp
                <x-course-card
                    :course="$course"
                    :show-route="route('teacher.courses.show', $course)"
                    :delete-route="route('teacher.courses.destroy', $course)"
                    :show-teacher="false"
                    data-card
                    x-show="matches({{ Js::from($course->title) }}, '{{ $published ? 'published' : 'draft' }}')"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                />
                @endforeach
            </div>
        </div>
    </section>
    @endforeach

    {{-- ─── Ungrouped ─── --}}
    @if($ungroupedCourses->isNotEmpty())
    <section class="mb-6">
        <button type="button"
                @click="toggle('ungrouped')"
                class="w-full flex items-center justify-between gap-3 py-3 px-1
                       border-b border-outline-variant/30 hover:border-outline-variant/60
                       transition-colors duration-150 cursor-pointer">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="material-symbols-outlined text-[18px] text-outline shrink-0">folder_off</span>
                <span class="text-sm font-semibold text-on-surface-variant truncate min-w-0"
                      style="font-family: var(--font-display);">
                    Ungrouped
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full
                             bg-surface-container text-[11px] font-medium text-on-surface-variant shrink-0">
                    {{ $ungroupedCourses->count() }}
                </span>
            </div>
            <span class="material-symbols-outlined text-[18px] text-outline shrink-0
                         transition-transform duration-200"
                  :class="{ '-rotate-90': !isOpen('ungrouped') }">
                expand_more
            </span>
        </button>

        <div x-show="isOpen('ungrouped')"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="pt-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                @foreach($ungroupedCourses as $course)
                @php $published = $course->is_published ?? false; @endphp
                <x-course-card
                    :course="$course"
                    :show-route="route('teacher.courses.show', $course)"
                    :delete-route="route('teacher.courses.destroy', $course)"
                    :show-teacher="false"
                    data-card
                    x-show="matches({{ Js::from($course->title) }}, '{{ $published ? 'published' : 'draft' }}')"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                />
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- No filter results --}}
    <div x-show="visibleCount === 0" x-cloak
         class="bg-surface-white border border-outline-variant/40 rounded-[20px] py-16
                flex flex-col items-center gap-3 text-center">
        <div class="w-14 h-14 rounded-full bg-surface-container flex items-center justify-center">
            <span class="material-symbols-outlined text-outline text-[28px]">search_off</span>
        </div>
        <p class="text-sm font-semibold text-on-surface">No courses match your filters</p>
        <p class="text-xs text-on-surface-variant">Try adjusting your search or clearing the filters.</p>
        <button @click="clearFilters()"
                class="mt-1 px-4 py-2 border border-outline-variant/60 text-primary text-sm
                       font-medium rounded-[24px] hover:bg-surface-container-low transition-colors cursor-pointer">
            Clear filters
        </button>
    </div>

</div>{{-- end sections x-data --}}

@endif

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

    <div class="relative w-full max-w-xl bg-surface-white rounded-[20px] shadow-2xl max-h-[85vh] flex flex-col"
         x-show="groupsOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 sm:px-6 py-4 sm:py-5 border-b border-outline-variant/20 shrink-0">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-on-surface truncate" style="font-family: var(--font-display);">
                    <span x-show="view === 'list'">Course Groups</span>
                    <span x-show="view === 'add'" x-cloak>New Group</span>
                    <span x-show="view === 'edit'" x-cloak>Edit Group</span>
                </h2>
                <p x-show="view === 'list'" class="text-xs text-on-surface-variant mt-0.5">
                    {{ $groups->count() }} {{ Str::plural('group', $groups->count()) }}
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button x-show="view === 'list'"
                        type="button"
                        @click="view = 'add'; $nextTick(() => document.getElementById('add-group-name')?.focus())"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-gold text-primary
                               text-xs font-semibold rounded-[24px] hover:bg-gold/90
                               active:scale-[0.96] transition-all duration-150 cursor-pointer whitespace-nowrap">
                    <span class="material-symbols-outlined text-[14px]">add</span>
                    Add Group
                </button>
                <button type="button" @click="closeModal()"
                        class="w-8 h-8 flex items-center justify-center rounded-full shrink-0
                               text-on-surface-variant hover:bg-surface-container
                               transition-colors duration-150 cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto">

            {{-- LIST view --}}
            <div x-show="view === 'list'">
                @if($groups->isEmpty())
                    <div class="py-16 flex flex-col items-center gap-4 text-center px-4 sm:px-6">
                        <div class="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center">
                            <span class="material-symbols-outlined text-outline text-[32px]">folder_open</span>
                        </div>
                        <div>
                            <p class="text-base font-semibold text-on-surface">No groups yet</p>
                            <p class="text-sm text-on-surface-variant mt-1">
                                Create a group to organise your courses by class, subject, or grade.
                            </p>
                        </div>
                        <button type="button"
                                @click="view = 'add'; $nextTick(() => document.getElementById('add-group-name')?.focus())"
                                class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-gold text-primary
                                       text-sm font-semibold rounded-[24px] hover:bg-gold/90
                                       transition-colors duration-150 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">add</span>
                            Create First Group
                        </button>
                    </div>
                @else
                    <ul class="divide-y divide-outline-variant/20">
                        @foreach($groups as $group)
                        <li class="flex flex-col sm:flex-row sm:items-center gap-3 px-4 sm:px-6 py-4
                                   hover:bg-surface-container-low/40 transition-colors duration-150">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
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
                            </div>
                            <div class="flex items-center gap-3 shrink-0 pl-12 sm:pl-0">
                                <button type="button"
                                        @click="editGroup({{ $group->id }}, @js($group->name), @js($group->description ?? ''))"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-primary
                                               hover:text-gold transition-colors duration-150 cursor-pointer">
                                    <span class="material-symbols-outlined text-[14px]">edit</span>
                                    Edit
                                </button>
                                <button type="button"
                                        onclick="confirmDelete({{ Js::from($group->name) }}, document.getElementById('delete-group-form-{{ $group->id }}'), 'Courses in this group will become unassigned, not deleted.')"
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

            {{-- ADD view --}}
            <form x-show="view === 'add'" x-cloak
                  method="POST" action="{{ route('teacher.groups.store') }}"
                  class="p-4 sm:p-6"
                  @submit.prevent="
                      addErrors.name = check(document.getElementById('add-group-name').value, ['required', 'maxLength:255']);
                      addErrors.desc = check(document.getElementById('add-group-desc').value, ['maxLength:1000']);
                      if (!addErrors.name && !addErrors.desc) $el.submit();
                  ">
                @csrf

                <div class="mb-5">
                    <label for="add-group-name"
                           class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                        Group Name <span class="text-error">*</span>
                    </label>
                    <input id="add-group-name" type="text" name="name"
                           @blur="addErrors.name = check($event.target.value, ['required', 'maxLength:255'])"
                           :class="addErrors.name ? 'border-error' : 'border-outline-variant/60'"
                           class="w-full px-4 py-2.5 bg-surface-white border rounded-[16px] text-sm
                                  placeholder:text-outline
                                  focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                           placeholder="e.g. Year 10, Science, Period 3">
                    <p x-show="addErrors.name" x-text="addErrors.name" x-cloak
                       class="mt-1.5 text-xs text-error"></p>
                </div>

                <div class="mb-6">
                    <label for="add-group-desc"
                           class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                        Description
                        <span class="ml-1 text-[10px] font-normal normal-case text-outline">(optional)</span>
                    </label>
                    <textarea id="add-group-desc" name="description" rows="3"
                              @blur="addErrors.desc = check($event.target.value, ['maxLength:1000'])"
                              :class="addErrors.desc ? 'border-error' : 'border-outline-variant/60'"
                              class="w-full px-4 py-2.5 bg-surface-white border rounded-[16px] text-sm
                                     placeholder:text-outline resize-none
                                     focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                              placeholder="Optional — e.g. Tuesday/Thursday afternoon block"></textarea>
                    <p x-show="addErrors.desc" x-text="addErrors.desc" x-cloak
                       class="mt-1.5 text-xs text-error"></p>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3">
                    <button type="button" @click="cancelAdd()"
                            class="w-full sm:w-auto px-5 py-2.5 border border-outline-variant/60 text-sm font-medium
                                   text-on-surface-variant rounded-[24px]
                                   hover:bg-surface-container-low hover:text-primary
                                   transition-colors duration-150 cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gold text-primary
                                   text-sm font-semibold rounded-[24px] hover:bg-gold/90
                                   active:scale-[0.96] transition-all duration-150 cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">save</span>
                        Save Group
                    </button>
                </div>
            </form>

            {{-- EDIT view --}}
            <form x-show="view === 'edit'" x-cloak
                  method="POST"
                  :action="'/teacher/groups/' + editId"
                  class="p-4 sm:p-6"
                  @submit.prevent="
                      editErrors.name = check(document.getElementById('edit-group-name').value, ['required', 'maxLength:255']);
                      if (!editErrors.name) $el.submit();
                  ">
                @csrf
                <input type="hidden" name="_method" value="PATCH">

                <div class="mb-5">
                    <label for="edit-group-name"
                           class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                        Group Name <span class="text-error">*</span>
                    </label>
                    <input id="edit-group-name" type="text" name="name"
                           x-model="editName"
                           @blur="editErrors.name = check($event.target.value, ['required', 'maxLength:255'])"
                           :class="editErrors.name ? 'border-error' : 'border-outline-variant/60'"
                           class="w-full px-4 py-2.5 bg-surface-white border rounded-[16px] text-sm
                                  focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                    <p x-show="editErrors.name" x-text="editErrors.name" x-cloak
                       class="mt-1.5 text-xs text-error"></p>
                </div>

                <div class="mb-6">
                    <label for="edit-group-desc"
                           class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                        Description
                        <span class="ml-1 text-[10px] font-normal normal-case text-outline">(optional)</span>
                    </label>
                    <textarea id="edit-group-desc" name="description" rows="3"
                              x-model="editDesc"
                              class="w-full px-4 py-2.5 bg-surface-white border border-outline-variant/60
                                     rounded-[16px] text-sm placeholder:text-outline resize-none
                                     focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"></textarea>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3">
                    <button type="button" @click="cancelEdit()"
                            class="w-full sm:w-auto px-5 py-2.5 border border-outline-variant/60 text-sm font-medium
                                   text-on-surface-variant rounded-[24px]
                                   hover:bg-surface-container-low hover:text-primary
                                   transition-colors duration-150 cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gold text-primary
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
<form id="delete-group-form-{{ $group->id }}" method="POST"
      action="{{ route('teacher.groups.destroy', $group->id) }}" class="hidden">
    @csrf
    @method('DELETE')
</form>
@endforeach

</div>{{-- end outer x-data --}}
@endsection
