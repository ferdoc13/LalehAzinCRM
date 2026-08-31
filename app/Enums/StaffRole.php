<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StaffRole: string implements HasColor, HasLabel
{
    case Employee = 'employee';
    case Manager = 'manager';
    case Admin = 'admin';

    public function getLabel(): string
    {
        return match ($this) {
            self::Employee => 'کارمند',
            self::Manager => 'مدیر',
            self::Admin => 'ادمین',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Employee => 'gray',
            self::Manager => 'warning',
            self::Admin => 'danger',
        };
    }

    public static function fromForm(mixed $role): self
    {
        if ($role instanceof self) {
            return $role;
        }

        return self::tryFrom((string) $role) ?? self::Employee;
    }
}
