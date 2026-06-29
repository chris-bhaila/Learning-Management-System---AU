<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\StoreCourseGroupRequest;
use App\Http\Requests\Shared\UpdateCourseGroupRequest;
use App\Repositories\Contracts\CourseGroupRepositoryInterface;

class CourseGroupController extends Controller
{
    public function __construct(
        private CourseGroupRepositoryInterface $groups,
    ) {}

    public function index()
    {
        return view('admin.groups.index', [
            'groups' => $this->groups->getAll(),
        ]);
    }

    public function store(StoreCourseGroupRequest $request)
    {
        $this->groups->create($request->validated());

        return back()->with('success', 'Group created.');
    }

    public function update(UpdateCourseGroupRequest $request, int $id)
    {
        $group = $this->groups->find($id);
        $this->groups->update($group, $request->validated());

        return back()->with('success', 'Group updated.');
    }

    public function destroy(int $id)
    {
        $group = $this->groups->find($id);
        $this->groups->delete($group);

        return back()->with('success', 'Group deleted.');
    }
}