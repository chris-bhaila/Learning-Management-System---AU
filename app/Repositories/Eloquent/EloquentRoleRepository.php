<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentRoleRepository implements RoleRepositoryInterface
{
    public function findByName(string $name): ?Role
    {
        return Role::where('name', $name)->first();
    }

    public function all(): Collection
    {
        return Role::all();
    }
}