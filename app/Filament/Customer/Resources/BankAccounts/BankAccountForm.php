<?php

namespace App\Filament\Customer\Resources\BankAccounts;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BankAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('bank_name')
                    ->label('نام بانک')
                    ->placeholder('مثلاً بانک ملت')
                    ->required()
                    ->maxLength(255),
                TextInput::make('account_number')
                    ->label('شماره حساب')
                    ->placeholder('شماره حساب بانکی')
                    ->required()
                    ->maxLength(255),
                TextInput::make('sheba_number')
                    ->label('شماره شبا')
                    ->placeholder('IR و ۲۴ رقم')
                    ->required()
                    ->maxLength(26)
                    ->regex('/^IR\d{24}$/')
                    ->validationMessages([
                        'regex' => 'شماره شبا باید با IR و ۲۴ رقم باشد.',
                    ]),
            ]);
    }
}
