<?php

namespace App\Repositories\Eloquent;

use App\Models\Course;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentCourseRepository implements CourseRepositoryInterface
{
    public function find(int $id): ?Course
    {
        return Course::find($id);
    }

    public function create(array $data): Course
    {
        return Course::create($data);
    }

    public function update(Course $course, array $data): Course
    {
        $course->update($data);
        return $course->fresh();
    }

    public function delete(Course $course): bool
    {
        return $course->delete();
    }

    public function getByTeacher(int $teacherId): Collection
    {
        return Course::where('teacher_id', $teacherId)->get();
    }

    public function getEnrolledByStudent(int $studentId): Collection
    {
        return Course::whereHas(
            'students',
            fn($q) => $q->where('student_id', $studentId)
                ->where('course_student.is_active', true)
        )->where('is_published', true)->get();
    }

    public function getByGroup(int $groupId): Collection
    {
        return Course::where('group_id', $groupId)->get();
    }
    
    public function getAll(): Collection
    {
        return Course::with('teacher')->get();
    }

    public function countPublished(): int
    {
        return Course::where('is_published', true)->count();
    }
}