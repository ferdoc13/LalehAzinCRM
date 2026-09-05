<?php

namespace App\Actions;

use App\Enums\DiscountRequestStatus;
use App\Exceptions\DiscountRequestAlreadyReviewedException;
use App\Models\DiscountRequest;
use App\Models\User;
use App\Services\CustomerCreditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Validation\ValidationException;

class ReviewDiscountRequest
{
    public function __construct(private CustomerCreditService $credits) {}

    public function approve(DiscountRequest $discountRequest, User $reviewer): DiscountRequest
    {
        return $this->complete(
            discountRequest: $discountRequest,
            reviewer: $reviewer,
            status: DiscountRequestStatus::Approved,
            finalAmount: (float) $discountRequest->proposed_amount,
        );
    }

    public function editAndApprove(DiscountRequest $discountRequest, User $reviewer, float $finalAmount): DiscountRequest
    {
        return $this->complete(
            discountRequest: $discountRequest,
            reviewer: $reviewer,
            status: DiscountRequestStatus::Edited,
            finalAmount: $finalAmount,
        );
    }

    public function reject(DiscountRequest $discountRequest, User $reviewer): DiscountRequest
    {
        return DB::transaction(function () use ($discountRequest, $reviewer): DiscountRequest {
            $discountRequest = $this->lockPending($discountRequest);

            $discountRequest->update([
                'status' => DiscountRequestStatus::Rejected,
                'final_amount' => null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            return $discountRequest->refresh();
        });
    }

    private function complete(
        DiscountRequest $discountRequest,
        User $reviewer,
        DiscountRequestStatus $status,
        float $finalAmount,
    ): DiscountRequest {
        return DB::transaction(function () use ($discountRequest, $reviewer, $status, $finalAmount): DiscountRequest {
            $discountRequest = $this->lockPending($discountRequest);
            $discountRequest->loadMissing(['customer', 'invoice.items']);

            $this->assertAmountWithinInvoice($discountRequest, $finalAmount);

            $discountRequest->update([
                'status' => $status,
                'final_amount' => $finalAmount,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            if ($finalAmount > 0) {
                $this->credits->addCredit(
                    customer: $discountRequest->customer,
                    amount: $finalAmount,
                    description: 'اعتبار ناشی از تأیید درخواست تخفیف',
                    discountRequest: $discountRequest,
                    invoice: $discountRequest->invoice,
                );
            }

            return $discountRequest->refresh();
        });
    }

    private function lockPending(DiscountRequest $discountRequest): DiscountRequest
    {
        $discountRequest = DiscountRequest::query()
            ->whereKey($discountRequest->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($discountRequest->status !== DiscountRequestStatus::Pending) {
            throw new DiscountRequestAlreadyReviewedException;
        }

        return $discountRequest;
    }

    private function assertAmountWithinInvoice(DiscountRequest $discountRequest, float $finalAmount): void
    {
        $invoice = $discountRequest->invoice;

        if ($invoice && $discountRequest->customer_id !== $invoice->customer_id) {
            throw ValidationException::withMessages([
                'customer_id' => 'مشتری درخواست تخفیف با مشتری فاکتور یکسان نیست.',
            ]);
        }

        $maxAmount = $invoice?->itemsTotal() ?? 0;

        if ($finalAmount <= 0 || $finalAmount > $maxAmount) {
            throw ValidationException::withMessages([
                'final_amount' => 'مبلغ تخفیف باید بزرگ‌تر از صفر و حداکثر '.Number::format($maxAmount, precision: 0).' ریال باشد.',
            ]);
        }
    }
}
