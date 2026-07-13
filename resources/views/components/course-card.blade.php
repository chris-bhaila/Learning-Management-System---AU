{{--
    x-course-card — shared course card used on admin and teacher course index pages.

    Props:
      $course       — Course model (needs students_count, units_count, courseGroup, teacher)
      $showRoute    — URL the card body links to
      $deleteRoute  — URL for the delete form; omit or pass null to hide delete button
      $showTeacher  — bool, show teacher avatar + name row (true on admin, false on teacher)

    All extra attributes (data-card, x-show, x-transition:*) flow through $attributes
    to the root element, so filter state and enter/leave animations work from the parent page.
--}}
@props([
    'course',
    'showRoute',
    'deleteRoute' => null,
    'showTeacher' => false,
])

@php
    $published   = $course->is_published ?? false;
    $teacherName = $course->teacher?->name ?? '—';
    $groupName   = $course->courseGroup?->name ?? null;
    $students    = $course->students_count ?? 0;
    $units       = $course->units_count ?? 0;
    $progress    = min($course->avg_progress ?? 0, 100);
@endphp

<div {{ $attributes->merge(['class' => 'group relative bg-surface-white border border-outline-variant/40 rounded-[20px] mt-2 shadow-[0px_2px_8px_rgba(30,42,74,0.06)] hover:shadow-[0px_8px_24px_rgba(30,42,74,0.12)] hover:-translate-y-0.5 transition-all duration-200 overflow-hidden']) }}>

    {{-- Top accent strip --}}
    <div class="h-1 w-full {{ $published ? 'bg-gold' : 'bg-outline-variant/40' }}"></div>

    {{-- Main clickable area — ring-inset keeps the focus ring inside the card's own box so
         the outer overflow-hidden never clips it, and matches the focus-ring color already
         used on every form input in this app (focus:ring-primary), not a new one-off color. --}}
    <a href="{{ $showRoute }}"
       class="block p-5 cursor-pointer focus-visible:outline-none
              focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary/50">
        <div class="flex flex-col gap-4">

            {{-- Header: title + status chip --}}
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    @if($groupName)
                        <span class="inline-block mb-1.5 px-2 py-0.5 rounded-full bg-surface-container
                                     text-[10px] font-semibold tracking-wide text-on-surface-variant uppercase">
                            {{ $groupName }}
                        </span>
                    @endif
                    <h3 class="font-semibold text-primary leading-snug group-hover:text-gold transition-colors duration-150"
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

            @if($showTeacher)
            {{-- Teacher avatar + name (admin only) --}}
            <div class="flex items-center gap-2 min-w-0">
                <div class="w-7 h-7 rounded-full bg-primary flex items-center justify-center shrink-0">
                    <span class="text-[10px] font-bold text-white"
                          style="font-family: var(--font-display);">
                        {{ strtoupper(substr($teacherName, 0, 2)) }}
                    </span>
                </div>
                <span class="text-sm text-on-surface-variant truncate">{{ $teacherName }}</span>
            </div>
            @endif

            {{-- Stats chips --}}
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container text-xs text-on-surface-variant">
                    <span class="material-symbols-outlined text-[14px]">group</span>
                    {{ $students }} {{ Str::plural('student', $students) }}
                </div>
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container text-xs text-on-surface-variant">
                    <span class="material-symbols-outlined text-[14px]">menu_book</span>
                    {{ $units }} {{ Str::plural('unit', $units) }}
                </div>
            </div>

            {{-- Progress bar --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-xs text-on-surface-variant">Avg. progress</span>
                    <span class="text-xs font-semibold text-primary">{{ $progress }}%</span>
                </div>
                <div class="h-1.5 bg-surface-container rounded-full overflow-hidden">
                    <div class="h-full bg-gold rounded-full transition-all duration-500"
                         style="width: {{ $progress }}%"></div>
                </div>
            </div>

        </div>
    </a>

    {{-- Footer — outside <a> so the delete form doesn't nest inside the link --}}
    <div class="px-5 pb-5 flex items-center justify-between border-t border-outline-variant/20 pt-3">
        <span class="text-xs text-outline">
            {{ $course->created_at?->diffForHumans() ?? '' }}
        </span>

        <div class="relative flex items-center">

            {{-- "View course" — a real link (not just a label) so clicking it does the same
                 thing as clicking the card. Only fades out on hover/focus when a delete pill
                 is about to take its place; with no delete pill there's nothing to reveal, so
                 it stays put. Sibling of the delete <form> below, not nested inside it.
                 group-focus-within (not just group-hover) so tabbing to the delete button
                 also triggers the swap — otherwise a keyboard user could focus an invisible
                 button while "View course" stays on top of it. --}}
            <a href="{{ $showRoute }}"
               class="inline-flex items-center gap-1 text-xs font-medium text-primary/60
                      hover:text-primary transition-all duration-150 ease-in-out
                      {{ $deleteRoute ? 'group-hover:opacity-0 group-hover:translate-x-1 group-focus-within:opacity-0 group-focus-within:translate-x-1' : '' }}
                      cursor-pointer whitespace-nowrap rounded-md
                      focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/50">
                View course
                <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
            </a>

            @if($deleteRoute)
            {{-- Delete pill — fades in on hover OR keyboard focus, overlays exact same position --}}
            <form method="POST" action="{{ $deleteRoute }}"
                  class="absolute inset-0 flex items-center justify-end
                         opacity-0 -translate-x-1
                         group-hover:opacity-100 group-hover:translate-x-0
                         group-focus-within:opacity-100 group-focus-within:translate-x-0
                         transition-all duration-150 ease-in-out">
                @csrf
                @method('DELETE')
                <button type="button"
                        onclick="confirmDelete({{ Js::from($course->title) }}, this.closest('form'))"
                        class="inline-flex items-center gap-1 px-3 py-1 rounded-full
                               border border-error/40 text-error text-xs font-medium
                               hover:bg-error hover:text-white hover:border-error
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-error/50
                               transition-all duration-150 cursor-pointer whitespace-nowrap">
                    <span class="material-symbols-outlined text-[13px]">delete</span>
                    Delete
                </button>
            </form>
            @endif

        </div>
    </div>

</div>
