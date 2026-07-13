<?php

namespace App\Repositories\Eloquent;

use App\Models\Token;
use App\Repositories\Contracts\TokenRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentTokenRepository implements TokenRepositoryInterface
{
    public function find(int $id): ?Token
    {
        return Token::find($id);
    }

    public function findByValue(string $value): ?Token
    {
        return Token::whereRaw('UPPER(token_value) = ?', [strtoupper($value)])->first();
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

    public function getClassTokensByTeacherPaginated(int $teacherId, int $perPage = 20): LengthAwarePaginator
    {
        return Token::where('teacher_id', $teacherId)
            ->where('type', 'class')
            ->latest()
            ->paginate($perPage);
    }

    public function getCourseTokensByTeacherPaginated(int $teacherId, int $perPage = 20): LengthAwarePaginator
    {
        return Token::where('teacher_id', $teacherId)
            ->where('type', 'course')
            ->with('course')
            ->latest()
            ->paginate($perPage);
    }

    public function generateUniqueValue(string $type): string
    {
        $letters = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz';
        $digits  = '23456789';
        $length  = $type === 'class' ? 11 : 9;

        // At least 40%, at most 50% of characters are digits — picked as the single
        // integer digit count satisfying both bounds for the given token length.
        $minDigits  = (int) ceil($length * 0.4);
        $maxDigits  = (int) floor($length * 0.5);
        $digitCount = max($minDigits, min($maxDigits, (int) round($length * 0.45)));

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $chars = [];
            for ($i = 0; $i < $digitCount; $i++) {
                $chars[] = $digits[random_int(0, strlen($digits) - 1)];
            }
            for ($i = 0; $i < $length - $digitCount; $i++) {
                $chars[] = $letters[random_int(0, strlen($letters) - 1)];
            }

            // Fisher–Yates shuffle (random_int, not shuffle()) so digit positions aren't predictable.
            for ($i = count($chars) - 1; $i > 0; $i--) {
                $j = random_int(0, $i);
                [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
            }

            $value = implode('', $chars);

            if (!Token::whereRaw('UPPER(token_value) = ?', [strtoupper($value)])->exists()) {
                return $value;
            }
        }

        throw new \RuntimeException('Failed to generate a unique token value after 5 attempts.');
    }
}