<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Enums\CustomerType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('نوع مشتری')
                    ->options(CustomerType::class)
                    ->default(CustomerType::Individual)
                    ->required()
                    ->live(),
                TextInput::make('first_name')
                    ->label('نام')
                    ->placeholder('مثلاً علی')
                    ->required()
                    ->maxLength(255),
                TextInput::make('last_name')
                    ->label('نام‌خانوادگی')
                    ->placeholder('مثلاً محمدی')
                    ->required()
                    ->maxLength(255),
                TextInput::make('national_code')
                    ->label('کد ملی')
                    ->placeholder('۱۰ رقم')
                    ->maxLength(10)
                    ->unique(ignoreRecord: true),
                TextInput::make('mobile')
                    ->label('شماره موبایل')
                    ->placeholder('09121234567')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->regex('/^09\d{9}$/')
                    ->validationMessages([
                        'regex' => 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.',
                    ]),
                Textarea::make('address')
                    ->label('آدرس')
                    ->placeholder('آدرس محل سکونت یا محل فعالیت')
                    ->columnSpanFull(),
                Section::make('اطلاعات شرکت')
                    ->relationship('companyProfile', fn (Get $get): bool => self::isCompany($get('type')))
                    ->visible(fn (Get $get): bool => self::isCompany($get('type')))
                    ->schema([
                        TextInput::make('company_name')
                            ->label('نام شرکت')
                            ->placeholder('نام ثبت‌شده شرکت')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('national_id')
                            ->label('شناسه ملی')
                            ->placeholder('شناسه ملی شرکت')
                            ->required()
                            ->maxLength(11),
                        TextInput::make('economic_code')
                            ->label('کد اقتصادی')
                            ->placeholder('کد اقتصادی شرکت')
                            ->maxLength(12),
                        Textarea::make('company_address')
                            ->label('آدرس شرکت')
                            ->placeholder('آدرس رسمی شرکت')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function isCompany(mixed $type): bool
    {
        $value = $type instanceof CustomerType
            ? $type
            : CustomerType::tryFrom((string) $type);

        return $value === CustomerType::Company;
    }
}
