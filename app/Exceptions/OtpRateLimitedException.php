<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

class OtpRateLimitedException extends Exception implements ShouldntReport
{
    public function __construct(public readonly int $secondsUntilAvailable)
    {
        parent::__construct('تعداد تلاش‌ها بیش از حد مجاز است.');
    }

    public function minutesUntilAvailable(): int
    {
        return (int) ceil($this->secondsUntilAvailable / 60);
    }
}
