<?php

namespace Database\Factories;

use App\Enums\SmsEventType;
use App\Enums\SmsSendStatus;
use App\Models\SmsLog;
use Database\Seeders\Support\PersianFaker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SmsLog>
 */
class SmsLogFactory extends Factory
{
    public function definition(): array
    {
        $eventType = fake()->randomElement(SmsEventType::cases());
        $status = fake()->randomElement(SmsSendStatus::cases());

        return [
            'recipient' => PersianFaker::mobile(),
            'event_type' => $eventType,
            'content' => $this->contentForEvent($eventType),
            'send_status' => $status,
            'service_response' => $status === SmsSendStatus::Sent
                ? json_encode(['message_id' => fake()->uuid(), 'status' => 'delivered'], JSON_UNESCAPED_UNICODE)
                : ($status === SmsSendStatus::Failed
                    ? json_encode(['error' => 'خطا در ارسال پیامک'], JSON_UNESCAPED_UNICODE)
                    : null),
        ];
    }

    private function contentForEvent(SmsEventType $eventType): string
    {
        return match ($eventType) {
            SmsEventType::Otp => 'کد تأیید شما: '.PersianFaker::otpCode(),
            SmsEventType::Invoice => 'فاکتور شماره INV-'.fake()->numerify('######').' صادر شد.',
            SmsEventType::Discount => 'درخواست تخفیف شما در حال بررسی است.',
            SmsEventType::Withdrawal => 'درخواست برداشت شما ثبت شد.',
            SmsEventType::General => 'پیام اطلاع‌رسانی لاله‌آذین',
        };
    }
}
