@php
    $total = method_exists($users, 'total') ? $users->total() : $users->count();
    $roleHeadings = $roleHeadings ?? ['admin' => 'Admins', 'teacher' => 'Teachers', 'student' => 'Students'];
    $roleBadge = $roleBadge ?? [
        'admin'   => 'bg-primary-container text-on-primary',
        'teacher' => 'bg-gold/20 text-on-gold',
        'student' => 'bg-surface-container text-on-surface-variant',
    ];
    $roleIcon = $roleIcon ?? [
        'admin'   => 'admin_panel_settings',
        'teacher' => 'school',
        'student' => 'menu_book',
    ];
@endphp

{{-- Card header --}}
<div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/30">
    <div>
        <h2 class="text-base font-semibold text-primary" style="font-family: var(--font-display);">
            {{ $roleHeadings[$currentRole] ?? 'Users' }}
        </h2>
        <p class="text-xs text-on-surface-variant mt-0.5">
            {{ number_format($total) }} {{ Str::plural('user', $total) }} found
            @if($currentSearch)
                for "{{ $currentSearch }}"
            @endif
        </p>
    </div>
</div>

{{-- Table --}}
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-outline-variant/30 bg-surface-container-low/50">
                <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase">
                    User
                </th>
                <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase hidden sm:table-cell">
                    Email
                </th>
                <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase">
                    Role
                </th>
                <th class="text-left px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase hidden md:table-cell">
                    Joined
                </th>
                <th class="px-6 py-3 text-[11px] font-semibold tracking-widest text-outline uppercase text-center">
                    Actions
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant/20">
            @forelse($users as $user)
                <tr class="hover:bg-surface-container-low/40 transition-colors">

                    {{-- Avatar + Name --}}
                    <td class="px-6 py-4">
                        @php
                            $avatarCls = [
                                'admin'   => 'bg-primary-container text-on-primary',
                                'teacher' => 'bg-gold/20 text-on-gold',
                                'student' => 'bg-surface-container text-on-surface',
                            ][$user->role->name] ?? 'bg-surface-container text-on-surface';
                        @endphp
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full {{ $avatarCls }} flex items-center justify-center
                                        text-xs font-semibold shrink-0 overflow-hidden select-none">
                                @if($user->profile_photo_path ?? false)
                                    <img src="{{ $user->profile_photo_url }}"
                                         alt="{{ $user->name }}"
                                         class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper(substr($user->name ?? '??', 0, 2)) }}
                                @endif
                            </div>
                            <span class="font-medium text-on-surface truncate max-w-[160px]">
                                {{ $user->name }}
                            </span>
                        </div>
                    </td>

                    {{-- Email --}}
                    <td class="px-6 py-4 text-on-surface-variant hidden sm:table-cell">
                        {{ $user->email }}
                    </td>

                    {{-- Role badge --}}
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full
                                     text-xs font-medium {{ $roleBadge[$user->role->name] ?? $roleBadge['student'] }}">
                            <span class="material-symbols-outlined text-[12px]">
                                {{ $roleIcon[$user->role->name] ?? 'person' }}
                            </span>
                            {{ ucfirst($user->role->name) }}
                        </span>
                    </td>

                    {{-- Joined --}}
                    <td class="px-6 py-4 text-on-surface-variant text-xs whitespace-nowrap hidden md:table-cell">
                        {{ $user->created_at?->format('d M Y') ?? '—' }}
                    </td>

                    {{-- Actions --}}
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-center gap-0.5">

                            {{-- View --}}
                            <a href="{{ Route::has('admin.users.show') ? route('admin.users.show', $user) : '#' }}"
                               title="View user"
                               class="w-8 h-8 inline-flex items-center justify-center rounded-lg cursor-pointer
                                      text-on-surface-variant hover:bg-surface-container hover:text-primary
                                      transition-colors">
                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                            </a>

                            {{-- Edit --}}
                            <a href="{{ Route::has('admin.users.edit') ? route('admin.users.edit', $user) : '#' }}"
                               title="Edit user"
                               class="w-8 h-8 inline-flex items-center justify-center rounded-lg cursor-pointer
                                      text-on-surface-variant hover:bg-surface-container hover:text-primary
                                      transition-colors">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                            </a>

                            {{-- Delete --}}
                            <button
                                type="button"
                                title="Delete user"
                                onclick="openDeleteModal({{ $user->id }}, '{{ e($user->name) }}')"
                                class="w-8 h-8 inline-flex items-center justify-center rounded-lg cursor-pointer
                                       text-on-surface-variant hover:bg-error-container hover:text-error
                                       transition-colors">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>

                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center">
                                <span class="material-symbols-outlined text-outline text-[24px]">group_off</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-on-surface">No users found</p>
                                <p class="text-xs text-on-surface-variant mt-0.5">
                                    @if($currentSearch)
                                        No results for "<strong>{{ $currentSearch }}</strong>".
                                        Try a different search term.
                                    @else
                                        No {{ $roleHeadings[$currentRole] ?? 'users' }} registered yet.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if($users instanceof \Illuminate\Pagination\LengthAwarePaginator && $users->hasPages())
    <div class="px-6 py-4 border-t border-outline-variant/30">
        {{ $users->appends(request()->query())->links() }}
    </div>
@endif
