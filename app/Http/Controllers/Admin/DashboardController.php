<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $users,
        private CourseRepositoryInterface $courses,
    ) {}

    public function index()
    {
        $roleCounts = $this->users->getRoleCounts();

        $totalAdmins   = $roleCounts['admin']   ?? 0;
        $totalTeachers = $roleCounts['teacher'] ?? 0;
        $totalStudents = $roleCounts['student'] ?? 0;
        $totalUsers    = $totalAdmins + $totalTeachers + $totalStudents;

        return view('admin.dashboard', [
            'stats' => [
                'total_users'    => $totalUsers,
                'total_admins'   => $totalAdmins,
                'total_teachers' => $totalTeachers,
                'total_students' => $totalStudents,
                'active_courses' => $this->courses->countPublished(),
                'pct_admins'     => $totalUsers > 0 ? round($totalAdmins   / $totalUsers * 100) : 0,
                'pct_teachers'   => $totalUsers > 0 ? round($totalTeachers / $totalUsers * 100) : 0,
                'pct_students'   => $totalUsers > 0 ? round($totalStudents / $totalUsers * 100) : 0,
            ],
            'recentUsers'    => $this->users->getRecent(8),
            // 'subject' is eager-loaded (not just 'causer') so the Event Details modal
            // can show the subject name without a per-row lazy query when opened.
            'recentActivity' => ActivityLogHelper::scopeVisibleTo(Activity::with('causer', 'subject')->latest(), Auth::user())
                ->take(20)
                ->get(),
        ]);
    }
}