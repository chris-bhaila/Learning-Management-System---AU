@extends('layouts.admin')

@section('title', $student->name . ' — ' . $teacher->name . '\'s Class')

@section('topbar-actions')
    <a href="{{ route('admin.users.show', $student->id) }}"
       class="inline-flex items-center gap-1.5 px-4 py-2 border border-outline-variant/60
              text-sm font-medium text-on-surface-variant rounded-[24px]
              hover:bg-surface-container-low hover:text-primary transition-colors cursor-pointer">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Back to {{ $student->name }}
    </a>
@endsection

@section('content')

<div class="flex flex-col gap-5 animate-fade-up">

    {{-- ─── Student Header Card ─── --}}
    <x-card class="p-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-5">

            {{-- Student avatar --}}
            <div class="w-16 h-16 rounded-full shrink-0 overflow-hidden
                        bg-primary-container border-2 border-outline-variant/30
                        flex items-center justify-center">
                @if($student->avatarUrl())
                    <img src="{{ $student->avatarUrl() }}"
                         alt="{{ $student->name }}"
                         class="w-full h-full object-cover">
                @else
                    <span class="text-xl font-bold text-on-primary"
                          style="font-family: var(--font-display);">
                        {{ strtoupper(substr($student->name, 0, 1)) }}
                    </span>
                @endif
            </div>

            {{-- Student info --}}
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-3 mb-1">
                    <h1 class="text-xl font-bold text-on-surface truncate"
                        style="font-family: var(--font-display);">
                        {{ $student->name }}
                    </h1>
                    @if($student->class_is_active)
                        <x-badge variant="active" dot>Active</x-badge>
                    @else
                        <x-badge variant="expired" dot>Inactive</x-badge>
                    @endif
                </div>
                <p class="text-sm text-on-surface-variant truncate">{{ $student->email }}</p>
                <p class="text-xs text-outline mt-1 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[14px]">school</span>
                    In
                    <a href="{{ route('admin.users.show', $teacher->id) }}"
                       class="font-medium text-primary hover:underline cursor-pointer">
                        {{ $teacher->name }}'s Class
                    </a>
                </p>
            </div>

            {{-- Stats --}}
            <div class="flex flex-row sm:flex-col items-start sm:items-end gap-4 sm:gap-1 shrink-0">
                <div class="text-right">
                    <p class="text-xs text-outline uppercase tracking-wider font-medium">Joined Class</p>
                    <p class="text-sm font-medium text-on-surface mt-0.5">
                        {{ $student->class_enrolled_at
                            ? \Carbon\Carbon::parse($student->class_enrolled_at)->format('M j, Y')
                            : '—' }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-outline uppercase tracking-wider font-medium">Courses Enrolled</p>
                    <p class="text-sm font-medium text-on-surface mt-0.5">{{ $courses->count() }}</p>
                </div>
            </div>

            {{-- Actions — own distinct, right-aligned slot, separated from the Stats column
                 by a divider, same treatment as teacher/students/show.blade.php. Only offered
                 while the class relationship is active. --}}
            @if($student->class_is_active)
                <div class="shrink-0 w-full sm:w-auto flex sm:flex-col items-center sm:items-end justify-center
                            pt-4 sm:pt-0 mt-1 sm:mt-0 border-t sm:border-t-0 sm:border-l
                            border-outline-variant/30 sm:pl-5">
                    <form id="kick-form" method="POST"
                          action="{{ route('admin.users.classes.kick', [$student->id, $teacher->id]) }}" class="hidden">
                        @csrf
                        @method('PATCH')
                    </form>
                    <x-button
                        type="button"
                        variant="danger"
                        icon="person_remove"
                        onclick="confirmDestructive(
                            {{ Js::from('Kick ' . $student->name . ' from ' . $teacher->name . '\'s class?') }},
                            {{ Js::from('This will also remove them from all of ' . $teacher->name . '\'s courses. They can rejoin later with a fresh token from this teacher.') }},
                            document.getElementById('kick-form'),
                            'Kick from Class'
                        )"
                    >
                        Kick from Class
                    </x-button>
                </div>
            @endif

        </div>
    </x-card>

    {{-- ─── Course Enrollments Card ─── --}}
    <x-card class="overflow-hidden">

        <div class="px-5 py-4 border-b border-outline-variant/20">
            <h2 class="text-sm font-semibold text-on-surface"
                style="font-family: var(--font-display);">
                {{ $teacher->name }}'s Courses
            </h2>
            <p class="text-xs text-on-surface-variant mt-0.5">
                Courses from this teacher's curriculum that {{ $student->name }} is enrolled in
            </p>
        </div>

        @if($courses->isEmpty())
            <div class="flex flex-col items-center gap-2 py-12 text-center">
                <span class="material-symbols-outlined text-[32px] text-outline">school</span>
                <p class="text-sm font-semibold text-on-surface">Not enrolled in any courses</p>
                <p class="text-xs text-on-surface-variant max-w-xs">
                    {{ $student->name }} hasn't enrolled in any of {{ $teacher->name }}'s courses yet.
                </p>
            </div>
        @else
            <ul class="divide-y divide-outline-variant/15">
                @foreach($courses as $course)
                    @php
                        $enrollment = $course->students->first();
                        $isActive   = (bool) ($enrollment?->pivot?->is_active ?? false);
                        $enrolledAt = $enrollment?->pivot?->enrolled_at;
                    @endphp
                    <li>
                        <a href="{{ route('admin.courses.show', $course->id) }}"
                           class="flex items-center gap-4 px-5 py-4
                                  hover:bg-surface-container-low/60 transition-colors duration-100
                                  cursor-pointer group">

                            {{-- Course icon --}}
                            <div class="w-9 h-9 rounded-[12px] bg-primary-container shrink-0
                                        flex items-center justify-center">
                                <span class="material-symbols-outlined text-[18px] text-primary">
                                    menu_book
                                </span>
                            </div>

                            {{-- Course info --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-on-surface truncate">
                                    {{ $course->title }}
                                </p>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-0.5">
                                    @if($course->group)
                                        <span class="text-xs text-on-surface-variant">
                                            {{ $course->group->name }}
                                        </span>
                                    @endif
                                    <span class="text-xs text-outline">
                                        {{ $course->units_count }}
                                        {{ Str::plural('unit', $course->units_count) }}
                                    </span>
                                    @if($enrolledAt)
                                        <span class="text-xs text-outline">
                                            Enrolled {{ \Carbon\Carbon::parse($enrolledAt)->format('M j, Y') }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Enrollment status + arrow --}}
                            <div class="shrink-0 flex items-center gap-3">
                                @if($isActive)
                                    <x-badge variant="active" dot>Active</x-badge>
                                @else
                                    <x-badge variant="expired" dot>Inactive</x-badge>
                                @endif
                                <span class="material-symbols-outlined text-[18px] text-outline
                                             group-hover:text-primary transition-colors">
                                    chevron_right
                                </span>
                            </div>

                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

    </x-card>

</div>

@endsection
