<?php

namespace App\Services;

use App\Enums\CreditTransactionType;
use App\Exceptions\InsufficientCreditException;
use App\Models\Customer;
use App\Models\CustomerCreditLedger;
use App\Models\DiscountRequest;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CustomerCreditService
{
    public function getBalance(Customer $customer): float
    {
        $latest = $customer->creditLedgers()
            ->reorder()
            ->orderByDesc('id')
            ->first();

        return round((float) ($latest?->balance_after ?? 0), 2);
    }

    public function addCredit(
        Customer $customer,
        float $amount,
        string $description,
        ?DiscountRequest $discountRequest = null,
        ?Invoice $invoice = null,
    ): CustomerCreditLedger {
        $this->assertPositiveAmount($amount);

        return $this->record(
            customer: $customer,
            amount: $amount,
            type: CreditTransactionType::Credit,
            description: $description,
            discountRequest: $discountRequest,
            invoice: $invoice,
        );
    }

    public function deductCredit(
        Customer $customer,
        float $amount,
        string $description,
        ?DiscountRequest $discountRequest = null,
        ?Invoice $invoice = null,
    ): CustomerCreditLedger {
        $this->assertPositiveAmount($amount);

        return $this->record(
            customer: $customer,
            amount: $amount,
            type: CreditTransactionType::Debit,
            description: $description,
            discountRequest: $discountRequest,
            invoice: $invoice,
        );
    }

    public function applyAvailableCreditToInvoice(Invoice $invoice): ?CustomerCreditLedger
    {
        return DB::transaction(function () use ($invoice): ?CustomerCreditLedger {
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $customer = Customer::query()->whereKey($invoice->customer_id)->lockForUpdate()->firstOrFail();
            $itemsTotal = round((float) $invoice->items()->sum('total_amount'), 2);
            $balance = $this->getBalance($customer);
            $discount = round(min($balance, $itemsTotal), 2);

            if ($discount <= 0) {
                return null;
            }

            $ledger = $this->deductCredit(
                customer: $customer,
                amount: $discount,
                description: "اعمال تخفیف روی فاکتور {$invoice->invoice_number}",
                invoice: $invoice,
            );

            $invoice->update([
                'discount_amount' => $discount,
                'total_amount' => round($itemsTotal - $discount, 2),
            ]);

            return $ledger;
        });
    }

    private function record(
        Customer $customer,
        float $amount,
        CreditTransactionType $type,
        string $description,
        ?DiscountRequest $discountRequest,
        ?Invoice $invoice,
    ): CustomerCreditLedger {
        return DB::transaction(function () use ($customer, $amount, $type, $description, $discountRequest, $invoice): CustomerCreditLedger {
            Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();

            $latest = CustomerCreditLedger::query()
                ->where('customer_id', $customer->id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $currentBalance = round((float) ($latest?->balance_after ?? 0), 2);

            if ($type === CreditTransactionType::Debit && $amount > $currentBalance) {
                throw new InsufficientCreditException($amount, $currentBalance);
            }

            $balanceAfter = $type === CreditTransactionType::Credit
                ? round($currentBalance + $amount, 2)
                : round($currentBalance - $amount, 2);

            return CustomerCreditLedger::query()->create([
                'customer_id' => $customer->id,
                'discount_request_id' => $discountRequest?->id,
                'invoice_id' => $invoice?->id,
                'amount' => $amount,
                'transaction_type' => $type,
                'description' => $description,
                'balance_after' => $balanceAfter,
            ]);
        });
    }

    private function assertPositiveAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('مبلغ تراکنش اعتباری باید بزرگ‌تر از صفر باشد.');
        }
    }
}
