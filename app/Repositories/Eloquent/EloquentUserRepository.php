<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

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
}