<?php

namespace App\Http\Controllers\Student;

use App\Helpers\StudentActivityHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    /** Cursor pagination (WHERE id/created_at < cursor), not numbered/offset pagination —
     *  cheaper on a growing table and a natural fit for "Load more" instead of page links.
     *  Mirrors Teacher\ActivityController exactly. */
    public function index(Request $request)
    {
        $student = Auth::user();
        $search  = $request->get('search', '');
        $type    = in_array($request->get('type'), array_keys(StudentActivityHelper::TYPE_FILTERS), true)
            ? $request->get('type')
            : '';

        $query = StudentActivityHelper::scopedQuery($student->id);
        StudentActivityHelper::applyType($query, $type);
        StudentActivityHelper::applySearch($query, $search);

        // withQueryString() so search/type survive into nextPageUrl() — otherwise every
        // "Load more" fetch after the first would silently drop the active filters.
        $activities = $query->cursorPaginate(20)->withQueryString();

        $data = [
            'activities'    => $activities,
            'currentSearch' => $search,
            'currentType'   => $type,
        ];

        if ($request->ajax()) {
            return view('student.activity._list', $data);
        }

        return view('student.activity.index', $data);
    }
}
