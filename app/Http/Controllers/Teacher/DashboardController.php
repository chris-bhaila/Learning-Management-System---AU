<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\TokenRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

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
        $studentIds   = $teacher->students()->pluck('users.id');

        $notifications = Activity::with('causer')
            ->whereIn('causer_id', $studentIds)
            ->where('causer_type', User::class)
            ->latest()
            ->take(20)
            ->get();

        $stats = [
            'total_courses'  => $courses->count(),
            'active_tokens'  => $activeTokens->count(),
            'total_students' => $teacher->students()->count(),
            'total_units'    => $courses->sum('units_count'),
        ];

        return view('teacher.dashboard', compact('courses', 'activeTokens', 'notifications', 'stats'));
    }
}