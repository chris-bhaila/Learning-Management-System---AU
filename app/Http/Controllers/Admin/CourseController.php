<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Models\Course;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\CourseGroupRepositoryInterface;
use App\Repositories\Contracts\FileRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    public function __construct(
        private CourseRepositoryInterface $courses,
        private CourseGroupRepositoryInterface $groups,
        private UserRepositoryInterface $users,
        private FileRepositoryInterface $files,
    ) {}

    public function index()
    {
        return view('admin.courses.index', [
            'courses' => $this->courses->getAll(),
            'groups'  => $this->groups->getAll(),
        ]);
    }

    public function create()
    {
        return view('admin.courses.create', [
            'teachers' => $this->users->getAllTeachers(),
            'groups'   => $this->groups->getAll(),
        ]);
    }

    public function store(StoreCourseRequest $request)
    {
        $course = $this->courses->create($request->safe()->except('files'));

        if ($request->hasFile('files')) {
            $this->files->storeUploads(
                $request->file('files'),
                Course::class,
                $course->id,
                Auth::id(),
            );
        }

        return redirect()->route('admin.courses.show', $course)->with('success', 'Course created.');
    }

    public function show(int $id)
    {
        return view('admin.courses.show', [
            'course'   => $this->courses->findWithRelations($id),
            'teachers' => $this->users->getAllTeachers(),
            'groups'   => $this->groups->getAll(),
        ]);
    }

    public function edit(int $id)
    {
        return view('admin.courses.edit', [
            'course'   => $this->courses->find($id),
            'groups'   => $this->groups->getAll(),
            'teachers' => $this->users->getAllTeachers(),
        ]);
    }

    public function update(UpdateCourseRequest $request, int $id)
    {
        $course = $this->courses->find($id);
        $this->courses->update($course, $request->validated());

        return redirect()->route('admin.courses.show', $id)->with('success', 'Course updated.');
    }

    public function destroy(int $id)
    {
        $course = $this->courses->find($id);
        $this->courses->delete($course);

        return redirect()->route('admin.courses.index')->with('success', 'Course deleted.');
    }

    /** Admin variant of Teacher\CourseController::removeStudent() — same Policy, same
     *  repository method, course ownership doesn't depend on who's performing the action. */
    public function removeStudent(int $id, int $studentId)
    {
        $course = $this->courses->find($id);
        abort_if(is_null($course), 404);

        $this->authorize('removeStudent', $course);

        $student = $this->users->find($studentId);
        abort_if(is_null($student), 404);

        $this->courses->removeStudentFromCourse($course->id, $student->id);

        activity()
            ->causedBy(Auth::user())
            ->withProperties([
                'student_id'   => $student->id,
                'student_name' => $student->name,
                'teacher_id'   => $course->teacher_id,
                'teacher_name' => $course->teacher?->name ?? 'Unknown',
                'scope'        => 'course',
                'course_id'    => $course->id,
                'course_title' => $course->title,
            ])
            ->log('Teacher removed student from course');

        return back()->with('success', "{$student->name} has been removed from {$course->title}.");
    }
}