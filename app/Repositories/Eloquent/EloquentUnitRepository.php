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

        // StoreUnitRequest doesn't accept is_published at all, so this explicit default
        // mirrors the DB column's own default(true) — meaning a newly created unit is
        // published (visible to students) immediately, unless a teacher separately drafts
        // it via a later edit. Set explicitly, not left to the DB: Eloquent's in-memory
        // model after create() only reflects attributes it was given, not DB-applied
        // defaults, so checking $unit->is_published right after would otherwise read
        // null (not true) even though the actual row correctly got 1.
        $data['is_published'] ??= true;

        $unit = Unit::create($data);

        // This IS the "becomes visible" moment for the common case.
        if ($unit->is_published) {
            $this->logPublished($unit);
        }

        return $unit;
    }

    public function update(Unit $unit, array $data): Unit
    {
        $wasPublished = $unit->is_published;

        if (array_key_exists('content', $data)) {
            $data['content'] = Purifier::clean($data['content'] ?? '');
        }

        $unit->update($data);

        // Only the false -> true transition is a genuine "becomes visible" event — covers
        // a teacher drafting a unit at creation (by immediately unpublishing it) and
        // republishing it later via this same generic update() flow, since there is no
        // separate publish() action in this app.
        if (! $wasPublished && $unit->is_published) {
            $this->logPublished($unit);
        }

        return $unit->fresh();
    }

    /** Logs a permanent, self-contained snapshot (plain scalars, not live FK lookups) so
     *  the notification stays readable even after the unit/course is later deleted —
     *  same pattern as every other notification-worthy log in this app. */
    private function logPublished(Unit $unit): void
    {
        $unit->loadMissing('course.teacher');

        activity()
            ->withProperties([
                'unit_id'      => $unit->id,
                'unit_name'    => $unit->title,
                'course_id'    => $unit->course_id,
                'course_name'  => $unit->course?->title ?? 'Unknown',
                'teacher_name' => $unit->course?->teacher?->name ?? 'Your teacher',
            ])
            ->log('Unit published');
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
