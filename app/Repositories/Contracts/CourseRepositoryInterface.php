<?php

namespace App\Repositories\Contracts;

use App\Models\Course;
use Illuminate\Database\Eloquent\Collection;

interface CourseRepositoryInterface
{
    public function find(int $id): ?Course;
    public function findWithRelations(int $id): ?Course;

    /** Same as findWithRelations() but never eager-loads 'group' — CourseGroups must
     *  never reach a student-facing response at all, not just be hidden in the view.
     *  Use this for every Student\CourseController lookup instead of the generic one. */
    public function findWithRelationsForStudent(int $id): ?Course;
    public function create(array $data): Course;
    public function update(Course $course, array $data): Course;
    public function delete(Course $course): bool;
    public function getByTeacher(int $teacherId): Collection;
    public function getRecentByTeacher(int $teacherId, int $limit = 5): Collection;
    public function getEnrolledByStudent(int $studentId): Collection;
    public function getByGroup(int $groupId): Collection;
    public function getAll(): Collection;
    public function countPublished(): int;

    /** Courses belonging to $teacherId that $studentId is enrolled in, with enrollment pivot. */
    public function getStudentCoursesForTeacher(int $studentId, int $teacherId): Collection;

    /** Published courses belonging to $teacherId that $studentId is actively enrolled in (student-facing). */
    public function getEnrolledByStudentForTeacher(int $studentId, int $teacherId): Collection;

    /** Deactivates (never deletes) the course_student row for this course/student pair. */
    public function removeStudentFromCourse(int $courseId, int $studentId): void;
}