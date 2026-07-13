<?php

namespace Database\Factories;

use App\Models\Token;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Token>
 */
class TokenFactory extends Factory
{
    private const LETTERS = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz';
    private const DIGITS  = '23456789';

    // Mirrors EloquentTokenRepository::generateUniqueValue() — at least 40%, at most
    // 50% of characters are digits, positions shuffled so it's not predictable.
    private static function randomToken(int $length): string
    {
        $minDigits  = (int) ceil($length * 0.4);
        $maxDigits  = (int) floor($length * 0.5);
        $digitCount = max($minDigits, min($maxDigits, (int) round($length * 0.45)));

        $chars = [];
        for ($i = 0; $i < $digitCount; $i++) {
            $chars[] = self::DIGITS[random_int(0, strlen(self::DIGITS) - 1)];
        }
        for ($i = 0; $i < $length - $digitCount; $i++) {
            $chars[] = self::LETTERS[random_int(0, strlen(self::LETTERS) - 1)];
        }

        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }

    public function definition(): array
    {
        return [
            'teacher_id'  => User::factory()->teacher(),
            'course_id'   => null,
            'token_value' => self::randomToken(11),
            'type'        => 'class',
            'expires_at'  => now()->addHour(),
            'max_uses'    => 30,
            'uses_count'  => 0,
        ];
    }

    public function forCourse(\App\Models\Course $course): static
    {
        return $this->state(fn (array $attributes) => [
            'teacher_id'  => $course->teacher_id,
            'course_id'   => $course->id,
            'token_value' => self::randomToken(9),
            'type'        => 'course',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    public function exhausted(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_uses'    => 1,
            'uses_count'  => 1,
        ]);
    }
}
