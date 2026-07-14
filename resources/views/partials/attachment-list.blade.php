{{--
    Renders a date-grouped attachment list.
    Expects:
      $groupedFiles     — Collection<string date, Collection<File>>, e.g. $unit->filesGroupedByDate()
      $canDelete        — bool, whether to render the delete action (default false)
      $deleteFormPrefix — string, DOM id prefix for the hidden delete form (e.g. 'delete-file-form')
--}}
@php
    $canDelete = $canDelete ?? false;
@endphp

@foreach($groupedFiles as $dateKey => $filesForDate)
    <div class="px-6 py-2 bg-surface-container-low/60 border-b border-outline-variant/20">
        <p class="text-xs font-semibold text-on-surface-variant tracking-wide">
            {{ $dateKey }} / {{ \Illuminate\Support\Carbon::parse($dateKey)->format('l') }}
        </p>
    </div>
    <ul class="divide-y divide-outline-variant/20">
        @foreach($filesForDate as $file)
            @php
                $fileSize = $file->size >= 1048576
                    ? number_format($file->size / 1048576, 1) . ' MB'
                    : number_format($file->size / 1024, 0) . ' KB';
            @endphp
            <li class="flex items-center gap-3 px-6 py-3.5 min-w-0
                       hover:bg-surface-container-low/40 transition-colors duration-200">
                <span class="material-symbols-outlined text-outline text-[20px] shrink-0">description</span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-on-surface truncate">
                        {{ $file->original_name ?? $file->filename }}
                    </p>
                    <p class="text-[11px] text-on-surface-variant mt-0.5">
                        {{ $fileSize }} · {{ $file->created_at->format('M j, Y') }}
                    </p>
                </div>
                <a href="{{ route('files.download', $file->id) }}"
                   class="shrink-0 inline-flex items-center gap-1 text-xs font-medium
                          text-primary hover:text-gold transition-colors cursor-pointer">
                    <span class="material-symbols-outlined text-[14px]">download</span>
                    Download
                </a>
                @if($canDelete)
                    <button type="button"
                            onclick="confirmDelete({{ Js::from($file->original_name ?? $file->filename) }}, document.getElementById('{{ $deleteFormPrefix }}-{{ $file->id }}'))"
                            class="shrink-0 inline-flex items-center gap-1 text-xs font-medium
                                   text-error hover:text-error/70 transition-colors cursor-pointer">
                        <span class="material-symbols-outlined text-[14px]">delete</span>
                        Delete
                    </button>
                @endif
            </li>
        @endforeach
    </ul>
@endforeach
