<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerBankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerBankAccount>
 */
class CustomerBankAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'bank_name' => 'بانک '.fake()->company(),
            'account_number' => fake()->numerify('##########'),
            'sheba_number' => 'IR'.fake()->numerify('########################'),
        ];
    }
}
