<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $users,
        private CourseRepositoryInterface $courses,
    ) {}

    public function index()
    {
        return view('teacher.students.index', [
            'students' => $this->users->getStudentsForTeacher(Auth::id()),
        ]);
    }

    public function show(int $id)
    {
        $student = $this->users->getStudentWithTeacherPivot($id, Auth::id());
        abort_if(is_null($student), 404);

        $this->authorize('viewProfile', $student);

        return view('teacher.students.show', [
            'student' => $student,
            'courses' => $this->courses->getStudentCoursesForTeacher($id, Auth::id()),
        ]);
    }
}
