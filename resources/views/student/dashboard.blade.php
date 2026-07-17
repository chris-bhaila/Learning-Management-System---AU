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

{{-- ─── Main layout: left (stat cards + enrolled courses) / right (notifications) ───
     Same grid-cols-1 xl:grid-cols-3 + xl:col-span-2 split already used by the teacher
     dashboard — one grid row, so the two columns naturally line up; the notifications
     panel additionally gets its own fixed height (see below) rather than just stretching
     to match, per the wireframe's explicit "fixed height and width" callout. --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 animate-fade-up">

    {{-- LEFT column (2/3 width) — unchanged stat cards, then the enrolled-courses grid --}}
    <div class="xl:col-span-2 space-y-6">

        {{-- ─── Stats (Card 1, Card 2) — content/position within this row unchanged ─── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

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

        {{-- ─── Enrolled Courses — 2x2 grid, capped at 4 on the dashboard ─── --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-on-surface" style="font-family: var(--font-display);">
                    My Courses
                </h2>
                @if($courses->count() > 4)
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

                {{-- sm:grid-cols-2 gives the 2x2 layout from the wireframe on this column's
                     own width; naturally collapses to a single column below sm. --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    @foreach($courses->take(4) as $course)
                        @include('student._enrolled_course_card', ['course' => $course])
                    @endforeach
                </div>

            @endif
        </div>

    </div>

    {{-- RIGHT column (1/3 width) — Notifications, fixed height + width, internally
         scrollable. h-[34rem] (544px) chosen to approximate the combined height of the
         stat-card row + 2x2 course grid at typical content length (rather than an
         arbitrary guess): ~6rem card row + 1.5rem gap + two ~11.5rem course-card rows +
         1.25rem gap ≈ 34rem, rounded to a clean rem value on the same 4px/0.25rem
         increment Tailwind's own spacing scale uses. Width is "fixed" in the sense that
         it's always exactly this column's 1/3 share of the grid — a hardcoded pixel width
         would fight the responsive grid instead of matching it. --}}
    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] overflow-hidden animate-fade-up flex flex-col h-[34rem]">

        <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/30 shrink-0">
            <h2 class="text-base font-semibold text-on-surface" style="font-family: var(--font-display);">
                Recent Activity
            </h2>
            <a href="{{ route('student.activity.index') }}"
               class="text-xs font-semibold text-on-surface-variant hover:text-primary transition-colors cursor-pointer">
                View all
            </a>
        </div>

        {{-- flex-1 + overflow-y-auto: scrolls internally within the fixed-height panel,
             never grows the page, even with the full 12-notification cap loaded. --}}
        <ul class="divide-y divide-outline-variant/20 flex-1 overflow-y-auto">
            @forelse($notifications as $log)
                @include('student.activity._item', ['log' => $log])
            @empty
                <li class="px-6 py-10 text-center text-sm text-outline">
                    No recent activity from your courses.
                </li>
            @endforelse
        </ul>

    </div>

</div>

@endsection

@include('student._enroll_modal')
