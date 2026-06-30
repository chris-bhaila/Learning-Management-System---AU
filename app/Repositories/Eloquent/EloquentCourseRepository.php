<?php

namespace App\Repositories\Eloquent;

use App\Models\Course;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Mews\Purifier\Facades\Purifier;

class EloquentCourseRepository implements CourseRepositoryInterface
{
    public function find(int $id): ?Course
    {
        return Course::find($id);
    }

    public function findWithRelations(int $id): ?Course
    {
        return Course::with([
            'teacher',
            'group',
            'units',
            'files',
            'tokens'   => fn($q) => $q->where('type', 'course')->latest(),
            'students' => fn($q) => $q->wherePivot('is_active', true)->orderBy('name'),
        ])->find($id);
    }

    public function create(array $data): Course
    {
        if (array_key_exists('description', $data)) {
            $data['description'] = Purifier::clean($data['description'] ?? '');
        }

        return Course::create($data);
    }

    public function update(Course $course, array $data): Course
    {
        if (array_key_exists('description', $data)) {
            $data['description'] = Purifier::clean($data['description'] ?? '');
        }

        $course->update($data);

        return $course->fresh();
    }

    public function delete(Course $course): bool
    {
        return $course->delete();
    }

    public function getByTeacher(int $teacherId): Collection
    {
        return Course::where('teacher_id', $teacherId)
            ->withCount(['units', 'students'])
            ->with('group')
            ->get();
    }

    public function getRecentByTeacher(int $teacherId, int $limit = 5): Collection
    {
        return Course::where('teacher_id', $teacherId)
            ->withCount(['units', 'students'])
            ->with('group')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function getEnrolledByStudent(int $studentId): Collection
    {
        return Course::whereHas(
            'students',
            fn($q) => $q->where('student_id', $studentId)
                ->where('course_student.is_active', true)
        )
        ->where('is_published', true)
        ->with('teacher')
        ->withCount('units')
        ->get();
    }

    public function getByGroup(int $groupId): Collection
    {
        return Course::where('group_id', $groupId)->get();
    }
    
    public function getAll(): Collection
    {
        return Course::with('teacher')->withCount(['units', 'students'])->get();
    }

    public function countPublished(): int
    {
        return Course::where('is_published', true)->count();
    }

    public function getStudentCoursesForTeacher(int $studentId, int $teacherId): Collection
    {
        return Course::where('teacher_id', $teacherId)
            ->whereHas('students', fn($q) => $q->where('student_id', $studentId))
            ->with([
                'group',
                'students' => fn($q) => $q->where('users.id', $studentId)
                                           ->withPivot(['is_active', 'enrolled_at']),
            ])
            ->withCount('units')
            ->orderBy('title')
            ->get();
    }
}