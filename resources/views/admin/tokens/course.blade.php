@extends('layouts.admin')

@section('title', 'Course Tokens')

@section('content')

{{-- ─── Page Header ─── --}}
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-8">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <a href="{{ route('admin.tokens.index', $teacher ? ['teacher_id' => $teacher->id] : []) }}"
               class="inline-flex items-center gap-1 text-xs text-on-surface-variant hover:text-primary
                      transition-colors duration-150 cursor-pointer">
                <span class="material-symbols-outlined text-[14px]">arrow_back</span>
                Tokens
            </a>
        </div>
        <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
            Course Tokens
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            All course enrollment tokens{{ $teacher ? ' for ' . $teacher->name : '' }}.
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
            Filter course tokens by teacher.
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
                    onchange="if(this.value) window.location='{{ route('admin.tokens.course') }}?teacher_id=' + this.value"
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

    {{-- ─── No Teacher Selected ─── --}}
    <div class="flex flex-col items-center gap-3 py-20 text-center animate-fade-up">
        <div class="w-14 h-14 rounded-2xl bg-surface-container flex items-center justify-center">
            <span class="material-symbols-outlined text-[28px] text-outline animate-float">library_books</span>
        </div>
        <p class="text-base font-semibold text-on-surface">Select a teacher to view their course tokens</p>
        <p class="text-sm text-on-surface-variant max-w-xs">
            Choose a teacher from the dropdown above to browse all of their course enrollment tokens.
        </p>
    </div>

@else

    {{-- ─── Section header ─── --}}
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mb-4 animate-fade-up">
        <div class="w-7 h-7 rounded-lg bg-gold/20 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-primary text-[15px]">library_books</span>
        </div>
        <h2 class="text-base font-semibold text-on-surface" style="font-family: var(--font-display);">
            {{ $teacher->name }}'s Course Tokens
        </h2>
        <span class="text-xs text-on-surface-variant hidden sm:inline">
            · {{ $tokens->total() }} total
        </span>
    </div>

    {{-- ─── Token list ─── --}}
    <x-card class="overflow-hidden animate-fade-up">
        @if($tokens->isEmpty())
            <div class="py-16 flex flex-col items-center gap-2 text-center px-4">
                <span class="material-symbols-outlined text-outline text-[28px] animate-float">key</span>
                <p class="text-sm font-semibold text-on-surface">No course tokens yet</p>
                <p class="text-xs text-on-surface-variant max-w-xs">
                    Generate course tokens from the
                    <a href="{{ route('admin.tokens.index', ['teacher_id' => $teacher->id]) }}"
                       class="text-primary hover:underline cursor-pointer">tokens page</a>.
                </p>
            </div>
        @else
            <ul class="divide-y divide-outline-variant/20">
                @foreach($tokens as $token)
                @php
                    $expired       = $token->isExpired();
                    $usesRemaining = max(0, $token->max_uses - $token->uses_count);
                @endphp
                <li class="px-5 py-3.5 min-w-0 hover:bg-surface-container-low/40 transition-colors duration-200">
                    <div class="flex items-center gap-2 min-w-0 mb-1">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold shrink-0
                                     {{ $expired ? 'bg-error/10 text-error' : 'bg-gold/20 text-primary' }}">
                            <span class="material-symbols-outlined text-[13px]">
                                {{ $expired ? 'cancel' : 'check_circle' }}
                            </span>
                            {{ $expired ? 'Expired' : 'Active' }}
                        </span>
                        <code class="text-xs font-mono font-semibold {{ $expired ? 'text-on-surface-variant line-through' : 'text-primary' }} bg-surface-container
                                     px-2.5 py-1 rounded-lg tracking-widest min-w-0 truncate">
                            {{ $token->token_value }}
                        </code>
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
                        <div class="flex items-center gap-3 shrink-0">
                            <a href="{{ route('admin.tokens.usage', $token->token_value) }}"
                               class="inline-flex items-center gap-1 text-[11px] text-on-surface-variant
                                      hover:text-primary transition-colors cursor-pointer">
                                <span class="material-symbols-outlined text-[13px]">bar_chart</span>
                                View usage
                            </a>
                            @unless($expired)
                            <button type="button"
                                    onclick="confirmDestructive(
                                        {{ Js::from('Revoke this course token?') }},
                                        {{ Js::from('Students will no longer be able to use this token to join ' . ($token->course->title ?? 'this course') . '.') }},
                                        document.getElementById('delete-token-form-{{ $token->id }}'),
                                        'Revoke'
                                    )"
                                    class="inline-flex items-center gap-1 text-[11px] text-error
                                           hover:text-error/80 transition-colors cursor-pointer">
                                <span class="material-symbols-outlined text-[13px]">block</span>
                                Revoke
                            </button>
                            @endunless
                        </div>
                    </div>
                </li>
                @endforeach
            </ul>

            {{-- Pagination --}}
            @if($tokens->hasPages())
                <div class="px-5 py-4 border-t border-outline-variant/20">
                    {{ $tokens->appends(['teacher_id' => $teacher->id])->links() }}
                </div>
            @endif
        @endif
    </x-card>

    {{-- Delete forms --}}
    @foreach($tokens as $token)
    <form id="delete-token-form-{{ $token->id }}"
          method="POST"
          action="{{ route('admin.tokens.revoke', $token->id) }}"
          class="hidden">
        @csrf
        @method('PATCH')
    </form>
    @endforeach

@endif

@endsection
