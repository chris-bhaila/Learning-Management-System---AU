@extends('layouts.admin')

@section('title', 'Activity Log')

@section('content')
@php
    $logs         ??= collect();
    $subjectTypes ??= collect();
    $totalToday   ??= 0;
    $totalWeek    ??= 0;

    $currentEvent   = request()->get('event', '');
    $currentSubject = request()->get('subject', '');
    $currentSearch  = request()->get('search', '');
    $currentDate    = request()->get('date', '');

    $eventConfig = [
        'created' => ['label' => 'Created', 'icon' => 'add_circle', 'badge' => 'bg-emerald-50 text-emerald-700 border border-emerald-200'],
        'updated' => ['label' => 'Updated', 'icon' => 'edit',       'badge' => 'bg-blue-50 text-blue-700 border border-blue-200'],
        'deleted' => ['label' => 'Deleted', 'icon' => 'delete',     'badge' => 'bg-red-50 text-error border border-red-200'],
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
<div class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] overflow-hidden animate-fade-up">

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
                        $newAttrs    = collect($log->properties->get('attributes', []));
                        $oldAttrs    = collect($log->properties->get('old', []));
                        $changeCount = $newAttrs->count();
                    @endphp
                    <tr class="hover:bg-surface-container-low/40 transition-colors align-top">

                        {{-- Event --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $cfg['badge'] }}">
                                <span class="material-symbols-outlined text-[13px]">{{ $cfg['icon'] }}</span>
                                {{ $cfg['label'] }}
                            </span>
                            @if($log->log_name && $log->log_name !== 'default')
                                <p class="text-[10px] text-outline mt-1 font-mono">{{ $log->log_name }}</p>
                            @endif
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
                                        {{ $log->subject->name ?? $log->subject->title ?? $log->subject->token_value ?? "ID #{$log->subject_id}" }}
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
                                        @if($log->causer->avatar ?? false)
                                            <img src="{{ $log->causer->avatar }}" alt="" class="w-full h-full object-cover">
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
                                <div class="space-y-1 max-w-[220px]">
                                    @foreach($newAttrs->take(3) as $attr => $newVal)
                                        @php
                                            $displayVal = is_array($newVal) ? json_encode($newVal) : (is_null($newVal) ? 'null' : (is_bool($newVal) ? ($newVal ? 'true' : 'false') : $newVal));
                                        @endphp
                                        <div class="flex items-center gap-1.5 text-[11px]">
                                            <span class="font-mono text-outline truncate max-w-[70px]">{{ $attr }}</span>
                                            <span class="material-symbols-outlined text-[12px] text-outline shrink-0">arrow_forward</span>
                                            <span class="text-on-surface truncate max-w-[100px]">{{ Str::limit((string) $displayVal, 30) }}</span>
                                        </div>
                                    @endforeach
                                    @if($changeCount > 3)
                                        <p class="text-[10px] text-outline">+{{ $changeCount - 3 }} more fields</p>
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

                        {{-- Detail toggle --}}
                        <td class="px-6 py-4 text-center">
                            @if($changeCount > 0)
                                <button type="button"
                                        onclick="toggleDetails({{ $log->id }})"
                                        title="View full diff"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg cursor-pointer
                                               text-on-surface-variant hover:bg-surface-container hover:text-primary
                                               transition-colors">
                                    <span id="icon-{{ $log->id }}" class="material-symbols-outlined text-[18px]">expand_more</span>
                                </button>
                            @else
                                <span class="text-outline text-xs">—</span>
                            @endif
                        </td>
                    </tr>

                    {{-- Expandable diff panel --}}
                    @if($changeCount > 0)
                        <tr id="details-{{ $log->id }}" class="hidden bg-surface-container-low/30">
                            <td colspan="6" class="px-6 pb-5 pt-1">
                                <div class="rounded-[12px] border border-outline-variant/40 overflow-hidden font-mono text-xs">
                                    <div class="grid grid-cols-3 px-4 py-2 bg-surface-container
                                                text-[10px] font-semibold tracking-widest text-outline uppercase not-italic">
                                        <span>Attribute</span>
                                        <span class="text-error">Old</span>
                                        <span class="text-emerald-700">New</span>
                                    </div>
                                    @foreach($newAttrs as $attr => $newVal)
                                        @php
                                            $oldVal = $oldAttrs->get($attr);
                                            $fmt = function($v) {
                                                if (is_null($v))   return 'null';
                                                if (is_bool($v))   return $v ? 'true' : 'false';
                                                if (is_array($v))  return json_encode($v);
                                                return Str::limit((string) $v, 80);
                                            };
                                        @endphp
                                        <div class="grid grid-cols-3 px-4 py-2.5 border-t border-outline-variant/20
                                                    hover:bg-surface-container/50 transition-colors">
                                            <span class="text-primary font-semibold not-italic">{{ $attr }}</span>
                                            <span class="text-error pr-3 break-all">{{ $fmt($oldVal) }}</span>
                                            <span class="text-emerald-700 break-all">{{ $fmt($newVal) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endif

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

</div>

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

    function toggleDetails(id) {
        const row  = document.getElementById('details-' + id);
        const icon = document.getElementById('icon-' + id);
        const hidden = row.classList.toggle('hidden');
        icon.style.transform = hidden ? '' : 'rotate(180deg)';
    }
</script>
@endpush
