<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;

class FilePolicy
{
    public function download(User $user, File $file): bool
    {
        if ($user->isAdmin() || $file->uploaded_by === $user->id) {
            return true;
        }

        $fileable = $file->fileable;

        $courseId = match (true) {
            $fileable instanceof \App\Models\Course => $fileable->id,
            $fileable instanceof \App\Models\Unit   => $fileable->course_id,
            default                                 => null,
        };

        if (!$courseId) return false;

        return $user->enrolledCourses()
            ->where('course_id', $courseId)
            ->where('is_active', true)
            ->exists();
    }

    public function delete(User $user, File $file): bool
    {
        return $user->isAdmin() || $file->uploaded_by === $user->id;
    }
}