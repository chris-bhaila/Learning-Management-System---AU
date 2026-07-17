<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Note: role_id/is_active are deliberately excluded from User::$fillable (see the
     * model) — application code can never set them via mass assignment. This factory's
     * teacher()/student() states (and any ad-hoc ->create(['role_id' => ...]) override
     * elsewhere in the test suite) still work unmodified: Laravel's base
     * Factory::makeInstance() already wraps model construction in Model::unguarded(),
     * so factory-created models were never subject to $fillable in the first place.
     * No override needed here — this comment exists so a future $fillable change on
     * this model doesn't get "fixed" by adding one.
     */

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'     => fake()->name(),
            'email'    => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id'   => Role::firstOrCreate(['name' => 'teacher'])->id,
            'is_active' => true,
        ]);
    }

    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id'   => Role::firstOrCreate(['name' => 'student'])->id,
            'is_active' => true,
        ]);
    }
}
