@extends($layout)

@section('title', 'Settings')

@section('content')

<div class="max-w-2xl space-y-8">

    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
            Settings
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            Manage your account preferences.
        </p>
    </div>

    {{-- Avatar Card --}}
    <x-card
        x-data="{
            preview: null,
            removing: false,
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
                this.removing = false;
            },
            cancelPreview() {
                this.preview = null;
                this.$refs.fileInput.value = '';
            },
        }"
        class="p-6 animate-fade-up"
    >
        <h2 class="text-base font-semibold text-primary mb-5" style="font-family: var(--font-display);">
            Profile Photo
        </h2>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">

            {{-- Avatar preview --}}
            <div class="relative shrink-0">
                <div class="w-24 h-24 rounded-full overflow-hidden
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

                @if(auth()->user()->hasManualAvatar())
                    <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-gold
                                 flex items-center justify-center" title="Custom photo">
                        <span class="material-symbols-outlined text-[12px] text-primary">check</span>
                    </span>
                @elseif(auth()->user()->avatar_source === 'google')
                    <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-surface-container
                                 flex items-center justify-center" title="Google photo">
                        <span class="material-symbols-outlined text-[12px] text-outline">account_circle</span>
                    </span>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex-1 min-w-0 space-y-3">

                {{-- Source badge --}}
                <div class="flex items-center gap-2">
                    @if(auth()->user()->hasManualAvatar())
                        <x-badge variant="active" icon="upload">Custom upload</x-badge>
                    @elseif(auth()->user()->avatar_source === 'google')
                        <x-badge variant="default" icon="account_circle">From Google</x-badge>
                    @else
                        <x-badge variant="default">No photo — showing initials</x-badge>
                    @endif
                </div>

                {{-- Upload form --}}
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

                    <div class="flex flex-wrap gap-2">
                        <x-button type="button" variant="secondary" size="sm" icon="upload"
                                  @click="$refs.fileInput.click()">
                            {{ auth()->user()->avatarUrl() ? 'Change photo' : 'Upload photo' }}
                        </x-button>

                        <x-button x-show="preview" type="submit" variant="primary" size="sm" icon="save"
                                  x-cloak>
                            Save photo
                        </x-button>

                        <x-button x-show="preview" type="button" variant="ghost" size="sm"
                                  @click="cancelPreview()" x-cloak>
                            Cancel
                        </x-button>
                    </div>

                    @error('avatar')
                        <p class="mt-2 text-xs text-error">{{ $message }}</p>
                    @enderror
                </form>

                {{-- Remove form --}}
                @if(auth()->user()->avatarUrl())
                <form method="POST" action="{{ route('settings.avatar.destroy') }}"
                      @submit.prevent="
                          if (confirm('Remove your photo? You will revert to your initials.'))
                              $el.submit();
                      ">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" size="sm" icon="delete">
                        Remove photo
                    </x-button>
                </form>
                @endif

                <p class="text-xs text-outline leading-relaxed">
                    JPEG, PNG, or WebP · max 2 MB · resized to 256×256.
                    @if(auth()->user()->avatar_source === 'google')
                        Uploading a custom photo will prevent Google login from overwriting it.
                    @endif
                </p>

            </div>
        </div>
    </x-card>

    {{-- Account info (read-only) --}}
    <x-card class="p-6 animate-fade-up">
        <h2 class="text-base font-semibold text-primary mb-5" style="font-family: var(--font-display);">
            Account
        </h2>
        <dl class="space-y-3">
            <div class="flex items-start gap-4">
                <dt class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide w-20 shrink-0 mt-0.5">
                    Name
                </dt>
                <dd class="text-sm text-on-surface">{{ auth()->user()->name }}</dd>
            </div>
            <div class="flex items-start gap-4">
                <dt class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide w-20 shrink-0 mt-0.5">
                    Email
                </dt>
                <dd class="text-sm text-on-surface">{{ auth()->user()->email }}</dd>
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
    </x-card>

</div>

@endsection
