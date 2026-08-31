<?php

namespace App\Notifications\Messages;

use App\Enums\SmsEventType;

final readonly class MelipayamakMessage
{
    /**
     * @param  array<int|string, scalar>  $params
     */
    public function __construct(
        public SmsEventType $eventType,
        public string $patternKey,
        public array $params = [],
        public bool $isOtp = false,
        public ?string $otpCode = null,
    ) {}
}
