<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public function getFilteredUsers(string $role, string $sort, ?string $search, int $perPage = 20): LengthAwarePaginator
    {
        $query = User::with('role')
            ->whereHas('role', fn($q) => $q->where('name', $role));

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $query->when($sort === 'oldest', fn($q) => $q->oldest())
              ->when($sort === 'az',     fn($q) => $q->orderBy('name'))
              ->when($sort === 'recent' || !in_array($sort, ['oldest', 'az']),
                                         fn($q) => $q->latest());

        return $query->paginate($perPage)->withQueryString();
    }

    public function getRecent(int $limit = 10): Collection
    {
        return User::with('role')->latest()->limit($limit)->get();
    }

    public function getRoleCounts(): array
    {
        return User::selectRaw('roles.name as role_name, count(*) as total')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->whereNull('users.deleted_at')
            ->groupBy('roles.name')
            ->pluck('total', 'role_name')
            ->toArray();
    }
}