@extends('layouts.admin')

@section('title', 'Tokens')

@php
    $lifetimeOptions = [
        ['value' => 15,  'unit' => 'minutes', 'label' => '15 minutes'],
        ['value' => 30,  'unit' => 'minutes', 'label' => '30 minutes'],
        ['value' => 45,  'unit' => 'minutes', 'label' => '45 minutes'],
        ['value' => 1,   'unit' => 'hours',   'label' => '1 hour'],
        ['value' => 2,   'unit' => 'hours',   'label' => '2 hours'],
        ['value' => 4,   'unit' => 'hours',   'label' => '4 hours'],
        ['value' => 6,   'unit' => 'hours',   'label' => '6 hours'],
        ['value' => 12,  'unit' => 'hours',   'label' => '12 hours'],
        ['value' => 24,  'unit' => 'hours',   'label' => '24 hours'],
        ['value' => 48,  'unit' => 'hours',   'label' => '48 hours'],
    ];
@endphp

@section('content')

{{-- ─── Page Header ─── --}}
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
            Tokens
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            Generate and revoke enrollment tokens on behalf of any teacher.
        </p>
    </div>
</div>

{{-- Flash messages --}}
@if(session('success'))
    <div class="flex items-center gap-3 px-4 py-3 mb-6 rounded-[16px] bg-surface-container text-on-surface text-sm
                border border-outline-variant/30 animate-fade-up">
        <span class="material-symbols-outlined text-[18px] text-primary shrink-0">check_circle</span>
        {{ session('success') }}
    </div>
@endif


{{-- ─── Teacher Selector ─── --}}
<x-card class="mb-6 animate-fade-up">
    <div class="px-6 py-4 border-b border-outline-variant/20">
        <p class="text-sm font-semibold text-on-surface" style="font-family: var(--font-display);">
            Select Teacher
        </p>
        <p class="text-xs text-on-surface-variant mt-0.5">
            Choose a teacher to view and manage their tokens.
        </p>
    </div>
    <div class="p-5">
        @if($teachers->isEmpty())
            <p class="text-sm text-on-surface-variant">No teachers found.</p>
        @else
            <div class="max-w-sm">
                <label for="teacher-select"
                       class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-2">
                    Teacher
                </label>
                <select
                    id="teacher-select"
                    onchange="if(this.value) window.location='{{ route('admin.tokens.index') }}?teacher_id=' + this.value"
                    class="w-full px-4 py-2.5 bg-surface-white border border-outline-variant/60 rounded-[16px]
                           text-sm text-on-surface focus:outline-none focus:ring-1 focus:ring-primary
                           focus:border-primary transition-colors cursor-pointer">
                    <option value="">Select a teacher…</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}" {{ optional($teacher)->id === $t->id ? 'selected' : '' }}>
                            {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>
</x-card>


@if(!$teacher)

    {{-- ─── No Teacher Selected — Prompt State ─── --}}
    <div class="flex flex-col items-center gap-3 py-20 text-center animate-fade-up">
        <div class="w-14 h-14 rounded-2xl bg-surface-container flex items-center justify-center">
            <span class="material-symbols-outlined text-[28px] text-outline animate-float">key</span>
        </div>
        <p class="text-base font-semibold text-on-surface">Select a teacher to manage their tokens</p>
        <p class="text-sm text-on-surface-variant max-w-xs">
            Choose a teacher from the dropdown above to view and generate class and course tokens on their behalf.
        </p>
    </div>

@else

    {{-- ══════════════════════════════════════════════════════
         SECTION 1 — CLASS TOKENS
    ══════════════════════════════════════════════════════ --}}
    <div class="space-y-4">

        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <div class="w-7 h-7 rounded-lg bg-primary-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-on-primary text-[15px]">group</span>
            </div>
            <h2 class="text-base font-semibold text-on-surface" style="font-family: var(--font-display);">
                Class Tokens
            </h2>
            <span class="text-xs text-on-surface-variant hidden sm:inline">
                · Students use these to join {{ $teacher->name }}'s class
            </span>
        </div>

        {{-- Generate Class Token --}}
        <div x-data="{
                lifetimeValue: 30,
                lifetimeUnit: 'minutes',
                submitting: false,
                maxUses: 30,
                options: @js($lifetimeOptions),
                setLifetime(opt) { this.lifetimeValue = opt.value; this.lifetimeUnit = opt.unit; },
                get selectedLabel() {
                    const o = this.options.find(o => o.value === this.lifetimeValue && o.unit === this.lifetimeUnit);
                    return o ? o.label : this.lifetimeValue + ' ' + this.lifetimeUnit;
                },
             }"
             class="animate-fade-up">
            <x-card>
                <div class="px-6 py-4 border-b border-outline-variant/20">
                    <p class="text-sm font-semibold text-on-surface" style="font-family: var(--font-display);">
                        Generate Class Token
                    </p>
                    <p class="text-xs text-on-surface-variant mt-0.5">
                        Generates a class enrollment token for {{ $teacher->name }}.
                    </p>
                </div>

                <div class="p-5 flex flex-col sm:flex-row gap-4 sm:items-end">
                    <input type="hidden" name="type"       value="class"           form="class-token-create-form">
                    <input type="hidden" name="teacher_id" value="{{ $teacher->id }}" form="class-token-create-form">

                    {{-- Lifetime picker --}}
                    <div class="flex-1 min-w-0">
                        <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-2">
                            Expires after
                        </label>
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button
                                type="button"
                                @click="open = !open"
                                class="w-full flex items-center justify-between pl-4 pr-3 py-2.5
                                       bg-surface-white border border-outline-variant/60 rounded-[16px]
                                       text-sm text-on-surface cursor-pointer
                                       focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary
                                       transition-colors hover:border-primary/50">
                                <span x-text="selectedLabel">30 minutes</span>
                                <span class="material-symbols-outlined text-outline text-[18px]"
                                      :class="open ? 'rotate-180' : ''"
                                      style="transition: transform 150ms ease">expand_more</span>
                            </button>
                            <div x-show="open" x-cloak
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                                 class="absolute z-10 mt-1 w-full bg-surface-white border border-outline-variant/40
                                        rounded-[16px] shadow-lg overflow-hidden">
                                <template x-for="opt in options" :key="opt.label">
                                    <button
                                        type="button"
                                        @click="setLifetime(opt); open = false"
                                        :class="(lifetimeValue === opt.value && lifetimeUnit === opt.unit)
                                            ? 'bg-gold/10 text-primary font-semibold'
                                            : 'text-on-surface hover:bg-surface-container-low'"
                                        class="w-full text-left px-4 py-2.5 text-sm transition-colors cursor-pointer"
                                        x-text="opt.label">
                                    </button>
                                </template>
                            </div>
                        </div>
                        <input type="hidden" name="lifetime_value" :value="lifetimeValue" form="class-token-create-form">
                        <input type="hidden" name="lifetime_unit"  :value="lifetimeUnit"  form="class-token-create-form">
                    </div>

                    {{-- Max uses --}}
                    <div class="flex-1 min-w-0">
                        <label for="class-token-max-uses"
                               class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-2">
                            Max uses
                        </label>
                        <input
                            id="class-token-max-uses"
                            type="number"
                            name="max_uses"
                            x-model.number="maxUses"
                            min="1" max="1000"
                            form="class-token-create-form"
                            class="w-full px-4 py-2.5 bg-surface-white border border-outline-variant/60
                                   rounded-[16px] text-sm focus:outline-none focus:ring-1 focus:ring-primary
                                   focus:border-primary transition-colors">
                    </div>

                    {{-- Submit --}}
                    <button
                        type="submit"
                        form="class-token-create-form"
                        @click="setTimeout(() => submitting = true, 0)"
                        :disabled="submitting"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gold text-primary
                               text-sm font-semibold rounded-[24px] hover:bg-gold/90
                               w-full sm:w-auto
                               active:scale-[0.96] transition-all duration-150 cursor-pointer
                               disabled:opacity-60 disabled:cursor-not-allowed sm:shrink-0">
                        <span class="material-symbols-outlined text-[18px]"
                              :class="submitting ? 'animate-spin' : ''"
                              x-text="submitting ? 'progress_activity' : 'key'">key</span>
                        <span x-text="submitting ? 'Generating…' : 'Generate Token'">Generate Token</span>
                    </button>
                </div>
            </x-card>
        </div>

        {{-- Class Tokens List --}}
        <x-card class="overflow-hidden animate-fade-up">
            <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-outline-variant/20">
                <p class="text-sm font-semibold text-on-surface" style="font-family: var(--font-display);">
                    Class Tokens
                </p>
                <span class="text-xs text-on-surface-variant">{{ $classTokens->count() }} total</span>
            </div>

            @if($classTokens->isEmpty())
                <div class="py-10 flex flex-col items-center gap-2 text-center px-4">
                    <span class="material-symbols-outlined text-outline text-[28px] animate-float">key</span>
                    <p class="text-xs text-on-surface-variant">No class tokens for this teacher yet.</p>
                </div>
            @else
                <ul class="divide-y divide-outline-variant/20">
                    @foreach($classTokens as $token)
                    @php
                        $expired       = $token->isExpired();
                        $usesRemaining = max(0, $token->max_uses - $token->uses_count);
                    @endphp
                    <li class="px-5 py-3.5 min-w-0 hover:bg-surface-container-low/40 transition-colors duration-200"
                        x-data="{ copied: false }">
                        <div class="flex items-center justify-between gap-2 min-w-0 mb-1">
                            <div class="flex items-center gap-2 min-w-0">
                                <code class="text-xs font-mono font-semibold text-primary bg-surface-container
                                             px-2.5 py-1 rounded-lg tracking-widest min-w-0 truncate">
                                    {{ $token->token_value }}
                                </code>
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $token->token_value }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                    class="shrink-0 inline-flex items-center gap-1 text-[11px] text-outline
                                           hover:text-primary transition-colors cursor-pointer"
                                    :title="copied ? 'Copied!' : 'Copy token'">
                                    <span class="material-symbols-outlined text-[14px]"
                                          x-text="copied ? 'check' : 'content_copy'">content_copy</span>
                                </button>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold shrink-0
                                         {{ $expired ? 'bg-surface-container text-on-surface-variant' : 'bg-gold/20 text-primary' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $expired ? 'bg-outline-variant' : 'bg-gold' }}"></span>
                                {{ $expired ? 'Expired' : 'Active' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2.5 flex-wrap justify-between">
                            <div class="flex items-center gap-3 text-[11px] text-on-surface-variant flex-wrap">
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[13px]">schedule</span>
                                    {{ $token->expires_at->diffForHumans() }}
                                </span>
                                <span class="text-outline-variant/60">·</span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[13px]">group</span>
                                    {{ $usesRemaining }}/{{ $token->max_uses }} uses remaining
                                </span>
                            </div>
                            <button type="button"
                                    onclick="confirmDelete('class token', document.getElementById('delete-token-form-{{ $token->id }}'))"
                                    class="inline-flex items-center gap-1 text-[11px] text-error shrink-0
                                           hover:text-error/80 transition-colors cursor-pointer">
                                <span class="material-symbols-outlined text-[13px]">delete</span>
                                Revoke
                            </button>
                        </div>
                    </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

    </div>{{-- end class tokens section --}}


    {{-- ══════════════════════════════════════════════════════
         SECTION 2 — COURSE TOKENS
    ══════════════════════════════════════════════════════ --}}
    <div class="space-y-4 mt-10" id="course-tokens">

        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <div class="w-7 h-7 rounded-lg bg-gold/20 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-primary text-[15px]">library_books</span>
            </div>
            <h2 class="text-base font-semibold text-on-surface" style="font-family: var(--font-display);">
                Course Tokens
            </h2>
            <span class="text-xs text-on-surface-variant hidden sm:inline">
                · Students use these to enroll in a specific course
            </span>
        </div>

        {{-- Generate Course Token --}}
        <div x-data="{
                courseId: '',
                lifetimeValue: 30,
                lifetimeUnit: 'minutes',
                maxUses: 30,
                submitting: false,
                options: @js($lifetimeOptions),
                setLifetime(opt) { this.lifetimeValue = opt.value; this.lifetimeUnit = opt.unit; },
                get selectedLabel() {
                    const o = this.options.find(o => o.value === this.lifetimeValue && o.unit === this.lifetimeUnit);
                    return o ? o.label : this.lifetimeValue + ' ' + this.lifetimeUnit;
                },
             }"
             class="animate-fade-up">
            <x-card>
                <div class="px-6 py-4 border-b border-outline-variant/20">
                    <p class="text-sm font-semibold text-on-surface" style="font-family: var(--font-display);">
                        Generate Course Token
                    </p>
                    <p class="text-xs text-on-surface-variant mt-0.5">
                        Generates a course enrollment token for one of {{ $teacher->name }}'s courses.
                    </p>
                </div>

                @if($courses->isEmpty())
                    <div class="p-5 flex items-center gap-3 text-sm text-on-surface-variant">
                        <span class="material-symbols-outlined text-[18px] text-outline">info</span>
                        This teacher has no courses yet.
                    </div>
                @else
                    <div class="p-5 flex flex-col gap-4">
                        <input type="hidden" name="type"       value="course"              form="course-token-create-form">
                        <input type="hidden" name="teacher_id" value="{{ $teacher->id }}"  form="course-token-create-form">
                        <input type="hidden" name="course_id"  :value="courseId"           form="course-token-create-form">

                        {{-- Course picker —scoped to selected teacher --}}
                        <div>
                            <label for="course-token-course"
                                   class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-2">
                                Course
                            </label>
                            <select
                                id="course-token-course"
                                x-model="courseId"
                                class="w-full px-4 py-2.5 bg-surface-white border border-outline-variant/60 rounded-[16px]
                                       text-sm text-on-surface focus:outline-none focus:ring-1 focus:ring-primary
                                       focus:border-primary transition-colors cursor-pointer">
                                <option value="">Select a course…</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4 sm:items-end">
                            {{-- Lifetime picker --}}
                            <div class="flex-1 min-w-0">
                                <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-2">
                                    Expires after
                                </label>
                                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                    <button
                                        type="button"
                                        @click="open = !open"
                                        class="w-full flex items-center justify-between pl-4 pr-3 py-2.5
                                               bg-surface-white border border-outline-variant/60 rounded-[16px]
                                               text-sm text-on-surface cursor-pointer
                                               focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary
                                               transition-colors hover:border-primary/50">
                                        <span x-text="selectedLabel">30 minutes</span>
                                        <span class="material-symbols-outlined text-outline text-[18px]"
                                              :class="open ? 'rotate-180' : ''"
                                              style="transition: transform 150ms ease">expand_more</span>
                                    </button>
                                    <div x-show="open" x-cloak
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                         x-transition:leave="transition ease-in duration-100"
                                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                         x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                                         class="absolute z-10 mt-1 w-full bg-surface-white border border-outline-variant/40
                                                rounded-[16px] shadow-lg overflow-hidden">
                                        <template x-for="opt in options" :key="opt.label">
                                            <button
                                                type="button"
                                                @click="setLifetime(opt); open = false"
                                                :class="(lifetimeValue === opt.value && lifetimeUnit === opt.unit)
                                                    ? 'bg-gold/10 text-primary font-semibold'
                                                    : 'text-on-surface hover:bg-surface-container-low'"
                                                class="w-full text-left px-4 py-2.5 text-sm transition-colors cursor-pointer"
                                                x-text="opt.label">
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <input type="hidden" name="lifetime_value" :value="lifetimeValue" form="course-token-create-form">
                                <input type="hidden" name="lifetime_unit"  :value="lifetimeUnit"  form="course-token-create-form">
                            </div>

                            {{-- Max uses --}}
                            <div class="flex-1 min-w-0">
                                <label for="course-token-max-uses"
                                       class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-2">
                                    Max uses
                                </label>
                                <input
                                    id="course-token-max-uses"
                                    type="number"
                                    name="max_uses"
                                    x-model.number="maxUses"
                                    min="1" max="1000"
                                    form="course-token-create-form"
                                    class="w-full px-4 py-2.5 bg-surface-white border border-outline-variant/60
                                           rounded-[16px] text-sm focus:outline-none focus:ring-1 focus:ring-primary
                                           focus:border-primary transition-colors">
                            </div>

                            {{-- Submit --}}
                            <button
                                type="submit"
                                form="course-token-create-form"
                                @click="setTimeout(() => submitting = true, 0)"
                                :disabled="submitting || !courseId"
                                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gold text-primary
                                       text-sm font-semibold rounded-[24px] hover:bg-gold/90
                                       w-full sm:w-auto
                                       active:scale-[0.96] transition-all duration-150 cursor-pointer
                                       disabled:opacity-60 disabled:cursor-not-allowed sm:shrink-0">
                                <span class="material-symbols-outlined text-[18px]"
                                      :class="submitting ? 'animate-spin' : ''"
                                      x-text="submitting ? 'progress_activity' : 'key'">key</span>
                                <span x-text="submitting ? 'Generating…' : 'Generate Token'">Generate Token</span>
                            </button>
                        </div>
                    </div>
                @endif
            </x-card>
        </div>

        {{-- Course Tokens List --}}
        <x-card class="overflow-hidden animate-fade-up">
            <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-outline-variant/20">
                <p class="text-sm font-semibold text-on-surface" style="font-family: var(--font-display);">
                    Course Tokens
                </p>
                <span class="text-xs text-on-surface-variant">{{ $courseTokens->count() }} total</span>
            </div>

            @if($courseTokens->isEmpty())
                <div class="py-10 flex flex-col items-center gap-2 text-center px-4">
                    <span class="material-symbols-outlined text-outline text-[28px] animate-float">key</span>
                    <p class="text-xs text-on-surface-variant">No course tokens for this teacher yet.</p>
                </div>
            @else
                <ul class="divide-y divide-outline-variant/20">
                    @foreach($courseTokens as $token)
                    @php
                        $expired       = $token->isExpired();
                        $usesRemaining = max(0, $token->max_uses - $token->uses_count);
                    @endphp
                    <li class="px-5 py-3.5 min-w-0 hover:bg-surface-container-low/40 transition-colors duration-200"
                        x-data="{ copied: false }">
                        <div class="flex items-center justify-between gap-2 min-w-0 mb-1">
                            <div class="flex items-center gap-2 min-w-0">
                                <code class="text-xs font-mono font-semibold text-primary bg-surface-container
                                             px-2.5 py-1 rounded-lg tracking-widest min-w-0 truncate">
                                    {{ $token->token_value }}
                                </code>
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $token->token_value }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                    class="shrink-0 inline-flex items-center gap-1 text-[11px] text-outline
                                           hover:text-primary transition-colors cursor-pointer"
                                    :title="copied ? 'Copied!' : 'Copy token'">
                                    <span class="material-symbols-outlined text-[14px]"
                                          x-text="copied ? 'check' : 'content_copy'">content_copy</span>
                                </button>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold shrink-0
                                         {{ $expired ? 'bg-surface-container text-on-surface-variant' : 'bg-gold/20 text-primary' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $expired ? 'bg-outline-variant' : 'bg-gold' }}"></span>
                                {{ $expired ? 'Expired' : 'Active' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2.5 flex-wrap justify-between">
                            <div class="flex items-center gap-3 text-[11px] text-on-surface-variant flex-wrap">
                                @if($token->course)
                                    <span class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[13px]">library_books</span>
                                        {{ Str::limit($token->course->title, 30) }}
                                    </span>
                                    <span class="text-outline-variant/60">·</span>
                                @endif
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[13px]">schedule</span>
                                    {{ $token->expires_at->diffForHumans() }}
                                </span>
                                <span class="text-outline-variant/60">·</span>
                                <span class="flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[13px]">group</span>
                                    {{ $usesRemaining }}/{{ $token->max_uses }} uses remaining
                                </span>
                            </div>
                            <button type="button"
                                    onclick="confirmDelete('course token', document.getElementById('delete-token-form-{{ $token->id }}'))"
                                    class="inline-flex items-center gap-1 text-[11px] text-error shrink-0
                                           hover:text-error/80 transition-colors cursor-pointer">
                                <span class="material-symbols-outlined text-[13px]">delete</span>
                                Revoke
                            </button>
                        </div>
                    </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

    </div>{{-- end course tokens section --}}


    {{-- ─── Standalone forms — none nested inside each other or inside any wrapper form ─── --}}

    <form id="class-token-create-form"
          method="POST"
          action="{{ route('admin.tokens.store') }}"
          class="hidden">
        @csrf
    </form>

    <form id="course-token-create-form"
          method="POST"
          action="{{ route('admin.tokens.store') }}"
          class="hidden">
        @csrf
    </form>

    @foreach($classTokens as $token)
    <form id="delete-token-form-{{ $token->id }}"
          method="POST"
          action="{{ route('admin.tokens.destroy', $token->id) }}"
          class="hidden">
        @csrf
        @method('DELETE')
    </form>
    @endforeach

    @foreach($courseTokens as $token)
    <form id="delete-token-form-{{ $token->id }}"
          method="POST"
          action="{{ route('admin.tokens.destroy', $token->id) }}"
          class="hidden">
        @csrf
        @method('DELETE')
    </form>
    @endforeach

@endif

@endsection
