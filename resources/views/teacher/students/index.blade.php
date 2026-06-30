@extends('layouts.teacher')

@section('title', 'Students')

@section('content')

{{-- ─── Page Header ─── --}}
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
            Students
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            {{ $students->count() }} {{ Str::plural('student', $students->count()) }} in your class
        </p>
    </div>
    <a href="{{ route('teacher.tokens.index') }}"
       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-on-surface-variant
              border border-outline-variant/60 rounded-[24px]
              hover:bg-surface-container-low hover:text-primary
              transition-all duration-150 cursor-pointer shrink-0">
        <span class="material-symbols-outlined text-[16px]">key</span>
        Manage class tokens
        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
    </a>
</div>

@if($students->isEmpty())

    {{-- ─── Empty State ─── --}}
    <x-card class="flex flex-col items-center gap-3 py-16 text-center animate-fade-up">
        <span class="material-symbols-outlined text-[40px] text-outline animate-float">group</span>
        <p class="text-sm font-semibold text-on-surface">No students yet</p>
        <p class="text-xs text-on-surface-variant max-w-xs">
            Students will appear here once they join your class using a class token.
        </p>
    </x-card>

@else

    @php
        $studentData = $students->map(fn($s) => [
            'id'            => $s->id,
            'name'          => $s->name,
            'email'         => $s->email,
            'initials'      => strtoupper(substr($s->name, 0, 1)),
            'avatar'        => $s->avatarUrl(),
            'active'        => (bool) $s->class_is_active,
            'enrolled_at'   => $s->class_enrolled_at
                                    ? \Carbon\Carbon::parse($s->class_enrolled_at)->format('M j, Y')
                                    : null,
            'course_count'  => (int) $s->teacher_course_count,
            'show_url'      => route('teacher.students.show', $s->id),
        ])->values()->toArray();
    @endphp

    <div
        x-data="{
            search: '',
            students: @js($studentData),
            get filtered() {
                const q = this.search.toLowerCase().trim();
                if (!q) return this.students;
                return this.students.filter(s =>
                    s.name.toLowerCase().includes(q) || s.email.toLowerCase().includes(q)
                );
            },
        }"
        class="space-y-4 animate-fade-up"
    >

        {{-- ─── Search ─── --}}
        <x-card class="p-4">
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2
                             text-outline text-[18px] pointer-events-none">search</span>
                <input
                    type="search"
                    x-model.debounce.150ms="search"
                    placeholder="Search by name or email…"
                    class="w-full pl-10 pr-4 py-2.5 bg-surface-container-low rounded-[16px] text-sm
                           border border-outline-variant/60 placeholder:text-outline
                           focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary
                           transition-colors"
                >
            </div>
        </x-card>

        {{-- ─── Student List ─── --}}
        <x-card class="overflow-hidden">

            {{-- Table header --}}
            <div class="overflow-x-auto">
                <table class="w-full min-w-[520px]">
                    <thead>
                        <tr class="border-b border-outline-variant/20">
                            <th class="px-5 py-3 text-left text-[11px] font-semibold tracking-widest text-outline uppercase">Student</th>
                            <th class="px-5 py-3 text-left text-[11px] font-semibold tracking-widest text-outline uppercase hidden sm:table-cell">Status</th>
                            <th class="px-5 py-3 text-left text-[11px] font-semibold tracking-widest text-outline uppercase hidden md:table-cell">Joined Class</th>
                            <th class="px-5 py-3 text-left text-[11px] font-semibold tracking-widest text-outline uppercase hidden lg:table-cell">Courses</th>
                            <th class="px-3 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/15">
                        <template x-for="s in filtered" :key="s.id">
                            <tr
                                class="hover:bg-surface-container-low/60 transition-colors duration-100 cursor-pointer group"
                                @click="window.location = s.show_url"
                            >
                                {{-- Avatar + name + email --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-9 h-9 rounded-full shrink-0 overflow-hidden
                                                    bg-primary-container border border-outline-variant/30
                                                    flex items-center justify-center">
                                            <template x-if="s.avatar">
                                                <img :src="s.avatar" :alt="s.name" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!s.avatar">
                                                <span class="text-xs font-semibold text-on-primary"
                                                      style="font-family: var(--font-display);"
                                                      x-text="s.initials"></span>
                                            </template>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-on-surface truncate max-w-[180px] sm:max-w-[240px]"
                                               x-text="s.name"></p>
                                            <p class="text-xs text-on-surface-variant truncate max-w-[180px] sm:max-w-[240px]"
                                               x-text="s.email"></p>
                                            {{-- Status visible on smallest screens only --}}
                                            <div class="mt-1 sm:hidden">
                                                <template x-if="s.active">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gold/20 text-primary">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-gold"></span>
                                                        Active
                                                    </span>
                                                </template>
                                                <template x-if="!s.active">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-surface-container text-on-surface-variant">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-outline-variant"></span>
                                                        Inactive
                                                    </span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td class="px-5 py-4 hidden sm:table-cell">
                                    <template x-if="s.active">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gold/20 text-primary">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gold"></span>
                                            Active
                                        </span>
                                    </template>
                                    <template x-if="!s.active">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-surface-container text-on-surface-variant">
                                            <span class="w-1.5 h-1.5 rounded-full bg-outline-variant"></span>
                                            Inactive
                                        </span>
                                    </template>
                                </td>

                                {{-- Joined date --}}
                                <td class="px-5 py-4 text-sm text-on-surface-variant hidden md:table-cell"
                                    x-text="s.enrolled_at ?? '—'"></td>

                                {{-- Course count --}}
                                <td class="px-5 py-4 hidden lg:table-cell">
                                    <span class="text-sm text-on-surface-variant"
                                          x-text="s.course_count + ' ' + (s.course_count === 1 ? 'course' : 'courses')">
                                    </span>
                                </td>

                                {{-- Arrow --}}
                                <td class="px-3 py-4">
                                    <span class="material-symbols-outlined text-[18px] text-outline
                                                 group-hover:text-primary transition-colors">
                                        chevron_right
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- No search results --}}
            <div x-show="search.trim() && filtered.length === 0"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="py-12 text-center border-t border-outline-variant/15"
                 x-cloak>
                <span class="material-symbols-outlined text-[28px] text-outline">search_off</span>
                <p class="text-sm text-on-surface-variant mt-2">
                    No students match "<span x-text="search" class="font-medium text-on-surface"></span>"
                </p>
            </div>

        </x-card>

    </div>

@endif

@endsection
