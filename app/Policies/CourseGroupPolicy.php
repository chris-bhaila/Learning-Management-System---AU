<?php

namespace App\Policies;

use App\Models\CourseGroup;
use App\Models\User;

class CourseGroupPolicy
{
    public function update(User $user, CourseGroup $group): bool
    {
        return $user->isAdmin() || ($user->isTeacher() && $group->teacher_id === $user->id);
    }

    public function delete(User $user, CourseGroup $group): bool
    {
        return $user->isAdmin() || ($user->isTeacher() && $group->teacher_id === $user->id);
    }
}