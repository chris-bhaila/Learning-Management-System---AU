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
    public function delete(Token $token): bool;
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
}