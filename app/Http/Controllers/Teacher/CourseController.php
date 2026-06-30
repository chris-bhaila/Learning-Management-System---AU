<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\StoreCourseRequest;
use App\Http\Requests\Shared\UpdateCourseRequest;
use App\Models\Course;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\CourseGroupRepositoryInterface;
use App\Repositories\Contracts\FileRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    public function __construct(
        private CourseRepositoryInterface $courses,
        private CourseGroupRepositoryInterface $groups,
        private FileRepositoryInterface $files,
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
}