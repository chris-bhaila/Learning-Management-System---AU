@extends('layouts.admin')

@section('title', 'Activity Log')

@section('content')
@php
    use App\Helpers\ActivityLogHelper;

    $logs         ??= collect();
    $subjectTypes ??= collect();
    $totalToday   ??= 0;
    $totalWeek    ??= 0;

    $currentEvent   = request()->get('event', '');
    $currentSubject = request()->get('subject', '');
    $currentSearch  = request()->get('search', '');
    $currentDate    = request()->get('date', '');

    $eventConfig = [
        'created'  => ['label' => 'Created',  'icon' => 'add_circle',  'badge' => 'bg-emerald-50 text-emerald-700 border border-emerald-200'],
        'updated'  => ['label' => 'Updated',  'icon' => 'edit',        'badge' => 'bg-blue-50 text-blue-700 border border-blue-200'],
        'deleted'  => ['label' => 'Deleted',  'icon' => 'delete',      'badge' => 'bg-red-50 text-error border border-red-200'],
        'restored' => ['label' => 'Restored', 'icon' => 'restore',     'badge' => 'bg-amber-50 text-amber-700 border border-amber-200'],
    ];
@endphp

{{-- ─── Page Header ─── --}}
<div class="flex items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
            Activity Log
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            Full audit trail of all actions across EduNest.
        </p>
    </div>
    <span class="text-xs text-outline bg-surface-container px-3 py-1.5 rounded-full shrink-0">
        {{ now()->format('D, d M Y') }}
    </span>
</div>

{{-- ─── Stats ─── --}}
@php $total = method_exists($logs, 'total') ? $logs->total() : $logs->count(); @endphp
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 animate-fade-up">
    @foreach([
        ['label' => 'Total Events',  'value' => number_format($total),          'icon' => 'history',        'tint' => 'bg-surface-container'],
        ['label' => 'Today',         'value' => number_format($totalToday),      'icon' => 'today',          'tint' => 'bg-gold/10'],
        ['label' => 'This Week',     'value' => number_format($totalWeek),       'icon' => 'calendar_month', 'tint' => 'bg-surface-container'],
        ['label' => 'Showing',       'value' => number_format($logs->count()),   'icon' => 'filter_list',    'tint' => 'bg-surface-container'],
    ] as $stat)
        <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] p-4 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl {{ $stat['tint'] }} flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-primary text-[18px]">{{ $stat['icon'] }}</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-on-surface-variant font-medium">{{ $stat['label'] }}</p>
                <p class="text-xl font-bold text-primary leading-tight" style="font-family: var(--font-display);">
                    {{ $stat['value'] }}
                </p>
            </div>
        </div>
    @endforeach
</div>

{{-- ─── Filters ─── --}}
<div class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] p-5 space-y-4 animate-fade-up">

    <div class="flex flex-wrap items-center gap-3">

        {{-- Search --}}
        <form method="GET" id="logFilterForm" class="relative flex-1 min-w-[180px] max-w-xs">
            <input type="hidden" name="event"   value="{{ $currentEvent }}">
            <input type="hidden" name="subject" value="{{ $currentSubject }}">
            <input type="hidden" name="date"    value="{{ $currentDate }}">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2
                         text-outline text-[18px] pointer-events-none">search</span>
            <input
                type="text"
                name="search"
                value="{{ $currentSearch }}"
                placeholder="Search by user name…"
                autocomplete="off"
                class="w-full pl-10 pr-4 py-2.5 rounded-[16px] text-sm bg-surface-container-low
                       border border-outline-variant/60 placeholder:text-outline
                       focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"
            >
        </form>

        {{-- Subject type --}}
        <div class="relative shrink-0">
            <select onchange="updateLogFilter('subject', this.value)"
                    class="appearance-none pl-4 pr-9 py-2.5 rounded-[16px] text-sm cursor-pointer
                           bg-surface-container-low border border-outline-variant/60 text-on-surface
                           focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                <option value="">All models</option>
                @foreach($subjectTypes as $type)
                    <option value="{{ class_basename($type) }}"
                            {{ $currentSubject === class_basename($type) ? 'selected' : '' }}>
                        {{ class_basename($type) }}
                    </option>
                @endforeach
            </select>
            <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2
                         text-outline text-[18px] pointer-events-none">expand_more</span>
        </div>

        {{-- Date range --}}
        <div class="relative shrink-0">
            <select onchange="updateLogFilter('date', this.value)"
                    class="appearance-none pl-4 pr-9 py-2.5 rounded-[16px] text-sm cursor-pointer
                           bg-surface-container-low border border-outline-variant/60 text-on-surface
                           focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                <option value=""      {{ $currentDate === ''      ? 'selected' : '' }}>All time</option>
                <option value="today" {{ $currentDate === 'today' ? 'selected' : '' }}>Today</option>
                <option value="week"  {{ $currentDate === 'week'  ? 'selected' : '' }}>This week</option>
                <option value="month" {{ $currentDate === 'month' ? 'selected' : '' }}>This month</option>
            </select>
            <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2
                         text-outline text-[18px] pointer-events-none">expand_more</span>
        </div>

        {{-- Clear all --}}
        @if($currentEvent || $currentSubject || $currentSearch || $currentDate)
            <a href="{{ route('admin.logs.index') }}"
               class="inline-flex items-center gap-1.5 px-3 py-2.5 rounded-[16px] text-sm cursor-pointer
                      text-on-surface-variant hover:text-primary hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[16px]">close</span>
                Clear filters
            </a>
        @endif

    </div>

    {{-- Event tabs --}}
    <div class="flex items-center gap-2 flex-wrap">
        <button type="button" onclick="updateLogFilter('event', '')"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium cursor-pointer
                       transition-all duration-150
                       {{ $currentEvent === '' ? 'bg-gold text-primary shadow-sm' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' }}">
            <span class="material-symbols-outlined text-[16px]">all_inclusive</span>
            All Events
        </button>
        @foreach($eventConfig as $key => $cfg)
            <button type="button" onclick="updateLogFilter('event', '{{ $key }}')"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium cursor-pointer
                           transition-all duration-150
                           {{ $currentEvent === $key ? 'bg-gold text-primary shadow-sm' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' }}">
                <span class="material-symbols-outlined text-[16px]">{{ $cfg['icon'] }}</span>
                {{ $cfg['label'] }}
            </button>
        @endforeach
    </div>

</div>

{{-- ─── Log Table ─── --}}
<div x-data="logDetailModal()"
     class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] overflow-hidden animate-fade-up">

    <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/30">
        <h2 class="text-base font-semibold text-primary" style="font-family: var(--font-display);">Events</h2>
        @if(method_exists($logs, 'currentPage'))
            <span class="text-xs text-outline">
                Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}
            </span>
        @endif
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-outline-variant/30 bg-surface-container-low/50">
                    <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase">Event</th>
                    <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase">Subject</th>
                    <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase hidden sm:table-cell">Caused By</th>
                    <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase hidden lg:table-cell">Changes</th>
                    <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase hidden md:table-cell">Time</th>
                    <th class="px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase text-center">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20">
                @forelse($logs as $log)
                    @php
                        $event       = $log->event ?? 'updated';
                        $cfg         = $eventConfig[$event] ?? ['label' => ucfirst($event), 'icon' => 'info', 'badge' => 'bg-surface-container text-on-surface-variant border border-outline-variant'];
                        $changes     = $log->attribute_changes ?? collect();
                        $newAttrs    = (array) ($changes->get('attributes') ?? []);
                        $oldAttrs    = (array) ($changes->get('old') ?? []);

                        // Build structured diff (server-side, with FK resolution and HTML stripping)
                        $diff = ActivityLogHelper::buildDiff($newAttrs, $oldAttrs, $event);

                        // Inline summary: pick up to 3 rows for the Changes column
                        $summaryRows  = array_slice($diff, 0, 3);
                        $changeCount  = count($diff);

                        $subjectName = $log->subject?->name ?? $log->subject?->title ?? $log->subject?->token_value ?? null;
                        $causerName  = $log->causer?->name ?? null;
                    @endphp
                    <tr class="hover:bg-surface-container-low/40 transition-colors align-top">

                        {{-- Event --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $cfg['badge'] }}">
                                <span class="material-symbols-outlined text-[13px]">{{ $cfg['icon'] }}</span>
                                {{ $cfg['label'] }}
                            </span>
                        </td>

                        {{-- Subject --}}
                        <td class="px-6 py-4">
                            @if($log->subject_type)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold
                                             bg-surface-container-high text-on-surface-variant tracking-wide">
                                    {{ class_basename($log->subject_type) }}
                                </span>
                                @if($log->subject)
                                    <p class="text-xs text-on-surface mt-1 font-medium max-w-[160px] truncate">
                                        {{ $subjectName ?? "ID #{$log->subject_id}" }}
                                    </p>
                                @else
                                    <p class="text-xs text-outline mt-1 italic">ID #{{ $log->subject_id }} (deleted)</p>
                                @endif
                            @else
                                <span class="text-xs text-outline">—</span>
                            @endif
                        </td>

                        {{-- Causer --}}
                        <td class="px-6 py-4 hidden sm:table-cell">
                            @if($log->causer)
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-primary-container flex items-center justify-center
                                                text-[10px] font-semibold text-on-primary shrink-0 overflow-hidden select-none">
                                        @if($log->causer->avatarUrl())
                                            <img src="{{ $log->causer->avatarUrl() }}" alt="" class="w-full h-full object-cover">
                                        @else
                                            {{ strtoupper(substr($log->causer->name ?? '?', 0, 2)) }}
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-medium text-on-surface truncate max-w-[130px]">
                                            {{ $log->causer->name ?? 'Unknown' }}
                                        </p>
                                        <p class="text-[10px] text-outline">
                                            {{ ucfirst($log->causer->role?->name ?? class_basename($log->causer_type ?? '')) }}
                                        </p>
                                    </div>
                                </div>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs text-outline italic">
                                    <span class="material-symbols-outlined text-[13px]">computer</span>
                                    System
                                </span>
                            @endif
                        </td>

                        {{-- Changes summary --}}
                        <td class="px-6 py-4 hidden lg:table-cell">
                            @if($changeCount > 0)
                                <div class="space-y-1 max-w-[240px]">
                                    @foreach($summaryRows as $row)
                                        <div class="flex items-baseline gap-1 text-[11px] min-w-0">
                                            <span class="text-outline shrink-0 font-medium">{{ $row['label'] }}:</span>
                                            @if($event === 'updated' && $row['old'] !== null)
                                                <span class="text-outline/70 line-through truncate max-w-[55px]">{{ $row['old'] }}</span>
                                                <span class="material-symbols-outlined text-[11px] text-outline shrink-0 leading-none" style="vertical-align:middle">arrow_forward</span>
                                            @endif
                                            <span class="text-on-surface truncate">{{ $row['new'] ?? $row['old'] }}</span>
                                        </div>
                                    @endforeach
                                    @if($changeCount > 3)
                                        <p class="text-[10px] text-outline">+{{ $changeCount - 3 }} more</p>
                                    @endif
                                </div>
                            @else
                                <span class="text-xs text-outline">—</span>
                            @endif
                        </td>

                        {{-- Time --}}
                        <td class="px-6 py-4 hidden md:table-cell">
                            <p class="text-xs text-on-surface-variant whitespace-nowrap">
                                {{ $log->created_at->diffForHumans() }}
                            </p>
                            <p class="text-[10px] text-outline mt-0.5 whitespace-nowrap">
                                {{ $log->created_at->format('d M Y, H:i') }}
                            </p>
                        </td>

                        {{-- Detail button --}}
                        <td class="px-6 py-4 text-center">
                            <button
                                type="button"
                                @click="open(@js([
                                    'event'       => $event,
                                    'eventLabel'  => $cfg['label'],
                                    'eventBadge'  => $cfg['badge'],
                                    'eventIcon'   => $cfg['icon'],
                                    'subjectType' => $log->subject_type ? class_basename($log->subject_type) : null,
                                    'subjectName' => $subjectName ?? ($log->subject_id ? 'ID #' . $log->subject_id : null),
                                    'causerName'  => $causerName,
                                    'causerRole'  => $log->causer?->role?->name ? ucfirst($log->causer->role->name) : null,
                                    'timestamp'   => $log->created_at->format('d M Y, H:i:s'),
                                    'timeAgo'     => $log->created_at->diffForHumans(),
                                    'diff'        => $diff,
                                ]))"
                                title="View details"
                                class="w-8 h-8 inline-flex items-center justify-center rounded-lg cursor-pointer
                                       text-on-surface-variant hover:bg-surface-container hover:text-primary
                                       transition-colors duration-150">
                                <span class="material-symbols-outlined text-[18px]">open_in_new</span>
                            </button>
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center">
                                    <span class="material-symbols-outlined text-outline text-[24px]">history_toggle_off</span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-on-surface">No activity found</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">
                                        @if($currentEvent || $currentSubject || $currentSearch || $currentDate)
                                            Try adjusting or clearing the filters.
                                        @else
                                            Activity will appear here as users interact with EduNest.
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if(method_exists($logs, 'hasPages') && $logs->hasPages())
        <div class="px-6 py-4 border-t border-outline-variant/30">
            {{ $logs->links() }}
        </div>
    @endif

    {{-- ─── Details Modal ─── --}}
    <div x-show="isOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-primary/40 backdrop-blur-sm" @click="close()"></div>

        {{-- Modal card --}}
        <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto z-10"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2">

            <x-card class="p-0 overflow-hidden">

                {{-- Header --}}
                <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-outline-variant/30">
                    <div class="flex items-center gap-3">
                        <template x-if="entry">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                  :class="entry.eventBadge">
                                <span class="material-symbols-outlined text-[13px]" x-text="entry.eventIcon"></span>
                                <span x-text="entry.eventLabel"></span>
                            </span>
                        </template>
                        <h2 class="text-base font-semibold text-primary" style="font-family: var(--font-display);">
                            Event Details
                        </h2>
                    </div>
                    <button type="button" @click="close()"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-outline
                                   hover:bg-surface-container hover:text-primary transition-colors cursor-pointer">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <template x-if="entry">
                    <div class="p-6 space-y-5">

                        {{-- Meta --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <div>
                                    <p class="text-[10px] font-semibold text-outline uppercase tracking-wide mb-0.5">Subject</p>
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <template x-if="entry.subjectType">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-semibold
                                                         bg-surface-container-high text-on-surface-variant"
                                                  x-text="entry.subjectType"></span>
                                        </template>
                                        <span class="text-sm text-on-surface font-medium" x-text="entry.subjectName ?? '—'"></span>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[10px] font-semibold text-outline uppercase tracking-wide mb-0.5">Caused By</p>
                                    <template x-if="entry.causerName">
                                        <div>
                                            <p class="text-sm text-on-surface font-medium" x-text="entry.causerName"></p>
                                            <p class="text-[11px] text-outline" x-text="entry.causerRole ?? ''"></p>
                                        </div>
                                    </template>
                                    <template x-if="!entry.causerName">
                                        <span class="inline-flex items-center gap-1 text-sm text-outline italic">
                                            <span class="material-symbols-outlined text-[14px]">computer</span>
                                            System
                                        </span>
                                    </template>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] font-semibold text-outline uppercase tracking-wide mb-0.5">Timestamp</p>
                                <p class="text-sm text-on-surface font-medium" x-text="entry.timestamp"></p>
                                <p class="text-[11px] text-outline mt-0.5" x-text="entry.timeAgo"></p>
                            </div>
                        </div>

                        {{-- Diff table --}}
                        <div>
                            <p class="text-[10px] font-semibold text-outline uppercase tracking-wide mb-2"
                               x-text="entry.event === 'created' ? 'Initial values'
                                      : entry.event === 'deleted' ? 'Last known values'
                                      : entry.event === 'restored' ? 'Restored values'
                                      : 'Field changes'">
                            </p>

                            {{-- No diff data --}}
                            <template x-if="!entry.diff || entry.diff.length === 0">
                                <p class="text-sm text-outline italic py-3 text-center">
                                    No field data recorded for this event.
                                </p>
                            </template>

                            {{-- Updated: 3-column diff (Before → After) --}}
                            <template x-if="entry.diff && entry.diff.length > 0 && entry.event === 'updated'">
                                <div class="rounded-[12px] border border-outline-variant/40 overflow-hidden text-xs">
                                    <div class="grid grid-cols-3 px-4 py-2.5 bg-surface-container
                                                text-[10px] font-semibold tracking-widest text-outline uppercase">
                                        <span>Field</span>
                                        <span class="text-error">Before</span>
                                        <span class="text-emerald-700">After</span>
                                    </div>
                                    <template x-for="row in entry.diff" :key="row.label">
                                        <div class="grid grid-cols-3 px-4 py-3 border-t border-outline-variant/20
                                                    hover:bg-surface-container/50 transition-colors">
                                            <span class="text-primary font-semibold" x-text="row.label"></span>
                                            <span class="text-error pr-3 break-words"
                                                  x-text="row.old ?? '—'"></span>
                                            <span class="text-emerald-700 break-words"
                                                  x-text="row.new ?? '—'"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            {{-- Created / Restored: 2-column (Field + Value) --}}
                            <template x-if="entry.diff && entry.diff.length > 0 && (entry.event === 'created' || entry.event === 'restored')">
                                <div class="rounded-[12px] border border-outline-variant/40 overflow-hidden text-xs">
                                    <div class="grid grid-cols-2 px-4 py-2.5 bg-surface-container
                                                text-[10px] font-semibold tracking-widest text-outline uppercase">
                                        <span>Field</span>
                                        <span class="text-emerald-700">Value</span>
                                    </div>
                                    <template x-for="row in entry.diff" :key="row.label">
                                        <div class="grid grid-cols-2 px-4 py-3 border-t border-outline-variant/20
                                                    hover:bg-surface-container/50 transition-colors">
                                            <span class="text-primary font-semibold" x-text="row.label"></span>
                                            <span class="text-emerald-700 break-words"
                                                  x-text="row.new ?? '—'"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            {{-- Deleted: 2-column (Field + Last value) --}}
                            <template x-if="entry.diff && entry.diff.length > 0 && entry.event === 'deleted'">
                                <div class="rounded-[12px] border border-outline-variant/40 overflow-hidden text-xs">
                                    <div class="grid grid-cols-2 px-4 py-2.5 bg-surface-container
                                                text-[10px] font-semibold tracking-widest text-outline uppercase">
                                        <span>Field</span>
                                        <span class="text-error">Last value</span>
                                    </div>
                                    <template x-for="row in entry.diff" :key="row.label">
                                        <div class="grid grid-cols-2 px-4 py-3 border-t border-outline-variant/20
                                                    hover:bg-surface-container/50 transition-colors">
                                            <span class="text-primary font-semibold" x-text="row.label"></span>
                                            <span class="text-error break-words" x-text="row.old ?? '—'"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>

                        </div>
                    </div>
                </template>

                {{-- Footer --}}
                <div class="flex justify-end px-6 py-4 border-t border-outline-variant/20 bg-surface-container-low/30">
                    <x-button variant="secondary" @click="close()">Close</x-button>
                </div>

            </x-card>
        </div>
    </div>

</div>{{-- end x-data --}}

@endsection

@push('scripts')
<script>
    function updateLogFilter(key, value) {
        const url = new URL(window.location.href);
        url.searchParams.set(key, value);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    document.getElementById('logFilterForm').addEventListener('submit', function (e) {
        e.preventDefault();
        updateLogFilter('search', this.querySelector('[name="search"]').value.trim());
    });

    document.querySelector('#logFilterForm [name="search"]').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            updateLogFilter('search', this.value.trim());
        }
    });

    function logDetailModal() {
        return {
            isOpen: false,
            entry: null,
            open(data) {
                this.entry = data;
                this.isOpen = true;
                document.body.style.overflow = 'hidden';
            },
            close() {
                this.isOpen = false;
                this.entry = null;
                document.body.style.overflow = '';
            },
        };
    }
</script>
@endpush
