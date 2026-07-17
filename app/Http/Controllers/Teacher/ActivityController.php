<?php

namespace App\Http\Controllers\Teacher;

use App\Helpers\TeacherActivityHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    /** Cursor pagination (WHERE id/created_at < cursor), not numbered/offset pagination —
     *  cheaper on a growing table and a natural fit for "Load more" instead of page links. */
    public function index(Request $request)
    {
        $teacher = Auth::user();
        $search  = $request->get('search', '');
        $type    = in_array($request->get('type'), array_keys(TeacherActivityHelper::TYPE_FILTERS), true)
            ? $request->get('type')
            : '';

        $query = TeacherActivityHelper::scopedQuery($teacher->id);
        TeacherActivityHelper::applyType($query, $type);
        TeacherActivityHelper::applySearch($query, $search);

        // withQueryString() so search/type survive into nextPageUrl() — otherwise every
        // "Load more" fetch after the first would silently drop the active filters.
        $activities = $query->cursorPaginate(20)->withQueryString();

        $data = [
            'activities'    => $activities,
            'currentSearch' => $search,
            'currentType'   => $type,
        ];

        if ($request->ajax()) {
            return view('teacher.activity._list', $data);
        }

        return view('teacher.activity.index', $data);
    }
}
