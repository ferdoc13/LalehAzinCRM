<?php

namespace App\Filament\Support;

use App\Models\Customer;
use App\Models\User;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;

class CustomerSelect
{
    public static function make(string $name = 'customer_id'): Select
    {
        return Select::make($name)
            ->label('مشتری')
            ->relationship(
                name: 'customer',
                titleAttribute: 'first_name',
                modifyQueryUsing: function (Builder $query): Builder {
                    /** @var User $user */
                    $user = auth()->user();

                    return $query->visibleTo($user)->latest();
                },
            )
            ->getOptionLabelFromRecordUsing(
                fn (Customer $record): string => "{$record->full_name} — {$record->mobile}"
            )
            ->searchable(['first_name', 'last_name', 'mobile', 'national_code'])
            ->preload()
            ->required();
    }
}
