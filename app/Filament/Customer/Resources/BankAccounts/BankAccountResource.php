<?php

namespace App\Filament\Customer\Resources\BankAccounts;

use App\Filament\Customer\Resources\BankAccounts\Pages\ManageBankAccounts;
use App\Models\Customer;
use App\Models\CustomerBankAccount;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BankAccountResource extends Resource
{
    protected static ?string $model = CustomerBankAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $slug = 'profile';

    protected static ?string $recordTitleAttribute = 'bank_name';

    protected static ?string $modelLabel = 'حساب بانکی';

    protected static ?string $pluralModelLabel = 'حساب‌های بانکی';

    protected static ?string $navigationLabel = 'پروفایل';

    protected static ?int $navigationSort = 1;

    protected static bool $hasTitleCaseModelLabel = false;

    public static function form(Schema $schema): Schema
    {
        return BankAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankAccountsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBankAccounts::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('customer_id', static::currentCustomer()->id);
    }

    public static function currentCustomer(): Customer
    {
        $user = Filament::auth()->user();

        abort_unless($user instanceof Customer, 403);

        return $user;
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        return $record instanceof CustomerBankAccount ? $record->bank_name : parent::getRecordTitle($record);
    }
}
