<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — EduNest</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

{{--
    Teacher layout: fixed sidebar (260px) + flex column (topbar + scrollable main).

    Sections available to child views:
      - title          : <title> text (default: "Dashboard")
      - sidebar-cta    : optional CTA button below nav (e.g. "New Course")
      - topbar-actions : page-level buttons rendered in the topbar
      - content        : main page body
      - styles / scripts (stacks)
--}}
<body class="bg-surface font-sans text-on-surface h-screen flex overflow-hidden">

    {{-- ═══════════════════════════════════════════════
         SIDEBAR
    ═══════════════════════════════════════════════ --}}
    <nav
        id="sidebar"
        class="fixed inset-y-0 left-0 z-50 w-[260px] flex flex-col
               bg-surface-white border-r border-outline-variant/30
               shadow-[4px_0px_24px_rgba(30,42,74,0.08)]
               -translate-x-full md:translate-x-0 transition-transform duration-200"
    >
        {{-- Logo --}}
        <div class="h-16 flex items-center gap-3 px-6 border-b border-outline-variant/30 shrink-0">
            <div class="w-9 h-9 rounded-full bg-gold flex items-center justify-center
                        font-bold text-primary text-sm shrink-0"
                 style="font-family: var(--font-display);">
                EN
            </div>
            <div class="min-w-0">
                <p class="font-semibold text-primary text-base leading-tight truncate"
                   style="font-family: var(--font-display);">EduNest</p>
                <p class="text-[11px] text-primary/50 leading-tight">Teacher Portal</p>
            </div>
        </div>

        {{-- Navigation --}}
        <div class="flex-1 overflow-y-auto py-3 flex flex-col">

            <p class="px-6 pb-1 pt-2 text-[10px] font-semibold tracking-widest text-outline uppercase">
                Overview
            </p>

            <a href="{{ route('teacher.dashboard') }}"
               class="nav-item {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-[20px]">dashboard</span>
                Dashboard
            </a>

            <p class="px-6 pb-1 pt-4 text-[10px] font-semibold tracking-widest text-outline uppercase">
                Teaching
            </p>

            <a href="{{ Route::has('teacher.courses.index') ? route('teacher.courses.index') : '#' }}"
               class="nav-item {{ request()->routeIs('teacher.courses.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-[20px]">library_books</span>
                My Courses
            </a>

            <a href="{{ Route::has('teacher.students.index') ? route('teacher.students.index') : '#' }}"
               class="nav-item {{ request()->routeIs('teacher.students.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-[20px]">group</span>
                Students
            </a>

            <a href="{{ Route::has('teacher.tokens.index') ? route('teacher.tokens.index') : '#' }}"
               class="nav-item {{ request()->routeIs('teacher.tokens.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-[20px]">key</span>
                Tokens
            </a>

            <p class="px-6 pb-1 pt-4 text-[10px] font-semibold tracking-widest text-outline uppercase">
                Activity
            </p>

            <a href="{{ Route::has('teacher.notifications.index') ? route('teacher.notifications.index') : '#' }}"
               class="nav-item {{ request()->routeIs('teacher.notifications.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-[20px]">notifications</span>
                Notifications
            </a>

        </div>

        {{-- Optional sidebar CTA (e.g. "New Course") --}}
        @hasSection('sidebar-cta')
            <div class="px-4 pb-4 shrink-0">
                @yield('sidebar-cta')
            </div>
        @endif

        {{-- Footer: Support + Logout --}}
        <div class="border-t border-outline-variant/30 py-2 flex flex-col shrink-0">
            <a href="#" class="nav-item">
                <span class="material-symbols-outlined text-[20px]">help</span>
                Support
            </a>

            <form method="POST" action="{{ Route::has('logout') ? route('logout') : '#' }}">
                @csrf
                <button type="submit" class="nav-item cursor-pointer w-full text-left">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                    Log out
                </button>
            </form>
        </div>
    </nav>

    {{-- Mobile backdrop --}}
    <div
        id="sidebar-backdrop"
        class="fixed inset-0 z-40 bg-black/30 hidden md:hidden"
        onclick="closeSidebar()"
    ></div>


    {{-- ═══════════════════════════════════════════════
         MAIN COLUMN (topbar + scrollable content)
    ═══════════════════════════════════════════════ --}}
    <div class="flex-1 flex flex-col md:ml-[260px] min-w-0 h-full overflow-hidden">

        {{-- ─── TOPBAR ─── --}}
        <header class="h-16 sticky top-0 z-30 bg-surface-white border-b border-outline-variant/30
                        shadow-[0px_4px_16px_rgba(30,42,74,0.06)]
                        flex items-center gap-4 px-4 md:px-8 shrink-0">

            {{-- Mobile sidebar toggle --}}
            <button onclick="openSidebar()" class="md:hidden text-primary -ml-1 shrink-0">
                <span class="material-symbols-outlined">menu</span>
            </button>

            {{-- Search --}}
            <div class="flex-1 max-w-md hidden sm:block">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2
                                 text-outline text-[18px] pointer-events-none">search</span>
                    <input
                        type="search"
                        placeholder="Search courses, students…"
                        class="w-full pl-10 pr-4 py-2 bg-surface-container-low rounded-full text-sm
                               border border-outline-variant/60
                               placeholder:text-outline
                               focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                    >
                </div>
            </div>

            <div class="ml-auto flex items-center gap-3">
                {{-- Page-level action buttons --}}
                @yield('topbar-actions')

                {{-- Notifications --}}
                <a href="{{ Route::has('teacher.notifications.index') ? route('teacher.notifications.index') : '#' }}"
                   class="relative text-on-surface-variant hover:text-primary transition-colors">
                    <span class="material-symbols-outlined">notifications</span>
                    @if(($unreadNotifications ?? 0) > 0)
                        <span class="absolute top-0.5 right-0.5 w-2 h-2 bg-gold rounded-full
                                     border-2 border-surface-white"></span>
                    @endif
                </a>

                {{-- User avatar --}}
                <div class="w-8 h-8 rounded-full bg-primary-container shrink-0
                            flex items-center justify-center overflow-hidden
                            border border-outline-variant/50 cursor-pointer select-none">
                    @auth
                        @if(auth()->user()->profile_photo_path ?? false)
                            <img
                                src="{{ auth()->user()->profile_photo_url }}"
                                alt="{{ auth()->user()->name }}"
                                class="w-full h-full object-cover"
                            >
                        @else
                            <span class="text-xs font-semibold text-on-primary"
                                  style="font-family: var(--font-display);">
                                {{ strtoupper(substr(auth()->user()->name ?? 'TC', 0, 2)) }}
                            </span>
                        @endif
                    @else
                        <span class="text-xs font-semibold text-on-primary"
                              style="font-family: var(--font-display);">TC</span>
                    @endauth
                </div>
            </div>
        </header>

        {{-- ─── SCROLLABLE CONTENT ─── --}}
        <main class="flex-1 overflow-y-auto p-4 md:p-8">
            <div class="max-w-full space-y-8">
                @yield('content')
            </div>
        </main>

    </div>{{-- end main column --}}


    {{-- Mobile sidebar open/close --}}
    <script>
        const sidebar  = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            backdrop.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            backdrop.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    </script>

    @stack('scripts')
</body>
</html>
