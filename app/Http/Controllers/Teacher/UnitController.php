<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreUnitRequest;
use App\Http\Requests\Teacher\UpdateUnitRequest;
use App\Repositories\Contracts\UnitRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct(
        private UnitRepositoryInterface $units,
        private CourseRepositoryInterface $courses,
    ) {}

    public function create(int $courseId)
    {
        $course = $this->courses->find($courseId);
        $this->authorize('update', $course);

        return view('teacher.units.create', compact('course'));
    }

    public function store(StoreUnitRequest $request, int $courseId)
    {
        $course = $this->courses->find($courseId);
        $this->authorize('update', $course);

        $this->units->create(array_merge(
            $request->validated(),
            ['course_id' => $courseId]
        ));

        return redirect()->route('teacher.courses.show', $courseId)->with('success', 'Unit added.');
    }

    public function edit(int $id)
    {
        $unit = $this->units->find($id);
        $this->authorize('update', $unit->course);

        return view('teacher.units.edit', compact('unit'));
    }

    public function update(UpdateUnitRequest $request, int $id)
    {
        $unit = $this->units->find($id);
        $this->authorize('update', $unit->course);

        $this->units->update($unit, $request->validated());

        return back()->with('success', 'Unit updated.');
    }

    public function destroy(int $id)
    {
        $unit = $this->units->find($id);
        $this->authorize('update', $unit->course);

        $course = $unit->course;
        $this->units->delete($unit);

        return redirect()->route('teacher.courses.show', $course)->with('success', 'Unit deleted.');
    }

    public function reorder(Request $request, int $courseId)
    {
        $course = $this->courses->find($courseId);
        $this->authorize('update', $course);

        $this->units->reorder($courseId, $request->input('order', []));

        return response()->json(['success' => true]);
    }
}