<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function find(int $id): ?User
    {
        return User::find($id);
    }

    public function findByGoogleId(string $googleId): ?User
    {
        return User::where('google_id', $googleId)->first();
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }

    public function getAllStudents(): Collection
    {
        return User::whereHas('role', fn($q) => $q->where('name', 'student'))->get();
    }

    public function getAllTeachers(): Collection
    {
        return User::whereHas('role', fn($q) => $q->where('name', 'teacher'))->get();
    }

    public function updateRole(User $user, int $roleId): User
    {
        $user->update(['role_id' => $roleId]);
        return $user->fresh();
    }

    public function getFilteredUsers(string $role, string $sort, ?string $search, ?string $status = null, int $perPage = 20, bool $includeSuperAdmins = false): LengthAwarePaginator
    {
        $query = User::with('role')
            ->whereHas('role', function ($q) use ($role, $includeSuperAdmins) {
                if ($role === 'admin' && $includeSuperAdmins) {
                    $q->whereIn('name', ['admin', 'super_admin']);
                } else {
                    $q->where('name', $role);
                }
            });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $query->when($sort === 'oldest', fn($q) => $q->oldest())
              ->when($sort === 'az',     fn($q) => $q->orderBy('name'))
              ->when($sort === 'recent' || !in_array($sort, ['oldest', 'az']),
                                         fn($q) => $q->latest());

        return $query->paginate($perPage)->withQueryString();
    }

    public function getRecent(int $limit = 10): Collection
    {
        return User::with('role')->latest()->limit($limit)->get();
    }

    public function updateAvatar(User $user, string $path): User
    {
        // Delete the previous uploaded file if there was one.
        if ($user->avatar_source === 'upload' && $user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->update([
            'avatar_path'   => $path,
            'avatar_source' => 'upload',
        ]);

        return $user->fresh();
    }

    public function removeAvatar(User $user): User
    {
        if ($user->avatar_source === 'upload' && $user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->update([
            'avatar_path'   => null,
            'avatar_source' => 'none',
        ]);

        return $user->fresh();
    }

    public function getStudentsForTeacher(int $teacherId): Collection
    {
        return User::select(
                'users.*',
                'teacher_student.is_active as class_is_active',
                'teacher_student.enrolled_at as class_enrolled_at'
            )
            ->join('teacher_student', function ($join) use ($teacherId) {
                $join->on('teacher_student.student_id', '=', 'users.id')
                     ->where('teacher_student.teacher_id', '=', $teacherId);
            })
            ->withCount([
                'enrolledCourses as teacher_course_count' => fn($q) =>
                    $q->where('courses.teacher_id', $teacherId),
            ])
            ->orderBy('users.name')
            ->get();
    }

    public function getStudentWithTeacherPivot(int $studentId, int $teacherId): ?User
    {
        return User::select(
                'users.*',
                'teacher_student.is_active as class_is_active',
                'teacher_student.enrolled_at as class_enrolled_at'
            )
            ->join('teacher_student', function ($join) use ($teacherId) {
                $join->on('teacher_student.student_id', '=', 'users.id')
                     ->where('teacher_student.teacher_id', '=', $teacherId);
            })
            ->where('users.id', $studentId)
            ->first();
    }

    public function getTeachersForStudent(int $studentId): Collection
    {
        return User::select(
                'users.*',
                'teacher_student.is_active as class_is_active',
                'teacher_student.enrolled_at as class_enrolled_at'
            )
            ->join('teacher_student', function ($join) use ($studentId) {
                $join->on('teacher_student.teacher_id', '=', 'users.id')
                     ->where('teacher_student.student_id', '=', $studentId)
                     ->where('teacher_student.is_active', true);
            })
            ->withCount([
                'courses as enrolled_course_count' => fn($q) => $q
                    ->where('is_published', true)
                    ->whereHas('students', fn($q2) => $q2
                        ->where('student_id', $studentId)
                        ->where('course_student.is_active', true)
                    ),
            ])
            ->orderBy('users.name')
            ->get();
    }

    public function getTeacherWithStudentPivot(int $teacherId, int $studentId): ?User
    {
        return User::select(
                'users.*',
                'teacher_student.is_active as class_is_active',
                'teacher_student.enrolled_at as class_enrolled_at'
            )
            ->join('teacher_student', function ($join) use ($studentId) {
                $join->on('teacher_student.teacher_id', '=', 'users.id')
                     ->where('teacher_student.student_id', '=', $studentId)
                     ->where('teacher_student.is_active', true);
            })
            ->where('users.id', $teacherId)
            ->first();
    }

    public function kickFromClass(int $teacherId, int $studentId): void
    {
        // Deactivate, never delete — preserves enrollment history/activity log integrity
        // and allows re-enrollment later via a fresh token (see EnrollmentController::store()).
        DB::table('teacher_student')
            ->where('teacher_id', $teacherId)
            ->where('student_id', $studentId)
            ->update(['is_active' => false, 'updated_at' => now()]);

        // Cascade: every course_student row for this student, scoped to THIS teacher's
        // courses only — a student can independently belong to other teachers' classes too
        // (teacher_student has no uniqueness constraint beyond the teacher/student pair
        // itself), so this must never touch courses outside $teacherId's own.
        DB::table('course_student')
            ->where('student_id', $studentId)
            ->whereIn('course_id', function ($query) use ($teacherId) {
                $query->select('id')->from('courses')->where('teacher_id', $teacherId);
            })
            ->update(['is_active' => false, 'updated_at' => now()]);
    }

    public function getRoleCounts(): array
    {
        return User::selectRaw('roles.name as role_name, count(*) as total')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->whereNull('users.deleted_at')
            ->groupBy('roles.name')
            ->pluck('total', 'role_name')
            ->toArray();
    }
}