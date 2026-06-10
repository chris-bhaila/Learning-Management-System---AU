<?php

namespace App\Repositories\Eloquent;

use App\Models\NotificationRead;
use App\Repositories\Contracts\NotificationReadRepositoryInterface;

class EloquentNotificationReadRepository implements NotificationReadRepositoryInterface
{
    public function markAsRead(int $userId, int $logId): NotificationRead
    {
        return NotificationRead::firstOrCreate([
            'user_id' => $userId,
            'log_id'  => $logId,
        ]);
    }

    public function markAllAsRead(int $userId): void
    {
        $unread = \App\Models\ActivityLog::whereDoesntHave('notificationReads', fn($q) =>
            $q->where('user_id', $userId)
        )->pluck('id');

        $records = $unread->map(fn($logId) => [
            'user_id' => $userId,
            'log_id'  => $logId,
            'read_at' => now(),
        ])->toArray();

        NotificationRead::insertOrIgnore($records);
    }

    public function isRead(int $userId, int $logId): bool
    {
        return NotificationRead::where('user_id', $userId)
            ->where('log_id', $logId)
            ->exists();
    }

    public function getUnreadCount(int $userId): int
    {
        return \App\Models\ActivityLog::whereDoesntHave('notificationReads', fn($q) =>
            $q->where('user_id', $userId)
        )->count();
    }
}