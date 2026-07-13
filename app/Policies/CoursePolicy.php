<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function view(User $user, Course $course): bool
    {
        if ($user->isAdmin() || ($user->isTeacher() && $course->teacher_id === $user->id)) {
            return true;
        }

        return $user->enrolledCourses()
            ->where('course_id', $course->id)
            ->where('is_active', true)
            ->exists();
    }

    public function update(User $user, Course $course): bool
    {
        return $user->isAdmin() || ($user->isTeacher() && $course->teacher_id === $user->id);
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->isAdmin() || ($user->isTeacher() && $course->teacher_id === $user->id);
    }

    /** Removing a student from a single course — same ownership rule as update/delete. */
    public function removeStudent(User $user, Course $course): bool
    {
        return $user->isAdmin() || ($user->isTeacher() && $course->teacher_id === $user->id);
    }
}