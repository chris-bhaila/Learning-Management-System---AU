<?php

namespace App\Repositories\Eloquent;

use App\Models\Unit;
use App\Repositories\Contracts\UnitRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Mews\Purifier\Facades\Purifier;

class EloquentUnitRepository implements UnitRepositoryInterface
{
    public function find(int $id): ?Unit
    {
        return Unit::with(['course', 'files'])->find($id);
    }

    public function create(array $data): Unit
    {
        if (array_key_exists('content', $data)) {
            $data['content'] = Purifier::clean($data['content'] ?? '');
        }

        return Unit::create($data);
    }

    public function update(Unit $unit, array $data): Unit
    {
        if (array_key_exists('content', $data)) {
            $data['content'] = Purifier::clean($data['content'] ?? '');
        }

        $unit->update($data);

        return $unit->fresh();
    }

    public function delete(Unit $unit): bool
    {
        return $unit->delete();
    }

    public function getByCourse(int $courseId): Collection
    {
        return Unit::where('course_id', $courseId)->orderBy('order')->get();
    }

    public function reorder(int $courseId, array $orderedIds): void
    {
        foreach ($orderedIds as $order => $id) {
            Unit::where('id', $id)->where('course_id', $courseId)->update(['order' => $order + 1]);
        }
    }
}
