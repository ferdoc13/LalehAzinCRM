<?php

namespace App\Filament\Resources\DiscountRequests\Schemas;

use App\Filament\Support\CustomerSelect;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DiscountRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                CustomerSelect::make()
                    ->placeholder('جستجوی نام، موبایل یا کد ملی'),
                TextInput::make('proposed_amount')
                    ->label('مبلغ پیشنهادی')
                    ->placeholder('مبلغ تخفیف پیشنهادی')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('ریال')
                    ->required(),
            ]);
    }
}
