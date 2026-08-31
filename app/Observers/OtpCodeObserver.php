<?php

namespace App\Observers;

use App\Models\OtpCode;
use App\Notifications\OtpCodeNotification;

class OtpCodeObserver
{
    public function created(OtpCode $otpCode): void
    {
        if ($otpCode->is_used || $otpCode->isExpired()) {
            return;
        }

        $customer = $otpCode->customer;

        if (! $customer) {
            return;
        }

        $customer->notify(new OtpCodeNotification($otpCode));
    }
}
