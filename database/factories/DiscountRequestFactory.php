<?php

namespace Database\Factories;

use App\Enums\DiscountRequestStatus;
use App\Models\Customer;
use App\Models\DiscountRequest;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscountRequest>
 */
class DiscountRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id' => null,
            'customer_id' => Customer::factory(),
            'requested_by' => User::factory(),
            'proposed_amount' => fake()->randomFloat(2, 500_000, 10_000_000),
            'final_amount' => null,
            'status' => DiscountRequestStatus::Pending,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (DiscountRequest $discountRequest): void {
            if ($discountRequest->invoice_id) {
                $invoice = Invoice::query()->find($discountRequest->invoice_id);

                if ($invoice) {
                    $discountRequest->customer_id = $invoice->customer_id;
                }

                return;
            }

            $customerId = $discountRequest->customer_id ?? Customer::factory()->create()->id;
            $employeeId = $discountRequest->requested_by
                ?? Customer::query()->find($customerId)?->employee_id
                ?? User::factory()->create()->id;

            $invoice = Invoice::withoutEvents(fn (): Invoice => Invoice::factory()->priced(10_000_000)->create([
                'customer_id' => $customerId,
                'employee_id' => $employeeId,
            ]));

            $discountRequest->invoice_id = $invoice->id;
            $discountRequest->customer_id = $customerId;
        });
    }

    public function approved(User $reviewer): static
    {
        return $this->state(function (array $attributes) use ($reviewer) {
            return [
                'status' => DiscountRequestStatus::Approved,
                'final_amount' => $attributes['proposed_amount'],
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ];
        });
    }

    public function edited(User $reviewer, ?float $finalAmount = null): static
    {
        return $this->state(function (array $attributes) use ($reviewer, $finalAmount) {
            $proposed = $attributes['proposed_amount'];

            return [
                'status' => DiscountRequestStatus::Edited,
                'final_amount' => $finalAmount ?? round($proposed * 0.8, 2),
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ];
        });
    }

    public function rejected(User $reviewer): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DiscountRequestStatus::Rejected,
            'final_amount' => null,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }
}
