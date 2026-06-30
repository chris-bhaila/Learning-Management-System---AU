<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\StoreUnitRequest;
use App\Http\Requests\Shared\UpdateUnitRequest;
use App\Models\Unit;
use App\Repositories\Contracts\UnitRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\FileRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class UnitController extends Controller
{
    public function __construct(
        private UnitRepositoryInterface $units,
        private CourseRepositoryInterface $courses,
        private FileRepositoryInterface $files,
    ) {}

    public function create(int $courseId)
    {
        $course = $this->courses->find($courseId);
        $this->authorize('update', $course);

        return view('units.create', [
            'course'     => $course,
            'layout'     => 'layouts.admin',
            'storeRoute' => route('admin.units.store', $courseId),
            'backRoute'  => route('admin.courses.show', $courseId),
        ]);
    }

    public function store(StoreUnitRequest $request, int $courseId)
    {
        $course = $this->courses->find($courseId);
        $this->authorize('update', $course);

        $data              = $request->safe()->except('files');
        $data['course_id'] = $courseId;

        if (empty($data['order'])) {
            $data['order'] = $this->units->getByCourse($courseId)->count() + 1;
        }

        $unit = $this->units->create($data);

        if ($request->hasFile('files')) {
            $this->files->storeUploads(
                $request->file('files'),
                Unit::class,
                $unit->id,
                Auth::id(),
            );
        }

        return redirect()->route('admin.units.show', $unit->id)->with('success', 'Unit added.');
    }

    public function show(int $id)
    {
        $unit = $this->units->find($id);
        abort_if(is_null($unit), 404);
        $this->authorize('view', $unit);

        return view('units.show', [
            'unit'         => $unit,
            'layout'       => 'layouts.admin',
            'updateRoute'  => route('admin.units.update', $id),
            'destroyRoute' => route('admin.units.destroy', $id),
            'backRoute'    => route('admin.courses.show', $unit->course_id),
        ]);
    }

    public function update(UpdateUnitRequest $request, int $id)
    {
        $unit = $this->units->find($id);
        abort_if(is_null($unit), 404);
        $this->authorize('update', $unit);

        $this->units->update($unit, $request->validated());

        return redirect()->route('admin.units.show', $id)->with('success', 'Unit updated.');
    }

    public function destroy(int $id)
    {
        $unit = $this->units->find($id);
        abort_if(is_null($unit), 404);
        $this->authorize('delete', $unit);

        $courseId = $unit->course_id;
        $this->units->delete($unit);

        return redirect()->route('admin.courses.show', $courseId)->with('success', 'Unit deleted.');
    }
}
