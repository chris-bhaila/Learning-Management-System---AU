<?php

namespace App\Repositories\Contracts;

use App\Models\Token;
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

    /** Generate a collision-safe token value for the given type ('class'=9 chars, 'course'=6 chars). */
    public function generateUniqueValue(string $type): string;
}