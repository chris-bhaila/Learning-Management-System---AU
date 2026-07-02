@extends($layout)

@section('title', 'Token Usage — ' . $tokenValue)

@section('content')

{{-- ─── Page Header ─── --}}
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-8">
    <div class="min-w-0">
        <div class="flex items-center gap-2 mb-1">
            <a href="{{ $backRoute }}"
               class="inline-flex items-center gap-1 text-xs text-on-surface-variant hover:text-primary
                      transition-colors duration-150 cursor-pointer">
                <span class="material-symbols-outlined text-[14px]">arrow_back</span>
                Tokens
            </a>
        </div>
        <h1 class="text-2xl font-bold text-primary truncate" style="font-family: var(--font-display);">
            Token Usage
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            Enrollment history for token
            <code class="font-mono font-semibold bg-surface-container px-2 py-0.5 rounded-lg tracking-widest text-xs">
                {{ $tokenValue }}
            </code>
        </p>
    </div>
</div>


{{-- ══════════════════════════════════════════════════════
     STATE A — live token stats
══════════════════════════════════════════════════════ --}}
@if($token)
@php
    $expired       = $token->isExpired();
    $usesRemaining = max(0, $token->max_uses - $token->uses_count);
@endphp
<x-card class="mb-6 animate-fade-up">
    <div class="px-6 py-4 border-b border-outline-variant/20 flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg {{ $expired ? 'bg-error/10' : 'bg-gold/20' }} flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[15px] {{ $expired ? 'text-error' : 'text-primary' }}">key</span>
            </div>
            <p class="text-sm font-semibold text-on-surface" style="font-family: var(--font-display);">
                Live Token Stats
            </p>
        </div>
        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold shrink-0
                     {{ $expired ? 'bg-error/10 text-error' : 'bg-gold/20 text-primary' }}">
            <span class="material-symbols-outlined text-[13px]">{{ $expired ? 'cancel' : 'check_circle' }}</span>
            {{ $expired ? 'Expired' : 'Active' }}
        </span>
    </div>

    <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Type --}}
        <div class="flex flex-col gap-1">
            <span class="text-[10px] font-semibold text-on-surface-variant uppercase tracking-wide">Type</span>
            <span class="text-sm font-semibold text-on-surface flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px] text-outline">
                    {{ $token->isClassToken() ? 'group' : 'library_books' }}
                </span>
                {{ $token->isClassToken() ? 'Class token' : 'Course token' }}
            </span>
            @if($token->isCourseToken() && $token->course)
                <span class="text-xs text-on-surface-variant truncate">{{ $token->course->title }}</span>
            @endif
        </div>

        {{-- Expiry --}}
        <div class="flex flex-col gap-1">
            <span class="text-[10px] font-semibold text-on-surface-variant uppercase tracking-wide">Expires</span>
            <span class="text-sm font-semibold text-on-surface flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px] text-outline">schedule</span>
                {{ $token->expires_at->diffForHumans() }}
            </span>
            <span class="text-xs text-on-surface-variant">{{ $token->expires_at->format('d M Y, H:i') }}</span>
        </div>

        {{-- Uses --}}
        <div class="flex flex-col gap-1">
            <span class="text-[10px] font-semibold text-on-surface-variant uppercase tracking-wide">Usage</span>
            <span class="text-sm font-semibold text-on-surface flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px] text-outline">group</span>
                {{ $token->uses_count }}/{{ $token->max_uses }} used
            </span>
            <span class="text-xs text-on-surface-variant">{{ $usesRemaining }} remaining</span>
        </div>
    </div>
</x-card>

{{-- ══════════════════════════════════════════════════════
     STATE B — pruned token banner
══════════════════════════════════════════════════════ --}}
@else
<div class="flex items-start gap-3 px-4 py-4 mb-6 rounded-[16px] bg-surface-container
            border border-outline-variant/30 animate-fade-up">
    <span class="material-symbols-outlined text-[20px] text-outline shrink-0 mt-0.5">info</span>
    <div class="min-w-0">
        <p class="text-sm font-semibold text-on-surface">
            This token has expired and been removed from active tokens.
        </p>
        <p class="text-xs text-on-surface-variant mt-0.5">
            Showing historical usage only. Live stats are no longer available.
        </p>
    </div>
</div>
@endif


{{-- ══════════════════════════════════════════════════════
     USAGE HISTORY
══════════════════════════════════════════════════════ --}}
<div class="flex flex-wrap items-center gap-x-3 gap-y-1 mb-4 animate-fade-up">
    <div class="w-7 h-7 rounded-lg bg-surface-container flex items-center justify-center shrink-0">
        <span class="material-symbols-outlined text-primary text-[15px]">history</span>
    </div>
    <h2 class="text-base font-semibold text-on-surface" style="font-family: var(--font-display);">
        Usage History
    </h2>
    @php
        $successCount = $activities->whereNull('properties.reason')->count();
        $failedCount  = $activities->whereNotNull('properties.reason')->count();
    @endphp
    <span class="text-xs text-on-surface-variant">
        · {{ $successCount }} {{ Str::plural('enrollment', $successCount) }}
        @if($failedCount > 0)
            <span class="text-error/70">· {{ $failedCount }} failed {{ Str::plural('attempt', $failedCount) }}</span>
        @endif
    </span>
</div>

<x-card class="overflow-hidden animate-fade-up">
    @if($activities->isEmpty())
        <div class="py-16 flex flex-col items-center gap-2 text-center px-4">
            <span class="material-symbols-outlined text-outline text-[32px] animate-float">person_search</span>
            <p class="text-sm font-semibold text-on-surface">No one has used this token yet</p>
            <p class="text-xs text-on-surface-variant max-w-xs">
                Enrollment history will appear here once a student uses this token.
            </p>
        </div>
    @else
        <ul class="divide-y divide-outline-variant/20">
            @foreach($activities as $entry)
            @php
                $student     = $entry->causer;
                $props       = $entry->properties;
                $tokenType   = $props->get('token_type', 'class');
                $courseTitle = $props->get('course_title');
                $reason      = $props->get('reason');
                $failed      = $reason !== null;
                $initials    = $student ? strtoupper(substr($student->name, 0, 1)) : '?';
                $avatarUrl   = $student?->avatarUrl();

                $reasonLabels = [
                    'not_found'       => 'Token not found',
                    'expired'         => 'Token expired',
                    'already_in_class'  => 'Already in class',
                    'already_enrolled'  => 'Already enrolled',
                    'not_in_class'    => 'Class not joined first',
                ];
            @endphp
            <li class="px-5 py-3.5 min-w-0 transition-colors duration-200
                        {{ $failed ? 'bg-error/5 hover:bg-error/10' : 'hover:bg-surface-container-low/40' }}">
                <div class="flex items-center gap-3 min-w-0">
                    {{-- Avatar --}}
                    <div class="w-8 h-8 rounded-full {{ $failed ? 'bg-error/10' : 'bg-primary-container' }} flex items-center justify-center shrink-0 overflow-hidden">
                        @if($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="{{ $student?->name }}"
                                 class="w-full h-full object-cover {{ $failed ? 'opacity-60' : '' }}">
                        @else
                            <span class="text-xs font-bold {{ $failed ? 'text-error' : 'text-on-primary' }}">{{ $initials }}</span>
                        @endif
                    </div>

                    {{-- Info --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap min-w-0">
                            <span class="text-sm font-semibold {{ $failed ? 'text-on-surface-variant' : 'text-on-surface' }} truncate">
                                {{ $student?->name ?? 'Deleted user' }}
                            </span>
                            @if($failed)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold shrink-0
                                             bg-error/10 text-error">
                                    <span class="material-symbols-outlined text-[11px]">block</span>
                                    Failed — {{ $reasonLabels[$reason] ?? $reason }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold shrink-0
                                             {{ $tokenType === 'class' ? 'bg-primary-container/60 text-primary' : 'bg-gold/15 text-primary' }}">
                                    <span class="material-symbols-outlined text-[11px]">
                                        {{ $tokenType === 'class' ? 'group' : 'library_books' }}
                                    </span>
                                    {{ $tokenType === 'class' ? 'Joined class' : 'Enrolled' }}
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2.5 flex-wrap text-[11px] text-on-surface-variant mt-0.5">
                            @if($courseTitle)
                                <span class="flex items-center gap-1 truncate max-w-[180px]">
                                    <span class="material-symbols-outlined text-[12px]">library_books</span>
                                    {{ $courseTitle }}
                                </span>
                                <span class="text-outline-variant/60">·</span>
                            @endif
                            <span class="flex items-center gap-1 shrink-0">
                                <span class="material-symbols-outlined text-[12px]">schedule</span>
                                {{ $entry->created_at->diffForHumans() }}
                            </span>
                            <span class="text-outline-variant/60">·</span>
                            <span class="shrink-0">{{ $entry->created_at->format('d M Y, H:i') }}</span>
                        </div>
                    </div>
                </div>
            </li>
            @endforeach
        </ul>
    @endif
</x-card>

@endsection
