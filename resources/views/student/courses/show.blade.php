@extends('layouts.student')

@section('title', $course->title)

@section('topbar-actions')
    <a href="{{ route('student.courses.index') }}"
       class="inline-flex items-center gap-1.5 px-4 py-2 border border-outline-variant/60
              text-sm font-medium text-on-surface-variant rounded-[24px]
              hover:bg-surface-container-low hover:text-primary transition-colors cursor-pointer">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        My Courses
    </a>
@endsection

@section('content')
@php
    $groupName = $course->group?->name ?? null;
@endphp

<div class="space-y-6">

    {{-- ─── Page Header ─── --}}
    <div class="animate-fade-up">
        <div class="flex items-center gap-2 flex-wrap mb-3">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium
                         bg-gold/20 text-primary">
                <span class="w-1.5 h-1.5 rounded-full bg-gold"></span>
                Enrolled
            </span>
            @if($groupName)
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium
                             bg-surface-container text-on-surface-variant">
                    <span class="material-symbols-outlined text-[14px]">folder</span>
                    {{ $groupName }}
                </span>
            @endif
        </div>
        <h1 class="text-2xl font-bold text-primary leading-tight break-words"
            style="font-family: var(--font-display);">
            {{ $course->title }}
        </h1>
        <p class="mt-1.5 text-sm text-on-surface-variant flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[15px]">person</span>
            {{ $course->teacher?->name ?? '—' }}
        </p>
    </div>

    {{-- ─── Two-column grid ─── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 animate-fade-up">

        {{-- ═══ MAIN COLUMN ═══ --}}
        <div class="lg:col-span-2 min-w-0 flex flex-col gap-5 order-2 lg:order-1">

            {{-- Description --}}
            <div class="bg-surface-white border border-outline-variant/40 rounded-[20px]
                        shadow-[0px_1px_4px_rgba(30,42,74,0.06)] p-6 overflow-hidden">
                <p class="text-sm font-semibold text-on-surface mb-4" style="font-family: var(--font-display);">
                    About this course
                </p>
                @if($course->description)
                    <div x-data="{ expanded: false, overflows: false }"
                         x-init="$nextTick(() => { overflows = $refs.descBody.scrollHeight > 220 })">
                        <div x-ref="descBody"
                             :class="!expanded && overflows ? 'max-h-[220px]' : ''"
                             class="relative overflow-hidden rich-text text-on-surface break-words">
                            {!! $course->description !!}
                            <div x-show="!expanded && overflows"
                                 class="absolute bottom-0 left-0 right-0 h-14
                                        bg-gradient-to-t from-surface-white to-transparent
                                        pointer-events-none">
                            </div>
                        </div>
                        <button x-show="overflows" x-cloak
                                type="button"
                                @click="expanded = !expanded"
                                class="mt-2 inline-flex items-center gap-0.5 text-xs font-medium
                                       text-primary hover:text-gold transition-colors cursor-pointer">
                            <span x-text="expanded ? 'Show less' : 'Show more'">Show more</span>
                            <span class="material-symbols-outlined text-[14px]"
                                  :style="expanded ? 'transform:rotate(180deg)' : ''"
                                  style="transition:transform 200ms ease">expand_more</span>
                        </button>
                    </div>
                @else
                    <p class="text-sm text-outline italic">No description provided.</p>
                @endif
            </div>

            {{-- Units --}}
            <div class="bg-surface-white border border-outline-variant/40 rounded-[20px]
                        shadow-[0px_1px_4px_rgba(30,42,74,0.06)] overflow-hidden">
                <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-outline-variant/20">
                    <p class="text-sm font-semibold text-on-surface" style="font-family: var(--font-display);">
                        Units
                    </p>
                    <span class="text-xs text-on-surface-variant">
                        {{ $units->count() }} {{ Str::plural('unit', $units->count()) }}
                    </span>
                </div>

                @if($units->isEmpty())
                    <div class="py-14 flex flex-col items-center gap-2 text-center px-4">
                        <span class="material-symbols-outlined text-outline text-[28px] animate-float">menu_book</span>
                        <p class="text-sm font-semibold text-on-surface">No units available yet</p>
                        <p class="text-xs text-on-surface-variant max-w-xs">
                            Your teacher hasn't published any units for this course yet. Check back soon.
                        </p>
                    </div>
                @else
                    <ul class="divide-y divide-outline-variant/20">
                        @foreach($units as $unit)
                            <li class="min-w-0">
                                <a href="{{ route('student.units.show', [$course->id, $unit->id]) }}"
                                   class="flex items-center gap-4 px-6 py-4 min-w-0
                                          hover:bg-surface-container-low/40 transition-colors duration-200 group cursor-pointer">
                                    <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center
                                                shrink-0 group-hover:bg-gold/20 transition-colors duration-200">
                                        <span class="text-xs font-bold text-on-surface-variant
                                                     group-hover:text-primary transition-colors">
                                            {{ $unit->order ?? $loop->iteration }}
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-on-surface truncate
                                                   group-hover:text-primary transition-colors">
                                            {{ $unit->title }}
                                        </p>
                                        @if($unit->files->isNotEmpty())
                                            <p class="text-[11px] text-on-surface-variant mt-0.5 flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[12px]">attach_file</span>
                                                {{ $unit->files->count() }} {{ Str::plural('attachment', $unit->files->count()) }}
                                            </p>
                                        @endif
                                    </div>
                                    <span class="material-symbols-outlined text-[18px] text-outline shrink-0
                                                 group-hover:text-primary transition-colors">
                                        arrow_forward
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Course Files --}}
            <div
                class="bg-surface-white border border-outline-variant/40 rounded-[20px]
                       shadow-[0px_1px_4px_rgba(30,42,74,0.06)] overflow-hidden"
            >
                <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-outline-variant/20">
                    <p class="text-sm font-semibold text-on-surface" style="font-family: var(--font-display);">
                        Course Files
                    </p>
                    <span class="text-xs text-on-surface-variant">
                        {{ $course->files->count() }} {{ Str::plural('file', $course->files->count()) }}
                    </span>
                </div>

                @if($course->files->isEmpty())
                    <div class="py-10 flex flex-col items-center gap-2 text-center px-4">
                        <span class="material-symbols-outlined text-outline text-[28px] animate-float">attach_file</span>
                        <p class="text-sm font-medium text-on-surface">No files attached</p>
                        <p class="text-xs text-on-surface-variant">Your teacher hasn't attached any files to this course.</p>
                    </div>
                @else
                    <ul class="divide-y divide-outline-variant/20">
                        @foreach($course->files as $file)
                            @php
                                $fileSize = $file->size >= 1048576
                                    ? number_format($file->size / 1048576, 1) . ' MB'
                                    : number_format($file->size / 1024, 0) . ' KB';
                            @endphp
                            <li class="flex items-center gap-3 px-6 py-3.5 min-w-0
                                       hover:bg-surface-container-low/40 transition-colors duration-200">
                                <span class="material-symbols-outlined text-outline text-[20px] shrink-0">description</span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-on-surface truncate">
                                        {{ $file->original_name ?? $file->filename }}
                                    </p>
                                    <p class="text-[11px] text-on-surface-variant mt-0.5">
                                        {{ $fileSize }} · {{ $file->created_at->format('M j, Y') }}
                                    </p>
                                </div>
                                <a href="{{ route('files.download', $file->id) }}"
                                   class="shrink-0 inline-flex items-center gap-1 text-xs font-medium
                                          text-primary hover:text-gold transition-colors cursor-pointer">
                                    <span class="material-symbols-outlined text-[14px]">download</span>
                                    Download
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

        </div>{{-- end main --}}

        {{-- ═══ SIDEBAR ═══ --}}
        <div class="min-w-0 flex flex-col gap-5 order-1 lg:order-2">

            <div class="bg-surface-white border border-outline-variant/40 rounded-[20px]
                        shadow-[0px_1px_4px_rgba(30,42,74,0.06)] p-6 flex flex-col gap-4">

                {{-- Teacher --}}
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-surface-container flex items-center justify-center shrink-0 overflow-hidden">
                        @if($course->teacher?->avatarUrl())
                            <img src="{{ $course->teacher->avatarUrl() }}"
                                 alt="{{ $course->teacher->name }}"
                                 class="w-full h-full object-cover">
                        @else
                            <span class="text-sm font-bold text-on-surface-variant">
                                {{ strtoupper(substr($course->teacher?->name ?? '?', 0, 1)) }}
                            </span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] text-outline font-medium uppercase tracking-wide">Teacher</p>
                        <p class="text-sm font-medium text-on-surface truncate">
                            {{ $course->teacher?->name ?? '—' }}
                        </p>
                    </div>
                </div>

                {{-- Group --}}
                @if($groupName)
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-surface-container flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-outline text-[18px]">folder</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] text-outline font-medium uppercase tracking-wide">Group</p>
                            <p class="text-sm text-on-surface truncate">{{ $groupName }}</p>
                        </div>
                    </div>
                @endif

                {{-- Units count --}}
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-surface-container flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-outline text-[18px]">menu_book</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] text-outline font-medium uppercase tracking-wide">Units</p>
                        <p class="text-sm text-on-surface">{{ $units->count() }} available</p>
                    </div>
                </div>

                {{-- Enrolled date --}}
                @php
                    $enrolledAt = auth()->user()->enrolledCourses()
                        ->where('course_id', $course->id)
                        ->first()?->pivot->enrolled_at;
                @endphp
                @if($enrolledAt)
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-surface-container flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-outline text-[18px]">event</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] text-outline font-medium uppercase tracking-wide">Enrolled</p>
                            <p class="text-sm text-on-surface">{{ \Carbon\Carbon::parse($enrolledAt)->format('M j, Y') }}</p>
                        </div>
                    </div>
                @endif

            </div>

        </div>{{-- end sidebar --}}

    </div>{{-- end grid --}}

</div>
@endsection
