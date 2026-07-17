{{-- Single activity row — shared by the dashboard's "Recent Activity" card and the
     full "All Activity" page, so the two never drift out of sync. Expects $log. --}}
@php
    $iconMap = [
        'Student joined teacher class via class token'   => ['bg' => 'bg-emerald-50', 'ic' => 'text-emerald-600', 'sym' => 'person_add'],
        'Student enrolled in course via course token'    => ['bg' => 'bg-emerald-50', 'ic' => 'text-emerald-600', 'sym' => 'menu_book'],
        'Course token expired: max uses reached'        => ['bg' => 'bg-amber-50',   'ic' => 'text-amber-600',   'sym' => 'hourglass_bottom'],
        'Class token expired: max uses reached'         => ['bg' => 'bg-amber-50',   'ic' => 'text-amber-600',   'sym' => 'hourglass_bottom'],
        'Course token expired: time limit reached'      => ['bg' => 'bg-red-50',     'ic' => 'text-error',        'sym' => 'timer_off'],
        'Class token expired: time limit reached'       => ['bg' => 'bg-red-50',     'ic' => 'text-error',        'sym' => 'timer_off'],
        'Course token revoked'                          => ['bg' => 'bg-surface-container', 'ic' => 'text-on-surface-variant', 'sym' => 'block'],
        'Class token revoked'                           => ['bg' => 'bg-surface-container', 'ic' => 'text-on-surface-variant', 'sym' => 'block'],
    ];
    $style = $iconMap[$log->description] ?? ['bg' => 'bg-surface-container', 'ic' => 'text-primary', 'sym' => 'info'];

    $p = $log->properties;
@endphp
<li class="px-6 py-4 flex items-start gap-3 hover:bg-surface-container-low/40 transition-colors duration-200">
    <div class="w-8 h-8 rounded-full {{ $style['bg'] }} flex items-center justify-center shrink-0 mt-0.5">
        <span class="material-symbols-outlined {{ $style['ic'] }} text-[16px]">
            {{ $style['sym'] }}
        </span>
    </div>
    <div class="min-w-0 flex-1">
        <p class="text-sm text-on-surface leading-snug">
            @switch($log->description)
                @case('Student joined teacher class via class token')
                    @include('teacher.activity._student_link', ['log' => $log]) joined your class
                    @break

                @case('Student enrolled in course via course token')
                    @include('teacher.activity._student_link', ['log' => $log]) joined your course
                    <strong>{{ $p['course_title'] }}</strong> via token
                    <strong>{{ $p['token_value'] }}</strong>
                    @break

                @case('Course token expired: max uses reached')
                    Token <strong>{{ $p['token_value'] }}</strong> for course
                    <strong>{{ $p['course_title'] }}</strong> has expired.
                    Max uses <strong>{{ $p['max_uses'] }}</strong> reached!
                    @break

                @case('Class token expired: max uses reached')
                    Token <strong>{{ $p['token_value'] }}</strong> for your class
                    has expired. Max uses <strong>{{ $p['max_uses'] }}</strong> reached!
                    @break

                @case('Course token expired: time limit reached')
                    Token <strong>{{ $p['token_value'] }}</strong> for course
                    <strong>{{ $p['course_title'] }}</strong> has expired.
                    Time limit reached!
                    @break

                @case('Class token expired: time limit reached')
                    Token <strong>{{ $p['token_value'] }}</strong> for your class
                    has expired. Time limit reached!
                    @break

                @case('Course token revoked')
                    Token <strong>{{ $p['token_value'] }}</strong> for course
                    <strong>{{ $p['course_title'] }}</strong> has been revoked
                    @break

                @case('Class token revoked')
                    Token <strong>{{ $p['token_value'] }}</strong> for your class
                    has been revoked
                    @break
            @endswitch
        </p>
        <p class="text-xs text-outline mt-1">{{ $log->created_at->diffForHumans() }}</p>
    </div>
</li>
