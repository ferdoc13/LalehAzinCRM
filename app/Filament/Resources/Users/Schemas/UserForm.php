<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\StaffRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('نام')
                    ->placeholder('مثلاً سارا احمدی')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('ایمیل')
                    ->placeholder('user@example.com')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('role')
                    ->label('نقش')
                    ->options(StaffRole::class)
                    ->default(StaffRole::Employee)
                    ->required(),
                TextInput::make('password')
                    ->label('رمز عبور')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->confirmed()
                    ->helperText(fn (string $operation): ?string => $operation === 'edit'
                        ? 'در صورت خالی بودن، رمز عبور تغییر نمی‌کند.'
                        : null),
                TextInput::make('password_confirmation')
                    ->label('تکرار رمز عبور')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false),
            ]);
    }
}
