<?php

namespace Database\Factories;

use App\Enums\WithdrawalRequestStatus;
use App\Models\Customer;
use App\Models\CustomerBankAccount;
use App\Models\WithdrawalRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WithdrawalRequest>
 */
class WithdrawalRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'credit_ledger_id' => null,
            'discount_request_id' => null,
            'amount' => fake()->randomFloat(2, 100_000, 3_000_000),
            'bank_account_id' => CustomerBankAccount::factory(),
            'status' => WithdrawalRequestStatus::Pending,
        ];
    }
}
