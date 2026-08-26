<?php

namespace Database\Factories;

use App\Enums\InvoicePaymentStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'employee_id' => User::factory(),
            'invoice_number' => 'INV-'.fake()->unique()->numerify('######'),
            'total_amount' => 0,
            'discount_amount' => 0,
            'invoice_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'payment_status' => fake()->randomElement(InvoicePaymentStatus::cases()),
        ];
    }

    public function withItems(int $count = 3): static
    {
        return $this->afterCreating(function (Invoice $invoice) use ($count): void {
            $items = InvoiceItemFactory::new()
                ->count($count)
                ->create(['invoice_id' => $invoice->id]);

            $invoice->update([
                'total_amount' => $items->sum('total_amount'),
            ]);
        });
    }
}
