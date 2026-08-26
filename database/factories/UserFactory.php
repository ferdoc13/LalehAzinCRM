<?php

namespace Database\Factories;

use App\Models\User;
use Database\Seeders\Support\PersianFaker;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $firstName = PersianFaker::firstName();
        $lastName = PersianFaker::lastName();

        return [
            'name' => "{$firstName} {$lastName}",
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function employee(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->assignRole('employee');
        });
    }

    public function manager(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->assignRole('manager');
        });
    }

    public function admin(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->assignRole('admin');
        });
    }
}
