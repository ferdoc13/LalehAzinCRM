<?php

namespace App\Notifications;

use App\Notifications\Messages\MelipayamakMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class MelipayamakNotification extends Notification implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [1, 5, 10];

    public int $timeout = 60;

    public function via(object $notifiable): array
    {
        return ['melipayamak'];
    }

    abstract public function toMelipayamak(object $notifiable): MelipayamakMessage;

    public function failed(?Throwable $exception): void
    {
        Log::error('Melipayamak notification failed', [
            'notification' => static::class,
            'error' => $exception?->getMessage(),
        ]);
    }

    protected function formatAmount(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 0, '.', ',');
    }
}
