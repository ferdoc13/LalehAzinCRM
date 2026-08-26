<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerBankAccount;
use Database\Seeders\Support\PersianFaker;
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
            'bank_name' => PersianFaker::bankName(),
            'account_number' => PersianFaker::accountNumber(),
            'sheba_number' => PersianFaker::shebaNumber(),
        ];
    }
}
