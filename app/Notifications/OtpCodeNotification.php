<?php

namespace App\Notifications;

use App\Enums\SmsEventType;
use App\Models\OtpCode;
use App\Notifications\Messages\MelipayamakMessage;

class OtpCodeNotification extends MelipayamakNotification
{
    public function __construct(public OtpCode $otpCode) {}

    public function toMelipayamak(object $notifiable): MelipayamakMessage
    {
        $code = $this->otpCode->code;

        return new MelipayamakMessage(
            eventType: SmsEventType::Otp,
            text: $this->messageText('otp', ['code' => $code]),
        );
    }
}
