<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $passwordHash = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            // Pre-hashed (and cached) so creating many users stays fast; the
            // `hashed` cast leaves an already-hashed value alone.
            'password' => static::$passwordHash ??= Hash::make('password'),
            'role' => User::ROLE_MEMBER,
            'email_verified_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_ADMIN]);
    }

    public function withPassword(string $password): static
    {
        return $this->state(fn () => ['password' => Hash::make($password)]);
    }
}
