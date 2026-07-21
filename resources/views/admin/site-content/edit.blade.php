@extends('layouts.admin')

@section('title', 'Landing Page Content')

@section('content')
@php
    // Grouped for the form; each entry's max matches UpdateSiteContentRequest's rule
    // for that same dotted key exactly. 'type' controls input vs textarea rendering.
    $groups = [
        'Site Identity' => [
            ['key' => 'site.name',        'label' => 'Site name (used across every page and email)', 'max' => 40, 'type' => 'input'],
            ['key' => 'site.short_label', 'label' => 'Short badge label (e.g. 2 letters)',            'max' => 4,  'type' => 'input'],
        ],
        'Navigation' => [
            ['key' => 'nav.sign_in_label', 'label' => 'Sign-in button label', 'max' => 30, 'type' => 'input'],
        ],
        'Hero' => [
            ['key' => 'hero.badge',         'label' => 'Badge text',                 'max' => 60,  'type' => 'input'],
            ['key' => 'hero.heading_line1', 'label' => 'Heading — line 1',           'max' => 60,  'type' => 'input'],
            ['key' => 'hero.heading_line2', 'label' => 'Heading — line 2',           'max' => 60,  'type' => 'input'],
            ['key' => 'hero.subheading',    'label' => 'Subheading',                 'max' => 300, 'type' => 'textarea'],
            ['key' => 'hero.cta_label',     'label' => 'Call-to-action button label','max' => 30,  'type' => 'input'],
            ['key' => 'hero.caption',       'label' => 'Caption below the button',   'max' => 150, 'type' => 'input'],
        ],
        'Feature Cards' => [
            ['key' => 'features.eyebrow',       'label' => 'Section eyebrow',       'max' => 60,  'type' => 'input'],
            ['key' => 'features.heading',       'label' => 'Section heading',       'max' => 80,  'type' => 'input'],
            ['key' => 'feature.1.title',        'label' => 'Card 1 — title',        'max' => 60,  'type' => 'input'],
            ['key' => 'feature.1.description',  'label' => 'Card 1 — description',  'max' => 200, 'type' => 'textarea'],
            ['key' => 'feature.2.title',        'label' => 'Card 2 — title',        'max' => 60,  'type' => 'input'],
            ['key' => 'feature.2.description',  'label' => 'Card 2 — description',  'max' => 200, 'type' => 'textarea'],
            ['key' => 'feature.3.title',        'label' => 'Card 3 — title',        'max' => 60,  'type' => 'input'],
            ['key' => 'feature.3.description',  'label' => 'Card 3 — description',  'max' => 200, 'type' => 'textarea'],
        ],
        'How It Works' => [
            ['key' => 'how_it_works.eyebrow',      'label' => 'Section eyebrow',   'max' => 60,  'type' => 'input'],
            ['key' => 'how_it_works.heading',      'label' => 'Section heading',   'max' => 80,  'type' => 'input'],
            ['key' => 'how_it_works.1.title',       'label' => 'Step 1 — title',       'max' => 40,  'type' => 'input'],
            ['key' => 'how_it_works.1.description', 'label' => 'Step 1 — description', 'max' => 200, 'type' => 'textarea'],
            ['key' => 'how_it_works.2.title',       'label' => 'Step 2 — title',       'max' => 40,  'type' => 'input'],
            ['key' => 'how_it_works.2.description', 'label' => 'Step 2 — description', 'max' => 200, 'type' => 'textarea'],
            ['key' => 'how_it_works.3.title',       'label' => 'Step 3 — title',       'max' => 40,  'type' => 'input'],
            ['key' => 'how_it_works.3.description', 'label' => 'Step 3 — description', 'max' => 200, 'type' => 'textarea'],
        ],
        'Footer' => [
            ['key' => 'footer.link.privacy', 'label' => 'Link label — Privacy', 'max' => 30,  'type' => 'input'],
            ['key' => 'footer.link.terms',   'label' => 'Link label — Terms',   'max' => 30,  'type' => 'input'],
            ['key' => 'footer.link.support', 'label' => 'Link label — Support', 'max' => 30,  'type' => 'input'],
            ['key' => 'footer.copyright',    'label' => 'Copyright text (after © and the year)', 'max' => 100, 'type' => 'input'],
        ],
    ];
@endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
        Landing Page Content
    </h1>
    <p class="mt-1 text-sm text-on-surface-variant">
        Edit every piece of text shown on the public landing page. Changes apply immediately — no redeploy needed.
    </p>
</div>

<form
    method="POST"
    action="{{ route('admin.site-content.update') }}"
    enctype="multipart/form-data"
    x-data="{
        errors: {},
        submitting: false,
        hasServerImage: @js(!empty($content['hero.image'] ?? null)),
        removeImage: false,
        imagePreview: null,
        check(value, rules) {
            const r = window.Iodine.assert(value ?? '', rules);
            return r.valid ? '' : r.error;
        },
        validateField(el, max) {
            const err = this.check(el.value, ['required', 'maxLength:' + max]);
            this.errors[el.name] = err;
            el.closest('.field-wrap')?.querySelectorAll('.field-error').forEach(p => p.textContent = err);
            el.classList.toggle('border-error', !!err);
            el.classList.toggle('border-outline-variant/60', !err);
        },
        onImageChange(el) {
            if (el.files && el.files[0]) {
                this.removeImage = false;
                this.imagePreview = URL.createObjectURL(el.files[0]);
            }
        },
        clearImage(fileInput) {
            fileInput.value = '';
            this.imagePreview = null;
            this.removeImage = this.hasServerImage;
        },
        submit(e) {
            this.submitting = true;
        },
    }"
    @submit="submit"
    class="space-y-6"
>
    @csrf
    @method('PATCH')

    <x-card class="p-6 animate-fade-up">
        <h2 class="text-base font-semibold text-primary mb-1" style="font-family: var(--font-display);">
            Hero Background Image
        </h2>
        <p class="text-xs text-on-surface-variant mb-5">
            Shown behind the hero heading at the top of the landing page. JPEG, PNG, or WebP — max 4&nbsp;MB.
        </p>

        <div class="flex flex-col sm:flex-row gap-5 items-start">
            @php
                // Falls back to the same stock photo the public landing page uses
                // when no image has been uploaded, so the preview is never blank.
                $currentImageUrl = !empty($content['hero.image'] ?? null)
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($content['hero.image'])
                    : \App\Models\SiteContent::DEFAULT_HERO_IMAGE_URL;
            @endphp
            <div class="w-full sm:w-64 aspect-video rounded-[16px] overflow-hidden bg-surface-container-low border border-outline-variant/60 shrink-0">
                <img
                    x-bind:src="removeImage ? @js(\App\Models\SiteContent::DEFAULT_HERO_IMAGE_URL) : (imagePreview || @js($currentImageUrl))"
                    class="w-full h-full object-cover"
                    alt="Hero background preview"
                >
            </div>

            <div class="flex-1 min-w-0 space-y-3">
                <div class="field-wrap">
                    <input
                        type="file"
                        name="hero_image"
                        x-ref="heroImageInput"
                        accept="image/jpeg,image/png,image/webp"
                        @change="onImageChange($el)"
                        class="block w-full text-sm text-on-surface-variant cursor-pointer
                               file:cursor-pointer file:mr-4 file:py-2.5 file:px-4 file:rounded-button
                               file:border-0 file:text-sm file:font-semibold
                               file:bg-primary file:text-on-primary hover:file:opacity-90 file:transition-opacity"
                    >
                    <p class="text-xs text-on-surface-variant mt-1.5">
                        Recommended size: 1920&times;1080px (16:9). Other sizes are automatically cropped to fit.
                    </p>
                    <p class="field-error text-xs text-error mt-1 min-h-[1rem]">
                        @error('hero_image')
                            {{ $message }}
                        @enderror
                    </p>
                </div>

                <input type="hidden" name="remove_hero_image" x-bind:value="removeImage ? 1 : 0">

                <button
                    type="button"
                    x-show="imagePreview || (hasServerImage && !removeImage)"
                    x-cloak
                    @click="clearImage($refs.heroImageInput)"
                    class="inline-flex items-center gap-1.5 text-sm text-error hover:opacity-80 transition-opacity cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[18px]">close</span>
                    Remove image and use the default photo
                </button>
            </div>
        </div>
    </x-card>

    @foreach($groups as $groupName => $fields)
        <x-card class="p-6 animate-fade-up">
            <h2 class="text-base font-semibold text-primary mb-5" style="font-family: var(--font-display);">
                {{ $groupName }}
            </h2>

            <div class="grid grid-cols-1 {{ $groupName === 'Navigation' ? '' : 'md:grid-cols-2' }} gap-5">
                @foreach($fields as $field)
                    @php
                        $name = "content[".str_replace('.', '][', $field['key'])."]";
                        $value = old($name, $content[$field['key']] ?? '');
                    @endphp
                    <div class="field-wrap {{ $field['type'] === 'textarea' ? 'md:col-span-2' : '' }}">
                        <label class="flex items-center justify-between text-xs font-medium text-on-surface-variant mb-1.5">
                            <span>{{ $field['label'] }}</span>
                            <span class="text-outline">max {{ $field['max'] }}</span>
                        </label>

                        @if($field['type'] === 'textarea')
                            <textarea
                                name="{{ $name }}"
                                rows="3"
                                maxlength="{{ $field['max'] }}"
                                @input="validateField($el, {{ $field['max'] }})"
                                @blur="validateField($el, {{ $field['max'] }})"
                                class="w-full px-4 py-2.5 rounded-[16px] text-sm bg-surface-container-low
                                       border border-outline-variant/60 resize-none
                                       focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary
                                       transition-colors duration-150"
                            >{{ $value }}</textarea>
                        @else
                            <input
                                type="text"
                                name="{{ $name }}"
                                value="{{ $value }}"
                                maxlength="{{ $field['max'] }}"
                                @input="validateField($el, {{ $field['max'] }})"
                                @blur="validateField($el, {{ $field['max'] }})"
                                class="w-full px-4 py-2.5 rounded-[16px] text-sm bg-surface-container-low
                                       border border-outline-variant/60
                                       focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary
                                       transition-colors duration-150"
                            >
                        @endif

                        <p class="field-error text-xs text-error mt-1 min-h-[1rem]">
                            @error("content." . $field['key'])
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endforeach

    <div class="flex justify-end">
        <x-button type="submit" variant="primary-dark" x-bind:disabled="submitting" class="min-w-[160px] justify-center">
            <span x-show="!submitting">Save Changes</span>
            <span x-show="submitting" x-cloak>Saving…</span>
        </x-button>
    </div>
</form>
@endsection
