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

    public function incrementUses(Token $token): void
    {
        $token->incrementUses();
    }

    public function getAll(): Collection
    {
        return Token::with('teacher', 'course')->get();
    }

    public function getClassTokensByTeacher(int $teacherId): Collection
    {
        return Token::where('teacher_id', $teacherId)
            ->where('type', 'class')
            ->latest()
            ->get();
    }

    public function getCourseTokensByTeacher(int $teacherId): Collection
    {
        return Token::where('teacher_id', $teacherId)
            ->where('type', 'course')
            ->with('course')
            ->latest()
            ->get();
    }

    public function generateUniqueValue(string $type): string
    {
        $charset = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $length  = $type === 'class' ? 9 : 6;

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $value = '';
            for ($i = 0; $i < $length; $i++) {
                $value .= $charset[random_int(0, strlen($charset) - 1)];
            }
            if (!Token::withTrashed()->where('token_value', $value)->exists()) {
                return $value;
            }
        }

        throw new \RuntimeException('Failed to generate a unique token value after 5 attempts.');
    }
}