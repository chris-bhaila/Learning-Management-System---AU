<?php

namespace App\Repositories\Contracts;

use App\Models\Token;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TokenRepositoryInterface
{
    public function find(int $id): ?Token;
    public function findByValue(string $value): ?Token;
    public function create(array $data): Token;
    public function getActiveByTeacher(int $teacherId): Collection;
    public function getAll(): Collection;

    /** All class tokens for a teacher, newest first. */
    public function getClassTokensByTeacher(int $teacherId): Collection;

    /** All course tokens for a teacher, newest first, with course eager-loaded. */
    public function getCourseTokensByTeacher(int $teacherId): Collection;

    /** Paginated class tokens for a teacher, newest first. */
    public function getClassTokensByTeacherPaginated(int $teacherId, int $perPage = 20): LengthAwarePaginator;

    /** Paginated course tokens for a teacher, newest first, with course eager-loaded. */
    public function getCourseTokensByTeacherPaginated(int $teacherId, int $perPage = 20): LengthAwarePaginator;

    /** Generate a collision-safe (case-insensitive) mixed-case token value for the given type ('class'=11 chars, 'course'=9 chars), guaranteeing 40-50% of characters are digits. */
    public function generateUniqueValue(string $type): string;

    /** Increments uses_count for a successful enrollment; also logs a permanent expiry
     *  notification (once, ever) if this increment pushes the token to its max_uses. */
    public function incrementUses(Token $token): void;

    /** Tokens whose time limit has passed but haven't had an expiry notification logged
     *  yet — consumed by the tokens:notify-expired scheduled command. */
    public function getExpiredUnnotified(): Collection;

    /** Logs a permanent, self-contained expiry notification for the teacher dashboard
     *  feed ($trigger: 'max_uses' | 'time_limit') and marks the token as notified so
     *  this never fires twice for the same token, regardless of which trigger reaches it. */
    public function logExpiry(Token $token, string $trigger): void;

    /** Soft-revokes a token: sets revoked_at (no longer deletes the row — see
     *  tokens:prune for the eventual, unrelated hard-delete) and logs a permanent,
     *  self-contained revocation notification distinct from natural-expiry notifications. */
    public function revoke(Token $token): void;
}