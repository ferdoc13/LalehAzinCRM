<?php

namespace App\Notifications\Messages;

use App\Enums\SmsEventType;

final readonly class MelipayamakMessage
{
    public function __construct(
        public SmsEventType $eventType,
        public string $text,
    ) {}
}
