<?php

namespace Database\Factories;

use App\Enums\CreditTransactionType;
use App\Models\Customer;
use App\Models\CustomerCreditLedger;
use App\Models\DiscountRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerCreditLedger>
 */
class CustomerCreditLedgerFactory extends Factory
{
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 100_000, 5_000_000);
        $type = fake()->randomElement(CreditTransactionType::cases());

        return [
            'customer_id' => Customer::factory(),
            'discount_request_id' => null,
            'invoice_id' => null,
            'amount' => $amount,
            'transaction_type' => $type,
            'description' => $type === CreditTransactionType::Credit
                ? 'افزایش اعتبار از درخواست تخفیف'
                : 'برداشت از اعتبار',
            'balance_after' => $amount,
        ];
    }

    public function forDiscountRequest(DiscountRequest $discountRequest): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $discountRequest->customer_id,
            'discount_request_id' => $discountRequest->id,
            'amount' => $discountRequest->final_amount ?? $discountRequest->proposed_amount,
            'transaction_type' => CreditTransactionType::Credit,
            'description' => 'ثبت اعتبار ناشی از تأیید درخواست تخفیف',
        ]);
    }
}
