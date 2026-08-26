<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DiscountRequestStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Edited = 'edited';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار بررسی',
            self::Approved => 'تأیید شده',
            self::Edited => 'ویرایش شده',
            self::Rejected => 'رد شده',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Edited => 'info',
            self::Rejected => 'danger',
        };
    }
}
