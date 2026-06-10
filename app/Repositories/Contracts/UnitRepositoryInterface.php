<?php

namespace App\Repositories\Contracts;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Collection;

interface UnitRepositoryInterface
{
    public function find(int $id): ?Unit;
    public function create(array $data): Unit;
    public function update(Unit $unit, array $data): Unit;
    public function delete(Unit $unit): bool;
    public function getByCourse(int $courseId): Collection;
    public function reorder(int $courseId, array $orderedIds): void;
}