<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CreditLedgersRelationManager extends RelationManager
{
    protected static string $relationship = 'creditLedgers';

    protected static ?string $title = 'تاریخچه اعتبار';

    protected static ?string $recordTitleAttribute = 'description';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('created_at')
                    ->label('تاریخ')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('transaction_type')
                    ->label('نوع تراکنش')
                    ->badge(),
                TextColumn::make('amount')
                    ->label('مبلغ')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
                TextColumn::make('balance_after')
                    ->label('موجودی بعد از تراکنش')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
                TextColumn::make('description')
                    ->label('توضیح')
                    ->wrap()
                    ->placeholder('-'),
                TextColumn::make('invoice.invoice_number')
                    ->label('فاکتور')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50]);
    }
}
