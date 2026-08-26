<?php

namespace App\Filament\Resources\DiscountRequests\Actions;

use App\Actions\ReviewDiscountRequest;
use App\Models\DiscountRequest;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class ReviewDiscountRequestActions
{
    /**
     * @return array<Action>
     */
    public static function make(): array
    {
        return [
            self::approve(),
            self::editAndApprove(),
            self::reject(),
        ];
    }

    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('تأیید')
            ->icon(Heroicon::CheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('تأیید درخواست تخفیف')
            ->modalDescription('مبلغ پیشنهادی به موجودی اعتبار مشتری اضافه می‌شود.')
            ->authorize('review')
            ->action(function (DiscountRequest $record, ReviewDiscountRequest $review): void {
                $review->approve($record, auth()->user());

                Notification::make()
                    ->title('درخواست تخفیف تأیید و اعتبار مشتری ثبت شد.')
                    ->success()
                    ->send();
            });
    }

    public static function editAndApprove(): Action
    {
        return Action::make('editAndApprove')
            ->label('ویرایش و تأیید')
            ->icon(Heroicon::PencilSquare)
            ->color('info')
            ->authorize('review')
            ->fillForm(fn (DiscountRequest $record): array => [
                'final_amount' => $record->proposed_amount,
            ])
            ->schema([
                TextInput::make('final_amount')
                    ->label('مبلغ نهایی')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->helperText('این مبلغ به موجودی اعتبار مشتری اضافه می‌شود.'),
            ])
            ->action(function (DiscountRequest $record, array $data, ReviewDiscountRequest $review): void {
                $review->editAndApprove($record, auth()->user(), (float) $data['final_amount']);

                Notification::make()
                    ->title('درخواست تخفیف ویرایش و تأیید شد و اعتبار مشتری ثبت شد.')
                    ->success()
                    ->send();
            });
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('رد')
            ->icon(Heroicon::XCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('رد درخواست تخفیف')
            ->modalDescription('با رد درخواست، تراکنش اعتباری ثبت نمی‌شود.')
            ->authorize('review')
            ->action(function (DiscountRequest $record, ReviewDiscountRequest $review): void {
                $review->reject($record, auth()->user());

                Notification::make()
                    ->title('درخواست تخفیف رد شد.')
                    ->danger()
                    ->send();
            });
    }
}
