<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    public function view(User $user, Unit $unit): bool
    {
        if ($user->isAdmin() || ($user->isTeacher() && $unit->course->teacher_id === $user->id)) {
            return true;
        }

        return $user->enrolledCourses()
            ->where('course_id', $unit->course_id)
            ->where('is_active', true)
            ->exists();
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->isAdmin() || ($user->isTeacher() && $unit->course->teacher_id === $user->id);
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->isAdmin() || ($user->isTeacher() && $unit->course->teacher_id === $user->id);
    }
}