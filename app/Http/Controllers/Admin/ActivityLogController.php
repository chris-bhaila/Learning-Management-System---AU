<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ActivityDescriptionHelper;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filteredQuery($request);

        $subjectTypes = Activity::selectRaw('DISTINCT subject_type')
            ->whereNotNull('subject_type')
            ->pluck('subject_type');

        return view('admin.logs.index', [
            'logs'         => $query->paginate(25)->withQueryString(),
            'subjectTypes' => $subjectTypes,
            'totalToday'   => Activity::whereDate('created_at', today())->count(),
            'totalWeek'    => Activity::where('created_at', '>=', now()->startOfWeek())->count(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filename = 'activity-log-export-' . now()->format('Y-m-d-His') . '.csv';

        $header = ['Causer ID', 'Causer', 'Timestamp', 'Event Type', 'Description'];

        return response()->streamDownload(function () use ($request, $header) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, array_map([$this, 'sanitizeCsvCell'], $header));

            // Note: cursor() does not eager-load relations even if the query builder has
            // with() applied (it bypasses eagerLoadRelations()), so accessing $activity->causer
            // / ->subject below is a lazy per-row query — an accepted tradeoff for streaming
            // without buffering the whole filtered result set in memory.
            foreach ($this->filteredQuery($request)->cursor() as $activity) {
                $row = [
                    $activity->causer_id !== null ? (string) $activity->causer_id : '',
                    $activity->causer?->name ?? '',
                    $activity->created_at?->format('Y-m-d H:i:s') ?? '',
                    ActivityLogHelper::resolveEventConfig($activity->event ?? 'updated')['label'],
                    ActivityDescriptionHelper::describe($activity),
                ];

                fputcsv($handle, array_map([$this, 'sanitizeCsvCell'], $row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Shared query-building logic for the on-screen filtered view and the CSV export —
     * both must stay in lockstep so a filter fix never silently diverges between the two.
     */
    private function filteredQuery(Request $request): Builder
    {
        $event   = $request->get('event');
        $subject = $request->get('subject');
        $search  = $request->get('search');
        $date    = $request->get('date');

        $query = Activity::with('causer', 'subject')->latest();

        if ($event && in_array($event, ['created', 'updated', 'deleted', 'login', 'logout'])) {
            $query->where('event', $event);
        }

        if ($subject) {
            $query->where('subject_type', 'like', "%{$subject}%");
        }

        if ($search) {
            $query->whereHasMorph('causer', '*', function ($q) use ($search) {
                // Both conditions must stay inside this closure, scoped by whereHasMorph's
                // own outer grouping — an orWhere() here only widens the match within the
                // causer subquery, it can't leak out and match unrelated activity rows.
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($date === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($date === 'week') {
            $query->where('created_at', '>=', now()->startOfWeek());
        } elseif ($date === 'month') {
            $query->where('created_at', '>=', now()->startOfMonth());
        }

        // Access-control scope, not a user-facing filter — unconditional, never skippable
        // via query params. See ActivityLogHelper::scopeVisibleTo() for the exact logic
        // and why it's not just whereHasMorph('causer', ...) alone.
        return ActivityLogHelper::scopeVisibleTo($query, Auth::user());
    }

    /** Prefix cells starting with =, +, -, or @ to prevent formula execution when opened in Excel/Sheets. */
    private function sanitizeCsvCell(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }
}