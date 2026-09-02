<?php

namespace App\Services;

use App\Enums\SmsEventType;
use App\Enums\SmsSendStatus;
use App\Models\SmsLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class MelipayamakService
{
    public function sendOtp(string $phone, string $code): SmsLog
    {
        return $this->send(
            $phone,
            str_replace('{code}', $code, (string) config('sms.messages.otp')),
            SmsEventType::Otp,
        );
    }

    public function send(
        string $phone,
        string $text,
        SmsEventType $eventType = SmsEventType::General,
    ): SmsLog {
        if (! $this->isEnabled()) {
            return $this->record($phone, $text, $eventType, SmsSendStatus::Failed, 'ارسال پیامک غیرفعال است.');
        }

        if (blank(config('sms.username'))) {
            return $this->record($phone, $text, $eventType, SmsSendStatus::Failed, 'نام کاربری ملی پیامک تنظیم نشده است.');
        }

        if (blank(config('sms.password'))) {
            return $this->record($phone, $text, $eventType, SmsSendStatus::Failed, 'رمز عبور یا APIKey ملی پیامک تنظیم نشده است.');
        }

        if (blank(config('sms.from'))) {
            return $this->record($phone, $text, $eventType, SmsSendStatus::Failed, 'شماره خط ارسال‌کننده تنظیم نشده است.');
        }

        if (blank($text)) {
            return $this->record($phone, $text, $eventType, SmsSendStatus::Failed, 'متن پیامک خالی است.');
        }

        try {
            $response = $this->postSmart($phone, $text);
        } catch (Throwable $exception) {
            return $this->recordFailedRequest($phone, $text, $eventType, $exception);
        }

        return $this->recordFromResponse($phone, $text, $eventType, $response);
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return match (true) {
            Str::startsWith($digits, '0098') => '0'.substr($digits, 4),
            Str::startsWith($digits, '98') && strlen($digits) >= 12 => '0'.substr($digits, 2),
            Str::startsWith($digits, '9') && strlen($digits) === 10 => '0'.$digits,
            default => $digits,
        };
    }

    private function isEnabled(): bool
    {
        return (bool) config('sms.enabled');
    }

    private function postSmart(string $phone, string $text): Response
    {
        return $this->http()
            ->asForm()
            ->post('https://rest.payamak-panel.com/api/SmartSMS/Send', [
                'username' => (string) config('sms.username'),
                'password' => (string) config('sms.password'),
                'to' => $this->normalizePhone($phone),
                'text' => $text,
                'from' => (string) config('sms.from'),
                'fromSupportOne' => (string) config('sms.from_support_one', ''),
                'fromSupportTwo' => (string) config('sms.from_support_two', ''),
            ]);
    }

    private function http(): PendingRequest
    {
        return Http::timeout((int) config('sms.timeout', 10))
            ->connectTimeout((int) config('sms.connect_timeout', 3))
            ->retry(3, 100, fn (Throwable $exception): bool => $exception instanceof ConnectionException, false)
            ->acceptJson();
    }

    private function recordFromResponse(string $phone, string $content, SmsEventType $eventType, Response $response): SmsLog
    {
        $payload = $response->json();
        $encoded = is_array($payload)
            ? json_encode($payload, JSON_UNESCAPED_UNICODE)
            : $response->body();

        $status = $response->successful() && $this->responseSucceeded($payload)
            ? SmsSendStatus::Sent
            : SmsSendStatus::Failed;

        return $this->record($phone, $content, $eventType, $status, $encoded);
    }

    private function recordFailedRequest(string $phone, string $content, SmsEventType $eventType, Throwable $exception): SmsLog
    {
        if (! $exception instanceof ConnectionException) {
            report($exception);
        }

        return $this->record($phone, $content, $eventType, SmsSendStatus::Failed, $exception->getMessage());
    }

    private function responseSucceeded(mixed $payload): bool
    {
        if (! is_array($payload)) {
            return is_numeric($payload) && (int) $payload > 0;
        }

        $retStatus = $payload['RetStatus'] ?? $payload['retStatus'] ?? null;

        if ($retStatus !== null && (int) $retStatus !== 1) {
            return false;
        }

        $value = $payload['Value'] ?? $payload['value'] ?? $payload['recId'] ?? $payload['RecId'] ?? null;

        if (is_string($value) && str_contains($value, ' - ')) {
            return false;
        }

        return is_numeric($value) && (int) $value > 0;
    }

    private function record(
        string $phone,
        string $content,
        SmsEventType $eventType,
        SmsSendStatus $status,
        ?string $serviceResponse,
    ): SmsLog {
        return SmsLog::query()->create([
            'recipient' => Str::limit($this->normalizePhone($phone), 11, ''),
            'event_type' => $eventType,
            'content' => $content,
            'send_status' => $status,
            'service_response' => $serviceResponse,
        ]);
    }
}
