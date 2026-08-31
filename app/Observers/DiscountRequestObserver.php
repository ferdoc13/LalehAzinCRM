<?php

namespace App\Observers;

use App\Enums\DiscountRequestStatus;
use App\Models\DiscountRequest;
use App\Notifications\DiscountRequestCreatedNotification;
use App\Notifications\DiscountReviewedNotification;
use Illuminate\Support\Facades\Notification;

class DiscountRequestObserver
{
    public function created(DiscountRequest $discountRequest): void
    {
        if ($discountRequest->status !== DiscountRequestStatus::Pending) {
            return;
        }

        foreach (config('sms.manager_mobiles', []) as $mobile) {
            if (blank($mobile)) {
                continue;
            }

            Notification::route('melipayamak', $mobile)
                ->notify(new DiscountRequestCreatedNotification($discountRequest));
        }
    }

    public function updated(DiscountRequest $discountRequest): void
    {
        if (! $discountRequest->wasChanged('status')) {
            return;
        }

        if (! in_array($discountRequest->status, [
            DiscountRequestStatus::Approved,
            DiscountRequestStatus::Edited,
            DiscountRequestStatus::Rejected,
        ], true)) {
            return;
        }

        $discountRequest->loadMissing('customer');
        $discountRequest->customer?->notify(new DiscountReviewedNotification($discountRequest));
    }
}
