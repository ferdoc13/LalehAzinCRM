<?php

namespace App\Enums;

enum SmsEventType: string
{
    case Otp = 'otp';
    case Invoice = 'invoice';
    case Discount = 'discount';
    case Withdrawal = 'withdrawal';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Otp => 'کد یکبار مصرف',
            self::Invoice => 'فاکتور',
            self::Discount => 'تخفیف',
            self::Withdrawal => 'برداشت',
            self::General => 'عمومی',
        };
    }
}
