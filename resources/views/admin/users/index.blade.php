@extends('layouts.admin')

@section('title', 'Users')

@section('content')
@php
    $users         ??= collect();
    $roleCounts    ??= ['admin' => 0, 'teacher' => 0, 'student' => 0];
    $currentRole   ??= 'admin';
    $currentSort   ??= 'recent';
    $currentStatus ??= null;
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

    $reopenCreate = old('_modal') === 'create';
    $reopenEdit   = old('_modal') === 'edit';
@endphp

{{-- ─── Page Header ─── --}}
<div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3 lg:gap-4">
    <div class="min-w-0">
        <h1 class="text-2xl font-bold text-primary break-words" style="font-family: var(--font-display);">
            Users
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            Manage all registered users across every role.
        </p>
    </div>

    <x-button type="button" variant="primary" icon="person_add" onclick="openCreateModal()" class="lg:shrink-0">
        Add User
    </x-button>
</div>

{{-- ─── Toolbar (search + sort + role tabs) ─── --}}
<div class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] p-5 space-y-4 animate-fade-up">

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

        {{-- Sort --}}
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

    {{-- Status filter + clear --}}
    <div class="flex items-center justify-between gap-2 pt-3 border-t border-outline-variant/20">
    <div class="flex items-center gap-2 flex-wrap">
        <span class="text-[11px] font-semibold text-outline uppercase tracking-wide mr-1">Status</span>

        <button
            type="button"
            onclick="updateFilter('status', '')"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium
                   cursor-pointer transition-all duration-150
                   {{ is_null($currentStatus)
                        ? 'bg-primary text-on-primary shadow-sm'
                        : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' }}"
        >
            All
        </button>

        <button
            type="button"
            onclick="updateFilter('status', 'active')"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium
                   cursor-pointer transition-all duration-150
                   {{ $currentStatus === 'active'
                        ? 'bg-green-600 text-white shadow-sm'
                        : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' }}"
        >
            <span class="w-1.5 h-1.5 rounded-full {{ $currentStatus === 'active' ? 'bg-white' : 'bg-green-500' }} shrink-0"></span>
            Active
        </button>

        <button
            type="button"
            onclick="updateFilter('status', 'inactive')"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium
                   cursor-pointer transition-all duration-150
                   {{ $currentStatus === 'inactive'
                        ? 'bg-outline text-white shadow-sm'
                        : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' }}"
        >
            <span class="w-1.5 h-1.5 rounded-full {{ $currentStatus === 'inactive' ? 'bg-white' : 'bg-outline' }} shrink-0"></span>
            Inactive
        </button>
    </div>

    @php
        $hasActiveFilters = $currentSearch !== '' || $currentSort !== 'recent' || !is_null($currentStatus);
    @endphp
    @if($hasActiveFilters)
        <a href="{{ route('admin.users.index', ['role' => $currentRole]) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium
                  cursor-pointer transition-all duration-150 shrink-0
                  text-on-surface-variant hover:text-error hover:bg-error-container/60">
            <span class="material-symbols-outlined text-[14px]">filter_alt_off</span>
            Clear filters
        </a>
    @endif
    </div>

</div>

{{-- ─── Users Table ─── --}}
<div id="usersTableContainer"
     class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)]
            transition-opacity duration-150 animate-fade-up">
    @include('admin.users._table')
</div>


{{-- ═══════════════════════════════════════════════════
     CREATE USER MODAL
     open: false on init — x-cloak hides it until Alpine runs.
     onSubmit only calls event.preventDefault() on validation failure;
     on success it lets the native form submit proceed.
     No backtick template literals — uses string concatenation for Iodine rules.
═══════════════════════════════════════════════════ --}}
<div
    x-data="{
        open: {{ $reopenCreate ? 'true' : 'false' }},
        submitting: false,
        name:            '{{ addslashes(old('name', '')) }}',
        email:           '{{ addslashes(old('email', '')) }}',
        role:            '{{ old('role', '') }}',
        password:        '',
        passwordConfirm: '',
        errors: {
            name:            '{{ addslashes($reopenCreate ? $errors->first('name')     : '') }}',
            email:           '{{ addslashes($reopenCreate ? $errors->first('email')    : '') }}',
            role:            '{{ addslashes($reopenCreate ? $errors->first('role')     : '') }}',
            password:        '{{ addslashes($reopenCreate ? $errors->first('password') : '') }}',
            passwordConfirm: '',
        },
        check(value, rules) {
            const r = window.Iodine.assert(value ?? '', rules);
            return r.valid ? '' : r.error;
        },
        onSubmit(event) {
            this.errors.name     = this.check(this.name,  ['required', 'maxLength:255']);
            this.errors.email    = this.check(this.email, ['required', 'email', 'maxLength:255']);
            this.errors.role     = this.check(this.role,  ['required']);
            this.errors.password = this.check(this.password, ['required', 'minLength:8']);
            this.errors.passwordConfirm = this.check(
                this.passwordConfirm, ['required', 'same:' + this.password]
            );
            if (Object.values(this.errors).some(Boolean)) {
                event.preventDefault();
                return;
            }
            this.submitting = true;
        },
        close() {
            this.open = false;
            document.body.classList.remove('overflow-hidden');
        },
    }"
    x-show="open"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @open-create-modal.window="open = true"
    @keydown.escape.window="if(open) close()"
    @click.self="close()"
    role="dialog"
    aria-modal="true"
    aria-labelledby="createModalTitle"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
>
    {{-- Inner panel --}}
    <div
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-1"
        class="w-full max-w-md"
    >
        <x-card class="overflow-hidden">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/30">
                <div>
                    <h3 id="createModalTitle"
                        class="text-base font-semibold text-primary"
                        style="font-family: var(--font-display);">
                        Add User
                    </h3>
                    <p class="text-xs text-on-surface-variant mt-0.5" x-show="role !== 'admin'">
                        New users can sign in with Google using the same email.
                    </p>
                    <p class="text-xs text-on-surface-variant mt-0.5" x-show="role === 'admin'" x-cloak>
                        Admin accounts sign in with email and password only — no Google Sign-In.
                    </p>
                </div>
                <button
                    type="button"
                    @click="close()"
                    class="w-8 h-8 flex items-center justify-center rounded-[12px] cursor-pointer
                           text-on-surface-variant hover:bg-surface-container hover:text-primary
                           transition-colors duration-150 shrink-0 ml-3">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            {{-- Form --}}
            <form
                method="POST"
                action="{{ route('admin.users.store') }}"
                @submit="onSubmit"
                novalidate
            >
                @csrf
                <input type="hidden" name="_modal" value="create">

                <div class="px-6 py-5 space-y-4 overflow-y-auto max-h-[65vh]">

                    {{-- Name --}}
                    <div>
                        <label for="c-name"
                               class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                            Full Name <span class="text-error normal-case tracking-normal font-normal">*</span>
                        </label>
                        <input
                            id="c-name"
                            type="text"
                            name="name"
                            x-model="name"
                            @blur="errors.name = check(name, ['required', 'maxLength:255'])"
                            autocomplete="off"
                            placeholder="Jane Smith"
                            :class="errors.name ? 'border-error focus:ring-error focus:border-error' : 'border-outline-variant/60 focus:ring-primary focus:border-primary'"
                            class="w-full px-4 py-2.5 bg-surface-white border rounded-[16px] text-sm
                                   placeholder:text-outline focus:outline-none focus:ring-1 transition-colors"
                        >
                        <p x-show="errors.name" x-text="errors.name"
                           class="mt-1.5 text-xs text-error" x-cloak></p>
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="c-email"
                               class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                            Email Address <span class="text-error normal-case tracking-normal font-normal">*</span>
                        </label>
                        <input
                            id="c-email"
                            type="email"
                            name="email"
                            x-model="email"
                            @blur="errors.email = check(email, ['required', 'email', 'maxLength:255'])"
                            autocomplete="off"
                            placeholder="jane@school.edu"
                            :class="errors.email ? 'border-error focus:ring-error focus:border-error' : 'border-outline-variant/60 focus:ring-primary focus:border-primary'"
                            class="w-full px-4 py-2.5 bg-surface-white border rounded-[16px] text-sm
                                   placeholder:text-outline focus:outline-none focus:ring-1 transition-colors"
                        >
                        <p x-show="errors.email" x-text="errors.email"
                           class="mt-1.5 text-xs text-error" x-cloak></p>
                    </div>

                    {{-- Role --}}
                    <div>
                        <label for="c-role"
                               class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                            Role <span class="text-error normal-case tracking-normal font-normal">*</span>
                        </label>
                        <div class="relative">
                            <select
                                id="c-role"
                                name="role"
                                x-model="role"
                                @change="errors.role = check(role, ['required'])"
                                :class="errors.role ? 'border-error focus:ring-error focus:border-error' : 'border-outline-variant/60 focus:ring-primary focus:border-primary'"
                                class="w-full appearance-none pl-4 pr-9 py-2.5 bg-surface-white border
                                       rounded-[16px] text-sm text-on-surface
                                       focus:outline-none focus:ring-1 transition-colors cursor-pointer"
                            >
                                <option value="" disabled selected>Select a role…</option>
                                <option value="teacher">Teacher</option>
                                <option value="student">Student</option>
                                {{-- Server-side enforcement is the real gate (StoreUserRequest::authorize()
                                     via UserPolicy::canAssignRole()) — this @if is defense-in-depth for
                                     the UI only, same pattern as the Promote to Admin button. --}}
                                @if(auth()->user()->isSuperAdmin())
                                    <option value="admin">Admin</option>
                                @endif
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2
                                         text-outline text-[18px] pointer-events-none">expand_more</span>
                        </div>
                        <p x-show="errors.role" x-text="errors.role"
                           class="mt-1.5 text-xs text-error" x-cloak></p>
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="c-password"
                               class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                            Password <span class="text-error normal-case tracking-normal font-normal">*</span>
                        </label>
                        <input
                            id="c-password"
                            type="password"
                            name="password"
                            x-model="password"
                            @blur="errors.password = check(password, ['required', 'minLength:8'])"
                            autocomplete="new-password"
                            placeholder="Min. 8 characters"
                            :class="errors.password ? 'border-error focus:ring-error focus:border-error' : 'border-outline-variant/60 focus:ring-primary focus:border-primary'"
                            class="w-full px-4 py-2.5 bg-surface-white border rounded-[16px] text-sm
                                   placeholder:text-outline focus:outline-none focus:ring-1 transition-colors"
                        >
                        <p x-show="errors.password" x-text="errors.password"
                           class="mt-1.5 text-xs text-error" x-cloak></p>
                    </div>

                    {{-- Confirm Password --}}
                    <div>
                        <label for="c-password-confirm"
                               class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                            Confirm Password <span class="text-error normal-case tracking-normal font-normal">*</span>
                        </label>
                        <input
                            id="c-password-confirm"
                            type="password"
                            name="password_confirmation"
                            x-model="passwordConfirm"
                            @blur="errors.passwordConfirm = check(passwordConfirm, ['required', 'same:' + password])"
                            autocomplete="new-password"
                            placeholder="Re-enter password"
                            :class="errors.passwordConfirm ? 'border-error focus:ring-error focus:border-error' : 'border-outline-variant/60 focus:ring-primary focus:border-primary'"
                            class="w-full px-4 py-2.5 bg-surface-white border rounded-[16px] text-sm
                                   placeholder:text-outline focus:outline-none focus:ring-1 transition-colors"
                        >
                        <p x-show="errors.passwordConfirm" x-text="errors.passwordConfirm"
                           class="mt-1.5 text-xs text-error" x-cloak></p>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-outline-variant/30 bg-surface-container-low/40">
                    <x-button type="button" variant="secondary" @click="close()">
                        Cancel
                    </x-button>
                    <x-button type="submit" variant="primary" x-bind:disabled="submitting"
                              class="min-w-[120px] justify-center">
                        <span x-show="!submitting">Create User</span>
                        <span x-show="submitting" x-cloak>Creating…</span>
                    </x-button>
                </div>

            </form>
        </x-card>
    </div>
</div>


{{-- ═══════════════════════════════════════════════════
     EDIT USER MODAL
═══════════════════════════════════════════════════ --}}
<div
    x-data="{
        open: {{ $reopenEdit ? 'true' : 'false' }},
        submitting: false,
        id:           '{{ old('_edit_id', '') }}',
        name:         '{{ addslashes(old('name', '')) }}',
        email:        '{{ addslashes(old('_display_email', '')) }}',
        role:         '{{ old('role', '') }}',
        isActive:     {{ old('is_active', '1') === '1' ? 'true' : 'false' }},
        avatarUrl:    '',
        avatarPreview: null,
        removingAvatar: false,
        errors: {
            name: '{{ addslashes($reopenEdit ? $errors->first('name') : '') }}',
            role: '{{ addslashes($reopenEdit ? $errors->first('role') : '') }}',
        },
        check(value, rules) {
            const r = window.Iodine.assert(value ?? '', rules);
            return r.valid ? '' : r.error;
        },
        onFileChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) {
                alert('Image must be 2 MB or smaller.');
                event.target.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => { this.avatarPreview = e.target.result; };
            reader.readAsDataURL(file);
            this.removingAvatar = false;
        },
        cancelAvatarChange() {
            this.avatarPreview = null;
            this.removingAvatar = false;
            const input = document.getElementById('editAvatarInput');
            if (input) input.value = '';
        },
        flagRemove() {
            this.removingAvatar = true;
            this.avatarPreview  = null;
            const input = document.getElementById('editAvatarInput');
            if (input) input.value = '';
        },
        get displayAvatar() {
            if (this.avatarPreview) return this.avatarPreview;
            if (!this.removingAvatar) return this.avatarUrl;
            return '';
        },
        onSubmit(event) {
            this.errors.name = this.check(this.name, ['required', 'maxLength:255']);
            this.errors.role = this.check(this.role, ['required']);
            if (Object.values(this.errors).some(Boolean)) {
                event.preventDefault();
                return;
            }
            this.submitting = true;
        },
        get formAction() {
            return '{{ rtrim(url('admin/users'), '/') }}/' + this.id;
        },
        close() {
            this.open = false;
            this.cancelAvatarChange();
            document.body.classList.remove('overflow-hidden');
        },
    }"
    x-show="open"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @open-edit-modal.window="
        id             = $event.detail.id;
        name           = $event.detail.name;
        email          = $event.detail.email;
        role           = $event.detail.role;
        isActive       = $event.detail.isActive;
        avatarUrl      = $event.detail.avatarUrl || '';
        avatarPreview  = null;
        removingAvatar = false;
        errors         = { name: '', role: '' };
        open           = true;
    "
    @keydown.escape.window="if(open) close()"
    @click.self="close()"
    role="dialog"
    aria-modal="true"
    aria-labelledby="editModalTitle"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
>
    <div
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-1"
        class="w-full max-w-md"
    >
        <x-card class="overflow-hidden">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/30">
                <div>
                    <h3 id="editModalTitle"
                        class="text-base font-semibold text-primary"
                        style="font-family: var(--font-display);">
                        Edit User
                    </h3>
                    <p class="text-xs text-on-surface-variant mt-0.5" x-text="email"></p>
                </div>
                <button
                    type="button"
                    @click="close()"
                    class="w-8 h-8 flex items-center justify-center rounded-[12px] cursor-pointer
                           text-on-surface-variant hover:bg-surface-container hover:text-primary
                           transition-colors duration-150 shrink-0 ml-3">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            {{-- Form — enctype required for avatar file upload --}}
            <form
                method="POST"
                :action="formAction"
                enctype="multipart/form-data"
                @submit="onSubmit"
                novalidate
            >
                @csrf
                @method('PATCH')
                <input type="hidden" name="_modal" value="edit">
                <input type="hidden" name="_edit_id" :value="id">
                {{-- Carries email for display-only on re-open after server error. --}}
                <input type="hidden" name="_display_email" :value="email">
                {{-- remove_avatar flag — set by clicking "Remove" in the modal --}}
                <input type="hidden" name="remove_avatar" :value="removingAvatar ? '1' : '0'">
                {{-- Avatar file input — selected file is submitted with Save Changes --}}
                <input type="file" id="editAvatarInput" name="avatar"
                       accept="image/jpeg,image/png,image/webp"
                       class="hidden sr-only"
                       @change="onFileChange">

                {{-- Avatar section --}}
                <div class="px-6 py-4 border-b border-outline-variant/20 bg-surface-container-low/40">
                    <div class="flex items-center gap-4">

                        {{-- Preview circle --}}
                        <div class="w-16 h-16 rounded-full shrink-0 overflow-hidden
                                    flex items-center justify-center select-none
                                    bg-primary-container text-on-primary text-xl font-bold">
                            <template x-if="displayAvatar">
                                <img :src="displayAvatar" :alt="name" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!displayAvatar">
                                <span x-text="name ? name.charAt(0).toUpperCase() : '?'"></span>
                            </template>
                        </div>

                        {{-- Controls --}}
                        <div class="flex flex-col gap-2 min-w-0">
                            <div class="flex flex-wrap gap-2">
                                <button type="button"
                                        @click="document.getElementById('editAvatarInput').click()"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[16px]
                                               text-xs font-medium cursor-pointer transition-colors duration-150
                                               bg-surface-container text-on-surface hover:bg-surface-container-high
                                               border border-outline-variant/40">
                                    <span class="material-symbols-outlined text-[14px]">upload</span>
                                    <span x-text="avatarUrl ? 'Change photo' : 'Upload photo'"></span>
                                </button>

                                <button x-show="avatarUrl && !removingAvatar" type="button"
                                        @click="flagRemove()" x-cloak
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[16px]
                                               text-xs font-medium cursor-pointer transition-colors duration-150
                                               text-error hover:bg-error-container/60">
                                    <span class="material-symbols-outlined text-[14px]">delete</span>
                                    Remove photo
                                </button>

                                <button x-show="avatarPreview || removingAvatar" type="button"
                                        @click="cancelAvatarChange()" x-cloak
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-[16px]
                                               text-xs font-medium cursor-pointer transition-colors duration-150
                                               text-on-surface-variant hover:bg-surface-container">
                                    Cancel
                                </button>
                            </div>

                            <p x-show="avatarPreview" x-cloak
                               class="text-[11px] text-gold font-medium">
                                Photo selected — click Save Changes to apply.
                            </p>
                            <p x-show="removingAvatar" x-cloak
                               class="text-[11px] text-error font-medium">
                                Photo will be removed on Save Changes.
                            </p>
                            <p x-show="!avatarPreview && !removingAvatar"
                               class="text-[11px] text-outline">
                                JPEG, PNG, or WebP · max 2 MB
                            </p>
                        </div>

                    </div>
                </div>

                {{-- Profile fields --}}
                <div class="px-6 py-5 space-y-4 overflow-y-auto max-h-[45vh]">

                    {{-- Email read-only display --}}
                    <div>
                        <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                            Email Address
                        </p>
                        <div class="flex items-center gap-2 px-4 py-2.5 bg-surface-container-low
                                    border border-outline-variant/30 rounded-[16px] text-sm text-on-surface-variant">
                            <span class="material-symbols-outlined text-[16px] text-outline shrink-0">lock</span>
                            <span x-text="email || '—'" class="truncate"></span>
                        </div>
                        <p class="mt-1 text-[11px] text-outline">
                            Email cannot be changed — used to match Google Sign-In.
                        </p>
                    </div>

                    {{-- Name --}}
                    <div>
                        <label for="e-name"
                               class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                            Full Name <span class="text-error normal-case tracking-normal font-normal">*</span>
                        </label>
                        <input
                            id="e-name"
                            type="text"
                            name="name"
                            x-model="name"
                            @blur="errors.name = check(name, ['required', 'maxLength:255'])"
                            autocomplete="off"
                            :class="errors.name ? 'border-error focus:ring-error focus:border-error' : 'border-outline-variant/60 focus:ring-primary focus:border-primary'"
                            class="w-full px-4 py-2.5 bg-surface-white border rounded-[16px] text-sm
                                   placeholder:text-outline focus:outline-none focus:ring-1 transition-colors"
                        >
                        <p x-show="errors.name" x-text="errors.name"
                           class="mt-1.5 text-xs text-error" x-cloak></p>
                    </div>

                    {{-- Role --}}
                    <div>
                        <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                            Role <span x-show="role !== 'admin'" class="text-error normal-case tracking-normal font-normal">*</span>
                        </p>

                        {{-- Admin: locked display, hidden input carries value --}}
                        <template x-if="role === 'admin'">
                            <div>
                                <div class="flex items-center gap-2 px-4 py-2.5 bg-surface-container-low
                                            border border-outline-variant/30 rounded-[16px] text-sm text-on-surface-variant">
                                    <span class="material-symbols-outlined text-[16px] text-outline shrink-0">lock</span>
                                    <span>Admin</span>
                                </div>
                                <p class="mt-1 text-[11px] text-outline">Admin role cannot be changed.</p>
                                <input type="hidden" name="role" value="admin">
                            </div>
                        </template>

                        {{-- Teacher / Student: editable select --}}
                        <template x-if="role !== 'admin'">
                            <div>
                                <div class="relative">
                                    <select
                                        name="role"
                                        x-model="role"
                                        @change="errors.role = check(role, ['required'])"
                                        :class="errors.role ? 'border-error focus:ring-error focus:border-error' : 'border-outline-variant/60 focus:ring-primary focus:border-primary'"
                                        class="w-full appearance-none pl-4 pr-9 py-2.5 bg-surface-white border
                                               rounded-[16px] text-sm text-on-surface
                                               focus:outline-none focus:ring-1 transition-colors cursor-pointer"
                                    >
                                        <option value="" disabled>Select a role…</option>
                                        <option value="teacher">Teacher</option>
                                        <option value="student">Student</option>
                                    </select>
                                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2
                                                 text-outline text-[18px] pointer-events-none">expand_more</span>
                                </div>
                                <p x-show="errors.role" x-text="errors.role"
                                   class="mt-1.5 text-xs text-error" x-cloak></p>
                            </div>
                        </template>
                    </div>

                    {{-- Active toggle --}}
                    <div class="flex items-center justify-between py-1">
                        <div>
                            <p class="text-sm font-medium text-on-surface">Active</p>
                            <p class="text-xs text-on-surface-variant mt-0.5">Inactive users cannot sign in.</p>
                        </div>
                        <input type="hidden" name="is_active" :value="isActive ? '1' : '0'">
                        <button
                            type="button"
                            @click="isActive = !isActive"
                            :class="isActive ? 'bg-gold' : 'bg-outline-variant'"
                            class="relative inline-flex w-11 h-6 shrink-0 rounded-full cursor-pointer
                                   transition-colors duration-200 ease-in-out
                                   focus:outline-none focus:ring-2 focus:ring-primary/40 focus:ring-offset-2"
                            role="switch"
                            :aria-checked="isActive.toString()"
                            aria-label="Active status"
                        >
                            <span
                                :class="isActive ? 'translate-x-5' : 'translate-x-0.5'"
                                class="inline-block w-5 h-5 mt-0.5 rounded-full bg-white shadow
                                       transition-transform duration-200 ease-in-out"
                            ></span>
                        </button>
                    </div>

                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-outline-variant/30 bg-surface-container-low/40">
                    <x-button type="button" variant="secondary" @click="close()">
                        Cancel
                    </x-button>
                    <x-button type="submit" variant="primary-dark" x-bind:disabled="submitting"
                              class="min-w-[120px] justify-center">
                        <span x-show="!submitting">Save Changes</span>
                        <span x-show="submitting" x-cloak>Saving…</span>
                    </x-button>
                </div>

            </form>
        </x-card>
    </div>
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
                       bg-surface-container text-on-surface hover:bg-surface-container-high transition-colors">
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
    // ── Filter navigation ─────────────────────────────────────────
    function updateFilter(key, value) {
        const url = new URL(window.location.href);
        if (value === '') {
            url.searchParams.delete(key);
        } else {
            url.searchParams.set(key, value);
        }
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
        history.replaceState(null, '', url.toString());
        tableContainer.style.opacity = '0.5';
        fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => { tableContainer.innerHTML = html; tableContainer.style.opacity = '1'; })
            .catch(() => { tableContainer.style.opacity = '1'; });
    }

    document.getElementById('filterForm').addEventListener('submit', e => e.preventDefault());

    // ── Create modal trigger ──────────────────────────────────────
    // Dispatches a window event that the Alpine x-data component listens for.
    function openCreateModal() {
        document.body.classList.add('overflow-hidden');
        window.dispatchEvent(new CustomEvent('open-create-modal'));
    }

    // ── Edit modal trigger ────────────────────────────────────────
    // Called from table rows (works in AJAX-injected HTML too).
    function openEditModal(id, name, email, role, isActive, avatarUrl) {
        document.body.classList.add('overflow-hidden');
        window.dispatchEvent(new CustomEvent('open-edit-modal', {
            detail: { id, name, email, role, isActive, avatarUrl: avatarUrl || '' }
        }));
    }

    // ── Delete modal (plain JS, matching existing pattern) ────────
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

    document.getElementById('deleteModal').addEventListener('click', function (e) {
        if (e.target === this) closeDeleteModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeDeleteModal();
    });
</script>
@endpush
