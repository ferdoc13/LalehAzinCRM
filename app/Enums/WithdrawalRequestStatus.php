<?php

namespace App\Enums;

enum WithdrawalRequestStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Done = 'done';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار',
            self::Processing => 'در حال پردازش',
            self::Done => 'انجام شده',
            self::Rejected => 'رد شده',
        };
    }
}
