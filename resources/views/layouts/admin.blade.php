<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — EduNest Admin</title>

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

{{--
    Admin layout: fixed sidebar (260px) + flex column (topbar + scrollable main).

    Sections available to child views:
      - title          : <title> text (default: "Dashboard")
      - topbar-actions : page-level buttons rendered in the topbar
      - content        : main page body
      - styles / scripts (stacks)

    Nav item pattern:
      Active state is driven by request()->routeIs() in each <a> class.
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
                EN
            </div>
            <div class="min-w-0 sidebar-block">
                <p class="font-semibold text-primary text-base leading-tight truncate"
                   style="font-family: var(--font-display);">EduNest</p>
                <p class="text-[11px] text-primary/50 leading-tight">Admin Portal</p>
            </div>
        </div>

        {{-- Navigation --}}
        <div class="flex-1 overflow-y-auto py-3 flex flex-col">

            <p class="sidebar-block px-6 pb-1 pt-2 text-[10px] font-semibold tracking-widest text-outline uppercase">
                Overview
            </p>

            <a href="{{ route('admin.dashboard') }}" title="Dashboard"
               class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-[20px]">dashboard</span>
                <span class="sidebar-label">Dashboard</span>
            </a>

            <p class="sidebar-block px-6 pb-1 pt-4 text-[10px] font-semibold tracking-widest text-outline uppercase">
                Management
            </p>

            <a href="{{ Route::has('admin.users.index') ? route('admin.users.index') : '#' }}" title="Users"
               class="nav-item {{ request()->routeIs('admin.users.index') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-[20px]">group</span>
                <span class="sidebar-label">Users</span>
            </a>

            <a href="{{ Route::has('admin.courses.index') ? route('admin.courses.index') : '#' }}" title="Courses"
               class="nav-item {{ request()->routeIs('admin.courses.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-[20px]">library_books</span>
                <span class="sidebar-label">Courses</span>
            </a>

            <a href="{{ Route::has('admin.tokens.index') ? route('admin.tokens.index') : '#' }}" title="Tokens"
               class="nav-item {{ request()->routeIs('admin.tokens.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-[20px]">key</span>
                <span class="sidebar-label">Tokens</span>
            </a>

            <p class="sidebar-block px-6 pb-1 pt-4 text-[10px] font-semibold tracking-widest text-outline uppercase">
                System
            </p>

            <a href="{{ Route::has('admin.logs.index') ? route('admin.logs.index') : '#' }}" title="Activity Log"
               class="nav-item {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-[20px]">history</span>
                <span class="sidebar-label">Activity Log</span>
            </a>

            <!-- <a href="{{ Route::has('admin.pages.index') ? route('admin.pages.index') : '#' }}"
               class="nav-item {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-[20px]">article</span>
                Pages
            </a>

            <a href="{{ Route::has('admin.site-content.edit') ? route('admin.site-content.edit') : '#' }}"
               class="nav-item {{ request()->routeIs('admin.site-content.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-[20px]">edit_note</span>
                Site Content
            </a> -->

        </div>

        {{-- Footer: Settings + Logout --}}
        <div class="border-t border-outline-variant/30 py-2 flex flex-col shrink-0">
            <a href="{{ route('settings.index') }}" title="Settings"
               class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <span class="material-symbols-outlined text-[20px]">manage_accounts</span>
                <span class="sidebar-label">Settings</span>
            </a>

            <form id="logout-form" method="POST" action="{{ Route::has('logout') ? route('logout') : '#' }}">
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
        class="fixed inset-0 z-40 bg-black/30 hidden md:hidden"
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
            <button onclick="openSidebar()" class="md:hidden text-primary -ml-1 shrink-0 cursor-pointer">
                <span class="material-symbols-outlined">menu</span>
            </button>

            <div class="ml-auto flex items-center gap-3">
                {{-- Page-level action buttons --}}
                @yield('topbar-actions')

                {{-- Notifications --}}
                {{--
                <button class="relative text-on-surface-variant hover:text-primary transition-colors">
                    <span class="material-symbols-outlined">notifications</span>
                    <span class="absolute top-0.5 right-0.5 w-2 h-2 bg-gold rounded-full
                                 border-2 border-surface-white"></span>
                </button>
                --}}

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
                                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                            </span>
                        @endif
                    @endauth
                </a>
            </div>
        </header>

        {{-- ─── SCROLLABLE CONTENT ─── --}}
        <main class="flex-1 overflow-y-auto p-4 md:p-8"
              style="padding-bottom: max(2rem, env(safe-area-inset-bottom))">
            <div class="max-w-full mx-auto space-y-8 mb-8 md:mb-0">
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

        function toggleSidebarCollapse() {
            const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
            try { localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0'); } catch (e) {}
        }
    </script>

    @stack('scripts')
</body>
</html>
