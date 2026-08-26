<?php

namespace Database\Factories;

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\Support\PersianFaker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => CustomerType::Individual,
            'first_name' => PersianFaker::firstName(),
            'last_name' => PersianFaker::lastName(),
            'national_code' => PersianFaker::nationalCode(),
            'mobile' => PersianFaker::mobile(),
            'address' => PersianFaker::address(),
            'employee_id' => User::factory(),
        ];
    }

    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CustomerType::Individual,
        ]);
    }

    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CustomerType::Company,
        ])->afterCreating(function (Customer $customer): void {
            CustomerCompanyProfileFactory::new()->create([
                'customer_id' => $customer->id,
            ]);
        });
    }
}
