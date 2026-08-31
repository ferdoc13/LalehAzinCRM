<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerCompanyProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerCompanyProfile>
 */
class CustomerCompanyProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'company_name' => fake()->company(),
            'national_id' => fake()->unique()->numerify('###########'),
            'economic_code' => fake()->numerify('############'),
            'company_address' => fake()->address(),
        ];
    }
}
