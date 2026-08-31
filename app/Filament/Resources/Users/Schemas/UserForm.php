<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\StaffRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

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
                Select::make('roles')
                    ->label('نقش')
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->where('guard_name', 'web')->orderBy('name'),
                    )
                    ->getOptionLabelFromRecordUsing(function (Role $record): string {
                        return StaffRole::tryFrom($record->name)?->getLabel() ?? $record->name;
                    })
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->minItems(1)
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
