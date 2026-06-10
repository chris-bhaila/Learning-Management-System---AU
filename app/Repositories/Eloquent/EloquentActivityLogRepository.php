<?php

namespace App\Repositories\Eloquent;

use App\Models\ActivityLog;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentActivityLogRepository implements ActivityLogRepositoryInterface
{
    public function create(array $data): ActivityLog
    {
        return ActivityLog::create($data);
    }

    public function getAll(): Collection
    {
        return ActivityLog::with('user')->latest()->get();
    }

    public function getByUser(int $userId): Collection
    {
        return ActivityLog::where('user_id', $userId)->latest()->get();
    }

    public function getForTeacherNotifications(int $teacherId): Collection
    {
        return ActivityLog::whereHas('user', function ($q) use ($teacherId) {
            $q->whereHas('teachers', fn($q) => $q->where('teacher_id', $teacherId));
        })->with('user')->latest()->get();
    }
}