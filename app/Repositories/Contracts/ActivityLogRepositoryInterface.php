<?php

namespace App\Repositories\Contracts;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Collection;

interface ActivityLogRepositoryInterface
{
    public function create(array $data): ActivityLog;
    public function getAll(): Collection;
    public function getByUser(int $userId): Collection;
    public function getForTeacherNotifications(int $teacherId): Collection;
}