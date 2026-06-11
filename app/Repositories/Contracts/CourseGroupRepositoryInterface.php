<?php

namespace App\Repositories\Contracts;

use App\Models\CourseGroup;
use Illuminate\Database\Eloquent\Collection;

interface CourseGroupRepositoryInterface
{
    public function find(int $id): ?CourseGroup;
    public function create(array $data): CourseGroup;
    public function update(CourseGroup $group, array $data): CourseGroup;
    public function delete(CourseGroup $group): bool;
    public function getByTeacher(int $teacherId): Collection;
    public function getAll(): Collection;
}