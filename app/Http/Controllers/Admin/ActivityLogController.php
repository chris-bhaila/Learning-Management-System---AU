<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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

        // First pass: union of all `properties` keys (manual activity()->log() calls, e.g.
        // enrollment/login) AND all attribute-change column names (automatic created/updated/
        // deleted logging via LogsActivity) across the filtered set. Selecting only the source
        // columns needed and streaming via cursor() keeps this from ever materializing the
        // full filtered result set in memory.
        $propertyKeys  = [];
        $attributeCols = [];
        foreach ($this->filteredQuery($request)->select('properties', 'attribute_changes', 'event')->cursor() as $activity) {
            $propertyKeys  += array_fill_keys(array_keys($activity->properties?->toArray() ?? []), true);
            $attributeCols += array_fill_keys(array_keys($this->attributeChangeCells($activity)), true);
        }
        $propertyKeys  = array_keys($propertyKeys);
        $attributeCols = array_keys($attributeCols);
        sort($propertyKeys);
        sort($attributeCols);

        $header = array_merge(['Timestamp', 'Causer', 'Action'], $propertyKeys, $attributeCols);

        return response()->streamDownload(function () use ($request, $propertyKeys, $attributeCols, $header) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, array_map([$this, 'sanitizeCsvCell'], $header));

            // Note: cursor() does not eager-load relations even if the query builder has
            // with() applied (it bypasses eagerLoadRelations()), so accessing $activity->causer
            // below is a lazy per-row query — an accepted tradeoff for streaming without
            // buffering the whole filtered result set in memory.
            foreach ($this->filteredQuery($request)->cursor() as $activity) {
                $properties = $activity->properties?->toArray() ?? [];
                $attrCells  = $this->attributeChangeCells($activity);

                $row = [
                    $activity->created_at?->format('Y-m-d H:i:s') ?? '',
                    $activity->causer?->name ?? '',
                    $activity->description ?? '',
                ];

                // A given row typically populates EITHER the properties-derived columns OR
                // the attribute-change-derived columns, not both — the other set is left blank.
                foreach ($propertyKeys as $key) {
                    $value = $properties[$key] ?? '';
                    $row[] = is_array($value) ? json_encode($value) : (string) $value;
                }

                foreach ($attributeCols as $col) {
                    $row[] = (string) ($attrCells[$col] ?? '');
                }

                fputcsv($handle, array_map([$this, 'sanitizeCsvCell'], $row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Derive CSV cells from an Activity's `attribute_changes` (created/updated/deleted events
     * logged automatically via LogsActivity) — empty for rows that only populate `properties`
     * (manual activity()->log() calls). Reuses ActivityLogHelper::buildDiff(), the exact same
     * method the Event Details modal calls, so labels, redaction (e.g. password → "(hidden)"),
     * FK resolution, and value formatting all stay in lockstep with what's shown on screen —
     * this is not a second, separately-maintained redaction path.
     *
     * created/deleted events produce one column per field: "{Label}".
     * updated events produce two columns per field: "{Label} (before)" and "{Label} (after)".
     *
     * @return array<string, string> column name => formatted value
     */
    private function attributeChangeCells(Activity $activity): array
    {
        $changes = $activity->attribute_changes;

        if (! $changes || $changes->isEmpty()) {
            return [];
        }

        $event    = $activity->event ?? 'updated';
        $newAttrs = (array) ($changes->get('attributes') ?? []);
        $oldAttrs = (array) ($changes->get('old') ?? []);

        if (empty($newAttrs) && empty($oldAttrs)) {
            return [];
        }

        $diff  = ActivityLogHelper::buildDiff($newAttrs, $oldAttrs, $event);
        $cells = [];

        foreach ($diff as $row) {
            $label = $row['label'];

            if ($event === 'deleted') {
                $cells[$label] = $row['old'] ?? '';
            } elseif ($event === 'updated') {
                $cells["{$label} (before)"] = $row['old'] ?? '';
                $cells["{$label} (after)"]  = $row['new'] ?? '';
            } else {
                // created (or any other non-deleted, non-updated event carrying attribute_changes)
                $cells[$label] = $row['new'] ?? '';
            }
        }

        return $cells;
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
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($date === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($date === 'week') {
            $query->where('created_at', '>=', now()->startOfWeek());
        } elseif ($date === 'month') {
            $query->where('created_at', '>=', now()->startOfMonth());
        }

        return $query;
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