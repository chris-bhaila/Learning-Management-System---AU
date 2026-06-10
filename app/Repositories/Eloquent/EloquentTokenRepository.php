<?php

namespace App\Repositories\Eloquent;

use App\Models\Token;
use App\Repositories\Contracts\TokenRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentTokenRepository implements TokenRepositoryInterface
{
    public function find(int $id): ?Token
    {
        return Token::find($id);
    }

    public function findByValue(string $value): ?Token
    {
        return Token::where('token_value', $value)->first();
    }

    public function create(array $data): Token
    {
        return Token::create($data);
    }

    public function delete(Token $token): bool
    {
        return $token->delete();
    }

    public function getActiveByTeacher(int $teacherId): Collection
    {
        return Token::where('teacher_id', $teacherId)
            ->where('expires_at', '>', now())
            ->get();
    }
}