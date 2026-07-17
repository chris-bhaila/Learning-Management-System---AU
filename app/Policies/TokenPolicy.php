<?php

namespace App\Policies;

use App\Models\Token;
use App\Models\User;

class TokenPolicy
{
    public function revoke(User $user, Token $token): bool
    {
        return $user->isAdmin() || ($user->isTeacher() && $token->teacher_id === $user->id);
    }
}