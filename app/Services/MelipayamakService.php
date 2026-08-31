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
        return $this->sendByPattern(
            $phone,
            (string) config('sms.patterns.otp'),
            [$code],
            SmsEventType::Otp,
            'پترن otp: '.$code,
        );
    }

    /**
     * @param  array<int|string, scalar>  $params
     */
    public function sendByPattern(
        string $phone,
        string $patternCode,
        array $params,
        SmsEventType $eventType = SmsEventType::General,
        ?string $content = null,
    ): SmsLog {
        $values = array_map(strval(...), array_values($params));
        $content ??= 'پترن '.$patternCode.': '.implode(';', $values);

        if (! $this->isEnabled()) {
            return $this->record($phone, $content, $eventType, SmsSendStatus::Failed, 'ارسال پیامک غیرفعال است.');
        }

        if (blank(config('sms.api_key'))) {
            return $this->record($phone, $content, $eventType, SmsSendStatus::Failed, 'کلید API ملی پیامک تنظیم نشده است.');
        }

        if (blank($patternCode)) {
            return $this->record($phone, $content, $eventType, SmsSendStatus::Failed, 'کد پترن خدماتی تنظیم نشده است.');
        }

        try {
            $response = $this->postShared($phone, $patternCode, $values);
        } catch (Throwable $exception) {
            return $this->recordFailedRequest($phone, $content, $eventType, $exception);
        }

        return $this->recordFromResponse($phone, $content, $eventType, $response);
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

    /**
     * @param  list<string>  $values
     */
    private function postShared(string $phone, string $patternCode, array $values): Response
    {
        return $this->http()
            ->asJson()
            ->post('https://console.melipayamak.com/api/send/shared/'.config('sms.api_key'), [
                'bodyId' => (int) $patternCode,
                'to' => $this->normalizePhone($phone),
                'args' => $values,
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
