<?php

namespace App\Notifications;

use App\Enums\SmsEventType;
use App\Models\DiscountRequest;
use App\Notifications\Messages\MelipayamakMessage;

class DiscountRequestCreatedNotification extends MelipayamakNotification
{
    public function __construct(public DiscountRequest $discountRequest) {}

    public function toMelipayamak(object $notifiable): MelipayamakMessage
    {
        $request = $this->discountRequest->fresh() ?? $this->discountRequest;
        $request->loadMissing('customer');
        $customerName = $request->customer?->full_name ?? '';
        $amount = $this->formatAmount($request->proposed_amount);

        return new MelipayamakMessage(
            eventType: SmsEventType::Discount,
            patternKey: 'discount_request_created',
            params: [$customerName, $amount],
            text: $this->fallbackText('discount_request_created', [
                'customer_name' => $customerName,
                'proposed_amount' => $amount,
            ]),
        );
    }
}
