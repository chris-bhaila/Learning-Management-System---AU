<?php

namespace App\Repositories\Contracts;

use App\Models\Course;
use Illuminate\Database\Eloquent\Collection;

interface CourseRepositoryInterface
{
    public function find(int $id): ?Course;
    public function create(array $data): Course;
    public function update(Course $course, array $data): Course;
    public function delete(Course $course): bool;
    public function getByTeacher(int $teacherId): Collection;
    public function getEnrolledByStudent(int $studentId): Collection;
    public function getByGroup(int $groupId): Collection;
}