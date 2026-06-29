<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EduNest — Private Learning Platform</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface-white text-on-surface font-sans antialiased">

    {{-- ═══════════════════════════════════════════════
         NAVBAR
    ═══════════════════════════════════════════════ --}}
    <header class="sticky top-0 z-50 bg-surface-white/95 backdrop-blur-sm border-b border-outline-variant/30">
        <div class="max-w-[1280px] mx-auto px-6 md:px-8 h-16 flex items-center justify-between">

            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-gold flex items-center justify-center font-bold text-primary-container text-sm shrink-0"
                     style="font-family: var(--font-display);">EN</div>
                <span class="font-bold text-lg text-primary" style="font-family: var(--font-display);">EduNest</span>
            </div>

            <a href="{{ route('login') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-button bg-primary text-on-primary font-semibold text-sm hover:opacity-90 transition-opacity">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Sign in
            </a>
        </div>
    </header>


    {{-- ═══════════════════════════════════════════════
         HERO
    ═══════════════════════════════════════════════ --}}
    <section class="relative pt-48 pb-56 px-6 md:px-8 overflow-hidden"
             style="background-image: url('https://images.unsplash.com/photo-1606761568499-6d2451b23c66?q=80&w=1374&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D');
                    background-size: cover; background-position: center;">

        {{-- Navy overlay so text stays legible over the photo --}}
        <div class="absolute inset-0 bg-primary/50 mix-blend-multiply"></div>

        <div class="relative max-w-[1280px] mx-auto">
            <div class="max-w-3xl mx-auto text-center">

                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-pill bg-gold/25 text-gold text-[11px] font-semibold uppercase tracking-widest mb-7">
                    Private · Instructor-Led · Focused
                </span>

                <h1 class="text-4xl md:text-5xl lg:text-[3.5rem] font-bold text-white leading-[1.15] tracking-tight mb-6"
                    style="font-family: var(--font-display);">
                    A focused space to<br class="hidden sm:block"> learn, not wander.
                </h1>

                <p class="text-lg text-white/75 max-w-xl mx-auto mb-10 leading-relaxed">
                    EduNest connects students directly with their instructor's courses — no marketplace noise, no subscriptions. Just structured, intentional learning.
                </p>

                <a href="/auth/google"
                   class="inline-flex items-center gap-2.5 px-8 py-4 rounded-button bg-gold text-white/75 font-semibold text-base hover:opacity-90 transition-opacity shadow-sm">
                    Get Started
                    <span class="material-symbols-outlined text-[18px] leading-none">arrow_forward</span>
                </a>

                <p class="mt-4 text-xs text-white/50">
                    First login creates your student account automatically.
                </p>
            </div>
        </div>

        {{-- Single smooth wave transition into the section below --}}
        <div class="absolute inset-x-0 bottom-0 leading-none overflow-hidden">
            <svg viewBox="0 0 1440 90" xmlns="http://www.w3.org/2000/svg"
                 preserveAspectRatio="none" class="w-full h-20 md:h-24">
                <path d="M0,90 L0,45 C360,-20 1080,110 1440,45 L1440,90 Z" fill="white"/>
            </svg>
        </div>
    </section>


    {{-- ═══════════════════════════════════════════════
         FEATURE CARDS
    ═══════════════════════════════════════════════ --}}
    <section class="py-24 px-6 md:px-8 bg-surface-white">
        <div class="max-w-[1280px] mx-auto">

            <div class="text-center mb-14">
                <p class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-widest mb-3">
                    What EduNest Offers
                </p>
                <h2 class="text-3xl font-semibold text-primary" style="font-family: var(--font-display);">
                    Built for real learning
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                {{-- Student-Centered Learning --}}
                <div class="p-8 rounded-card bg-surface-white border border-outline-variant/40
                            hover:shadow-[0_4px_16px_rgba(30,42,74,0.07)] transition-shadow duration-200">
                    <div class="w-12 h-12 rounded-xl bg-gold/15 flex items-center justify-center mb-5">
                        <span class="material-symbols-outlined text-on-gold text-[22px]">school</span>
                    </div>
                    <h3 class="text-base font-semibold text-primary mb-2" style="font-family: var(--font-display);">
                        Student-Centered Learning
                    </h3>
                    <p class="text-on-surface-variant text-sm leading-relaxed">
                        Access your enrolled courses, track progress through units, and download materials — all inside a clean, distraction-free workspace.
                    </p>
                </div>

                {{-- Teacher-Led Success --}}
                <div class="p-8 rounded-card bg-surface-white border border-outline-variant/40
                            hover:shadow-[0_4px_16px_rgba(30,42,74,0.07)] transition-shadow duration-200">
                    <div class="w-12 h-12 rounded-xl bg-primary/8 flex items-center justify-center mb-5">
                        <span class="material-symbols-outlined text-primary text-[22px]">cast_for_education</span>
                    </div>
                    <h3 class="text-base font-semibold text-primary mb-2" style="font-family: var(--font-display);">
                        Teacher-Led Success
                    </h3>
                    <p class="text-on-surface-variant text-sm leading-relaxed">
                        Instructors build courses, organise units, and share secure enrollment tokens — keeping every class intentional and private.
                    </p>
                </div>

                {{-- Private & Secure --}}
                <div class="p-8 rounded-card bg-surface-white border border-outline-variant/40
                            hover:shadow-[0_4px_16px_rgba(30,42,74,0.07)] transition-shadow duration-200">
                    <div class="w-12 h-12 rounded-xl bg-surface-container flex items-center justify-center mb-5">
                        <span class="material-symbols-outlined text-primary-container text-[22px]">lock</span>
                    </div>
                    <h3 class="text-base font-semibold text-primary mb-2" style="font-family: var(--font-display);">
                        Private &amp; Secure
                    </h3>
                    <p class="text-on-surface-variant text-sm leading-relaxed">
                        No public marketplace. Students enroll only via a token from their instructor. Content is protected behind role-based access control.
                    </p>
                </div>

            </div>
        </div>
    </section>


    {{-- ═══════════════════════════════════════════════
         HOW IT WORKS
    ═══════════════════════════════════════════════ --}}
    <section class="py-24 px-6 md:px-8 bg-primary-container">
        <div class="max-w-[1280px] mx-auto">

            <div class="text-center mb-14">
                <p class="text-[11px] font-semibold text-gold uppercase tracking-widest mb-3">
                    Simple by Design
                </p>
                <h2 class="text-3xl font-semibold text-on-primary" style="font-family: var(--font-display);">
                    How It Works
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-10 md:gap-8">

                {{-- Step 1: Join --}}
                <div class="text-center">
                    <div class="w-14 h-14 rounded-full bg-gold flex items-center justify-center mx-auto mb-5">
                        <span class="text-on-gold font-bold text-xl" style="font-family: var(--font-display);">1</span>
                    </div>
                    <h3 class="text-base font-semibold text-on-primary mb-2" style="font-family: var(--font-display);">
                        Join
                    </h3>
                    <p class="text-on-primary/65 text-sm leading-relaxed max-w-xs mx-auto">
                        Sign in with your Google account. Your student profile is created automatically on first login — no passwords, no forms.
                    </p>
                </div>

                {{-- Step 2: Learn --}}
                <div class="text-center">
                    <div class="w-14 h-14 rounded-full bg-gold flex items-center justify-center mx-auto mb-5">
                        <span class="text-on-gold font-bold text-xl" style="font-family: var(--font-display);">2</span>
                    </div>
                    <h3 class="text-base font-semibold text-on-primary mb-2" style="font-family: var(--font-display);">
                        Learn
                    </h3>
                    <p class="text-on-primary/65 text-sm leading-relaxed max-w-xs mx-auto">
                        Enter the enrollment token your instructor gave you to unlock access to all course materials and units.
                    </p>
                </div>

                {{-- Step 3: Succeed --}}
                <div class="text-center">
                    <div class="w-14 h-14 rounded-full bg-gold flex items-center justify-center mx-auto mb-5">
                        <span class="text-on-gold font-bold text-xl" style="font-family: var(--font-display);">3</span>
                    </div>
                    <h3 class="text-base font-semibold text-on-primary mb-2" style="font-family: var(--font-display);">
                        Succeed
                    </h3>
                    <p class="text-on-primary/65 text-sm leading-relaxed max-w-xs mx-auto">
                        Progress through units at your pace, download resources, and stay on top of your learning journey.
                    </p>
                </div>

            </div>
        </div>
    </section>


    {{-- ═══════════════════════════════════════════════
         TESTIMONIALS
    ═══════════════════════════════════════════════ --}}
    <!-- <section class="py-24 px-6 md:px-8 bg-surface-container-low">
        <div class="max-w-[1280px] mx-auto">

            <div class="text-center mb-14">
                <p class="text-[11px] font-semibold text-on-surface-variant uppercase tracking-widest mb-3">
                    From Our Students
                </p>
                <h2 class="text-3xl font-semibold text-primary" style="font-family: var(--font-display);">
                    What learners are saying
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                {{-- Testimonial 1 --}}
                <div class="p-8 rounded-card bg-surface-white border border-outline-variant/40 flex flex-col">
                    <div class="flex gap-0.5 mb-5">
                        @for ($i = 0; $i < 5; $i++)
                            <span class="material-symbols-outlined text-gold text-[18px]" data-weight="fill">star</span>
                        @endfor
                    </div>
                    <p class="text-on-surface-variant text-sm leading-relaxed flex-1 mb-6">
                        "The course is so well-structured. I can see exactly where I am and what's next. It feels like having a personal study guide."
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-primary-container flex items-center justify-center shrink-0">
                            <span class="text-xs font-semibold text-on-primary" style="font-family: var(--font-display);">AK</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-primary">Aisha K.</p>
                            <p class="text-xs text-on-surface-variant">Biology Student</p>
                        </div>
                    </div>
                </div>

                {{-- Testimonial 2 --}}
                <div class="p-8 rounded-card bg-surface-white border border-outline-variant/40 flex flex-col">
                    <div class="flex gap-0.5 mb-5">
                        @for ($i = 0; $i < 5; $i++)
                            <span class="material-symbols-outlined text-gold text-[18px]" data-weight="fill">star</span>
                        @endfor
                    </div>
                    <p class="text-on-surface-variant text-sm leading-relaxed flex-1 mb-6">
                        "No distractions, no ads — just course content. Token enrollment keeps the class focused. Everyone there actually wants to be there."
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-primary-container flex items-center justify-center shrink-0">
                            <span class="text-xs font-semibold text-on-primary" style="font-family: var(--font-display);">JM</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-primary">James M.</p>
                            <p class="text-xs text-on-surface-variant">Computer Science Student</p>
                        </div>
                    </div>
                </div>

                {{-- Testimonial 3 --}}
                <div class="p-8 rounded-card bg-surface-white border border-outline-variant/40 flex flex-col">
                    <div class="flex gap-0.5 mb-5">
                        @for ($i = 0; $i < 5; $i++)
                            <span class="material-symbols-outlined text-gold text-[18px]" data-weight="fill">star</span>
                        @endfor
                    </div>
                    <p class="text-on-surface-variant text-sm leading-relaxed flex-1 mb-6">
                        "Knowing downloads are protected and only accessible to enrolled students makes me trust that the content is curated and high quality."
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-primary-container flex items-center justify-center shrink-0">
                            <span class="text-xs font-semibold text-on-primary" style="font-family: var(--font-display);">SR</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-primary">Sofia R.</p>
                            <p class="text-xs text-on-surface-variant">Mathematics Student</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section> -->


    {{-- ═══════════════════════════════════════════════
         BOTTOM CTA
    ═══════════════════════════════════════════════ --}}
    <!-- <section class="py-24 px-6 md:px-8 bg-surface-white">
        <div class="max-w-xl mx-auto text-center">
            <h2 class="text-3xl font-bold text-primary mb-4" style="font-family: var(--font-display);">
                Ready to get started?
            </h2>
            <p class="text-on-surface-variant mb-8 leading-relaxed">
                Sign in with your Google account and enter your enrollment token to access your courses.
            </p>
            <a href="/auth/google"
               class="inline-flex items-center gap-2.5 px-8 py-4 rounded-button bg-gold text-on-gold font-semibold text-base hover:opacity-90 transition-opacity shadow-sm">
                Sign in with Google
                <span class="material-symbols-outlined text-[18px] leading-none">arrow_forward</span>
            </a>
        </div>
    </section> -->


    {{-- ═══════════════════════════════════════════════
         FOOTER
    ═══════════════════════════════════════════════ --}}
    <footer class="border-t border-outline-variant/30 py-8 px-6 md:px-8">
        <div class="max-w-[1280px] mx-auto flex flex-col md:flex-row items-center justify-between gap-4">

            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-full bg-gold flex items-center justify-center font-bold text-primary-container text-xs"
                     style="font-family: var(--font-display);">EN</div>
                <span class="font-semibold text-primary text-sm" style="font-family: var(--font-display);">EduNest</span>
            </div>

            <div class="flex items-center gap-6 text-sm text-on-surface-variant">
                <a href="#" class="hover:text-primary transition-colors">Privacy</a>
                <a href="#" class="hover:text-primary transition-colors">Terms</a>
                <a href="#" class="hover:text-primary transition-colors">Support</a>
            </div>

            <p class="text-xs text-on-surface-variant">
                &copy; {{ date('Y') }} EduNest. All rights reserved.
            </p>
        </div>
    </footer>

</body>
</html>
