<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $event   = $request->get('event');
        $subject = $request->get('subject');
        $search  = $request->get('search');
        $date    = $request->get('date');

        $query = Activity::with('causer', 'subject')->latest();

        if ($event && in_array($event, ['created', 'updated', 'deleted'])) {
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
}