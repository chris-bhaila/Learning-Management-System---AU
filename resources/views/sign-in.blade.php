<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In — EduNest</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface font-sans text-on-surface antialiased h-screen flex overflow-hidden">

    {{-- ═══════════════════════════════════════════════
         LEFT — Branding panel
    ═══════════════════════════════════════════════ --}}
    <div class="hidden lg:flex lg:w-[480px] xl:w-[540px] shrink-0
                flex-col justify-between
                bg-primary-container p-10 xl:p-14">

        {{-- Logo --}}
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gold flex items-center justify-center
                        font-bold text-primary text-sm shrink-0"
                 style="font-family: var(--font-display);">EN</div>
            <span class="font-bold text-xl text-white" style="font-family: var(--font-display);">EduNest</span>
        </div>

        {{-- Centre copy --}}
        <div class="space-y-6 animate-fade-up">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full
                         bg-gold/20 text-gold text-[11px] font-semibold uppercase tracking-widest">
                Private · Instructor-Led · Focused
            </span>

            <h1 class="text-3xl xl:text-4xl font-bold text-white leading-snug"
                style="font-family: var(--font-display);">
                Your classroom,<br>exactly how you<br>want it.
            </h1>

            <p class="text-white/60 text-sm leading-relaxed max-w-sm">
                EduNest gives educators a focused space to deliver courses,
                share materials, and track student progress — without the noise.
            </p>

            {{-- Feature bullets --}}
            <ul class="space-y-3 pt-2">
                @foreach([
                    ['icon' => 'verified',      'text' => 'Token-based enrollment — you control access'],
                    ['icon' => 'folder_shared',  'text' => 'Organized units with files and rich content'],
                    ['icon' => 'shield_person',  'text' => 'Role-gated — students, teachers, and admins'],
                ] as $item)
                    <li class="flex items-center gap-3 text-sm text-white/70">
                        <span class="material-symbols-outlined text-gold text-[18px] shrink-0">{{ $item['icon'] }}</span>
                        {{ $item['text'] }}
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Footer credit --}}
        <p class="text-white/30 text-xs">© {{ date('Y') }} EduNest. Private use only.</p>

    </div>


    {{-- ═══════════════════════════════════════════════
         RIGHT — Sign-in form
    ═══════════════════════════════════════════════ --}}
    <div class="flex-1 flex flex-col overflow-y-auto">

        {{-- Mobile logo bar --}}
        <div class="lg:hidden flex items-center gap-3 px-6 py-5 border-b border-outline-variant/30">
            <div class="w-8 h-8 rounded-full bg-gold flex items-center justify-center
                        font-bold text-primary text-xs shrink-0"
                 style="font-family: var(--font-display);">EN</div>
            <span class="font-bold text-base text-primary" style="font-family: var(--font-display);">EduNest</span>
        </div>

        {{-- Centred form area --}}
        <div class="flex-1 flex items-center justify-center px-6 py-10 md:px-12">
            <div class="w-full max-w-[400px] space-y-7 animate-fade-up">

                {{-- Heading --}}
                <div>
                    <h2 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
                        Welcome back
                    </h2>
                    <p class="mt-1.5 text-sm text-on-surface-variant">
                        Sign in to continue to your EduNest portal.
                    </p>
                </div>

                {{-- Session / validation errors --}}
                @if(session('error'))
                    <div class="flex items-start gap-3 px-4 py-3 rounded-[16px] bg-error-container text-error text-sm">
                        <span class="material-symbols-outlined text-[18px] shrink-0 mt-0.5">error</span>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="flex items-start gap-3 px-4 py-3 rounded-[16px] bg-error-container text-error text-sm">
                        <span class="material-symbols-outlined text-[18px] shrink-0 mt-0.5">error</span>
                        <ul class="space-y-0.5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Email / password form --}}
                <form method="POST"
                      action="{{ Route::has('login') ? route('login') : '#' }}"
                      class="space-y-5">
                    @csrf

                    {{-- Email --}}
                    <div class="space-y-1.5">
                        <label for="email"
                               class="block text-[11px] font-semibold tracking-widest text-outline uppercase">
                            Email address
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autocomplete="email"
                            placeholder="you@example.com"
                            class="w-full px-4 py-3 rounded-[16px] text-sm bg-surface-container-low
                                   border border-outline-variant/60 placeholder:text-outline
                                   focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary
                                   transition-colors cursor-text
                                   @error('email') border-error focus:border-error focus:ring-error @enderror"
                        >
                    </div>

                    {{-- Password --}}
                    <div class="space-y-1.5">
                        <label for="password"
                               class="block text-[11px] font-semibold tracking-widest text-outline uppercase">
                            Password
                        </label>
                        <div class="relative">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="w-full px-4 py-3 pr-11 rounded-[16px] text-sm bg-surface-container-low
                                       border border-outline-variant/60 placeholder:text-outline
                                       focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary
                                       transition-colors cursor-text
                                       @error('password') border-error focus:border-error focus:ring-error @enderror"
                            >
                            {{-- Toggle visibility --}}
                            <button
                                type="button"
                                onclick="togglePassword()"
                                title="Toggle password visibility"
                                class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer
                                       text-outline hover:text-primary transition-colors">
                                <span id="pwToggleIcon" class="material-symbols-outlined text-[20px]">visibility</span>
                            </button>
                        </div>
                    </div>

                    {{-- Remember me --}}
                    <div class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            id="remember"
                            name="remember"
                            class="w-4 h-4 rounded accent-primary cursor-pointer"
                        >
                        <label for="remember"
                               class="text-sm text-on-surface-variant select-none cursor-pointer">
                            Keep me signed in
                        </label>
                    </div>

                    {{-- Submit --}}
                    <button
                        type="submit"
                        class="w-full py-3 rounded-[24px] text-sm font-semibold cursor-pointer
                               bg-primary text-on-primary
                               hover:opacity-90 active:scale-[0.99] transition-all">
                        Sign in
                    </button>

                </form>

                {{-- Divider --}}
                <div class="flex items-center gap-3">
                    <div class="flex-1 h-px bg-outline-variant/40"></div>
                    <span class="text-xs text-outline">or</span>
                    <div class="flex-1 h-px bg-outline-variant/40"></div>
                </div>

                {{-- Google Sign-In --}}
                <x-button href="{{ route('auth.google') }}" variant="secondary" class="w-full">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Continue with Google
                </x-button>

                {{-- Back to landing --}}
                <p class="text-center text-xs text-on-surface-variant">
                    <a href="{{ url('/') }}"
                       class="hover:text-primary transition-colors cursor-pointer inline-flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">arrow_back</span>
                        Back to home
                    </a>
                </p>

            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon  = document.getElementById('pwToggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
            }
        }
    </script>

</body>
</html>
