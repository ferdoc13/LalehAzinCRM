<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Enums\CustomerType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('نام مشتری')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name', 'last_name']),
                TextColumn::make('type')
                    ->label('نوع مشتری')
                    ->badge(),
                TextColumn::make('mobile')
                    ->label('شماره موبایل')
                    ->searchable(),
                TextColumn::make('national_code')
                    ->label('کد ملی')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('employee.name')
                    ->label('کارمند ثبت‌کننده')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('نوع مشتری')
                    ->options(CustomerType::class),
                SelectFilter::make('employee_id')
                    ->label('کارمند ثبت‌کننده')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make()->label('مشاهده'),
                EditAction::make()->label('ویرایش'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف انتخاب‌شده‌ها'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
