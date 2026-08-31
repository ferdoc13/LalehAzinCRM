<?php

namespace App\Notifications\Channels;

use App\Notifications\Messages\MelipayamakMessage;
use App\Services\MelipayamakService;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;

class MelipayamakChannel
{
    public function __construct(private MelipayamakService $sms) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toMelipayamak')) {
            return;
        }

        $message = $notification->toMelipayamak($notifiable);

        if (! $message instanceof MelipayamakMessage) {
            return;
        }

        $phones = Arr::wrap(
            $notifiable instanceof AnonymousNotifiable
                ? $notifiable->routeNotificationFor('melipayamak')
                : $notifiable->routeNotificationFor('melipayamak', $notification)
        );

        foreach ($phones as $phone) {
            if (blank($phone)) {
                continue;
            }

            $this->sms->send((string) $phone, $message->text, $message->eventType);
        }
    }
}
