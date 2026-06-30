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
    private const CHARSET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private static function randomToken(int $length): string
    {
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= self::CHARSET[random_int(0, strlen(self::CHARSET) - 1)];
        }
        return $out;
    }

    public function definition(): array
    {
        return [
            'teacher_id'  => User::factory()->teacher(),
            'course_id'   => null,
            'token_value' => self::randomToken(9),
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
            'token_value' => self::randomToken(6),
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
