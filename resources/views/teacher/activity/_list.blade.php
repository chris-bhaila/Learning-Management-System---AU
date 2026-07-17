{{-- Expects $activities (a CursorPaginator). Returned as-is for the AJAX "Load more"
     fetch, and @included into the <ul> on first page load — same markup either way. --}}
@forelse($activities as $log)
    @include('teacher.activity._item', ['log' => $log])
@empty
    <li class="px-6 py-10 text-center text-sm text-outline">
        No activity yet.
    </li>
@endforelse

@if($activities->hasMorePages())
    {{-- Read by the page's JS and stripped before insertion — never left in the real DOM. --}}
    <div data-next-page-url="{{ $activities->nextPageUrl() }}" class="hidden"></div>
@endif
