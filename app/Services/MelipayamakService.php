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

        if (blank(config('sms.api_key'))) {
            return $this->record($phone, $text, $eventType, SmsSendStatus::Failed, 'کلید API ملی پیامک تنظیم نشده است.');
        }

        if (blank(config('sms.from'))) {
            return $this->record($phone, $text, $eventType, SmsSendStatus::Failed, 'شماره خط ارسال‌کننده تنظیم نشده است.');
        }

        if (blank($text)) {
            return $this->record($phone, $text, $eventType, SmsSendStatus::Failed, 'متن پیامک خالی است.');
        }

        try {
            $response = $this->postSimple($phone, $text);
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

    private function postSimple(string $phone, string $text): Response
    {
        return $this->http()
            ->asJson()
            ->post('https://console.melipayamak.com/api/send/simple/'.config('sms.api_key'), [
                'from' => (string) config('sms.from'),
                'to' => $this->normalizePhone($phone),
                'text' => $text,
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
            return false;
        }

        $recId = $payload['recId'] ?? $payload['RecId'] ?? null;

        return is_numeric($recId) && (int) $recId > 0;
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
