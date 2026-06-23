@extends('layouts.student')

@section('title', 'Dashboard')

@section('nav-items')
    <a href="{{ route('student.dashboard') }}"
       class="nav-item {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
        <span class="material-symbols-outlined text-[20px]">dashboard</span>
        Dashboard
    </a>

    <a href="{{ Route::has('student.courses.index') ? route('student.courses.index') : '#' }}"
       class="nav-item {{ request()->routeIs('student.courses.*') ? 'active' : '' }}">
        <span class="material-symbols-outlined text-[20px]">menu_book</span>
        My Courses
    </a>

    <a href="{{ Route::has('student.notifications.index') ? route('student.notifications.index') : '#' }}"
       class="nav-item {{ request()->routeIs('student.notifications.*') ? 'active' : '' }}">
        <span class="material-symbols-outlined text-[20px]">notifications</span>
        Notifications
    </a>
@endsection

@section('content')
@php
    // Fallbacks — controller will pass real values once backend is wired
    $enrolledCourses   ??= collect();
    $stats ??= [
        'enrolled'   => 0,
        'completed'  => 0,
        'in_progress' => 0,
    ];
@endphp

{{-- ─── Page Header ─── --}}
<div class="flex items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
            Welcome back, {{ auth()->user()?->name ?? 'Student' }}
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">You're all caught up. Keep up the great work!</p>
    </div>
    <span class="text-xs text-outline bg-surface-container px-3 py-1.5 rounded-full shrink-0">
        {{ now()->format('D, d M Y') }}
    </span>
</div>


{{-- ─── Stat Cards ─── --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 animate-fade-up">

    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] p-5 flex items-start gap-4
                shadow-[0px_2px_8px_rgba(30,42,74,0.06)] hover:shadow-[0px_8px_24px_rgba(30,42,74,0.10)] transition-shadow">
        <div class="w-11 h-11 rounded-xl bg-surface-container flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-primary text-[22px]">school</span>
        </div>
        <div class="min-w-0">
            <p class="text-[11px] font-semibold tracking-widest text-outline uppercase">Enrolled</p>
            <p class="text-2xl font-bold text-primary mt-0.5" style="font-family: var(--font-display);">
                {{ $stats['enrolled'] }}
            </p>
            <p class="text-xs text-on-surface-variant mt-0.5">Active courses</p>
        </div>
    </div>

    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] p-5 flex items-start gap-4
                shadow-[0px_2px_8px_rgba(30,42,74,0.06)] hover:shadow-[0px_8px_24px_rgba(30,42,74,0.10)] transition-shadow">
        <div class="w-11 h-11 rounded-xl bg-gold/20 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-[22px]" style="color: var(--color-on-gold);">trending_up</span>
        </div>
        <div class="min-w-0">
            <p class="text-[11px] font-semibold tracking-widest text-outline uppercase">In Progress</p>
            <p class="text-2xl font-bold text-primary mt-0.5" style="font-family: var(--font-display);">
                {{ $stats['in_progress'] }}
            </p>
            <p class="text-xs text-on-surface-variant mt-0.5">Units underway</p>
        </div>
    </div>

    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] p-5 flex items-start gap-4
                shadow-[0px_2px_8px_rgba(30,42,74,0.06)] hover:shadow-[0px_8px_24px_rgba(30,42,74,0.10)] transition-shadow">
        <div class="w-11 h-11 rounded-xl bg-surface-container flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-primary text-[22px]">task_alt</span>
        </div>
        <div class="min-w-0">
            <p class="text-[11px] font-semibold tracking-widest text-outline uppercase">Completed</p>
            <p class="text-2xl font-bold text-primary mt-0.5" style="font-family: var(--font-display);">
                {{ $stats['completed'] }}
            </p>
            <p class="text-xs text-on-surface-variant mt-0.5">Units finished</p>
        </div>
    </div>

</div>


{{-- ─── My Courses ─── --}}
<div class="animate-fade-up">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-semibold text-primary" style="font-family: var(--font-display);">
            My Courses
        </h2>
        <a href="{{ Route::has('student.courses.index') ? route('student.courses.index') : '#' }}"
           class="text-xs font-medium text-on-surface-variant hover:text-primary transition-colors flex items-center gap-1">
            View all
            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
        </a>
    </div>

    @if($enrolledCourses->isEmpty())

        {{-- Empty state --}}
        <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] py-14 flex flex-col items-center gap-3">
            <div class="w-14 h-14 rounded-full bg-surface-container flex items-center justify-center">
                <span class="material-symbols-outlined text-outline text-[28px]">menu_book</span>
            </div>
            <p class="text-sm font-medium text-on-surface-variant">You haven't enrolled in any courses yet</p>
            <p class="text-xs text-outline">Use an enrollment token from your teacher to get started.</p>
            <a href="{{ Route::has('student.enroll') ? route('student.enroll') : '#' }}"
               class="mt-2 inline-flex items-center gap-1.5 px-5 py-2.5 bg-gold text-primary
                      text-sm font-semibold rounded-[24px] hover:bg-gold/90 transition-colors">
                <span class="material-symbols-outlined text-[16px]">vpn_key</span>
                Enroll with Token
            </a>
        </div>

    @else

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($enrolledCourses as $enrollment)
                @php
                    $course = $enrollment->course ?? $enrollment;
                    $pct    = $enrollment->progress ?? 0;
                @endphp
                <a href="{{ Route::has('student.courses.show') ? route('student.courses.show', $course) : '#' }}"
                   class="bg-surface-white border border-outline-variant/40 rounded-[20px] p-5 flex flex-col gap-4
                          shadow-[0px_2px_8px_rgba(30,42,74,0.06)] hover:shadow-[0px_8px_24px_rgba(30,42,74,0.12)] hover:-translate-y-0.5 transition-all duration-200 group">

                    {{-- Course identity --}}
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-primary text-[20px]">library_books</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-on-surface text-sm leading-snug
                                      group-hover:text-primary transition-colors line-clamp-2">
                                {{ $course->title ?? 'Untitled Course' }}
                            </p>
                            <p class="text-xs text-outline mt-0.5">
                                {{ $course->teacher?->name ?? 'Unknown Teacher' }}
                            </p>
                        </div>
                    </div>

                    {{-- Progress --}}
                    <div class="mt-auto">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs text-on-surface-variant">Progress</span>
                            <span class="text-xs font-semibold text-primary">{{ $pct }}%</span>
                        </div>
                        <div class="h-1.5 bg-surface-container rounded-full overflow-hidden">
                            <div class="h-full bg-gold rounded-full transition-all duration-500"
                                 style="width: {{ min($pct, 100) }}%"></div>
                        </div>
                    </div>

                </a>
            @endforeach
        </div>

    @endif

</div>

@endsection
