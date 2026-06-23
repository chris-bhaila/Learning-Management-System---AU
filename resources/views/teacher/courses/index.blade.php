@extends('layouts.teacher')

@section('title', 'My Courses')

@section('topbar-actions')
    <a href="{{ Route::has('teacher.groups.index') ? route('teacher.groups.index') : '#' }}"
       class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2
              bg-surface-white border border-outline-variant/60 text-primary
              text-sm font-medium rounded-[24px]
              hover:bg-surface-container-low transition-colors cursor-pointer">
        <span class="material-symbols-outlined text-[18px]">folder_managed</span>
        Manage Groups
    </a>
    <a href="{{ Route::has('teacher.courses.create') ? route('teacher.courses.create') : '#' }}"
       class="inline-flex items-center gap-1.5 px-4 py-2
              bg-gold text-primary text-sm font-semibold rounded-[24px]
              hover:bg-gold/90 transition-colors cursor-pointer">
        <span class="material-symbols-outlined text-[18px]">add</span>
        <span class="hidden sm:inline">Add Course</span>
        <span class="sm:hidden">Add</span>
    </a>
@endsection

@section('content')
@php
    $courses   ??= collect();
    $allGroups = $courses->pluck('courseGroup')->filter()->unique('id')->values();
@endphp

{{-- ─── Page Header ─── --}}
<div>
    <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
        My Courses
    </h1>
    <p class="mt-1 text-sm text-on-surface-variant">
        {{ $courses->count() }} {{ Str::plural('course', $courses->count()) }} in your library
    </p>
</div>


{{-- ─── Filters + Grid ─── --}}
<div class="animate-fade-up"
    x-data="{
        search: '',
        status: 'published',
        group: 'all',
        visibleCount: {{ $courses->count() }},
        matches(title, courseStatus, courseGroup) {
            const searchOk = this.search.trim() === '' || title.toLowerCase().includes(this.search.toLowerCase().trim());
            const statusOk = this.status === 'all' || courseStatus === this.status;
            const groupOk  = this.group  === 'all' || courseGroup  === this.group;
            return searchOk && statusOk && groupOk;
        },
        clearFilters() {
            this.search = '';
            this.status = 'published';
            this.group  = 'all';
        }
    }"
    x-effect="$nextTick(() => {
        visibleCount = Array.from($el.querySelectorAll('[data-card]'))
            .filter(el => el.style.display !== 'none').length;
    })"
>

    {{-- Search + Status --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4">

        <div class="relative flex-1 max-w-md">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2
                         text-outline text-[18px] pointer-events-none">search</span>
            <input
                type="search"
                x-model="search"
                placeholder="Search courses…"
                class="w-full pl-10 pr-4 py-2.5 bg-surface-white border border-outline-variant/60
                       rounded-[16px] text-sm placeholder:text-outline
                       focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"
            >
        </div>

        <div class="flex items-center gap-2">
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
        </div>

    </div>

    {{-- Group filter chips --}}
    @if($allGroups->isNotEmpty())
        <div class="flex items-center gap-2 flex-wrap mt-3">

            <button
                @click="group = 'all'"
                :class="group === 'all'
                    ? 'bg-gold/20 text-primary border-gold/40'
                    : 'bg-surface-white border-outline-variant/60 text-on-surface-variant hover:bg-surface-container-low'"
                class="px-3 py-1 rounded-full text-xs font-medium border transition-colors cursor-pointer"
            >All</button>

            @foreach($allGroups as $g)
            <button
                @click="group = '{{ $g->id }}'"
                :class="group === '{{ $g->id }}'
                    ? 'bg-gold/20 text-primary border-gold/40'
                    : 'bg-surface-white border-outline-variant/60 text-on-surface-variant hover:bg-surface-container-low'"
                class="px-3 py-1 rounded-full text-xs font-medium border transition-colors cursor-pointer"
            >{{ $g->name }}</button>
            @endforeach

            <button
                @click="group = 'none'"
                :class="group === 'none'
                    ? 'bg-gold/20 text-primary border-gold/40'
                    : 'bg-surface-white border-outline-variant/60 text-on-surface-variant hover:bg-surface-container-low'"
                class="px-3 py-1 rounded-full text-xs font-medium border transition-colors cursor-pointer"
            >No group</button>
        </div>
    @endif


    {{-- ─── Cards / Empty States ─── --}}
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
            <a href="{{ Route::has('teacher.courses.create') ? route('teacher.courses.create') : '#' }}"
               class="mt-1 inline-flex items-center gap-1.5 px-5 py-2.5 bg-gold text-primary
                      text-sm font-semibold rounded-[24px] hover:bg-gold/90 transition-colors cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Add Course
            </a>
        </div>

    @else

        {{-- Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($courses as $course)
            @php
                $published = $course->is_published ?? false;
                $groupId   = $course->courseGroup?->id ? (string) $course->courseGroup->id : 'none';
                $groupName = $course->courseGroup?->name ?? null;
                $students  = $course->students_count ?? 0;
                $units     = $course->units_count ?? 0;
                $progress  = min($course->avg_progress ?? 0, 100);
            @endphp
            <div
                data-card
                x-show="matches(@js($course->title), '{{ $published ? 'published' : 'draft' }}', '{{ $groupId }}')"
                class="bg-surface-white border border-outline-variant/40 rounded-[20px] p-5 flex flex-col gap-4
                       shadow-[0px_2px_8px_rgba(30,42,74,0.06)] hover:shadow-[0px_8px_24px_rgba(30,42,74,0.12)] hover:-translate-y-0.5 transition-all duration-200"
            >
                {{-- Header: title + status chip --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        @if($groupName)
                            <span class="inline-block mb-1.5 px-2 py-0.5 rounded-full bg-surface-container
                                         text-[10px] font-semibold tracking-wide text-on-surface-variant uppercase">
                                {{ $groupName }}
                            </span>
                        @endif
                        <h3 class="font-semibold text-primary leading-snug"
                            style="font-family: var(--font-display);">
                            {{ $course->title }}
                        </h3>
                    </div>

                    <span class="shrink-0 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium
                                 {{ $published ? 'bg-gold/20 text-on-gold' : 'bg-surface-container text-on-surface-variant' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $published ? 'bg-gold' : 'bg-outline-variant' }}"></span>
                        {{ $published ? 'Published' : 'Draft' }}
                    </span>
                </div>

                {{-- Stats --}}
                <div class="flex items-center gap-5 text-sm text-on-surface-variant">
                    <span class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">group</span>
                        {{ $students }} {{ Str::plural('student', $students) }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">menu_book</span>
                        {{ $units }} {{ Str::plural('unit', $units) }}
                    </span>
                </div>

                {{-- Progress bar --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-xs text-on-surface-variant">Avg. progress</span>
                        <span class="text-xs font-medium text-on-surface-variant">{{ $progress }}%</span>
                    </div>
                    <div class="h-1.5 bg-surface-container rounded-full overflow-hidden">
                        <div class="h-full bg-gold rounded-full" style="width: {{ $progress }}%"></div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2 pt-1 border-t border-outline-variant/20">
                    <a href="{{ Route::has('teacher.courses.show') ? route('teacher.courses.show', $course) : '#' }}"
                       class="flex-1 flex items-center justify-center gap-1.5 py-2 bg-gold text-primary
                              text-sm font-semibold rounded-[24px] hover:bg-gold/90 transition-colors cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                        View Course
                    </a>
                    <a href="{{ Route::has('teacher.courses.edit') ? route('teacher.courses.edit', $course) : '#' }}"
                       class="flex items-center justify-center px-3.5 py-2
                              border border-outline-variant/60 text-on-surface-variant rounded-[24px]
                              hover:bg-surface-container-low hover:text-primary transition-colors cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">edit</span>
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        {{-- No filter results --}}
        <div
            x-show="visibleCount === 0"
            x-cloak
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
@endsection
