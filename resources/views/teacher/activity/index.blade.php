@extends('layouts.teacher')

@section('title', 'All Activity')

@section('content')

<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
            All Activity
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            Every update from your students and tokens, most recent first.
        </p>
    </div>

    {{-- Filters --}}
    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] p-5">
        <div class="flex flex-wrap items-center gap-3">

            <div class="relative flex-1 min-w-[180px] max-w-xs">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2
                             text-outline text-[18px] pointer-events-none">search</span>
                <input
                    type="text"
                    id="activity-search"
                    value="{{ $currentSearch }}"
                    placeholder="Search by student, token, or course…"
                    autocomplete="off"
                    class="w-full pl-10 pr-4 py-2.5 rounded-[16px] text-sm bg-surface-container-low
                           border border-outline-variant/60 placeholder:text-outline
                           focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary
                           transition-colors cursor-text">
            </div>

            <div class="relative shrink-0">
                <select id="activity-type"
                        class="appearance-none pl-4 pr-9 py-2.5 rounded-[16px] text-sm cursor-pointer
                               bg-surface-container-low border border-outline-variant/60 text-on-surface
                               focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary
                               transition-colors">
                    @foreach(\App\Helpers\TeacherActivityHelper::TYPE_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected($currentType === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2
                             text-outline text-[18px] pointer-events-none">expand_more</span>
            </div>

            <button
                type="button"
                id="activity-clear-filters"
                class="inline-flex items-center gap-1.5 px-3 py-2.5 rounded-[16px] text-sm cursor-pointer
                       text-on-surface-variant hover:text-primary hover:bg-surface-container transition-colors duration-150
                       {{ ($currentSearch === '' && $currentType === '') ? 'hidden' : '' }}">
                <span class="material-symbols-outlined text-[16px]">close</span>
                Clear filters
            </button>

        </div>
    </div>

    <div class="bg-surface-white border border-outline-variant/40 rounded-[20px] shadow-[0px_1px_4px_rgba(30,42,74,0.06)] overflow-hidden">
        <ul id="activity-list" class="divide-y divide-outline-variant/20">
            @include('teacher.activity._list', ['activities' => $activities])
        </ul>
    </div>

    <div id="activity-load-more-wrap" class="flex justify-center" @if(! $activities->hasMorePages()) style="display:none" @endif>
        <button
            type="button"
            id="activity-load-more"
            data-next-url="{{ $activities->hasMorePages() ? $activities->nextPageUrl() : '' }}"
            class="inline-flex items-center gap-2 px-5 py-2.5 border border-outline-variant/60 text-sm font-medium
                   text-on-surface-variant rounded-[24px] hover:bg-surface-container-low hover:text-primary
                   active:scale-[0.96] transition-all duration-150 cursor-pointer
                   disabled:opacity-60 disabled:cursor-not-allowed">
            <span class="material-symbols-outlined text-[18px]" id="activity-load-more-icon">expand_more</span>
            <span id="activity-load-more-label">Load more</span>
        </button>
    </div>

</div>

@push('scripts')
<script>
    (function () {
        const list       = document.getElementById('activity-list');
        const wrap       = document.getElementById('activity-load-more-wrap');
        const btn        = document.getElementById('activity-load-more');
        const icon       = document.getElementById('activity-load-more-icon');
        const label      = document.getElementById('activity-load-more-label');
        const searchInput = document.getElementById('activity-search');
        const typeSelect  = document.getElementById('activity-type');
        const clearBtn    = document.getElementById('activity-clear-filters');
        const baseUrl     = '{{ route('teacher.activity.index') }}';

        function setLoadMoreState(isLoading) {
            btn.disabled = isLoading;
            icon.textContent = isLoading ? 'progress_activity' : 'expand_more';
            icon.classList.toggle('animate-spin', isLoading);
            label.textContent = isLoading ? 'Loading…' : 'Load more';
        }

        function extractNextUrl(temp) {
            const marker  = temp.querySelector('[data-next-page-url]');
            const nextUrl = marker?.dataset.nextPageUrl || '';
            marker?.remove();
            return nextUrl;
        }

        function applyNextUrl(nextUrl) {
            if (nextUrl) {
                btn.dataset.nextUrl = nextUrl;
                wrap.style.display = '';
                setLoadMoreState(false);
            } else {
                wrap.style.display = 'none';
            }
        }

        // ── Load more (append) ──────────────────────────────────────
        btn.addEventListener('click', function () {
            const url = btn.dataset.nextUrl;
            if (!url) return;

            setLoadMoreState(true);

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.text())
                .then(html => {
                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    const nextUrl = extractNextUrl(temp);
                    list.insertAdjacentHTML('beforeend', temp.innerHTML);
                    applyNextUrl(nextUrl);
                })
                .catch(() => setLoadMoreState(false));
        });

        // ── Search / type filter (replace) ──────────────────────────
        let searchTimer;

        function currentParams() {
            const params = new URLSearchParams();
            if (searchInput.value.trim() !== '') params.set('search', searchInput.value.trim());
            if (typeSelect.value !== '') params.set('type', typeSelect.value);
            return params;
        }

        function refetch() {
            const params = currentParams();
            const query  = params.toString();
            const url    = baseUrl + (query ? '?' + query : '');

            list.style.opacity = '0.5';

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.text())
                .then(html => {
                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    const nextUrl = extractNextUrl(temp);
                    list.innerHTML = temp.innerHTML;
                    list.style.opacity = '1';
                    applyNextUrl(nextUrl);

                    clearBtn.classList.toggle('hidden', query === '');
                    history.replaceState(null, '', url);
                })
                .catch(() => { list.style.opacity = '1'; });
        }

        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(refetch, 350);
        });

        typeSelect.addEventListener('change', refetch);

        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            typeSelect.value = '';
            refetch();
        });
    })();
</script>
@endpush

@endsection
