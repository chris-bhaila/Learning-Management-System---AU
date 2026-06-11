@extends('layouts.admin')

@section('title', 'Users')

@section('content')
@php
    $users        ??= collect();
    $roleCounts   ??= ['admin' => 0, 'teacher' => 0, 'student' => 0];
    $currentRole  = request()->get('role', 'admin');
    $currentSort  = request()->get('sort', 'recent');
    $currentSearch = request()->get('search', '');

    $roleTabs = [
        ['key' => 'admin',   'label' => 'Admins',   'icon' => 'admin_panel_settings'],
        ['key' => 'teacher', 'label' => 'Teachers', 'icon' => 'school'],
        ['key' => 'student', 'label' => 'Students', 'icon' => 'menu_book'],
    ];
    $roleHeadings = ['admin' => 'Admins', 'teacher' => 'Teachers', 'student' => 'Students'];
    $roleBadge = [
        'admin'   => 'bg-primary-container text-on-primary',
        'teacher' => 'bg-gold/20 text-on-gold',
        'student' => 'bg-surface-container text-on-surface-variant',
    ];
    $roleIcon = [
        'admin'   => 'admin_panel_settings',
        'teacher' => 'school',
        'student' => 'menu_book',
    ];
@endphp

{{-- ─── Page Header ─── --}}
<div class="flex items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
            Users
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            Manage all registered users across every role.
        </p>
    </div>
</div>

{{-- ─── Toolbar (search + sort + role tabs) ─── --}}
<div class="bg-surface-white border border-outline-variant/40 rounded-[20px] p-5 space-y-4">

    {{-- Top row: search + sort --}}
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">

        {{-- Search --}}
        <form method="GET" action="{{ request()->url() }}" id="filterForm" class="relative flex-1 max-w-sm">
            <input type="hidden" name="role"  value="{{ $currentRole }}">
            <input type="hidden" name="sort"  value="{{ $currentSort }}">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2
                         text-outline text-[18px] pointer-events-none">search</span>
            <input
                type="search"
                name="search"
                value="{{ $currentSearch }}"
                placeholder="Search by name or email…"
                class="w-full pl-10 pr-4 py-2.5 rounded-[16px] text-sm bg-surface-container-low
                       border border-outline-variant/60 placeholder:text-outline
                       focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"
            >
        </form>

        {{-- Sort select --}}
        <div class="relative self-start sm:self-auto shrink-0">
            <select
                onchange="updateFilter('sort', this.value)"
                class="appearance-none pl-4 pr-9 py-2.5 rounded-[16px] text-sm
                       bg-surface-container-low border border-outline-variant/60 text-on-surface
                       focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary cursor-pointer"
            >
                <option value="recent"  {{ $currentSort === 'recent'  ? 'selected' : '' }}>Recently Joined</option>
                <option value="oldest"  {{ $currentSort === 'oldest'  ? 'selected' : '' }}>First Joined</option>
                <option value="az"      {{ $currentSort === 'az'      ? 'selected' : '' }}>Alphabetical</option>
            </select>
            <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2
                         text-outline text-[18px] pointer-events-none">expand_more</span>
        </div>

    </div>

    {{-- Role tabs --}}
    <div class="flex items-center gap-2 flex-wrap">
        @foreach($roleTabs as $tab)
            <button
                type="button"
                onclick="updateFilter('role', '{{ $tab['key'] }}')"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium
                       cursor-pointer transition-all duration-150
                       {{ $currentRole === $tab['key']
                            ? 'bg-gold text-primary shadow-sm'
                            : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' }}"
            >
                <span class="material-symbols-outlined text-[16px]">{{ $tab['icon'] }}</span>
                {{ $tab['label'] }}
                @isset($roleCounts[$tab['key']])
                    <span class="text-[11px] font-semibold tabular-nums
                                 {{ $currentRole === $tab['key'] ? 'text-primary/60' : 'text-outline' }}">
                        {{ $roleCounts[$tab['key']] }}
                    </span>
                @endisset
            </button>
        @endforeach
    </div>

</div>

{{-- ─── Users Table ─── --}}
<div class="bg-surface-white border border-outline-variant/40 rounded-[20px] overflow-hidden">

    {{-- Card header --}}
    <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/30">
        <div>
            <h2 class="text-base font-semibold text-primary" style="font-family: var(--font-display);">
                {{ $roleHeadings[$currentRole] ?? 'Users' }}
            </h2>
            <p class="text-xs text-on-surface-variant mt-0.5">
                @php
                    $total = method_exists($users, 'total') ? $users->total() : $users->count();
                @endphp
                {{ number_format($total) }} {{ Str::plural('user', $total) }} found
                @if($currentSearch)
                    for "{{ $currentSearch }}"
                @endif
            </p>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-outline-variant/30 bg-surface-container-low/50">
                    <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase">
                        User
                    </th>
                    <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase hidden sm:table-cell">
                        Email
                    </th>
                    <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase">
                        Role
                    </th>
                    <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase hidden md:table-cell">
                        Joined
                    </th>
                    <th class="px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase text-center">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20">
                @forelse($users as $user)
                    @php $role = $user->role ?? 'student'; @endphp
                    <tr class="hover:bg-surface-container-low/40 transition-colors">

                        {{-- Avatar + Name --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-primary-container flex items-center justify-center
                                            text-xs font-semibold text-on-primary shrink-0 overflow-hidden select-none">
                                    @if($user->profile_photo_path ?? false)
                                        <img src="{{ $user->profile_photo_url }}"
                                             alt="{{ $user->name }}"
                                             class="w-full h-full object-cover">
                                    @else
                                        {{ strtoupper(substr($user->name ?? '??', 0, 2)) }}
                                    @endif
                                </div>
                                <span class="font-medium text-on-surface truncate max-w-[160px]">
                                    {{ $user->name }}
                                </span>
                            </div>
                        </td>

                        {{-- Email --}}
                        <td class="px-6 py-4 text-on-surface-variant hidden sm:table-cell">
                            {{ $user->email }}
                        </td>

                        {{-- Role badge --}}
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full
                                         text-xs font-medium {{ $roleBadge[$user->role->name] ?? $roleBadge['student'] }}">
                                <span class="material-symbols-outlined text-[12px]">
                                    {{ $roleIcon[$user->role->name] ?? 'person' }}
                                </span>
                                {{ ucfirst($user->role->name) }}
                            </span>
                        </td>

                        {{-- Joined --}}
                        <td class="px-6 py-4 text-on-surface-variant text-xs whitespace-nowrap hidden md:table-cell">
                            {{ $user->created_at?->format('d M Y') ?? '—' }}
                        </td>

                        {{-- Actions --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-0.5">

                                {{-- View --}}
                                <a href="{{ Route::has('admin.users.show') ? route('admin.users.show', $user) : '#' }}"
                                   title="View user"
                                   class="w-8 h-8 inline-flex items-center justify-center rounded-lg cursor-pointer
                                          text-on-surface-variant hover:bg-surface-container hover:text-primary
                                          transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                                </a>

                                {{-- Edit --}}
                                <a href="{{ Route::has('admin.users.edit') ? route('admin.users.edit', $user) : '#' }}"
                                   title="Edit user"
                                   class="w-8 h-8 inline-flex items-center justify-center rounded-lg cursor-pointer
                                          text-on-surface-variant hover:bg-surface-container hover:text-primary
                                          transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </a>

                                {{-- Delete --}}
                                <button
                                    type="button"
                                    title="Delete user"
                                    onclick="openDeleteModal({{ $user->id }}, '{{ e($user->name) }}')"
                                    class="w-8 h-8 inline-flex items-center justify-center rounded-lg cursor-pointer
                                           text-on-surface-variant hover:bg-error-container hover:text-error
                                           transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-surface-container
                                            flex items-center justify-center">
                                    <span class="material-symbols-outlined text-outline text-[24px]">group_off</span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-on-surface">No users found</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">
                                        @if($currentSearch)
                                            No results for "<strong>{{ $currentSearch }}</strong>".
                                            Try a different search term.
                                        @else
                                            No {{ $roleHeadings[$currentRole] ?? 'users' }} registered yet.
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
    @if($users instanceof \Illuminate\Pagination\LengthAwarePaginator && $users->hasPages())
        <div class="px-6 py-4 border-t border-outline-variant/30">
            {{ $users->appends(request()->query())->links() }}
        </div>
    @endif

</div>

{{-- ─── Delete Confirmation Modal ─── --}}
<div
    id="deleteModal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="deleteModalTitle"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4"
>
    <div class="bg-surface-white rounded-[20px] shadow-xl w-full max-w-sm p-6">
        <div class="flex flex-col items-center text-center gap-3">
            <div class="w-12 h-12 rounded-full bg-error-container flex items-center justify-center">
                <span class="material-symbols-outlined text-error text-[22px]">warning</span>
            </div>
            <h3 id="deleteModalTitle"
                class="text-lg font-semibold text-primary"
                style="font-family: var(--font-display);">
                Delete User?
            </h3>
            <p class="text-sm text-on-surface-variant leading-relaxed">
                You are about to permanently delete
                <strong id="deleteUserName" class="text-on-surface"></strong>.
                This action cannot be undone.
            </p>
        </div>

        <div class="flex gap-3 mt-6">
            <button
                type="button"
                onclick="closeDeleteModal()"
                class="flex-1 px-4 py-2.5 rounded-[24px] text-sm font-medium cursor-pointer
                       bg-surface-container text-on-surface
                       hover:bg-surface-container-high transition-colors">
                Cancel
            </button>

            <form id="deleteForm" method="POST" class="flex-1">
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="w-full px-4 py-2.5 rounded-[24px] text-sm font-medium cursor-pointer
                           bg-error text-white hover:opacity-90 transition-opacity">
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // ── Filter navigation ──────────────────────────────────────────
    function updateFilter(key, value) {
        const url = new URL(window.location.href);
        url.searchParams.set(key, value);
        url.searchParams.delete('page'); // reset pagination on filter change
        window.location.href = url.toString();
    }

    // Submit the search form on Enter (browser default) or when input is cleared
    document.getElementById('filterForm').addEventListener('submit', function () {
        this.querySelector('input[name="search"]').value =
            this.querySelector('input[name="search"]').value.trim();
    });

    // ── Delete modal ───────────────────────────────────────────────
    function openDeleteModal(userId, userName) {
        document.getElementById('deleteUserName').textContent = userName;
        document.getElementById('deleteForm').action =
            '{{ rtrim(url("admin/users"), "/") }}/' + userId;
        const modal = document.getElementById('deleteModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    // Close on backdrop click
    document.getElementById('deleteModal').addEventListener('click', function (e) {
        if (e.target === this) closeDeleteModal();
    });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeDeleteModal();
    });
</script>
@endpush
