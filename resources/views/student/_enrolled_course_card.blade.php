{{-- Single enrolled-course card for the student dashboard's 2x2 grid. Same visual markup
     as My Courses' cards (resources/views/student/courses/index.blade.php) — kept as a
     separate partial rather than a shared include because My Courses renders its cards
     via an Alpine <template x-for> for client-side live search, which can't @include a
     server-rendered Blade partial; this dashboard grid has no search, so a plain @foreach
     + @include is the right fit here. Expects $course. --}}
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
