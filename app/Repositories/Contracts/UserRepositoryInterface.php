<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function find(int $id): ?User;
    public function findByGoogleId(string $googleId): ?User;
    public function findByEmail(string $email): ?User;
    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function delete(User $user): bool;
    public function getAllStudents(): Collection;
    public function getAllTeachers(): Collection;
    public function updateRole(User $user, int $roleId): User;
    public function getFilteredUsers(string $role, string $sort, ?string $search, ?string $status = null, int $perPage = 20): LengthAwarePaginator;
    public function getRoleCounts(): array;
    public function getRecent(int $limit = 10): Collection;
    public function updateAvatar(User $user, string $path): User;
    public function removeAvatar(User $user): User;
}