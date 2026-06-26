@extends('layouts.admin')

@section('title', $subject->name)

@section('topbar-actions')
    <a href="{{ route('admin.users.index', ['role' => $subject->role->name]) }}"
       class="inline-flex items-center gap-1.5 px-4 py-2 border border-outline-variant/60
              text-sm font-medium text-on-surface-variant rounded-[24px]
              hover:bg-surface-container-low hover:text-primary transition-colors cursor-pointer">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Back to {{ ucfirst($subject->role->name) }}s
    </a>
@endsection

@section('content')
@php
    $roleName  = $subject->role->name;
    $avatarCls = match($roleName) {
        'admin'   => 'bg-primary-container text-on-primary',
        'teacher' => 'bg-gold/20 text-on-gold',
        default   => 'bg-surface-container text-on-surface',
    };
    $roleIcon  = match($roleName) {
        'admin'   => 'admin_panel_settings',
        'teacher' => 'school',
        default   => 'menu_book',
    };
@endphp

{{-- ─── Profile header card ─── --}}
<div class="bg-surface-white border border-outline-variant/40 rounded-[20px]
            shadow-[0px_1px_4px_rgba(30,42,74,0.06)] p-6 animate-fade-up">
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">

        {{-- Avatar --}}
        <div class="w-16 h-16 rounded-full {{ $avatarCls }} flex items-center justify-center
                    shrink-0 overflow-hidden select-none text-xl font-bold">
            @if($subject->avatarUrl())
                <img src="{{ $subject->avatarUrl() }}" alt="{{ $subject->name }}"
                     class="w-full h-full object-cover">
            @else
                {{ strtoupper(substr($subject->name ?? '?', 0, 1)) }}
            @endif
        </div>

        {{-- Name / email / badges --}}
        <div class="flex-1 min-w-0">
            <h1 class="text-xl font-bold text-primary leading-tight break-words"
                style="font-family: var(--font-display);">
                {{ $subject->name }}
            </h1>
            <p class="mt-0.5 text-sm text-on-surface-variant truncate">{{ $subject->email }}</p>
            <div class="flex items-center gap-2 mt-2 flex-wrap">
                {{-- Role badge --}}
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full
                             text-xs font-medium
                             {{ $roleName === 'admin' ? 'bg-primary-container text-on-primary'
                                : ($roleName === 'teacher' ? 'bg-gold/20 text-on-gold'
                                : 'bg-surface-container text-on-surface-variant') }}">
                    <span class="material-symbols-outlined text-[12px]">{{ $roleIcon }}</span>
                    {{ ucfirst($roleName) }}
                </span>
                {{-- Status badge --}}
                @if($subject->is_active)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full
                                 text-xs font-medium bg-green-100 text-green-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 shrink-0"></span>
                        Active
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full
                                 text-xs font-medium bg-surface-container text-on-surface-variant">
                        <span class="w-1.5 h-1.5 rounded-full bg-outline shrink-0"></span>
                        Inactive
                    </span>
                @endif
                {{-- Google linked --}}
                @if($subject->google_id)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full
                                 text-xs font-medium bg-surface-container text-on-surface-variant">
                        <span class="material-symbols-outlined text-[12px]">account_circle</span>
                        Google linked
                    </span>
                @endif
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="flex items-center gap-2 shrink-0">
            <button type="button"
                    onclick="openUserEditModal()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gold text-primary
                           text-sm font-semibold rounded-[24px] hover:bg-gold/90
                           active:scale-[0.96] transition-all duration-150 cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">edit</span>
                Edit
            </button>
        </div>
    </div>
</div>

{{-- ─── Role-specific content ─── --}}

@if($roleName === 'admin')
    {{-- ══ ADMIN: account details ══ --}}
    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px]
                shadow-[0px_1px_4px_rgba(30,42,74,0.06)] p-6 animate-fade-up">
        <p class="text-sm font-semibold text-on-surface mb-4" style="font-family: var(--font-display);">
            Account Details
        </p>
        <dl class="divide-y divide-outline-variant/20">
            <div class="flex items-start gap-4 py-3 first:pt-0 last:pb-0">
                <dt class="text-xs font-semibold text-outline uppercase tracking-wide w-32 shrink-0 mt-0.5">
                    Full Name
                </dt>
                <dd class="text-sm text-on-surface">{{ $subject->name }}</dd>
            </div>
            <div class="flex items-start gap-4 py-3 first:pt-0 last:pb-0">
                <dt class="text-xs font-semibold text-outline uppercase tracking-wide w-32 shrink-0 mt-0.5">
                    Email
                </dt>
                <dd class="text-sm text-on-surface break-all">{{ $subject->email }}</dd>
            </div>
            <div class="flex items-start gap-4 py-3 first:pt-0 last:pb-0">
                <dt class="text-xs font-semibold text-outline uppercase tracking-wide w-32 shrink-0 mt-0.5">
                    Role
                </dt>
                <dd class="text-sm text-on-surface">Admin</dd>
            </div>
            <div class="flex items-start gap-4 py-3 first:pt-0 last:pb-0">
                <dt class="text-xs font-semibold text-outline uppercase tracking-wide w-32 shrink-0 mt-0.5">
                    Status
                </dt>
                <dd class="text-sm text-on-surface">{{ $subject->is_active ? 'Active' : 'Inactive' }}</dd>
            </div>
            <div class="flex items-start gap-4 py-3 first:pt-0 last:pb-0">
                <dt class="text-xs font-semibold text-outline uppercase tracking-wide w-32 shrink-0 mt-0.5">
                    Joined
                </dt>
                <dd class="text-sm text-on-surface">{{ $subject->created_at->format('d M Y') }}</dd>
            </div>
            <div class="flex items-start gap-4 py-3 first:pt-0 last:pb-0">
                <dt class="text-xs font-semibold text-outline uppercase tracking-wide w-32 shrink-0 mt-0.5">
                    Google SSO
                </dt>
                <dd class="text-sm text-on-surface">{{ $subject->google_id ? 'Linked' : 'Not linked' }}</dd>
            </div>
        </dl>
    </div>

@elseif($roleName === 'teacher')
    {{-- ══ TEACHER: courses ══ --}}
    @php $courses = $subject->courses; @endphp
    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px]
                shadow-[0px_1px_4px_rgba(30,42,74,0.06)] overflow-hidden animate-fade-up">

        <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-outline-variant/20">
            <p class="text-sm font-semibold text-on-surface" style="font-family: var(--font-display);">
                Courses
            </p>
            <span class="text-xs text-on-surface-variant">
                {{ $courses->count() }} {{ Str::plural('course', $courses->count()) }}
            </span>
        </div>

        @if($courses->isEmpty())
            <div class="py-14 flex flex-col items-center gap-3 text-center px-4">
                <div class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center animate-float">
                    <span class="material-symbols-outlined text-outline text-[24px]">library_books</span>
                </div>
                <p class="text-sm font-medium text-on-surface">No courses yet</p>
                <p class="text-xs text-on-surface-variant">This teacher hasn't created any courses.</p>
            </div>
        @else
            <ul class="divide-y divide-outline-variant/20">
                @foreach($courses as $course)
                    <li class="flex items-center gap-4 px-6 py-4 min-w-0
                               hover:bg-surface-container-low/40 transition-colors duration-200">

                        {{-- Status dot --}}
                        <span class="w-2 h-2 rounded-full shrink-0
                                     {{ $course->is_published ? 'bg-gold' : 'bg-outline-variant' }}"
                              title="{{ $course->is_published ? 'Published' : 'Draft' }}">
                        </span>

                        {{-- Title + meta --}}
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('admin.courses.show', $course->id) }}"
                               class="text-sm font-medium text-on-surface hover:text-gold
                                      transition-colors truncate block cursor-pointer">
                                {{ $course->title }}
                            </a>
                            <p class="text-xs text-on-surface-variant mt-0.5 flex items-center gap-2 flex-wrap">
                                <span class="inline-flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[13px]">menu_book</span>
                                    {{ $course->units->count() }} {{ Str::plural('unit', $course->units->count()) }}
                                </span>
                                <span class="text-outline-variant/60">·</span>
                                <span class="inline-flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[13px]">group</span>
                                    {{ $course->students_count }} {{ Str::plural('student', $course->students_count) }}
                                </span>
                                <span class="text-outline-variant/60">·</span>
                                <span>{{ $course->is_published ? 'Published' : 'Draft' }}</span>
                            </p>
                        </div>

                        {{-- View arrow --}}
                        <a href="{{ route('admin.courses.show', $course->id) }}"
                           class="shrink-0 text-outline hover:text-primary transition-colors cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

@else
    {{-- ══ STUDENT: enrolled courses ══ --}}
    @php $enrolledCourses = $subject->enrolledCourses; @endphp
    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px]
                shadow-[0px_1px_4px_rgba(30,42,74,0.06)] overflow-hidden animate-fade-up">

        <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-outline-variant/20">
            <p class="text-sm font-semibold text-on-surface" style="font-family: var(--font-display);">
                Enrolled Courses
            </p>
            <span class="text-xs text-on-surface-variant">
                {{ $enrolledCourses->count() }} {{ Str::plural('course', $enrolledCourses->count()) }}
            </span>
        </div>

        @if($enrolledCourses->isEmpty())
            <div class="py-14 flex flex-col items-center gap-3 text-center px-4">
                <div class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center animate-float">
                    <span class="material-symbols-outlined text-outline text-[24px]">school</span>
                </div>
                <p class="text-sm font-medium text-on-surface">Not enrolled in any courses</p>
                <p class="text-xs text-on-surface-variant">This student hasn't joined any courses yet.</p>
            </div>
        @else
            <ul class="divide-y divide-outline-variant/20">
                @foreach($enrolledCourses as $course)
                    @php
                        $enrollmentActive = $course->pivot->is_active ?? true;
                        $enrolledAt       = $course->pivot->enrolled_at
                                                ? \Carbon\Carbon::parse($course->pivot->enrolled_at)->format('d M Y')
                                                : '—';
                    @endphp
                    <li class="flex items-center gap-4 px-6 py-4 min-w-0
                               hover:bg-surface-container-low/40 transition-colors duration-200">

                        {{-- Enrollment status dot --}}
                        <span class="w-2 h-2 rounded-full shrink-0
                                     {{ $enrollmentActive ? 'bg-green-500' : 'bg-outline-variant' }}"
                              title="{{ $enrollmentActive ? 'Access active' : 'Access revoked' }}">
                        </span>

                        {{-- Title + meta --}}
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('admin.courses.show', $course->id) }}"
                               class="text-sm font-medium text-on-surface hover:text-gold
                                      transition-colors truncate block cursor-pointer">
                                {{ $course->title }}
                            </a>
                            <p class="text-xs text-on-surface-variant mt-0.5 flex items-center gap-2 flex-wrap">
                                <span class="inline-flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[13px]">person</span>
                                    {{ $course->teacher?->name ?? '—' }}
                                </span>
                                <span class="text-outline-variant/60">·</span>
                                <span class="inline-flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[13px]">menu_book</span>
                                    {{ $course->units_count }} {{ Str::plural('unit', $course->units_count) }}
                                </span>
                                <span class="text-outline-variant/60">·</span>
                                <span>Enrolled {{ $enrolledAt }}</span>
                                @unless($enrollmentActive)
                                    <span class="text-outline-variant/60">·</span>
                                    <span class="text-error text-[11px] font-medium">Access revoked</span>
                                @endunless
                            </p>
                        </div>

                        {{-- View arrow --}}
                        <a href="{{ route('admin.courses.show', $course->id) }}"
                           class="shrink-0 text-outline hover:text-primary transition-colors cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endif

{{-- ─── Edit user modal ─── --}}
<div
    x-data="{
        open: false,
        submitting: false,
        id:            '{{ $subject->id }}',
        name:          '{{ addslashes($subject->name) }}',
        email:         '{{ addslashes($subject->email) }}',
        role:          '{{ $subject->role->name }}',
        isActive:      {{ $subject->is_active ? 'true' : 'false' }},
        avatarUrl:     '{{ addslashes($subject->avatarUrl() ?? '') }}',
        avatarPreview: null,
        removingAvatar: false,
        errors: { name: '', role: '' },
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
            const input = document.getElementById('showEditAvatarInput');
            if (input) input.value = '';
        },
        flagRemove() {
            this.removingAvatar = true;
            this.avatarPreview = null;
            const input = document.getElementById('showEditAvatarInput');
            if (input) input.value = '';
        },
        get displayAvatar() {
            if (this.avatarPreview) return this.avatarPreview;
            if (!this.removingAvatar) return this.avatarUrl;
            return '';
        },
        onSubmit(event) {
            this.errors.name = this.check(this.name, ['required', 'maxLength:255']);
            if (this.role !== 'admin') {
                this.errors.role = this.check(this.role, ['required']);
            }
            if (Object.values(this.errors).some(Boolean)) {
                event.preventDefault();
                return;
            }
            this.submitting = true;
        },
        get formAction() {
            return '{{ route('admin.users.update', $subject->id) }}';
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
    @open-user-edit-modal.window="open = true; document.body.classList.add('overflow-hidden')"
    @keydown.escape.window="if(open) close()"
    @click.self="close()"
    role="dialog"
    aria-modal="true"
    aria-labelledby="showEditModalTitle"
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

            <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/30">
                <div>
                    <h3 id="showEditModalTitle"
                        class="text-base font-semibold text-primary"
                        style="font-family: var(--font-display);">
                        Edit User
                    </h3>
                    <p class="text-xs text-on-surface-variant mt-0.5" x-text="email"></p>
                </div>
                <button type="button" @click="close()"
                        class="w-8 h-8 flex items-center justify-center rounded-[12px] cursor-pointer
                               text-on-surface-variant hover:bg-surface-container hover:text-primary
                               transition-colors duration-150 shrink-0 ml-3">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <form method="POST" :action="formAction" enctype="multipart/form-data"
                  @submit="onSubmit" novalidate>
                @csrf
                @method('PATCH')
                <input type="hidden" name="_edit_id" :value="id">
                <input type="hidden" name="_display_email" :value="email">
                <input type="hidden" name="remove_avatar" :value="removingAvatar ? '1' : '0'">
                <input type="file" id="showEditAvatarInput" name="avatar"
                       accept="image/jpeg,image/png,image/webp"
                       class="hidden sr-only" @change="onFileChange">

                {{-- Avatar --}}
                <div class="px-6 py-4 border-b border-outline-variant/20 bg-surface-container-low/40">
                    <div class="flex items-center gap-4">
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
                        <div class="flex flex-col gap-2 min-w-0">
                            <div class="flex flex-wrap gap-2">
                                <button type="button"
                                        @click="document.getElementById('showEditAvatarInput').click()"
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
                            <p x-show="avatarPreview" x-cloak class="text-[11px] text-gold font-medium">
                                Photo selected — click Save Changes to apply.
                            </p>
                            <p x-show="removingAvatar" x-cloak class="text-[11px] text-error font-medium">
                                Photo will be removed on Save Changes.
                            </p>
                            <p x-show="!avatarPreview && !removingAvatar" class="text-[11px] text-outline">
                                JPEG, PNG, or WebP · max 2 MB
                            </p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-5 space-y-4 overflow-y-auto max-h-[45vh]">

                    {{-- Email read-only --}}
                    <div>
                        <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                            Email Address
                        </p>
                        <div class="flex items-center gap-2 px-4 py-2.5 bg-surface-container-low
                                    border border-outline-variant/30 rounded-[16px] text-sm text-on-surface-variant">
                            <span class="material-symbols-outlined text-[16px] text-outline shrink-0">lock</span>
                            <span x-text="email || '—'" class="truncate"></span>
                        </div>
                        <p class="mt-1 text-[11px] text-outline">Email cannot be changed — used to match Google Sign-In.</p>
                    </div>

                    {{-- Name --}}
                    <div>
                        <label for="se-name"
                               class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                            Full Name <span class="text-error normal-case tracking-normal font-normal">*</span>
                        </label>
                        <input id="se-name" type="text" name="name" x-model="name"
                               @blur="errors.name = check(name, ['required', 'maxLength:255'])"
                               autocomplete="off"
                               :class="errors.name ? 'border-error focus:ring-error focus:border-error' : 'border-outline-variant/60 focus:ring-primary focus:border-primary'"
                               class="w-full px-4 py-2.5 bg-surface-white border rounded-[16px] text-sm
                                      focus:outline-none focus:ring-1 transition-colors">
                        <p x-show="errors.name" x-text="errors.name"
                           class="mt-1.5 text-xs text-error" x-cloak></p>
                    </div>

                    {{-- Role --}}
                    <div>
                        <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                            Role <span x-show="role !== 'admin'" class="text-error normal-case tracking-normal font-normal">*</span>
                        </p>
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
                        <template x-if="role !== 'admin'">
                            <div>
                                <div class="relative">
                                    <select name="role" x-model="role"
                                            @change="errors.role = check(role, ['required'])"
                                            :class="errors.role ? 'border-error focus:ring-error focus:border-error' : 'border-outline-variant/60 focus:ring-primary focus:border-primary'"
                                            class="w-full appearance-none pl-4 pr-9 py-2.5 bg-surface-white border
                                                   rounded-[16px] text-sm text-on-surface
                                                   focus:outline-none focus:ring-1 transition-colors cursor-pointer">
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
                        <button type="button" @click="isActive = !isActive"
                                :class="isActive ? 'bg-gold' : 'bg-outline-variant'"
                                class="relative inline-flex w-11 h-6 shrink-0 rounded-full cursor-pointer
                                       transition-colors duration-200 ease-in-out
                                       focus:outline-none focus:ring-2 focus:ring-primary/40 focus:ring-offset-2"
                                role="switch" :aria-checked="isActive.toString()" aria-label="Active status">
                            <span :class="isActive ? 'translate-x-5' : 'translate-x-0.5'"
                                  class="inline-block w-5 h-5 mt-0.5 rounded-full bg-white shadow
                                         transition-transform duration-200 ease-in-out"></span>
                        </button>
                    </div>

                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-outline-variant/30 bg-surface-container-low/40">
                    <x-button type="button" variant="secondary" @click="close()">Cancel</x-button>
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

@endsection

@push('scripts')
<script>
    function openUserEditModal() {
        window.dispatchEvent(new CustomEvent('open-user-edit-modal'));
    }
</script>
@endpush
