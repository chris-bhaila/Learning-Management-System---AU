<?php

namespace App\Repositories\Eloquent;

use App\Models\CourseGroup;
use App\Repositories\Contracts\CourseGroupRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentCourseGroupRepository implements CourseGroupRepositoryInterface
{
    public function find(int $id): ?CourseGroup
    {
        return CourseGroup::find($id);
    }

    public function create(array $data): CourseGroup
    {
        return CourseGroup::create($data);
    }

    public function update(CourseGroup $group, array $data): CourseGroup
    {
        $group->update($data);
        return $group->fresh();
    }

    public function delete(CourseGroup $group): bool
    {
        return $group->delete();
    }

    public function getByTeacher(int $teacherId): Collection
    {
        return CourseGroup::withCount('courses')->where('teacher_id', $teacherId)->get();
    }

    public function getAll(): Collection
    {
        return CourseGroup::with('teacher')->withCount('courses')->get();
    }
}