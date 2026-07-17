<?php

namespace App\Http\Controllers\Teacher;

use App\Helpers\TeacherActivityHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\TokenRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        private CourseRepositoryInterface $courses,
        private TokenRepositoryInterface $tokens,
    ) {}

    public function index()
    {
        $teacher      = Auth::user();
        $courses      = $this->courses->getByTeacher($teacher->id);
        $activeTokens = $this->tokens->getActiveByTeacher($teacher->id);

        $notifications = TeacherActivityHelper::scopedQuery($teacher->id)->take(20)->get();

        $stats = [
            'total_courses'  => $courses->count(),
            'active_tokens'  => $activeTokens->count(),
            'total_students' => $teacher->students()->count(),
            'total_units'    => $courses->sum('units_count'),
        ];

        return view('teacher.dashboard', compact('courses', 'activeTokens', 'notifications', 'stats'));
    }
}
