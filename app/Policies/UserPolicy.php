<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserPolicy
{
    public function updateAvatar(User $authUser, User $targetUser): bool
    {
        return $authUser->isAdmin() || $authUser->id === $targetUser->id;
    }

    public function removeAvatar(User $authUser, User $targetUser): bool
    {
        return $authUser->isAdmin() || $authUser->id === $targetUser->id;
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
}
