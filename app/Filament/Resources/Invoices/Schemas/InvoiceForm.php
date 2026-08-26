<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoicePaymentStatus;
use App\Filament\Support\CustomerSelect;
use App\Models\Customer;
use App\Services\CustomerCreditService;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Number;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                CustomerSelect::make()
                    ->placeholder('جستجوی نام، موبایل یا کد ملی')
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('apply_customer_credit', false)),
                TextInput::make('invoice_number')
                    ->label('شماره فاکتور')
                    ->placeholder('در صورت خالی بودن به‌صورت خودکار ساخته می‌شود')
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                DatePicker::make('invoice_date')
                    ->label('تاریخ فاکتور')
                    ->default(now())
                    ->required(),
                Select::make('payment_status')
                    ->label('وضعیت پرداخت')
                    ->options(InvoicePaymentStatus::class)
                    ->default(InvoicePaymentStatus::Pending)
                    ->required(),
                Repeater::make('items')
                    ->label('اقلام فاکتور')
                    ->relationship()
                    ->schema([
                        TextInput::make('description')
                            ->label('شرح')
                            ->placeholder('شرح کالا یا خدمت')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        TextInput::make('quantity')
                            ->label('تعداد')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(self::recalculateItem(...)),
                        TextInput::make('unit_price')
                            ->label('قیمت واحد')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(self::recalculateItem(...)),
                        TextInput::make('total_amount')
                            ->label('مبلغ کل')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->default(0),
                    ])
                    ->columns(6)
                    ->minItems(1)
                    ->defaultItems(1)
                    ->addActionLabel('افزودن قلم')
                    ->live()
                    ->afterStateUpdated(self::recalculateInvoiceTotal(...))
                    ->columnSpanFull(),
                TextInput::make('total_amount')
                    ->label('مبلغ کل فاکتور')
                    ->numeric()
                    ->prefix('ریال')
                    ->disabled()
                    ->dehydrated()
                    ->default(0),
                Checkbox::make('apply_customer_credit')
                    ->label('اعمال تخفیف موجود از حساب مشتری')
                    ->helperText(fn (Get $get): ?string => self::creditHelperText($get))
                    ->live()
                    ->dehydrated()
                    ->hiddenOn('edit')
                    ->visible(fn (Get $get): bool => self::customerBalance($get) > 0)
                    ->columnSpanFull(),
                Placeholder::make('payable_after_credit')
                    ->label('مبلغ قابل پرداخت')
                    ->content(function (Get $get): string {
                        $payable = max(0, self::itemsTotalFromState($get) - self::discountFromState($get));

                        return Number::format($payable, precision: 0).' ریال';
                    })
                    ->hiddenOn('edit')
                    ->visible(fn (Get $get): bool => (bool) $get('apply_customer_credit'))
                    ->columnSpanFull(),
            ]);
    }

    public static function recalculateItem(Get $get, Set $set): void
    {
        $set('total_amount', self::lineTotal($get('quantity'), $get('unit_price')));
        self::recalculateInvoiceTotal($get, $set, fromItem: true);
    }

    public static function recalculateInvoiceTotal(Get $get, Set $set, bool $fromItem = false): void
    {
        $items = $fromItem ? $get('../../items') : $get('items');
        $total = self::sumItems($items);

        if ($fromItem) {
            $set('../../total_amount', $total);

            return;
        }

        $set('total_amount', $total);
    }

    private static function customerBalance(Get $get): float
    {
        $customerId = $get('customer_id');

        if (blank($customerId)) {
            return 0;
        }

        $customer = Customer::query()->find($customerId);

        if (! $customer) {
            return 0;
        }

        return app(CustomerCreditService::class)->getBalance($customer);
    }

    private static function creditHelperText(Get $get): ?string
    {
        $balance = self::customerBalance($get);

        if ($balance <= 0) {
            return null;
        }

        $itemsTotal = self::itemsTotalFromState($get);
        $applied = round(min($balance, $itemsTotal), 2);

        return 'موجودی فعلی: '.Number::format($balance, precision: 0).' ریال — تا سقف مبلغ فاکتور ('.Number::format($applied, precision: 0).' ریال) کسر می‌شود.';
    }

    private static function itemsTotalFromState(Get $get): float
    {
        return self::sumItems($get('items'));
    }

    private static function discountFromState(Get $get): float
    {
        if (! $get('apply_customer_credit')) {
            return 0;
        }

        return round(min(self::customerBalance($get), self::itemsTotalFromState($get)), 2);
    }

    private static function sumItems(mixed $items): float
    {
        return round(collect($items ?? [])->sum(
            fn (mixed $item): float => self::lineTotal(
                is_array($item) ? ($item['quantity'] ?? 0) : 0,
                is_array($item) ? ($item['unit_price'] ?? 0) : 0,
            )
        ), 2);
    }

    private static function lineTotal(mixed $quantity, mixed $unitPrice): float
    {
        return round((float) $quantity * (float) $unitPrice, 2);
    }
}
