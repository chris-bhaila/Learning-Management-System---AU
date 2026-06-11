<?php

namespace App\Http\Controllers\Student;

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
        ]);
    }
}