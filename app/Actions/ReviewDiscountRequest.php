<?php

namespace App\Actions;

use App\Enums\DiscountRequestStatus;
use App\Exceptions\DiscountRequestAlreadyReviewedException;
use App\Models\DiscountRequest;
use App\Models\User;
use App\Services\CustomerCreditService;
use Illuminate\Support\Facades\DB;

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
}
