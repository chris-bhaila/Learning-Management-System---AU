<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\UnitRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    public function __construct(
        private CourseRepositoryInterface $courses,
        private UnitRepositoryInterface $units,
    ) {}

    public function index()
    {
        return view('student.courses.index', [
            'courses' => $this->courses->getEnrolledByStudent(Auth::id()),
        ]);
    }

    public function show(int $id)
    {
        $course = $this->courses->find($id);
        $this->authorize('view', $course);

        return view('student.courses.show', [
            'course' => $course,
            'units'  => $this->units->getByCourse($id),
        ]);
    }

    public function showUnit(int $courseId, int $unitId)
    {
        $course = $this->courses->find($courseId);
        $this->authorize('view', $course);

        $unit = $this->units->find($unitId);
        abort_if(is_null($unit) || $unit->course_id !== $courseId, 404);

        return view('student.units.show', compact('course', 'unit'));
    }
}