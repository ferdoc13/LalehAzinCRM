<?php

namespace App\Notifications;

use App\Enums\SmsEventType;
use App\Models\Customer;
use App\Notifications\Messages\MelipayamakMessage;

class CustomerRegisteredNotification extends MelipayamakNotification
{
    public function toMelipayamak(object $notifiable): MelipayamakMessage
    {
        $firstName = $notifiable instanceof Customer ? $notifiable->first_name : '';
        $lastName = $notifiable instanceof Customer ? $notifiable->last_name : '';

        return new MelipayamakMessage(
            eventType: SmsEventType::General,
            patternKey: 'customer_registered',
            params: [$firstName, $lastName],
            text: $this->fallbackText('customer_registered', [
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]),
        );
    }
}
