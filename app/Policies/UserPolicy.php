<?php

namespace App\Policies;

use App\Models\User;

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
}
