<?php

namespace App\Enums;

enum SmsSendStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در صف ارسال',
            self::Sent => 'ارسال شده',
            self::Failed => 'ناموفق',
        };
    }
}
