<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\StoreCourseRequest;
use App\Http\Requests\Shared\UpdateCourseRequest;
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
        private FileRepositoryInterface $files,
        private UserRepositoryInterface $users,
    ) {}

    public function index()
    {
        $courses = $this->courses->getByTeacher(Auth::id());

        return view('teacher.courses.index', [
            'courses'          => $courses,
            'recentCourses'    => $this->courses->getRecentByTeacher(Auth::id()),
            'coursesByGroup'   => $courses
                ->filter(fn($c) => $c->group_id !== null && $c->group !== null)
                ->groupBy('group_id')
                ->sortBy(fn($gc) => strtolower($gc->first()->group->name ?? '')),
            'ungroupedCourses' => $courses
                ->filter(fn($c) => $c->group_id === null || $c->group === null),
            'groups'           => $this->groups->getByTeacher(Auth::id()),
        ]);
    }

    public function create()
    {
        return view('teacher.courses.create', [
            'groups' => $this->groups->getByTeacher(Auth::id()),
        ]);
    }

    public function store(StoreCourseRequest $request)
    {
        $course = $this->courses->create(array_merge(
            $request->safe()->except('files'),
            ['teacher_id' => Auth::id()]
        ));

        if ($request->hasFile('files')) {
            $this->files->storeUploads(
                $request->file('files'),
                Course::class,
                $course->id,
                Auth::id(),
            );
        }

        return redirect()->route('teacher.courses.show', $course)->with('success', 'Course created.');
    }

    public function show(int $id)
    {
        $course = $this->courses->findWithRelations($id);
        $this->authorize('view', $course);

        return view('teacher.courses.show', [
            'course' => $course,
            'groups' => $this->groups->getByTeacher(Auth::id()),
        ]);
    }

    public function edit(int $id)
    {
        $course = $this->courses->find($id);
        $this->authorize('update', $course);

        return view('teacher.courses.edit', [
            'course' => $course,
            'groups' => $this->groups->getByTeacher(Auth::id()),
        ]);
    }

    public function update(UpdateCourseRequest $request, int $id)
    {
        $course = $this->courses->find($id);
        $this->authorize('update', $course);

        $this->courses->update($course, $request->validated());

        return redirect()->route('teacher.courses.show', $course)->with('success', 'Course updated.');
    }

    public function destroy(int $id)
    {
        $course = $this->courses->find($id);
        $this->authorize('delete', $course);

        $this->courses->delete($course);

        return redirect()->route('teacher.courses.index')->with('success', 'Course deleted.');
    }

    /** Removes (deactivates) a single student's enrollment in this course only — does not
     *  touch the class relationship or the student's other courses. */
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