<?php

namespace App\Filament\Customer\Resources\BankAccounts;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('bank_name')
            ->columns([
                TextColumn::make('bank_name')
                    ->label('نام بانک')
                    ->searchable(),
                TextColumn::make('account_number')
                    ->label('شماره حساب')
                    ->searchable(),
                TextColumn::make('sheba_number')
                    ->label('شماره شبا')
                    ->searchable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('افزودن حساب بانکی')
                    ->mutateDataUsing(function (array $data): array {
                        $data['customer_id'] = BankAccountResource::currentCustomer()->id;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('ویرایش')
                    ->mutateDataUsing(function (array $data): array {
                        unset($data['customer_id']);

                        return $data;
                    }),
                DeleteAction::make()->label('حذف'),
            ])
            ->emptyStateHeading('حساب بانکی ثبت نشده است')
            ->emptyStateDescription('برای درخواست واریز، حداقل یک حساب بانکی اضافه کنید.')
            ->defaultSort('created_at', 'desc');
    }
}
