@extends('layouts.admin')

@section('title', 'All Courses')

@section('content')
@php
    $courses     ??= collect();
    $allTeachers = $courses->pluck('teacher')->filter()->unique('id')->values();
@endphp

{{-- ─── Flash ─── --}}
@if(session('success'))
    <div
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 4000)"
        x-transition:leave="transition duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="flex items-center gap-3 px-4 py-3 bg-surface-container-low border border-outline-variant/40
               rounded-[16px] text-sm text-on-surface"
    >
        <span class="material-symbols-outlined text-[20px] text-gold shrink-0">check_circle</span>
        {{ session('success') }}
        <button type="button" @click="show = false"
                class="ml-auto text-outline hover:text-on-surface transition-colors cursor-pointer">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
    </div>
@endif

{{-- ─── Page Header ─── --}}
<div class="flex items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
            All Courses
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            {{ $courses->count() }} {{ Str::plural('course', $courses->count()) }} across all teachers
        </p>
    </div>

    <a href="{{ route('admin.courses.create') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 bg-gold text-primary
              text-sm font-semibold rounded-[24px] hover:bg-gold/90 transition-colors
              cursor-pointer shrink-0">
        <span class="material-symbols-outlined text-[18px]">add</span>
        New Course
    </a>
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

    {{-- Search + Status --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4">

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

    {{-- Teacher filter chips --}}
    @if($allTeachers->isNotEmpty())
        <div class="flex items-center gap-2 flex-wrap mt-3 mb-2">

            <button
                @click="teacher = 'all'"
                :class="teacher === 'all'
                    ? 'bg-gold/20 text-primary border-gold/40'
                    : 'bg-surface-white border-outline-variant/60 text-on-surface-variant hover:bg-surface-container-low'"
                class="px-3 py-1 rounded-full text-xs font-medium border transition-colors cursor-pointer"
            >All</button>

            @foreach($allTeachers as $t)
            <button
                @click="teacher = @js($t->name)"
                :class="teacher === @js($t->name)
                    ? 'bg-gold/20 text-primary border-gold/40'
                    : 'bg-surface-white border-outline-variant/60 text-on-surface-variant hover:bg-surface-container-low'"
                class="px-3 py-1 rounded-full text-xs font-medium border transition-colors cursor-pointer"
            >{{ $t->name }}</button>
            @endforeach
        </div>
    @endif


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
                $groupName   = $course->courseGroup?->name ?? null;
                $students    = $course->students_count ?? 0;
                $units       = $course->units_count ?? 0;
                $progress    = min($course->avg_progress ?? 0, 100);
            @endphp
            <div
                data-card
                x-show="matches(@js($course->title), @js($teacherName), '{{ $published ? 'published' : 'draft' }}')"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
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

                {{-- Teacher --}}
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-6 h-6 rounded-full bg-primary-container flex items-center justify-center shrink-0">
                        <span class="text-[10px] font-semibold text-on-primary"
                              style="font-family: var(--font-display);">
                            {{ strtoupper(substr($teacherName, 0, 2)) }}
                        </span>
                    </div>
                    <span class="text-sm text-on-surface-variant truncate">{{ $teacherName }}</span>
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
                        <div class="h-full bg-gold rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2 pt-1 border-t border-outline-variant/20">
                    <a href="{{ Route::has('admin.courses.show') ? route('admin.courses.show', $course) : '#' }}"
                       class="flex-1 flex items-center justify-center gap-1.5 py-2 bg-gold text-primary
                              text-sm font-semibold rounded-[24px] hover:bg-gold/90 transition-colors cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                        View Course
                    </a>
                    <a href="{{ Route::has('admin.courses.edit') ? route('admin.courses.edit', $course) : '#' }}"
                       class="flex items-center justify-center px-3.5 py-2
                              border border-outline-variant/60 text-on-surface-variant rounded-[24px]
                              hover:bg-surface-container-low hover:text-primary transition-colors cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">edit</span>
                    </a>
                    <form method="POST"
                          action="{{ Route::has('admin.courses.destroy') ? route('admin.courses.destroy', $course) : '#' }}"
                          onsubmit="return confirm('Delete \'{{ addslashes($course->title) }}\'? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="flex items-center justify-center px-3.5 py-2
                                       border border-error/40 text-error rounded-[24px]
                                       hover:bg-error/5 transition-colors cursor-pointer">
                            <span class="material-symbols-outlined text-[16px]">delete</span>
                        </button>
                    </form>
                </div>
            </div>
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
@endsection
