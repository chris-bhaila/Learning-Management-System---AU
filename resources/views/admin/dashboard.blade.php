@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@php
    use App\Helpers\ActivityLogHelper;

    // Fallbacks — controller will pass real values once backend is wired
    $stats ??= [
        'total_users'    => 0,
        'total_teachers' => 0,
        'total_students' => 0,
        'active_courses' => 0,
        'total_admins'   => 0,
        'pct_admins'     => 0,
        'pct_teachers'   => 0,
        'pct_students'   => 0,
    ];
    $recentUsers    ??= collect();
    $recentActivity ??= collect();

    $hour     = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
@endphp

{{-- ─── Page Header ─── --}}
<div class="flex items-start justify-between gap-4">
    <div class="min-w-0">
        <h1 class="text-2xl font-bold text-primary break-words" style="font-family: var(--font-display);">
            Admin Dashboard
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            {{ $greeting }}, {{ auth()->user()?->name ?? 'Admin' }}. Here's what's happening on {{ $siteName }}.
        </p>
    </div>
    <span class="text-xs text-outline bg-surface-container px-3 py-1.5 rounded-full shrink-0">
        {{ now()->format('D, d M Y') }}
    </span>
</div>


{{-- ─── Stat Cards ─── --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 animate-fade-up">

    {{-- Total Users --}}
    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] p-5 flex items-start gap-4
                shadow-[0px_2px_8px_rgba(30,42,74,0.06)] hover:shadow-[0px_8px_24px_rgba(30,42,74,0.10)] transition-shadow">
        <div class="w-11 h-11 rounded-xl bg-surface-container flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-primary text-[22px]">group</span>
        </div>
        <div class="min-w-0">
            <p class="text-xs text-on-surface-variant font-medium">Total Users</p>
            <p class="text-2xl font-bold text-primary mt-0.5" style="font-family: var(--font-display);">
                {{ number_format($stats['total_users'] ?? 0) }}
            </p>
            <p class="text-xs text-on-surface-variant mt-0.5">All roles combined</p>
        </div>
    </div>

    {{-- Teachers --}}
    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] p-5 flex items-start gap-4
                shadow-[0px_2px_8px_rgba(30,42,74,0.06)] hover:shadow-[0px_8px_24px_rgba(30,42,74,0.10)] transition-shadow">
        <div class="w-11 h-11 rounded-xl bg-primary-container flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-on-primary text-[22px]">school</span>
        </div>
        <div class="min-w-0">
            <p class="text-xs text-on-surface-variant font-medium">Teachers</p>
            <p class="text-2xl font-bold text-primary mt-0.5" style="font-family: var(--font-display);">
                {{ number_format($stats['total_teachers'] ?? 0) }}
            </p>
            <p class="text-xs text-on-surface-variant mt-0.5">Active educators</p>
        </div>
    </div>

    {{-- Students --}}
    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] p-5 flex items-start gap-4
                shadow-[0px_2px_8px_rgba(30,42,74,0.06)] hover:shadow-[0px_8px_24px_rgba(30,42,74,0.10)] transition-shadow">
        <div class="w-11 h-11 rounded-xl bg-gold/10 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-[22px]" style="color: var(--color-on-gold);">menu_book</span>
        </div>
        <div class="min-w-0">
            <p class="text-xs text-on-surface-variant font-medium">Students</p>
            <p class="text-2xl font-bold text-primary mt-0.5" style="font-family: var(--font-display);">
                {{ number_format($stats['total_students'] ?? 0) }}
            </p>
            <p class="text-xs text-on-surface-variant mt-0.5">Enrolled learners</p>
        </div>
    </div>

    {{-- Active Courses --}}
    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] p-5 flex items-start gap-4
                shadow-[0px_2px_8px_rgba(30,42,74,0.06)] hover:shadow-[0px_8px_24px_rgba(30,42,74,0.10)] transition-shadow">
        <div class="w-11 h-11 rounded-xl bg-gold/20 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-[22px]" style="color: var(--color-on-gold);">library_books</span>
        </div>
        <div class="min-w-0">
            <p class="text-xs text-on-surface-variant font-medium">Active Courses</p>
            <p class="text-2xl font-bold text-primary mt-0.5" style="font-family: var(--font-display);">
                {{ number_format($stats['active_courses'] ?? 0) }}
            </p>
            <p class="text-xs text-on-surface-variant mt-0.5">Across all teachers</p>
        </div>
    </div>

</div>


{{-- ─── Main Content Row ─── --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 animate-fade-up">

    {{-- Recent Users Table (2/3 width) --}}
    <div class="xl:col-span-2 h-[480px] bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] overflow-hidden flex flex-col">

        <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/30 shrink-0">
            <h2 class="text-base font-semibold text-primary" style="font-family: var(--font-display);">
                Recent Users
            </h2>
            <a href="{{ Route::has('admin.users.index') ? route('admin.users.index') : '#' }}"
               class="text-xs font-medium text-on-surface-variant hover:text-primary transition-colors flex items-center gap-1">
                View all
                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </a>
        </div>

        <div class="flex-1 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                        <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase">
                            Name
                        </th>
                        <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase">
                            Email
                        </th>
                        <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase">
                            Role
                        </th>
                        <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase">
                            Joined
                        </th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    @forelse($recentUsers ?? [] as $user)
                        @php
                            $roleName  = $user->role?->name ?? 'student';
                            $roleColors = [
                                'admin'   => 'bg-primary-container text-on-primary',
                                'teacher' => 'bg-gold/20 text-on-gold',
                                'student' => 'bg-surface-container text-on-surface-variant',
                            ];
                            $roleColor  = $roleColors[$roleName] ?? $roleColors['student'];
                            $avatarBg   = [
                                'admin'   => 'bg-primary-container text-on-primary',
                                'teacher' => 'bg-gold/20 text-on-gold',
                                'student' => 'bg-surface-container text-on-surface',
                            ];
                            $ava = $avatarBg[$roleName] ?? $avatarBg['student'];
                        @endphp
                        <tr class="hover:bg-surface-container-low/40 transition-colors">
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full {{ $ava }} flex items-center justify-center
                                                text-xs font-semibold shrink-0 overflow-hidden">
                                        @if($user->profile_photo_path ?? false)
                                            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                                        @else
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        @endif
                                    </div>
                                    <span class="font-medium text-on-surface">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 text-on-surface-variant">{{ $user->email }}</td>
                            <td class="px-6 py-3.5">
                                @php
                                    $roleColor = $roleColors[$roleName] ?? $roleColors['student'];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $roleColor }}">
                                    {{ ucfirst($roleName) }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-on-surface-variant text-xs">
                                {{ $user->created_at->format('d M Y') }}
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <a href="{{ Route::has('admin.users.show') ? route('admin.users.show', $user) : '#' }}"
                                   class="text-xs font-medium text-primary hover:underline">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-outline">
                                No users yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Activity (1/3 width) --}}
    <div x-data="logDetailModal()"
         class="h-[480px] bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] overflow-hidden flex flex-col">

        <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/30 shrink-0">
            <h2 class="text-base font-semibold text-primary" style="font-family: var(--font-display);">
                Recent Activity
            </h2>
            <a href="{{ Route::has('admin.logs.index') ? route('admin.logs.index') : '#' }}"
               class="text-xs font-medium text-on-surface-variant hover:text-primary transition-colors flex items-center gap-1">
                View all
                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </a>
        </div>

        <ul class="divide-y divide-outline-variant/20 flex-1 overflow-y-auto">
            @forelse($recentActivity ?? [] as $log)
                @php
                    $evtType  = $log->event ?? 'updated';
                    $cfg      = ActivityLogHelper::resolveEventConfig($evtType);
                    $evtMap   = [
                        'created'  => ['bg' => 'bg-emerald-50', 'ic' => 'text-emerald-600'],
                        'updated'  => ['bg' => 'bg-blue-50',    'ic' => 'text-blue-600'],
                        'deleted'  => ['bg' => 'bg-red-50',     'ic' => 'text-error'],
                        'restored' => ['bg' => 'bg-amber-50',   'ic' => 'text-amber-600'],
                        'login'    => ['bg' => 'bg-violet-50',  'ic' => 'text-violet-600'],
                        'logout'   => ['bg' => 'bg-surface-container', 'ic' => 'text-on-surface-variant'],
                    ];
                    $evtStyle = $evtMap[$evtType] ?? ['bg' => 'bg-surface-container', 'ic' => 'text-primary'];

                    $changes  = $log->attribute_changes ?? collect();
                    $newAttrs = (array) ($changes->get('attributes') ?? []);
                    $oldAttrs = (array) ($changes->get('old') ?? []);
                    $diff     = ActivityLogHelper::buildDiff($newAttrs, $oldAttrs, $evtType);

                    $subjectName = $log->subject?->name ?? $log->subject?->title ?? $log->subject?->token_value ?? null;
                @endphp
                <li class="px-6 py-4 flex items-start gap-3 hover:bg-surface-container-low/40 transition-colors duration-200 cursor-pointer"
                    @click="open(@js([
                        'event'       => $evtType,
                        'eventLabel'  => $cfg['label'],
                        'eventBadge'  => $cfg['badge'],
                        'eventIcon'   => $cfg['icon'],
                        'subjectType' => $log->subject_type ? class_basename($log->subject_type) : null,
                        'subjectName' => $subjectName ?? ($log->subject_id ? 'ID #' . $log->subject_id : null),
                        'causerName'  => $log->causer?->name,
                        'causerRole'  => $log->causer?->role?->name ? ucfirst($log->causer->role->name) : null,
                        'timestamp'   => $log->created_at->format('d M Y, H:i:s'),
                        'timeAgo'     => $log->created_at->diffForHumans(),
                        'diff'        => $diff,
                    ]))">
                    <div class="w-8 h-8 rounded-full {{ $evtStyle['bg'] }} flex items-center justify-center shrink-0 mt-0.5">
                        <span class="material-symbols-outlined {{ $evtStyle['ic'] }} text-[16px]">
                            {{ $cfg['icon'] }}
                        </span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-on-surface leading-snug">
                            @if($log->causer)
                                <span class="font-semibold">{{ $log->causer->name }}</span>
                            @else
                                <span class="font-semibold text-outline italic">System</span>
                            @endif
                            {{ $log->description }}
                        </p>
                        <p class="text-xs text-outline mt-1">{{ $log->created_at->diffForHumans() }}</p>
                    </div>
                </li>
            @empty
                <li class="px-6 py-10 text-center text-sm text-outline">
                    No recent activity.
                </li>
            @endforelse
        </ul>

        @include('partials.activity-log-detail-modal')

    </div>

</div>

<!-- 
{{-- ─── Role Distribution ─── --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5">

    @foreach([
        ['label' => 'Admins',   'count' => $stats['total_admins']   ?? 0, 'icon' => 'admin_panel_settings', 'pct' => $stats['pct_admins']   ?? 0],
        ['label' => 'Teachers', 'count' => $stats['total_teachers'] ?? 0, 'icon' => 'school',               'pct' => $stats['pct_teachers'] ?? 0],
        ['label' => 'Students', 'count' => $stats['total_students'] ?? 0, 'icon' => 'menu_book',            'pct' => $stats['pct_students'] ?? 0],
    ] as $item)
        <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[18px]">{{ $item['icon'] }}</span>
                    <span class="text-sm font-medium text-on-surface">{{ $item['label'] }}</span>
                </div>
                <span class="text-sm font-semibold text-primary">{{ number_format($item['count']) }}</span>
            </div>
            <div class="h-1.5 bg-surface-container rounded-full overflow-hidden">
                <div class="h-full bg-gold rounded-full transition-all duration-500"
                     style="width: {{ min($item['pct'], 100) }}%"></div>
            </div>
            <p class="text-xs text-outline mt-1.5">{{ $item['pct'] }}% of total users</p>
        </div>
    @endforeach

</div> -->

@endsection
