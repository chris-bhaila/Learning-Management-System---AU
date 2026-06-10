<?php

namespace App\Repositories\Contracts;

use App\Models\NotificationRead;

interface NotificationReadRepositoryInterface
{
    public function markAsRead(int $userId, int $logId): NotificationRead;
    public function markAllAsRead(int $userId): void;
    public function isRead(int $userId, int $logId): bool;
    public function getUnreadCount(int $userId): int;
}