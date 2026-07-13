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

    /** Kicks a student from the acting teacher's class — cascades to every course_student
     *  row for that student scoped to this teacher's own courses only. */
    public function kickFromClass(int $id)
    {
        $student = $this->users->find($id);
        abort_if(is_null($student), 404);

        $teacher = Auth::user();

        $this->authorize('kickFromClass', [$student, $teacher->id]);

        $this->users->kickFromClass($teacher->id, $student->id);

        activity()
            ->causedBy($teacher)
            ->withProperties([
                'student_id'   => $student->id,
                'student_name' => $student->name,
                'teacher_id'   => $teacher->id,
                'teacher_name' => $teacher->name,
                'scope'        => 'class',
            ])
            ->log('Teacher kicked student from class');

        return back()->with('success', "{$student->name} has been removed from your class.");
    }
}
