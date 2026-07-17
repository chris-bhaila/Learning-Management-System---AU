<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function find(int $id): ?User;
    public function findByGoogleId(string $googleId): ?User;
    public function findByEmail(string $email): ?User;

    /** Soft-deleted rows only, matched by email — used to detect a previously-deleted
     *  account re-signing in via Google so it can be restored cleanly instead of
     *  colliding with the plain (non-partial) unique constraint on users.email. */
    public function findTrashedByEmail(string $email): ?User;

    /** Clears deleted_at and applies $updates in the same call. Deliberately does NOT
     *  touch teacher_student/course_student pivot rows — a restored account must not
     *  silently regain stale enrollments; the student still needs a fresh token. */
    public function restore(User $user, array $updates = []): User;

    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function delete(User $user): bool;
    public function getAllStudents(): Collection;
    public function getAllTeachers(): Collection;
    public function updateRole(User $user, int $roleId): User;
    /** $includeSuperAdmins only has an effect when $role === 'admin' — folds super_admin-role
     *  users into the "admin" tab's result set. Callers must gate this on the viewer being a
     *  Super Admin themselves (see UserController::index()); this method does not check that. */
    public function getFilteredUsers(string $role, string $sort, ?string $search, ?string $status = null, int $perPage = 20, bool $includeSuperAdmins = false): LengthAwarePaginator;
    public function getRoleCounts(): array;
    public function getRecent(int $limit = 10): Collection;
    public function updateAvatar(User $user, string $path): User;
    public function removeAvatar(User $user): User;

    /** All students in a teacher's class, with pivot status and scoped course count. */
    public function getStudentsForTeacher(int $teacherId): Collection;

    /** A single student with teacher_student pivot data, or null if not in the class. */
    public function getStudentWithTeacherPivot(int $studentId, int $teacherId): ?User;

    /** All active teachers whose class a student is enrolled in, with enrolled published course count. */
    public function getTeachersForStudent(int $studentId): Collection;

    /** Returns the teacher if the student has an active class relationship with them, or null. */
    public function getTeacherWithStudentPivot(int $teacherId, int $studentId): ?User;

    /** Deactivates the teacher_student row AND every course_student row for this student
     *  scoped to $teacherId's own courses only — never touches other teachers' enrollments. */
    public function kickFromClass(int $teacherId, int $studentId): void;
}