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
        $pattern = config('sms.patterns.otp');
        $content = $this->interpolate('otp', ['code' => $code]);

        if (filled($pattern)) {
            return $this->sendByPattern($phone, (string) $pattern, [$code], SmsEventType::Otp, $content);
        }

        return $this->sendRaw($phone, $content, SmsEventType::Otp);
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

        if (! $this->hasCredentials()) {
            return $this->record($phone, $content, $eventType, SmsSendStatus::Failed, 'اطلاعات اتصال ملی پیامک تنظیم نشده است.');
        }

        try {
            $response = $this->usesConsoleApi()
                ? $this->postConsoleShared($phone, $patternCode, $values)
                : $this->postRestPattern($phone, $patternCode, $values);
        } catch (Throwable $exception) {
            return $this->recordFailedRequest($phone, $content, $eventType, $exception);
        }

        return $this->recordFromResponse($phone, $content, $eventType, $response);
    }

    public function sendRaw(
        string $phone,
        string $text,
        SmsEventType $eventType = SmsEventType::General,
    ): SmsLog {
        if (! $this->isEnabled()) {
            return $this->record($phone, $text, $eventType, SmsSendStatus::Failed, 'ارسال پیامک غیرفعال است.');
        }

        if (! $this->hasCredentials()) {
            return $this->record($phone, $text, $eventType, SmsSendStatus::Failed, 'اطلاعات اتصال ملی پیامک تنظیم نشده است.');
        }

        if (! $this->usesConsoleApi() && blank(config('sms.from'))) {
            return $this->record($phone, $text, $eventType, SmsSendStatus::Failed, 'شماره خط ارسال (MELIPAYAMAK_FROM) تنظیم نشده است.');
        }

        try {
            $response = $this->usesConsoleApi()
                ? $this->postConsoleSimple($phone, $text)
                : $this->postRestSimple($phone, $text);
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

    /**
     * @param  array<string, scalar>  $replacements
     */
    public function interpolate(string $messageKey, array $replacements): string
    {
        $template = (string) config("sms.fallback_messages.{$messageKey}", '');

        return str_replace(
            array_map(fn (string $key): string => '{'.$key.'}', array_keys($replacements)),
            array_map(strval(...), array_values($replacements)),
            $template,
        );
    }

    private function isEnabled(): bool
    {
        return (bool) config('sms.enabled');
    }

    private function usesConsoleApi(): bool
    {
        return match (config('sms.driver', 'auto')) {
            'console' => true,
            'rest' => false,
            default => filled(config('sms.api_key')),
        };
    }

    private function hasCredentials(): bool
    {
        if ($this->usesConsoleApi()) {
            return filled(config('sms.api_key'));
        }

        return filled(config('sms.username')) && filled(config('sms.password'));
    }

    /**
     * @param  list<string>  $values
     */
    private function postConsoleShared(string $phone, string $patternCode, array $values): Response
    {
        return $this->http()
            ->asJson()
            ->post($this->consoleUrl('shared'), [
                'bodyId' => (int) $patternCode,
                'to' => $this->normalizePhone($phone),
                'args' => $values,
            ]);
    }

    private function postConsoleSimple(string $phone, string $text): Response
    {
        return $this->http()
            ->asJson()
            ->post($this->consoleUrl('simple'), [
                'from' => (string) config('sms.from'),
                'to' => $this->normalizePhone($phone),
                'text' => $text,
            ]);
    }

    /**
     * @param  list<string>  $values
     */
    private function postRestPattern(string $phone, string $patternCode, array $values): Response
    {
        return $this->http()
            ->asForm()
            ->post('https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber', [
                'username' => (string) config('sms.username'),
                'password' => (string) config('sms.password'),
                'text' => implode(';', $values),
                'to' => $this->normalizePhone($phone),
                'bodyId' => (int) $patternCode,
            ]);
    }

    private function postRestSimple(string $phone, string $text): Response
    {
        return $this->http()
            ->asForm()
            ->post('https://rest.payamak-panel.com/api/SendSMS/SendSMS', [
                'username' => (string) config('sms.username'),
                'password' => (string) config('sms.password'),
                'to' => $this->normalizePhone($phone),
                'from' => (string) config('sms.from'),
                'text' => $text,
                'isflash' => false,
            ]);
    }

    private function consoleUrl(string $operation): string
    {
        return 'https://console.melipayamak.com/api/send/'.$operation.'/'.config('sms.api_key');
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

        $recId = $payload['recId'] ?? $payload['RecId'] ?? $payload['Value'] ?? $payload['value'] ?? null;
        $retStatus = $payload['RetStatus'] ?? $payload['retStatus'] ?? null;

        if ($retStatus !== null && (int) $retStatus !== 1) {
            return false;
        }

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
