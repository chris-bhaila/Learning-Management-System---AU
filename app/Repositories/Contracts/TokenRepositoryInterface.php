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
}