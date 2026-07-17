<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ $siteName }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <script>
        window.__flash = {
            success: @js(session('success')),
            error:   @js(session('error')),
            warning: @js(session('warning')),
        };
        // Applied before first paint to avoid a flash of the expanded sidebar.
        (function () {
            try {
                if (localStorage.getItem('sidebarCollapsed') === '1') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="bg-surface font-sans text-on-surface h-screen flex overflow-hidden">

    {{-- ═══════════════════════════════════════════════
         SIDEBAR
    ═══════════════════════════════════════════════ --}}
    {{-- Transition itself (transition-property/duration/timing) is defined on
         #sidebar in app.css, not as utility classes here — that ID rule already
         exists for the desktop collapse feature and its specificity would beat
         any transition-* utility class placed on this element anyway. --}}
    <nav
        id="sidebar"
        class="fixed inset-y-0 left-0 z-50 w-[260px] flex flex-col
               bg-surface-white border-r border-outline-variant/30
               shadow-[4px_0px_24px_rgba(30,42,74,0.08)]
               -translate-x-full md:translate-x-0 opacity-0 md:opacity-100"
    >
        {{-- Desktop collapse toggle — floats on the sidebar's edge, hidden on mobile --}}
        <button type="button"
                onclick="toggleSidebarCollapse()"
                title="Collapse sidebar"
                class="sidebar-collapse-btn hidden md:flex absolute top-5 -right-3 z-10
                       w-6 h-6 items-center justify-center rounded-full
                       bg-surface-white border border-outline-variant/50 shadow-sm
                       text-primary/60 hover:text-primary hover:bg-primary/5
                       transition-colors duration-150 cursor-pointer">
            <span class="material-symbols-outlined text-[16px]">chevron_left</span>
        </button>

        {{-- Logo --}}
        <div class="sidebar-header h-16 flex items-center gap-3 px-6 border-b border-outline-variant/30 shrink-0">
            <div class="w-9 h-9 rounded-full bg-gold flex items-center justify-center
                        font-bold text-primary text-sm shrink-0"
                 style="font-family: var(--font-display);">
                {{ $siteShortLabel }}
            </div>
            <div class="min-w-0 sidebar-block">
                <p class="font-semibold text-primary text-base leading-tight truncate"
                   style="font-family: var(--font-display);">{{ $siteName }}</p>
                <p class="text-[11px] text-primary/50 leading-tight">Student Portal</p>
            </div>
        </div>

        {{-- Navigation --}}
        <div class="flex-1 overflow-y-auto py-3 flex flex-col">

            <p class="sidebar-block px-6 pb-1 pt-2 text-[10px] font-semibold tracking-widest text-outline uppercase">
                Overview
            </p>

            <a href="{{ route('student.dashboard') }}" title="Dashboard"
               class="nav-item {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-[20px]">dashboard</span>
                <span class="sidebar-label">Dashboard</span>
            </a>

            <p class="sidebar-block px-6 pb-1 pt-4 text-[10px] font-semibold tracking-widest text-outline uppercase">
                Learning
            </p>

            <a href="{{ route('student.classes.index') }}" title="My Classes"
               class="nav-item {{ request()->routeIs('student.classes.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-[20px]">school</span>
                <span class="sidebar-label">My Classes</span>
            </a>

            <a href="{{ route('student.courses.index') }}" title="My Courses"
               class="nav-item {{ request()->routeIs('student.courses.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-[20px]">library_books</span>
                <span class="sidebar-label">My Courses</span>
            </a>

            <p class="sidebar-block px-6 pb-1 pt-4 text-[10px] font-semibold tracking-widest text-outline uppercase">
                Activity
            </p>

            <a href="{{ route('student.activity.index') }}" title="Activity"
               class="nav-item {{ request()->routeIs('student.activity.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-[20px]">notifications</span>
                <span class="sidebar-label">Activity</span>
            </a>

        </div>

        {{-- Optional sidebar CTA --}}
        @hasSection('sidebar-cta')
            <div class="sidebar-block px-4 pb-4 shrink-0">
                @yield('sidebar-cta')
            </div>
        @endif

        {{-- Footer: Settings + Logout --}}
        <div class="border-t border-outline-variant/30 py-2 flex flex-col shrink-0">
            <a href="{{ route('settings.index') }}" title="Settings"
               class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-[20px]">manage_accounts</span>
                <span class="sidebar-label">Settings</span>
            </a>

            <form id="logout-form" method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="button" title="Log out" class="nav-item cursor-pointer w-full text-left"
                        onclick="confirmNeutral('Log out?', 'You will be signed out of your account.', document.getElementById('logout-form'), 'Log out', getComputedStyle(document.documentElement).getPropertyValue('--color-error').trim())">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                    <span class="sidebar-label">Log out</span>
                </button>
            </form>
        </div>
    </nav>

    {{-- Mobile backdrop --}}
    <div
        id="sidebar-backdrop"
        class="fixed inset-0 z-40 bg-black/30 md:hidden
               opacity-0 pointer-events-none transition-opacity duration-200 ease-in-out"
        onclick="closeSidebar()"
    ></div>


    {{-- ═══════════════════════════════════════════════
         MAIN COLUMN (topbar + scrollable content)
    ═══════════════════════════════════════════════ --}}
    <div id="main-content" class="flex-1 flex flex-col md:ml-[260px] min-w-0 h-full overflow-hidden">

        {{-- ─── TOPBAR ─── --}}
        <header class="h-16 sticky top-0 z-30 bg-surface-white border-b border-outline-variant/30
                        shadow-[0px_4px_16px_rgba(30,42,74,0.06)]
                        flex items-center gap-4 px-4 md:px-8 shrink-0">

            {{-- Mobile sidebar toggle --}}
            <button onclick="openSidebar()"
                    class="md:hidden text-primary -ml-1 shrink-0 cursor-pointer">
                <span class="material-symbols-outlined">menu</span>
            </button>

            {{-- Page-level action buttons --}}
            <div class="flex items-center gap-3">
                @yield('topbar-actions')
            </div>

            <div class="ml-auto flex items-center gap-3">

                {{-- User avatar --}}
                <a href="{{ route('settings.index') }}"
                   class="w-8 h-8 rounded-full bg-primary-container shrink-0
                          flex items-center justify-center overflow-hidden
                          border border-outline-variant/50 cursor-pointer select-none
                          hover:ring-2 hover:ring-gold transition-all">
                    @auth
                        @if(auth()->user()->avatarUrl())
                            <img src="{{ auth()->user()->avatarUrl() }}"
                                 alt="{{ auth()->user()->name }}"
                                 class="w-full h-full object-cover">
                        @else
                            <span class="text-xs font-semibold text-on-primary"
                                  style="font-family: var(--font-display);">
                                {{ strtoupper(substr(auth()->user()->name ?? 'S', 0, 1)) }}
                            </span>
                        @endif
                    @endauth
                </a>
            </div>
        </header>

        {{-- ─── SCROLLABLE CONTENT ─── --}}
        <main class="flex-1 overflow-y-auto p-4 md:p-8"
              style="padding-bottom: max(2rem, env(safe-area-inset-bottom))">
            <div class="max-w-full space-y-8 mb-8 md:mb-0">
                @yield('content')
            </div>
        </main>

    </div>{{-- end main column --}}


    {{-- Mobile sidebar open/close --}}
    <script>
        const sidebar  = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full', 'opacity-0');
            backdrop.classList.remove('opacity-0', 'pointer-events-none');
            document.body.classList.add('overflow-hidden');
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full', 'opacity-0');
            backdrop.classList.add('opacity-0', 'pointer-events-none');
            document.body.classList.remove('overflow-hidden');
        }

        function toggleSidebarCollapse() {
            const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
            try { localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0'); } catch (e) {}
        }
    </script>

    @stack('scripts')
</body>
</html>
