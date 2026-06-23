@extends('layouts.admin')

@section('title', 'Users')

@section('content')
@php
    $users         ??= collect();
    $roleCounts    ??= ['admin' => 0, 'teacher' => 0, 'student' => 0];
    $currentRole   ??= 'admin';
    $currentSort   ??= 'recent';
    $currentSearch ??= '';

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
<div class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] p-5 space-y-4 animate-fade-up">

    {{-- Top row: search + sort --}}
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">

        {{-- Search --}}
        <form method="GET" action="{{ request()->url() }}" id="filterForm" class="relative flex-1 max-w-sm">
            <input type="hidden" name="role" value="{{ $currentRole }}">
            <input type="hidden" name="sort" value="{{ $currentSort }}">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2
                         text-outline text-[18px] pointer-events-none">search</span>
            <input
                type="text"
                id="searchInput"
                name="search"
                value="{{ $currentSearch }}"
                placeholder="Search by name or email…"
                autocomplete="off"
                oninput="debouncedSearch(this.value)"
                class="w-full pl-10 pr-9 py-2.5 rounded-[16px] text-sm bg-surface-container-low
                       border border-outline-variant/60 placeholder:text-outline
                       focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"
            >
            <button
                type="button"
                id="clearSearchBtn"
                title="Clear search"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 cursor-pointer
                       text-outline hover:text-primary transition-colors
                       {{ $currentSearch ? '' : 'hidden' }}">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
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
<div id="usersTableContainer"
     class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)]
            transition-opacity duration-150 animate-fade-up">
    @include('admin.users._table')
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
    // ── Full-page filter navigation (role tabs, sort) ─────────────
    function updateFilter(key, value) {
        const url = new URL(window.location.href);
        url.searchParams.set(key, value);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    // ── AJAX live search ──────────────────────────────────────────
    const tableContainer = document.getElementById('usersTableContainer');
    let searchTimer;

    const clearBtn = document.getElementById('clearSearchBtn');

    function debouncedSearch(value) {
        clearBtn.classList.toggle('hidden', value.trim() === '');
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => fetchTable(value.trim()), 350);
    }

    clearBtn.addEventListener('click', function () {
        const input = document.getElementById('searchInput');
        input.value = '';
        clearBtn.classList.add('hidden');
        input.focus();
        fetchTable('');
    });

    function fetchTable(search) {
        const url = new URL(window.location.href);
        url.searchParams.set('search', search);
        url.searchParams.delete('page');

        // Update address bar without navigation
        history.replaceState(null, '', url.toString());

        tableContainer.style.opacity = '0.5';

        fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => {
            tableContainer.innerHTML = html;
            tableContainer.style.opacity = '1';
        })
        .catch(() => { tableContainer.style.opacity = '1'; });
    }

    // Prevent form submit on Enter — live search already handles it
    document.getElementById('filterForm').addEventListener('submit', e => e.preventDefault());

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
