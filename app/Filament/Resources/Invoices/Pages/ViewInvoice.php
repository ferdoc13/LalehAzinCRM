<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Actions\CreateDiscountRequest as CreateDiscountRequestAction;
use App\Filament\Resources\DiscountRequests\Actions\ReviewDiscountRequestActions;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\DiscountRequest;
use App\Models\Invoice;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load([
            'items',
            'discountRequests.requester',
            'pendingDiscountRequest',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->requestDiscountAction(),
            ...$this->reviewDiscountActions(),
            EditAction::make()->label('ویرایش'),
        ];
    }

    private function requestDiscountAction(): Action
    {
        return Action::make('requestDiscount')
            ->label('درخواست تخفیف')
            ->icon(Heroicon::OutlinedPercentBadge)
            ->visible(function (): bool {
                /** @var Invoice $invoice */
                $invoice = $this->getRecord();
                $user = auth()->user();

                return $user instanceof User
                    && $user->can('create', DiscountRequest::class)
                    && $invoice->itemsTotal() > 0
                    && $invoice->pendingDiscountRequest === null;
            })
            ->schema([
                TextInput::make('proposed_amount')
                    ->label('مبلغ پیشنهادی')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(fn (): float => $this->getRecord()->itemsTotal())
                    ->prefix('ریال')
                    ->required()
                    ->helperText(fn (): string => 'سقف مجاز برابر مبلغ اقلام فاکتور: '.Number::format($this->getRecord()->itemsTotal(), precision: 0).' ریال'),
            ])
            ->action(function (array $data): void {
                app(CreateDiscountRequestAction::class)->handle(
                    invoice: $this->getRecord(),
                    requester: auth()->user(),
                    proposedAmount: (float) $data['proposed_amount'],
                );

                Notification::make()
                    ->title('درخواست تخفیف ثبت شد.')
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<Action>
     */
    private function reviewDiscountActions(): array
    {
        $pending = $this->getRecord()->pendingDiscountRequest;

        if (! $pending) {
            return [];
        }

        return collect(ReviewDiscountRequestActions::make())
            ->each(fn (Action $action): Action => $action->record($pending))
            ->all();
    }
}
