@extends('layouts.student')

@section('title', 'My Courses')

@section('content')

{{-- ─── Page Header ─── --}}
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
            My Courses
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            {{ $courses->count() }} {{ Str::plural('course', $courses->count()) }} enrolled
        </p>
    </div>
    <x-button type="button" variant="primary" icon="vpn_key" onclick="openEnrollModal()">
        Enroll with Token
    </x-button>
</div>

@if($courses->isEmpty())

    {{-- ─── Empty State ─── --}}
    <x-card class="py-16 flex flex-col items-center gap-3 text-center animate-fade-up">
        <div class="w-14 h-14 rounded-full bg-surface-container flex items-center justify-center animate-float">
            <span class="material-symbols-outlined text-outline text-[28px]">menu_book</span>
        </div>
        <p class="text-sm font-semibold text-on-surface">No courses yet</p>
        <p class="text-xs text-on-surface-variant max-w-xs">
            Ask your teacher for an enrollment token to join a class and access courses.
        </p>
        <x-button type="button" variant="primary" icon="vpn_key"
                  class="mt-2" onclick="openEnrollModal()">
            Enroll with Token
        </x-button>
    </x-card>

@else

    @php
        $courseData = $courses->map(fn($c) => [
            'id'          => $c->id,
            'title'       => $c->title,
            'teacher'     => $c->teacher?->name ?? '—',
            'units_count' => (int) $c->units_count,
            'show_url'    => route('student.courses.show', $c->id),
        ])->values()->toArray();
    @endphp

    <div
        x-data="{
            search: '',
            courses: @js($courseData),
            get filtered() {
                const q = this.search.toLowerCase().trim();
                if (!q) return this.courses;
                return this.courses.filter(c =>
                    c.title.toLowerCase().includes(q) ||
                    c.teacher.toLowerCase().includes(q)
                );
            },
        }"
        class="space-y-5 animate-fade-up"
    >

        {{-- ─── Search ─── --}}
        <x-card class="p-4">
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2
                             text-outline text-[18px] pointer-events-none">search</span>
                <input
                    type="search"
                    x-model.debounce.150ms="search"
                    placeholder="Search by course title or teacher…"
                    class="w-full pl-10 pr-4 py-2.5 bg-surface-container-low rounded-[16px] text-sm
                           border border-outline-variant/60 placeholder:text-outline
                           focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary
                           transition-colors"
                >
            </div>
        </x-card>

        {{-- ─── Course Grid ─── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
            <template x-for="c in filtered" :key="c.id">
                <a :href="c.show_url"
                   class="bg-surface-white border border-outline-variant/40 rounded-[20px] p-5
                          flex flex-col gap-3 min-w-0
                          shadow-[0px_2px_8px_rgba(30,42,74,0.06)]
                          hover:shadow-[0px_8px_24px_rgba(30,42,74,0.12)] hover:-translate-y-0.5
                          transition-all duration-200 group cursor-pointer">

                    {{-- Course icon --}}
                    <div class="w-10 h-10 rounded-xl bg-gold/20
                                flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-primary text-[20px]">library_books</span>
                    </div>

                    {{-- Title + teacher --}}
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-on-surface text-sm leading-snug
                                  group-hover:text-primary transition-colors"
                           x-text="c.title"
                           style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        </p>
                        <p class="text-xs text-on-surface-variant mt-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[13px]">person</span>
                            <span x-text="c.teacher"></span>
                        </p>
                    </div>

                    {{-- Meta row --}}
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-outline"
                              x-text="c.units_count + ' ' + (c.units_count === 1 ? 'unit' : 'units')">
                        </span>
                        <span class="material-symbols-outlined text-[16px] text-outline
                                     group-hover:text-primary transition-colors">
                            arrow_forward
                        </span>
                    </div>
                </a>
            </template>
        </div>

        {{-- ─── No search results ─── --}}
        <div x-show="search.trim() && filtered.length === 0"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="py-12 text-center"
             x-cloak>
            <x-card class="py-12 flex flex-col items-center gap-2">
                <span class="material-symbols-outlined text-[28px] text-outline">search_off</span>
                <p class="text-sm text-on-surface-variant mt-1">
                    No courses match "<span x-text="search" class="font-medium text-on-surface"></span>"
                </p>
            </x-card>
        </div>

    </div>

@endif

@endsection

@include('student._enroll_modal')
