<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Services\CustomerCreditService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Number;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('type')
                    ->label('نوع مشتری')
                    ->badge(),
                TextEntry::make('full_name')
                    ->label('نام مشتری'),
                TextEntry::make('national_code')
                    ->label('کد ملی')
                    ->placeholder('-'),
                TextEntry::make('mobile')
                    ->label('شماره موبایل'),
                TextEntry::make('address')
                    ->label('آدرس')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('employee.name')
                    ->label('کارمند ثبت‌کننده'),
                TextEntry::make('credit_balance')
                    ->label('موجودی اعتبار')
                    ->state(fn (Customer $record): string => Number::format(
                        app(CustomerCreditService::class)->getBalance($record),
                        precision: 0,
                    ).' ریال'),
                TextEntry::make('apply_credit_to_next_invoice')
                    ->label('اعمال روی فاکتور بعدی')
                    ->state(fn (Customer $record): string => $record->apply_credit_to_next_invoice
                        ? 'مشتری درخواست کرده است'
                        : 'خیر')
                    ->visible(fn (?Customer $record): bool => (bool) $record?->apply_credit_to_next_invoice),
                Section::make('اطلاعات شرکت')
                    ->visible(fn ($record): bool => $record?->type === CustomerType::Company)
                    ->schema([
                        TextEntry::make('companyProfile.company_name')
                            ->label('نام شرکت')
                            ->placeholder('-'),
                        TextEntry::make('companyProfile.national_id')
                            ->label('شناسه ملی')
                            ->placeholder('-'),
                        TextEntry::make('companyProfile.economic_code')
                            ->label('کد اقتصادی')
                            ->placeholder('-'),
                        TextEntry::make('companyProfile.company_address')
                            ->label('آدرس شرکت')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label('تاریخ ثبت')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
