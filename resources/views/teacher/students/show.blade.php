@extends('layouts.teacher')

@section('title', $student->name)

@section('content')

{{-- ─── Back nav ─── --}}
<div class="mb-6">
    <a href="{{ route('teacher.students.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-on-surface-variant
              hover:text-primary transition-colors cursor-pointer">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Back to Students
    </a>
</div>

<div class="flex flex-col gap-5 animate-fade-up">

    {{-- ─── Student Header Card ─── --}}
    <x-card class="p-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-5">

            {{-- Avatar --}}
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

            {{-- Info --}}
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
                 by a divider (top border when stacked on mobile, left border once the header
                 goes horizontal at sm:) so it reads as its own action area, not part of the
                 stats. Only offered while the class relationship is active; kicking an
                 already-inactive relationship doesn't mean anything. --}}
            @if($student->class_is_active)
                <div class="shrink-0 w-full sm:w-auto flex sm:flex-col items-center sm:items-end justify-center
                            pt-4 sm:pt-0 mt-1 sm:mt-0 border-t sm:border-t-0 sm:border-l
                            border-outline-variant/30 sm:pl-5">
                    <form id="kick-form" method="POST"
                          action="{{ route('teacher.students.kick', $student->id) }}" class="hidden">
                        @csrf
                        @method('PATCH')
                    </form>
                    <x-button
                        type="button"
                        variant="danger"
                        icon="person_remove"
                        onclick="confirmDestructive(
                            {{ Js::from('Kick ' . $student->name . ' from your class?') }},
                            {{ Js::from('This will also remove them from all of your courses. They can rejoin later if you give them a fresh token.') }},
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
                Your Courses
            </h2>
            <p class="text-xs text-on-surface-variant mt-0.5">
                Courses from your curriculum that {{ $student->name }} is enrolled in
            </p>
        </div>

        @if($courses->isEmpty())
            <div class="flex flex-col items-center gap-2 py-12 text-center">
                <span class="material-symbols-outlined text-[32px] text-outline">school</span>
                <p class="text-sm font-semibold text-on-surface">Not enrolled in any courses yet</p>
                <p class="text-xs text-on-surface-variant max-w-xs">
                    {{ $student->name }} hasn't enrolled in any of your courses.
                </p>
            </div>
        @else
            <ul class="divide-y divide-outline-variant/15">
                @foreach($courses as $course)
                    @php
                        $enrollment  = $course->students->first();
                        $isActive    = (bool) ($enrollment?->pivot?->is_active ?? false);
                        $enrolledAt  = $enrollment?->pivot?->enrolled_at;
                    @endphp
                    <li class="flex items-center px-5 py-4 hover:bg-surface-container-low/60 transition-colors duration-100">
                        <a href="{{ route('teacher.courses.show', $course->id) }}"
                           class="flex items-center gap-4 flex-1 min-w-0 cursor-pointer group">

                            {{-- Course icon --}}
                            <div class="w-9 h-9 rounded-[12px] bg-primary shrink-0
                                        flex items-center justify-center">
                                <span class="material-symbols-outlined text-[18px] text-white">
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

                            {{-- Enrollment status --}}
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

                        {{-- Remove from THIS course only — separate, standalone form (never nested
                             inside the anchor above), same action already used from the course's
                             own page. Only offered while the enrollment is active, same reasoning
                             as "Kick from Class" above. --}}
                        @if($isActive)
                            <form id="remove-course-form-{{ $course->id }}" method="POST"
                                  action="{{ route('teacher.courses.students.remove', [$course->id, $student->id]) }}"
                                  class="hidden">
                                @csrf
                                @method('PATCH')
                            </form>
                            <button
                                type="button"
                                title="Remove from this course"
                                onclick="confirmDestructive(
                                    {{ Js::from('Remove ' . $student->name . ' from ' . $course->title . '?') }},
                                    {{ Js::from('They will lose access to this course only — their class enrollment and other courses are unaffected.') }},
                                    document.getElementById('remove-course-form-{{ $course->id }}'),
                                    'Remove'
                                )"
                                class="shrink-0 w-8 h-8 ml-2 inline-flex items-center justify-center rounded-lg cursor-pointer
                                       text-on-surface-variant hover:bg-error-container hover:text-error
                                       transition-colors duration-150">
                                <span class="material-symbols-outlined text-[16px]">person_remove</span>
                            </button>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

    </x-card>

</div>

@endsection
