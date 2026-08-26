<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CreditTransactionType: string implements HasColor, HasLabel
{
    case Credit = 'credit';
    case Debit = 'debit';

    public function getLabel(): string
    {
        return match ($this) {
            self::Credit => 'افزایش اعتبار',
            self::Debit => 'کاهش اعتبار',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Credit => 'success',
            self::Debit => 'danger',
        };
    }
}
