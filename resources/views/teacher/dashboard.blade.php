@extends('layouts.teacher')

@section('title', 'Dashboard')

@section('sidebar-cta')
    <a href="{{ Route::has('teacher.courses.create') ? route('teacher.courses.create') : '#' }}"
       class="flex items-center justify-center gap-2 w-full py-2.5 px-4
              bg-gold text-primary font-semibold text-sm rounded-[24px]
              hover:bg-gold/90 transition-colors">
        <span class="material-symbols-outlined text-[18px]">add</span>
        New Course
    </a>
@endsection

@section('content')
@php
    // Fallbacks — controller will pass real values once backend is wired
    $stats ??= [
        'total_students'  => 0,
        'active_courses'  => 0,
        'pending_grading' => 0,
        'avg_progress'    => 0,
    ];
    $courses        ??= collect();
    $unreadNotifications ??= 0;
@endphp

{{-- ─── Page Header ─── --}}
<div class="flex items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
            Welcome back, {{ auth()->user()?->name ?? 'Teacher' }}
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            Here's your teaching overview for the current term.
        </p>
    </div>
    <span class="text-xs text-outline bg-surface-container px-3 py-1.5 rounded-full shrink-0">
        {{ now()->format('D, d M Y') }}
    </span>
</div>


{{-- ─── Stat Cards ─── --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 animate-fade-up">

    {{-- Total Students --}}
    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] p-5 flex items-start gap-4
                shadow-[0px_2px_8px_rgba(30,42,74,0.06)] hover:shadow-[0px_8px_24px_rgba(30,42,74,0.10)] transition-shadow">
        <div class="w-11 h-11 rounded-xl bg-surface-container flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-primary text-[22px]">group</span>
        </div>
        <div class="min-w-0">
            <p class="text-[11px] font-semibold tracking-widest text-outline uppercase">Total Students</p>
            <p class="text-2xl font-bold text-primary mt-0.5" style="font-family: var(--font-display);">
                {{ number_format($stats['total_students']) }}
            </p>
            <p class="text-xs text-on-surface-variant mt-0.5">Across all classes</p>
        </div>
    </div>

    {{-- Active Courses --}}
    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] p-5 flex items-start gap-4
                shadow-[0px_2px_8px_rgba(30,42,74,0.06)] hover:shadow-[0px_8px_24px_rgba(30,42,74,0.10)] transition-shadow">
        <div class="w-11 h-11 rounded-xl bg-surface-container flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-primary text-[22px]">library_books</span>
        </div>
        <div class="min-w-0">
            <p class="text-[11px] font-semibold tracking-widest text-outline uppercase">Active Courses</p>
            <p class="text-2xl font-bold text-primary mt-0.5" style="font-family: var(--font-display);">
                {{ number_format($stats['active_courses']) }}
            </p>
            <p class="text-xs text-on-surface-variant mt-0.5">Current term</p>
        </div>
    </div>

    {{-- Pending Grading --}}
    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] p-5 flex items-start gap-4
                shadow-[0px_2px_8px_rgba(30,42,74,0.06)] hover:shadow-[0px_8px_24px_rgba(30,42,74,0.10)] transition-shadow">
        <div class="w-11 h-11 rounded-xl bg-gold/20 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-[22px]" style="color: var(--color-on-gold);">pending_actions</span>
        </div>
        <div class="min-w-0">
            <p class="text-[11px] font-semibold tracking-widest text-outline uppercase">Needs Grading</p>
            <p class="text-2xl font-bold text-primary mt-0.5" style="font-family: var(--font-display);">
                {{ number_format($stats['pending_grading']) }}
            </p>
            <p class="text-xs text-on-surface-variant mt-0.5">This week</p>
        </div>
    </div>

    {{-- Avg Progress --}}
    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] p-5 flex items-start gap-4
                shadow-[0px_2px_8px_rgba(30,42,74,0.06)] hover:shadow-[0px_8px_24px_rgba(30,42,74,0.10)] transition-shadow">
        <div class="w-11 h-11 rounded-xl bg-surface-container flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-primary text-[22px]">trending_up</span>
        </div>
        <div class="min-w-0">
            <p class="text-[11px] font-semibold tracking-widest text-outline uppercase">Avg. Progress</p>
            <p class="text-2xl font-bold text-primary mt-0.5" style="font-family: var(--font-display);">
                {{ $stats['avg_progress'] }}%
            </p>
            <p class="text-xs text-on-surface-variant mt-0.5">Across all courses</p>
        </div>
    </div>

</div>


{{-- ─── Course Management Table ─── --}}
<div class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] overflow-hidden animate-fade-up">

    <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/30">
        <h2 class="text-base font-semibold text-primary" style="font-family: var(--font-display);">
            Course Management
        </h2>
        <a href="{{ Route::has('teacher.courses.index') ? route('teacher.courses.index') : '#' }}"
           class="text-xs font-medium text-on-surface-variant hover:text-primary transition-colors flex items-center gap-1">
            View all
            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-outline-variant/30 bg-surface-container-low/50">
                    <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase">
                        Course
                    </th>
                    <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase">
                        Enrolled
                    </th>
                    <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase">
                        Status
                    </th>
                    <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase min-w-[140px]">
                        Progress
                    </th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20">
                @forelse($courses as $course)
                    <tr class="hover:bg-surface-container-low/40 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-medium text-on-surface">{{ $course->title }}</p>
                            @if($course->courseGroup)
                                <p class="text-xs text-outline mt-0.5">{{ $course->courseGroup->name }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-on-surface-variant">
                            {{ $course->students_count ?? 0 }}
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $published = $course->is_published ?? false;
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                         {{ $published ? 'bg-gold/20 text-on-gold' : 'bg-surface-container text-on-surface-variant' }}">
                                {{ $published ? 'Published' : 'Draft' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @php $pct = $course->avg_progress ?? 0; @endphp
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-1.5 bg-surface-container rounded-full overflow-hidden">
                                    <div class="h-full bg-gold rounded-full" style="width: {{ min($pct, 100) }}%"></div>
                                </div>
                                <span class="text-xs text-outline w-8 text-right">{{ $pct }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ Route::has('teacher.courses.show') ? route('teacher.courses.show', $course) : '#' }}"
                               class="text-xs font-medium text-primary hover:underline">
                                Manage
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="flex flex-col items-center py-12 gap-3">
                                <div class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center">
                                    <span class="material-symbols-outlined text-outline text-[24px]">library_books</span>
                                </div>
                                <p class="text-sm font-medium text-on-surface-variant">No courses yet</p>
                                <p class="text-xs text-outline">Create your first course to get started.</p>
                                <a href="{{ Route::has('teacher.courses.create') ? route('teacher.courses.create') : '#' }}"
                                   class="mt-1 inline-flex items-center gap-1.5 px-4 py-2 bg-gold text-primary
                                          text-sm font-semibold rounded-[24px] hover:bg-gold/90 transition-colors">
                                    <span class="material-symbols-outlined text-[16px]">add</span>
                                    New Course
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

@endsection
