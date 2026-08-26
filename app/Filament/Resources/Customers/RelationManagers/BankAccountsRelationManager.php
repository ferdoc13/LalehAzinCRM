<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'bankAccounts';

    protected static ?string $title = 'حساب‌های بانکی';

    protected static ?string $modelLabel = 'حساب بانکی';

    protected static ?string $pluralModelLabel = 'حساب‌های بانکی';

    protected static ?string $recordTitleAttribute = 'bank_name';

    public function form(Schema $schema): Schema
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

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('bank_name')
                    ->label('نام بانک'),
                TextEntry::make('account_number')
                    ->label('شماره حساب'),
                TextEntry::make('sheba_number')
                    ->label('شماره شبا'),
            ]);
    }

    public function table(Table $table): Table
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
                CreateAction::make()->label('افزودن حساب بانکی'),
            ])
            ->recordActions([
                ViewAction::make()->label('مشاهده'),
                EditAction::make()->label('ویرایش'),
                DeleteAction::make()->label('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف انتخاب‌شده‌ها'),
                ]),
            ]);
    }
}
