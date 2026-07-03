@extends($layout)

@section('title', 'Settings')

@section('content')

<div class="max-w-full space-y-8">

    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
            Settings
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            Manage your account preferences.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">

        {{-- Left: Avatar + Account card --}}
        <x-card class="p-6 animate-fade-up h-full">
            <div class="flex flex-col sm:flex-row gap-8 h-full">

                {{-- Avatar block --}}
                <div
                    x-data="{
                        preview: null,
                        hasAvatar: {{ auth()->user()->avatarUrl() ? 'true' : 'false' }},
                        onFileChange(event) {
                            const file = event.target.files[0];
                            if (!file) return;
                            // Client-side size guard (2 MB)
                            if (file.size > 2 * 1024 * 1024) {
                                alert('Image must be 2 MB or smaller.');
                                event.target.value = '';
                                return;
                            }
                            const reader = new FileReader();
                            reader.onload = (e) => { this.preview = e.target.result; };
                            reader.readAsDataURL(file);
                        },
                        cancelPreview() {
                            this.preview = null;
                            this.$refs.fileInput.value = '';
                        },
                    }"
                    class="flex flex-row sm:flex-col items-center gap-3 shrink-0"
                >
                    <div class="relative shrink-0">
                        <div class="w-28 h-28 rounded-full overflow-hidden
                                    bg-primary-container flex items-center justify-center select-none">
                            <template x-if="preview">
                                <img :src="preview" alt="Preview" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!preview">
                                @if(auth()->user()->avatarUrl())
                                    <img src="{{ auth()->user()->avatarUrl() }}"
                                         alt="{{ auth()->user()->name }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <span class="text-2xl font-bold text-on-primary"
                                          style="font-family: var(--font-display);">
                                        {{ strtoupper(substr(auth()->user()->name ?? '?', 0, 1)) }}
                                    </span>
                                @endif
                            </template>
                        </div>

                        @if(auth()->user()->avatar_source === 'google' && ! auth()->user()->hasManualAvatar())
                            <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-surface-container
                                         flex items-center justify-center" title="Google photo">
                                <span class="material-symbols-outlined text-[12px] text-outline">account_circle</span>
                            </span>
                        @endif
                    </div>

                    {{-- Upload form (icon-only edit button) --}}
                    <form method="POST"
                          action="{{ route('settings.avatar.update') }}"
                          enctype="multipart/form-data"
                          x-ref="uploadForm"
                          @submit="if (!$refs.fileInput.files.length) { $event.preventDefault(); }"
                    >
                        @csrf
                        <input
                            type="file"
                            name="avatar"
                            accept="image/jpeg,image/png,image/webp"
                            class="hidden sr-only"
                            x-ref="fileInput"
                            @change="onFileChange"
                        >

                        <div class="flex items-center gap-2">
                            <button type="button" @click="$refs.fileInput.click()"
                                title="{{ auth()->user()->avatarUrl() ? 'Change photo' : 'Upload photo' }}"
                                aria-label="{{ auth()->user()->avatarUrl() ? 'Change photo' : 'Upload photo' }}"
                                class="w-9 h-9 flex items-center justify-center rounded-[12px]
                                       border border-outline-variant/60 text-on-surface-variant
                                       hover:bg-surface-container hover:text-primary
                                       active:scale-[0.96] transition-all duration-150 cursor-pointer">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                            </button>

                            <button x-show="preview" x-cloak type="submit"
                                title="Save photo" aria-label="Save photo"
                                class="w-9 h-9 flex items-center justify-center rounded-[12px]
                                       bg-gold text-primary
                                       hover:bg-gold/90 active:scale-[0.96] transition-all duration-150 cursor-pointer">
                                <span class="material-symbols-outlined text-[18px]">save</span>
                            </button>

                            <button x-show="preview" x-cloak type="button" @click="cancelPreview()"
                                title="Cancel" aria-label="Cancel"
                                class="w-9 h-9 flex items-center justify-center rounded-[12px]
                                       border border-outline-variant/60 text-on-surface-variant
                                       hover:bg-surface-container hover:text-primary
                                       active:scale-[0.96] transition-all duration-150 cursor-pointer">
                                <span class="material-symbols-outlined text-[18px]">close</span>
                            </button>

                            @if(auth()->user()->avatarUrl())
                            <button type="button"
                                title="Remove photo" aria-label="Remove photo"
                                @click.prevent="confirmDelete('Profile Photo', $refs.removeForm, 'You will revert to your initials.')"
                                class="w-9 h-9 flex items-center justify-center rounded-[12px]
                                       border border-outline-variant/60 text-error
                                       hover:bg-error/10
                                       active:scale-[0.96] transition-all duration-150 cursor-pointer">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                            @endif
                        </div>

                        @error('avatar')
                            <p class="mt-2 text-xs text-error">{{ $message }}</p>
                        @enderror
                    </form>

                    {{-- Remove form (submitted via the trash button above) --}}
                    @if(auth()->user()->avatarUrl())
                    <form method="POST" action="{{ route('settings.avatar.destroy') }}" x-ref="removeForm" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                    @endif
                </div>

                {{-- Account section --}}
                <div
                    class="flex-1 min-w-0"
                    x-data="{
                        editing: {{ $errors->has('name') ? 'true' : 'false' }},
                        submitting: false,
                        name: '{{ addslashes(old('name', auth()->user()->name)) }}',
                        errors: { name: '{{ addslashes($errors->first('name')) }}' },
                        check(value, rules) {
                            const r = window.Iodine.assert(value ?? '', rules);
                            return r.valid ? '' : r.error;
                        },
                        onSubmit(event) {
                            this.errors.name = this.check(this.name, ['required', 'maxLength:255']);
                            if (this.errors.name) {
                                event.preventDefault();
                                return;
                            }
                            this.submitting = true;
                        },
                        cancelEdit() {
                            this.editing = false;
                            this.name = '{{ addslashes(auth()->user()->name) }}';
                            this.errors.name = '';
                        },
                    }"
                >
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-base font-semibold text-primary" style="font-family: var(--font-display);">
                            Account
                        </h2>
                        <button type="button" x-show="!editing" @click="editing = true"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-gold text-primary
                                   text-xs font-semibold rounded-[24px] hover:bg-gold/90
                                   active:scale-[0.96] transition-all duration-150 cursor-pointer">
                            <span class="material-symbols-outlined text-[16px]">edit</span>
                            Edit Name
                        </button>
                    </div>

                    {{-- View mode --}}
                    <dl x-show="!editing" x-transition class="space-y-3">
                        <div class="flex items-start gap-4">
                            <dt class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide w-20 shrink-0 mt-0.5">
                                Name
                            </dt>
                            <dd class="text-sm text-on-surface min-w-0 break-words">{{ auth()->user()->name }}</dd>
                        </div>
                        <div class="flex items-start gap-4">
                            <dt class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide w-20 shrink-0 mt-0.5">
                                Email
                            </dt>
                            <dd class="text-sm text-on-surface min-w-0 break-words">{{ auth()->user()->email }}</dd>
                        </div>
                        <div class="flex items-start gap-4">
                            <dt class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide w-20 shrink-0 mt-0.5">
                                Role
                            </dt>
                            <dd>
                                <x-badge :variant="auth()->user()->role->name">
                                    {{ ucfirst(auth()->user()->role->name) }}
                                </x-badge>
                            </dd>
                        </div>
                    </dl>

                    {{-- Edit mode --}}
                    <form x-show="editing" x-cloak x-transition
                          method="POST" action="{{ route('settings.profile.update') }}"
                          @submit="onSubmit" novalidate class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="settings-name"
                                   class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                                Full Name <span class="text-error normal-case tracking-normal font-normal">*</span>
                            </label>
                            <input
                                id="settings-name"
                                type="text"
                                name="name"
                                x-model="name"
                                @blur="errors.name = check(name, ['required', 'maxLength:255'])"
                                autocomplete="name"
                                :class="errors.name ? 'border-error focus:ring-error focus:border-error' : 'border-outline-variant/60 focus:ring-primary focus:border-primary'"
                                class="w-full px-4 py-2.5 bg-surface-white border rounded-[16px] text-sm
                                       placeholder:text-outline focus:outline-none focus:ring-1 transition-colors">
                            <p x-show="errors.name" x-text="errors.name"
                               class="mt-1.5 text-xs text-error" x-cloak></p>
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit" :disabled="submitting"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-gold text-primary
                                       text-sm font-semibold rounded-[24px] hover:bg-gold/90
                                       active:scale-[0.96] transition-all duration-150 cursor-pointer
                                       disabled:opacity-60 disabled:cursor-not-allowed disabled:active:scale-100">
                                <span class="material-symbols-outlined text-[18px]"
                                      :class="submitting ? 'animate-spin' : ''"
                                      x-text="submitting ? 'progress_activity' : 'save'">save</span>
                                <span x-text="submitting ? 'Saving…' : 'Save'">Save</span>
                            </button>
                            <button type="button" @click="cancelEdit()"
                                class="px-4 py-2.5 border border-outline-variant/60 text-sm font-medium
                                       text-on-surface-variant rounded-[24px]
                                       hover:bg-surface-container-low hover:text-primary
                                       active:scale-[0.96] transition-all duration-150 cursor-pointer">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </x-card>

        {{-- Right: Password card --}}
        @php $hasPassword = ! is_null(auth()->user()->password); @endphp
        <x-card
            class="p-6 animate-fade-up h-full"
            x-data="{
                editing: {{ $errors->has('password') || $errors->has('current_password') ? 'true' : 'false' }},
                submitting: false,
                currentPassword: '',
                password: '',
                passwordConfirm: '',
                hasPassword: {{ $hasPassword ? 'true' : 'false' }},
                errors: {
                    currentPassword: '{{ addslashes($errors->first('current_password')) }}',
                    password: '{{ addslashes($errors->first('password')) }}',
                    passwordConfirm: '',
                },
                check(value, rules) {
                    const r = window.Iodine.assert(value ?? '', rules);
                    return r.valid ? '' : r.error;
                },
                onSubmit(event) {
                    if (this.hasPassword) {
                        this.errors.currentPassword = this.check(this.currentPassword, ['required']);
                    }
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
                cancelEdit() {
                    this.editing = false;
                    this.currentPassword = '';
                    this.password = '';
                    this.passwordConfirm = '';
                    this.errors = { currentPassword: '', password: '', passwordConfirm: '' };
                },
            }"
        >
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-base font-semibold text-primary" style="font-family: var(--font-display);">
                    Password
                </h2>
                <button type="button" x-show="!editing" @click="editing = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-gold text-primary
                           text-xs font-semibold rounded-[24px] hover:bg-gold/90
                           active:scale-[0.96] transition-all duration-150 cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">edit</span>
                    {{ $hasPassword ? 'Change Password' : 'Set Password' }}
                </button>
            </div>

            {{-- View mode --}}
            <p x-show="!editing" x-transition class="text-sm text-on-surface-variant">
                @if($hasPassword)
                    ••••••••••••
                @else
                    No password set — you sign in with Google. Set a password to also enable email/password sign-in.
                @endif
            </p>

            {{-- Edit mode --}}
            <form x-show="editing" x-cloak x-transition
                  method="POST" action="{{ route('settings.password.update') }}"
                  @submit="onSubmit" novalidate class="space-y-4">
                @csrf
                @method('PATCH')

                @if($hasPassword)
                <div>
                    <label for="settings-current-password"
                           class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                        Current Password <span class="text-error normal-case tracking-normal font-normal">*</span>
                    </label>
                    <input
                        id="settings-current-password"
                        type="password"
                        name="current_password"
                        x-model="currentPassword"
                        @blur="errors.currentPassword = check(currentPassword, ['required'])"
                        autocomplete="current-password"
                        :class="errors.currentPassword ? 'border-error focus:ring-error focus:border-error' : 'border-outline-variant/60 focus:ring-primary focus:border-primary'"
                        class="w-full px-4 py-2.5 bg-surface-white border rounded-[16px] text-sm
                               placeholder:text-outline focus:outline-none focus:ring-1 transition-colors">
                    <p x-show="errors.currentPassword" x-text="errors.currentPassword"
                       class="mt-1.5 text-xs text-error" x-cloak></p>
                </div>
                @endif

                <div>
                    <label for="settings-password"
                           class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                        New Password <span class="text-error normal-case tracking-normal font-normal">*</span>
                    </label>
                    <input
                        id="settings-password"
                        type="password"
                        name="password"
                        x-model="password"
                        @blur="errors.password = check(password, ['required', 'minLength:8'])"
                        autocomplete="new-password"
                        placeholder="Min. 8 characters"
                        :class="errors.password ? 'border-error focus:ring-error focus:border-error' : 'border-outline-variant/60 focus:ring-primary focus:border-primary'"
                        class="w-full px-4 py-2.5 bg-surface-white border rounded-[16px] text-sm
                               placeholder:text-outline focus:outline-none focus:ring-1 transition-colors">
                    <p x-show="errors.password" x-text="errors.password"
                       class="mt-1.5 text-xs text-error" x-cloak></p>
                </div>

                <div>
                    <label for="settings-password-confirm"
                           class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wide mb-1.5">
                        Confirm New Password <span class="text-error normal-case tracking-normal font-normal">*</span>
                    </label>
                    <input
                        id="settings-password-confirm"
                        type="password"
                        name="password_confirmation"
                        x-model="passwordConfirm"
                        @blur="errors.passwordConfirm = check(passwordConfirm, ['required', 'same:' + password])"
                        autocomplete="new-password"
                        placeholder="Re-enter password"
                        :class="errors.passwordConfirm ? 'border-error focus:ring-error focus:border-error' : 'border-outline-variant/60 focus:ring-primary focus:border-primary'"
                        class="w-full px-4 py-2.5 bg-surface-white border rounded-[16px] text-sm
                               placeholder:text-outline focus:outline-none focus:ring-1 transition-colors">
                    <p x-show="errors.passwordConfirm" x-text="errors.passwordConfirm"
                       class="mt-1.5 text-xs text-error" x-cloak></p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" :disabled="submitting"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gold text-primary
                               text-sm font-semibold rounded-[24px] hover:bg-gold/90
                               active:scale-[0.96] transition-all duration-150 cursor-pointer
                               disabled:opacity-60 disabled:cursor-not-allowed disabled:active:scale-100">
                        <span class="material-symbols-outlined text-[18px]"
                              :class="submitting ? 'animate-spin' : ''"
                              x-text="submitting ? 'progress_activity' : 'save'">save</span>
                        <span x-text="submitting ? 'Saving…' : 'Save'">Save</span>
                    </button>
                    <button type="button" @click="cancelEdit()"
                        class="px-4 py-2.5 border border-outline-variant/60 text-sm font-medium
                               text-on-surface-variant rounded-[24px]
                               hover:bg-surface-container-low hover:text-primary
                               active:scale-[0.96] transition-all duration-150 cursor-pointer">
                        Cancel
                    </button>
                </div>
            </form>
        </x-card>

    </div>

</div>

@endsection
