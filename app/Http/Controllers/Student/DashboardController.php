<?php

namespace App\Http\Controllers\Student;

use App\Helpers\StudentActivityHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        private CourseRepositoryInterface $courses,
    ) {}

    public function index()
    {
        return view('student.dashboard', [
            'courses' => $this->courses->getEnrolledByStudent(Auth::id()),
            // Capped at the query level (not just visually truncated) — 12 is the
            // midpoint of the requested 10-15 range: enough to fill the fixed-height
            // notifications panel plus a comfortable scroll (each row is ~72-80px, so
            // ~6-7 are visible before scrolling), without this "at a glance" dashboard
            // panel duplicating the full history the dedicated Activity page already covers.
            'notifications' => StudentActivityHelper::scopedQuery(Auth::id())->take(12)->get(),
        ]);
    }
}
