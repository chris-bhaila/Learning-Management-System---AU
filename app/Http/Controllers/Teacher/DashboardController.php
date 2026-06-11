<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\TokenRepositoryInterface;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        private CourseRepositoryInterface $courses,
        private TokenRepositoryInterface $tokens,
        private ActivityLogRepositoryInterface $logs,
    ) {}

    public function index()
    {
        $teacher = Auth::user();

        return view('teacher.dashboard', [
            'courses'       => $this->courses->getByTeacher($teacher->id),
            'activeTokens'  => $this->tokens->getActiveByTeacher($teacher->id),
            'notifications' => $this->logs->getForTeacherNotifications($teacher->id)->take(20),
        ]);
    }
}