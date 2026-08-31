<?php

namespace App\Filament\Resources\Users\Actions;

use App\Actions\BlockUser;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class ToggleUserBlockActions
{
    /**
     * @return array<Action>
     */
    public static function make(): array
    {
        return [
            self::block(),
            self::unblock(),
        ];
    }

    public static function block(): Action
    {
        return Action::make('block')
            ->label('مسدود کردن')
            ->icon(Heroicon::NoSymbol)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('مسدود کردن کاربر')
            ->modalDescription('کاربر بلافاصله از پنل خارج می‌شود و دیگر نمی‌تواند وارد شود.')
            ->authorize('block')
            ->visible(function (User $record): bool {
                $actor = auth()->user();

                return $actor instanceof User
                    && ! $record->isBlocked()
                    && $actor->isNot($record);
            })
            ->action(function (User $record, BlockUser $block): void {
                $actor = auth()->user();

                if (! $actor instanceof User) {
                    return;
                }

                $block->block($record, $actor);

                Notification::make()
                    ->title('کاربر مسدود شد.')
                    ->danger()
                    ->send();
            });
    }

    public static function unblock(): Action
    {
        return Action::make('unblock')
            ->label('رفع مسدودی')
            ->icon(Heroicon::CheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('رفع مسدودی کاربر')
            ->modalDescription('کاربر دوباره می‌تواند وارد پنل شود.')
            ->authorize('unblock')
            ->visible(fn (User $record): bool => $record->isBlocked())
            ->action(function (User $record, BlockUser $block): void {
                $block->unblock($record);

                Notification::make()
                    ->title('مسدودی کاربر برداشته شد.')
                    ->success()
                    ->send();
            });
    }
}
