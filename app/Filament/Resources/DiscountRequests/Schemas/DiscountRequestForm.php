<?php

namespace App\Filament\Resources\DiscountRequests\Schemas;

use App\Models\Invoice;
use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class DiscountRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('invoice_id')
                    ->label('فاکتور')
                    ->relationship(
                        name: 'invoice',
                        titleAttribute: 'invoice_number',
                        modifyQueryUsing: function (Builder $query): Builder {
                            /** @var User $user */
                            $user = auth()->user();

                            return $query->visibleTo($user)->with('customer')->latest();
                        },
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn (Invoice $record): string => "{$record->invoice_number} — {$record->customer?->full_name}"
                    )
                    ->searchable(['invoice_number'])
                    ->preload()
                    ->live()
                    ->required(),
                Placeholder::make('customer_name')
                    ->label('مشتری')
                    ->content(function (Get $get): string {
                        $invoice = $get('invoice_id')
                            ? Invoice::query()->with('customer')->find($get('invoice_id'))
                            : null;

                        return $invoice?->customer?->full_name ?? 'ابتدا فاکتور را انتخاب کنید';
                    }),
                TextInput::make('proposed_amount')
                    ->label('مبلغ پیشنهادی')
                    ->placeholder('مبلغ تخفیف پیشنهادی')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(fn (Get $get): ?float => self::invoiceItemsTotal($get))
                    ->prefix('ریال')
                    ->required()
                    ->helperText(function (Get $get): ?string {
                        $max = self::invoiceItemsTotal($get);

                        if ($max === null) {
                            return null;
                        }

                        return 'سقف مجاز برابر مبلغ اقلام فاکتور: '.Number::format($max, precision: 0).' ریال';
                    }),
            ]);
    }

    private static function invoiceItemsTotal(Get $get): ?float
    {
        $invoiceId = $get('invoice_id');

        if (blank($invoiceId)) {
            return null;
        }

        $invoice = Invoice::query()->with('items')->find($invoiceId);

        return $invoice?->itemsTotal();
    }
}
