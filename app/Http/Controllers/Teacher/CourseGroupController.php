<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\StoreCourseGroupRequest;
use App\Http\Requests\Shared\UpdateCourseGroupRequest;
use App\Repositories\Contracts\CourseGroupRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class CourseGroupController extends Controller
{
    public function __construct(
        private CourseGroupRepositoryInterface $groups,
    ) {}

    public function index()
    {
        return view('teacher.groups.index', [
            'groups' => $this->groups->getByTeacher(Auth::id()),
        ]);
    }

    public function store(StoreCourseGroupRequest $request)
    {
        $this->groups->create(array_merge(
            $request->validated(),
            ['teacher_id' => Auth::id()]
        ));

        return back()->with('success', 'Group created.');
    }

    public function update(UpdateCourseGroupRequest $request, int $id)
    {
        $group = $this->groups->find($id);
        $this->authorize('update', $group);

        $this->groups->update($group, $request->validated());

        return back()->with('success', 'Group updated.');
    }

    public function destroy(int $id)
    {
        $group = $this->groups->find($id);
        $this->authorize('delete', $group);

        $this->groups->delete($group);

        return back()->with('success', 'Group deleted.');
    }
}