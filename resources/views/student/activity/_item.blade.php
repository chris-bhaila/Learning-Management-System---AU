{{-- Single activity row for the student notification feed — mirrors
     teacher/activity/_item.blade.php's pattern exactly (icon map keyed by description,
     @switch per case, bold {{ }}-escaped interpolation, never raw HTML from stored
     data). Expects $log. --}}
@php
    $iconMap = [
        'File uploaded to course' => ['bg' => 'bg-primary/8',  'ic' => 'text-primary',     'sym' => 'upload_file'],
        'File uploaded to unit'   => ['bg' => 'bg-primary/8',  'ic' => 'text-primary',     'sym' => 'upload_file'],
        'Unit published'         => ['bg' => 'bg-emerald-50', 'ic' => 'text-emerald-600', 'sym' => 'menu_book'],
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
                @case('File uploaded to course')
                    <strong>{{ $p['teacher_name'] }}</strong> uploaded
                    {{ \App\Helpers\StudentActivityHelper::describeFileType($p['file_type'] ?? null) }}
                    @if(!empty($p['file_id']))
                        <a href="{{ route('files.download', $p['file_id']) }}"
                           class="font-semibold text-primary hover:underline cursor-pointer">{{ $p['file_name'] }}</a>
                    @else
                        <strong>{{ $p['file_name'] }}</strong>
                    @endif
                    for <strong>{{ $p['course_name'] }}</strong>
                    @break

                @case('File uploaded to unit')
                    <strong>{{ $p['teacher_name'] }}</strong> uploaded
                    {{ \App\Helpers\StudentActivityHelper::describeFileType($p['file_type'] ?? null) }}
                    @if(!empty($p['file_id']))
                        <a href="{{ route('files.download', $p['file_id']) }}"
                           class="font-semibold text-primary hover:underline cursor-pointer">{{ $p['file_name'] }}</a>
                    @else
                        <strong>{{ $p['file_name'] }}</strong>
                    @endif
                    for unit <strong>{{ $p['unit_name'] }}</strong> of <strong>{{ $p['course_name'] }}</strong>
                    @break

                @case('Unit published')
                    A new unit <strong>{{ $p['unit_name'] }}</strong> has been published in
                    <strong>{{ $p['course_name'] }}</strong> by <strong>{{ $p['teacher_name'] }}</strong>
                    @break
            @endswitch
        </p>
        <p class="text-xs text-outline mt-1">{{ $log->created_at->diffForHumans() }}</p>
    </div>
</li>
