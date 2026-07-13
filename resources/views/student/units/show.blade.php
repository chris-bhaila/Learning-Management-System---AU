@extends('layouts.student')

@section('title', $unit->title)

@section('topbar-actions')
    <a href="{{ route('student.courses.show', $course->id) }}"
       class="inline-flex items-center gap-1.5 px-4 py-2 border border-outline-variant/60
              text-sm font-medium text-on-surface-variant rounded-[24px]
              hover:bg-surface-container-low hover:text-primary transition-colors cursor-pointer">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Back to Course
    </a>
@endsection

@section('content')

<div class="space-y-6">

    {{-- ─── Page Header ─── --}}
    <div class="animate-fade-up">
        <div class="flex items-center gap-2 mb-2">
            <a href="{{ route('student.courses.show', $course->id) }}"
               class="text-xs text-on-surface-variant hover:text-primary transition-colors cursor-pointer
                      flex items-center gap-1">
                <span class="material-symbols-outlined text-[13px]">library_books</span>
                {{ $course->title }}
            </a>
            <span class="text-outline-variant/60 text-xs">/</span>
            <span class="text-xs text-outline">Unit {{ $unit->order ?? '' }}</span>
        </div>
        <h1 class="text-2xl font-bold text-primary leading-tight break-words"
            style="font-family: var(--font-display);">
            {{ $unit->title }}
        </h1>
    </div>

    {{-- ─── Two-column grid ─── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 animate-fade-up">

        {{-- ═══ MAIN COLUMN ═══ --}}
        <div class="lg:col-span-2 min-w-0 flex flex-col gap-5 order-2 lg:order-1">

            {{-- Content card --}}
            <div class="bg-surface-white border border-outline-variant/40 rounded-[20px]
                        shadow-[0px_1px_4px_rgba(30,42,74,0.06)] p-6 overflow-hidden">
                <p class="text-sm font-semibold text-on-surface mb-4" style="font-family: var(--font-display);">
                    Content
                </p>
                @if($unit->content)
                    <div x-data="{ expanded: false, overflows: false }"
                         x-init="$nextTick(() => { overflows = $refs.contentBody.scrollHeight > 320 })">
                        <div x-ref="contentBody"
                             :class="!expanded && overflows ? 'max-h-[320px]' : ''"
                             class="relative overflow-hidden rich-text text-on-surface break-words">
                            {!! $unit->content !!}
                            <div x-show="!expanded && overflows"
                                 class="absolute bottom-0 left-0 right-0 h-14
                                        bg-gradient-to-t from-surface-white to-transparent
                                        pointer-events-none">
                            </div>
                        </div>
                        <button x-show="overflows" x-cloak
                                type="button"
                                @click="expanded = !expanded"
                                class="mt-3 inline-flex items-center gap-0.5 text-xs font-medium
                                       text-primary hover:text-gold transition-colors cursor-pointer">
                            <span x-text="expanded ? 'Show less' : 'Show more'">Show more</span>
                            <span class="material-symbols-outlined text-[14px]"
                                  :style="expanded ? 'transform:rotate(180deg)' : ''"
                                  style="transition:transform 200ms ease">expand_more</span>
                        </button>
                    </div>
                @else
                    <p class="text-sm text-outline italic">No content provided for this unit.</p>
                @endif
            </div>

            {{-- Attachments --}}
            <div
                class="bg-surface-white border border-outline-variant/40 rounded-[20px]
                       shadow-[0px_1px_4px_rgba(30,42,74,0.06)] overflow-hidden"
            >
                <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-outline-variant/20">
                    <p class="text-sm font-semibold text-on-surface" style="font-family: var(--font-display);">
                        Attachments
                    </p>
                    <span class="text-xs text-on-surface-variant">
                        {{ $unit->files->count() }} {{ Str::plural('file', $unit->files->count()) }}
                    </span>
                </div>

                @if($unit->files->isEmpty())
                    <div class="py-10 flex flex-col items-center gap-2 text-center px-4">
                        <span class="material-symbols-outlined text-outline text-[28px] animate-float">attach_file</span>
                        <p class="text-sm font-medium text-on-surface">No attachments</p>
                        <p class="text-xs text-on-surface-variant">Your teacher hasn't attached any files to this unit.</p>
                    </div>
                @else
                    <ul class="divide-y divide-outline-variant/20">
                        @foreach($unit->files as $file)
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

                {{-- Course --}}
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-surface-container flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-outline text-[18px]">library_books</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] text-outline font-medium uppercase tracking-wide">Course</p>
                        <a href="{{ route('student.courses.show', $course->id) }}"
                           class="text-sm font-medium text-on-surface hover:text-gold
                                  transition-colors truncate block cursor-pointer">
                            {{ $course->title }}
                        </a>
                    </div>
                </div>

                {{-- Position --}}
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-surface-container flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-outline text-[18px]">format_list_numbered</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] text-outline font-medium uppercase tracking-wide">Position</p>
                        <p class="text-sm text-on-surface">Unit {{ $unit->order ?? '—' }}</p>
                    </div>
                </div>

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
                        <p class="text-sm text-on-surface truncate">{{ $course->teacher?->name ?? '—' }}</p>
                    </div>
                </div>

            </div>

            {{-- Unit navigation --}}
            @php
                $allUnits = $course->units->where('is_published', true)->values();
                $currentIndex = $allUnits->search(fn($u) => $u->id === $unit->id);
                $prevUnit = $currentIndex > 0 ? $allUnits[$currentIndex - 1] : null;
                $nextUnit = $currentIndex !== false && $currentIndex < $allUnits->count() - 1 ? $allUnits[$currentIndex + 1] : null;
            @endphp
            @if($prevUnit || $nextUnit)
                <div class="bg-surface-white border border-outline-variant/40 rounded-[20px]
                            shadow-[0px_1px_4px_rgba(30,42,74,0.06)] p-4 flex flex-col gap-2">
                    <p class="text-[10px] text-outline font-medium uppercase tracking-wide px-1 mb-1">Navigate</p>
                    @if($prevUnit)
                        <a href="{{ route('student.units.show', [$course->id, $prevUnit->id]) }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-[12px]
                                  hover:bg-surface-container transition-colors duration-150 group cursor-pointer">
                            <span class="material-symbols-outlined text-[16px] text-outline shrink-0
                                         group-hover:text-primary transition-colors">arrow_back</span>
                            <div class="min-w-0">
                                <p class="text-[10px] text-outline-variant">Previous</p>
                                <p class="text-xs font-medium text-on-surface truncate group-hover:text-primary
                                          transition-colors">{{ $prevUnit->title }}</p>
                            </div>
                        </a>
                    @endif
                    @if($nextUnit)
                        <a href="{{ route('student.units.show', [$course->id, $nextUnit->id]) }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-[12px]
                                  hover:bg-surface-container transition-colors duration-150 group cursor-pointer">
                            <div class="flex-1 min-w-0 text-right">
                                <p class="text-[10px] text-outline-variant">Next</p>
                                <p class="text-xs font-medium text-on-surface truncate group-hover:text-primary
                                          transition-colors">{{ $nextUnit->title }}</p>
                            </div>
                            <span class="material-symbols-outlined text-[16px] text-outline shrink-0
                                         group-hover:text-primary transition-colors">arrow_forward</span>
                        </a>
                    @endif
                </div>
            @endif

        </div>{{-- end sidebar --}}

    </div>{{-- end grid --}}

</div>
@endsection
