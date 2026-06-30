@extends('layouts.student')

@section('title', 'Dashboard')

@section('content')

{{-- ─── Page Header ─── --}}
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
            Welcome back, {{ auth()->user()?->name ?? 'Student' }}
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            {{ now()->format('l, d M Y') }}
        </p>
    </div>
    <x-button type="button" variant="primary" icon="vpn_key" onclick="openEnrollModal()">
        Enroll with Token
    </x-button>
</div>

{{-- ─── Stats ─── --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-5 animate-fade-up">

    <x-card class="p-5 flex items-start gap-4">
        <div class="w-11 h-11 rounded-xl bg-surface-container flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-primary text-[22px]">school</span>
        </div>
        <div class="min-w-0">
            <p class="text-[11px] font-semibold tracking-widest text-outline uppercase">Enrolled Courses</p>
            <p class="text-2xl font-bold text-primary mt-0.5" style="font-family: var(--font-display);">
                {{ $courses->count() }}
            </p>
            <p class="text-xs text-on-surface-variant mt-0.5">Active enrollments</p>
        </div>
    </x-card>

    <x-card class="p-5 flex items-start gap-4">
        <div class="w-11 h-11 rounded-xl bg-gold/20 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-[22px]" style="color: var(--color-on-gold);">menu_book</span>
        </div>
        <div class="min-w-0">
            <p class="text-[11px] font-semibold tracking-widest text-outline uppercase">Total Units</p>
            <p class="text-2xl font-bold text-primary mt-0.5" style="font-family: var(--font-display);">
                {{ $courses->sum('units_count') }}
            </p>
            <p class="text-xs text-on-surface-variant mt-0.5">Across all your courses</p>
        </div>
    </x-card>

</div>


{{-- ─── Recent Courses ─── --}}
<div class="animate-fade-up">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-semibold text-on-surface" style="font-family: var(--font-display);">
            My Courses
        </h2>
        @if($courses->count() > 3)
            <a href="{{ route('student.courses.index') }}"
               class="text-xs font-medium text-on-surface-variant hover:text-primary
                      transition-colors flex items-center gap-1 cursor-pointer">
                View all {{ $courses->count() }}
                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </a>
        @endif
    </div>

    @if($courses->isEmpty())

        <x-card class="py-16 flex flex-col items-center gap-3 text-center">
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

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($courses->take(3) as $course)
                <a href="{{ route('student.courses.show', $course->id) }}"
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
                                  group-hover:text-primary transition-colors line-clamp-2">
                            {{ $course->title }}
                        </p>
                        <p class="text-xs text-on-surface-variant mt-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[13px]">person</span>
                            {{ $course->teacher?->name ?? '—' }}
                        </p>
                    </div>

                    {{-- Meta row --}}
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-outline">
                            {{ $course->units_count }} {{ Str::plural('unit', $course->units_count) }}
                        </span>
                        <span class="material-symbols-outlined text-[16px] text-outline
                                     group-hover:text-primary transition-colors">
                            arrow_forward
                        </span>
                    </div>
                </a>
            @endforeach
        </div>

        @if($courses->count() > 3)
            <div class="mt-4 text-center">
                <a href="{{ route('student.courses.index') }}"
                   class="inline-flex items-center gap-1.5 text-sm font-medium text-on-surface-variant
                          hover:text-primary transition-colors cursor-pointer">
                    View all {{ $courses->count() }} courses
                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </a>
            </div>
        @endif

    @endif
</div>

@endsection

@include('student._enroll_modal')
