<?php

namespace App\Filament\Customer\Pages;

use App\Enums\WithdrawalRequestStatus;
use App\Models\Customer;
use App\Models\CustomerBankAccount;
use App\Models\WithdrawalRequest;
use App\Services\CustomerCreditService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Number;

class CreditBalance extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'موجودی تخفیف';

    protected static ?string $title = 'موجودی تخفیف';

    protected static ?string $slug = 'credit';

    protected static ?int $navigationSort = 3;

    public function getHeading(): string|Htmlable|null
    {
        return 'موجودی تخفیف';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('موجودی فعلی')
                    ->schema([
                        TextEntry::make('balance')
                            ->hiddenLabel()
                            ->state(fn (): string => Number::format($this->balance(), precision: 0).' ریال'),
                        TextEntry::make('apply_credit_flag')
                            ->label('اعمال روی فاکتور بعدی')
                            ->state(fn (): string => $this->customer()->apply_credit_to_next_invoice
                                ? 'درخواست شما ثبت شده است و در فاکتور بعدی لحاظ می‌شود.'
                                : 'هنوز درخواستی ثبت نشده است.')
                            ->visible(fn (): bool => $this->customer()->apply_credit_to_next_invoice),
                    ]),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('applyToNextInvoice')
                ->label('اعمال روی فاکتور بعدی')
                ->icon(Heroicon::OutlinedReceiptPercent)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('اعمال موجودی روی فاکتور بعدی')
                ->modalDescription('با تأیید، درخواست می‌کنید موجودی اعتبار روی فاکتور بعدی‌تان اعمال شود.')
                ->modalSubmitActionLabel('ثبت درخواست')
                ->visible(fn (): bool => $this->balance() > 0 && ! $this->customer()->apply_credit_to_next_invoice && ! $this->hasPendingWithdrawal())
                ->action(function (): void {
                    $customer = $this->customer();
                    Gate::authorize('update', $customer);

                    $customer->update([
                        'apply_credit_to_next_invoice' => true,
                    ]);

                    Notification::make()
                        ->title('درخواست ثبت شد')
                        ->body('موجودی اعتبار در فاکتور بعدی اعمال خواهد شد.')
                        ->success()
                        ->send();
                }),
            Action::make('requestWithdrawal')
                ->label('درخواست واریز به حساب')
                ->icon(Heroicon::OutlinedBanknotes)
                ->visible(fn (): bool => $this->balance() > 0 && ! $this->hasPendingWithdrawal())
                ->form(function (): array {
                    $accounts = $this->customer()->bankAccounts()->get();

                    if ($accounts->isEmpty()) {
                        return [
                            Placeholder::make('no_account')
                                ->hiddenLabel()
                                ->content('ابتدا یک حساب بانکی در صفحه پروفایل ثبت کنید.'),
                        ];
                    }

                    return [
                        Placeholder::make('amount')
                            ->label('مبلغ قابل برداشت')
                            ->content(Number::format($this->balance(), precision: 0).' ریال'),
                        Select::make('bank_account_id')
                            ->label('حساب بانکی')
                            ->options(
                                $accounts->mapWithKeys(
                                    fn (CustomerBankAccount $account): array => [
                                        $account->id => "{$account->bank_name} — {$account->sheba_number}",
                                    ],
                                ),
                            )
                            ->default($accounts->first()?->id)
                            ->required()
                            ->native(false),
                    ];
                })
                ->modalSubmitActionLabel('ثبت درخواست واریز')
                ->action(function (array $data): void {
                    $this->createWithdrawalRequest($data['bank_account_id'] ?? null);
                }),
        ];
    }

    private function createWithdrawalRequest(mixed $bankAccountId): void
    {
        Gate::authorize('create', WithdrawalRequest::class);

        $customer = $this->customer();
        $balance = $this->balance();

        if ($balance <= 0) {
            Notification::make()
                ->title('موجودی کافی نیست')
                ->danger()
                ->send();

            return;
        }

        if ($this->hasPendingWithdrawal()) {
            Notification::make()
                ->title('یک درخواست واریز در انتظار بررسی است')
                ->danger()
                ->send();

            return;
        }

        $account = $customer->bankAccounts()->whereKey($bankAccountId)->first();

        if (! $account) {
            Notification::make()
                ->title('حساب بانکی معتبری انتخاب نشده است')
                ->body('لطفاً ابتدا حساب بانکی خود را در پروفایل ثبت کنید.')
                ->danger()
                ->send();

            return;
        }

        WithdrawalRequest::query()->create([
            'customer_id' => $customer->id,
            'credit_ledger_id' => $customer->creditLedgers()->reorder()->orderByDesc('id')->value('id'),
            'amount' => $balance,
            'bank_account_id' => $account->id,
            'status' => WithdrawalRequestStatus::Pending,
        ]);

        $customer->update([
            'apply_credit_to_next_invoice' => false,
        ]);

        Notification::make()
            ->title('درخواست واریز ثبت شد')
            ->body('درخواست شما با وضعیت «در انتظار» ثبت شد.')
            ->success()
            ->send();
    }

    private function customer(): Customer
    {
        $user = Filament::auth()->user();

        abort_unless($user instanceof Customer, 403);

        return $user;
    }

    private function balance(): float
    {
        return app(CustomerCreditService::class)->getBalance($this->customer());
    }

    private function hasPendingWithdrawal(): bool
    {
        return $this->customer()
            ->withdrawalRequests()
            ->where('status', WithdrawalRequestStatus::Pending)
            ->exists();
    }
}
