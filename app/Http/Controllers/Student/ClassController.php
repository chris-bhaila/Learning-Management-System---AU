<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class ClassController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $users,
        private CourseRepositoryInterface $courses,
    ) {}

    public function index()
    {
        return view('student.classes.index', [
            'teachers' => $this->users->getTeachersForStudent(Auth::id()),
        ]);
    }

    public function show(int $teacherId)
    {
        $teacher = $this->users->getTeacherWithStudentPivot($teacherId, Auth::id());
        abort_if(is_null($teacher), 404);

        return view('student.classes.show', [
            'teacher' => $teacher,
            'courses' => $this->courses->getEnrolledByStudentForTeacher(Auth::id(), $teacherId),
        ]);
    }
}
