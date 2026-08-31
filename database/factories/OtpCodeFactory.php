<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\OtpCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OtpCode>
 */
class OtpCodeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'phone' => null,
            'code' => fake()->numerify('######'),
            'expires_at' => now()->addMinutes(5),
            'is_used' => false,
        ];
    }

    public function forPhone(string $phone): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => null,
            'phone' => $phone,
        ]);
    }

    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_used' => true,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinutes(10),
        ]);
    }
}
