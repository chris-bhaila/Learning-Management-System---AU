<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\StoreUnitRequest;
use App\Http\Requests\Shared\UpdateUnitRequest;
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

        return view('units.create', [
            'course'     => $course,
            'layout'     => 'layouts.teacher',
            'storeRoute' => route('teacher.units.store', $courseId),
            'backRoute'  => route('teacher.courses.show', $courseId),
        ]);
    }

    public function store(StoreUnitRequest $request, int $courseId)
    {
        $course = $this->courses->find($courseId);
        $this->authorize('update', $course);

        $data              = $request->validated();
        $data['course_id'] = $courseId;

        if (empty($data['order'])) {
            $data['order'] = $this->units->getByCourse($courseId)->count() + 1;
        }

        $unit = $this->units->create($data);

        return redirect()->route('teacher.units.show', $unit->id)->with('success', 'Unit added.');
    }

    public function show(int $id)
    {
        $unit = $this->units->find($id);
        abort_if(is_null($unit), 404);
        $this->authorize('update', $unit);

        return view('units.show', [
            'unit'         => $unit,
            'layout'       => 'layouts.teacher',
            'updateRoute'  => route('teacher.units.update', $id),
            'destroyRoute' => route('teacher.units.destroy', $id),
            'backRoute'    => route('teacher.courses.show', $unit->course_id),
        ]);
    }

    public function update(UpdateUnitRequest $request, int $id)
    {
        $unit = $this->units->find($id);
        abort_if(is_null($unit), 404);
        $this->authorize('update', $unit);

        $this->units->update($unit, $request->validated());

        return redirect()->route('teacher.units.show', $id)->with('success', 'Unit updated.');
    }

    public function destroy(int $id)
    {
        $unit = $this->units->find($id);
        abort_if(is_null($unit), 404);
        $this->authorize('delete', $unit);

        $courseId = $unit->course_id;
        $this->units->delete($unit);

        return redirect()->route('teacher.courses.show', $courseId)->with('success', 'Unit deleted.');
    }

    public function reorder(Request $request, int $courseId)
    {
        $course = $this->courses->find($courseId);
        $this->authorize('update', $course);

        $this->units->reorder($courseId, $request->input('order', []));

        return response()->json(['success' => true]);
    }
}
