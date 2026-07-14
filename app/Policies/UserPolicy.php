<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserPolicy
{
    /** Generic "edit this user" gate for Admin\UserController::update() — the Users
     *  management page. A Super Admin may never edit *another* Super Admin's account
     *  through this endpoint (their own row is exempt, though self-service profile edits
     *  normally go through /settings, not this admin-management endpoint). Mirrors
     *  demoteAdmin()'s "a super_admin is never actionable through this path" rule,
     *  scoped to other accounts only. */
    public function update(User $authUser, User $targetUser): bool
    {
        if ($targetUser->isSuperAdmin() && $targetUser->id !== $authUser->id) {
            return false;
        }

        return $authUser->isAdmin();
    }

    /** Generic "delete this user" gate for Admin\UserController::destroy(). Same rule as
     *  update() — another Super Admin's account can never be deleted through this
     *  endpoint; a Super Admin's own row is exempt (not that self-deletion is a good
     *  idea, but that's an existing, unrelated risk this task doesn't ask to change). */
    public function delete(User $authUser, User $targetUser): bool
    {
        if ($targetUser->isSuperAdmin() && $targetUser->id !== $authUser->id) {
            return false;
        }

        return $authUser->isAdmin();
    }

    public function updateAvatar(User $authUser, User $targetUser): bool
    {
        return $authUser->isAdmin() || $authUser->id === $targetUser->id;
    }

    public function removeAvatar(User $authUser, User $targetUser): bool
    {
        return $authUser->isAdmin() || $authUser->id === $targetUser->id;
    }

    /** Single source of truth for "who can grant/assign a given role" — used both by
     *  promoteToAdmin() (granting admin to an existing user) and by the create-user flow
     *  (creating a brand-new user directly as admin), so the "who can grant admin" rule
     *  lives in exactly one place. Only 'admin' is restricted; teacher/student remain
     *  assignable by any Admin-or-above, unchanged from prior behavior. */
    public function canAssignRole(User $authUser, string $role): bool
    {
        if ($role !== 'admin') {
            return $authUser->isAdmin();
        }

        return $authUser->isSuperAdmin();
    }

    /** Exclusive to Super Admin — grants the admin role to a teacher/student. Not visible
     *  to, or usable by, a regular Admin; this is the one differentiator between the roles. */
    public function promoteToAdmin(User $authUser, User $targetUser): bool
    {
        return $this->canAssignRole($authUser, 'admin') && !$targetUser->isAdmin();
    }

    /** Exclusive to Super Admin — demotes an existing admin down to teacher or student,
     *  the target role picked at demotion time. Mirrors promoteToAdmin() but in the
     *  opposite direction and with a different allowed-target-role set, so it is not
     *  expressed via canAssignRole() (that method only answers "who may grant role X",
     *  not "who may move an admin down to X"). $targetUser->role must be exactly
     *  'admin' — a super_admin is never demotable through this or any other path. */
    public function demoteAdmin(User $authUser, User $targetUser, string $newRole): bool
    {
        return $authUser->isSuperAdmin()
            && $targetUser->role?->name === 'admin'
            && in_array($newRole, ['teacher', 'student'], true);
    }

    /** A teacher may only view a student profile if that student is in their class. */
    public function viewProfile(User $authUser, User $targetUser): bool
    {
        if ($authUser->isAdmin()) {
            return true;
        }

        if ($authUser->isTeacher()) {
            return DB::table('teacher_student')
                ->where('teacher_id', $authUser->id)
                ->where('student_id', $targetUser->id)
                ->exists();
        }

        return false;
    }

    /** Kicking a student from a class — $teacherId is explicit (not always $authUser->id)
     *  because Admin acts on a specific teacher's class from a route-scoped pair, while a
     *  Teacher may only ever act on their own. Requires the relationship to be currently
     *  active — kicking someone not currently in the class doesn't make sense. */
    public function kickFromClass(User $authUser, User $targetStudent, int $teacherId): bool
    {
        if ($authUser->isAdmin()) {
            return true;
        }

        if ($authUser->isTeacher() && $authUser->id === $teacherId) {
            return DB::table('teacher_student')
                ->where('teacher_id', $teacherId)
                ->where('student_id', $targetStudent->id)
                ->where('is_active', true)
                ->exists();
        }

        return false;
    }
}
