<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\CourseGroupRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;

class CourseController extends Controller
{
    public function __construct(
        private CourseRepositoryInterface $courses,
        private CourseGroupRepositoryInterface $groups,
        private UserRepositoryInterface $users,
    ) {}

    public function index()
    {
        return view('admin.courses.index', [
            'courses' => $this->courses->getAll(),
        ]);
    }

    public function show(int $id)
    {
        return view('admin.courses.show', [
            'course' => $this->courses->find($id),
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

        return redirect()->route('admin.courses.show', $course)->with('success', 'Course updated.');
    }

    public function destroy(int $id)
    {
        $course = $this->courses->find($id);
        $this->courses->delete($course);

        return redirect()->route('admin.courses.index')->with('success', 'Course deleted.');
    }
}