<?php

namespace App\Actions;

use App\Enums\DiscountRequestStatus;
use App\Models\DiscountRequest;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Validation\ValidationException;

class CreateDiscountRequest
{
    public function handle(Invoice $invoice, User $requester, float $proposedAmount): DiscountRequest
    {
        return DB::transaction(function () use ($invoice, $requester, $proposedAmount): DiscountRequest {
            $invoice = Invoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            $pendingExists = DiscountRequest::query()
                ->where('invoice_id', $invoice->id)
                ->where('status', DiscountRequestStatus::Pending)
                ->lockForUpdate()
                ->exists();

            if ($pendingExists) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'برای این فاکتور یک درخواست تخفیف در انتظار بررسی وجود دارد.',
                ]);
            }

            $maxAmount = $invoice->itemsTotal();

            if ($proposedAmount <= 0 || $proposedAmount > $maxAmount) {
                throw ValidationException::withMessages([
                    'proposed_amount' => 'مبلغ تخفیف باید بزرگ‌تر از صفر و حداکثر '.Number::format($maxAmount, precision: 0).' ریال باشد.',
                ]);
            }

            return DiscountRequest::query()->create([
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'requested_by' => $requester->id,
                'proposed_amount' => $proposedAmount,
                'final_amount' => null,
                'status' => DiscountRequestStatus::Pending,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
        });
    }
}
