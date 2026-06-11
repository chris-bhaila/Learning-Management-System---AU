<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUnitRequest;
use App\Repositories\Contracts\UnitRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;

class UnitController extends Controller
{
    public function __construct(
        private UnitRepositoryInterface $units,
        private CourseRepositoryInterface $courses,
    ) {}

    public function show(int $id)
    {
        return view('admin.units.show', [
            'unit' => $this->units->find($id),
        ]);
    }

    public function edit(int $id)
    {
        return view('admin.units.edit', [
            'unit' => $this->units->find($id),
        ]);
    }

    public function update(UpdateUnitRequest $request, int $id)
    {
        $unit = $this->units->find($id);
        $this->units->update($unit, $request->validated());

        return back()->with('success', 'Unit updated.');
    }

    public function destroy(int $id)
    {
        $unit = $this->units->find($id);
        $course = $unit->course;
        $this->units->delete($unit);

        return redirect()->route('admin.courses.show', $course)->with('success', 'Unit deleted.');
    }
}