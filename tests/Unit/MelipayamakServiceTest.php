<?php

use App\Enums\SmsEventType;
use App\Enums\SmsSendStatus;
use App\Models\SmsLog;
use App\Services\MelipayamakService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();

    config([
        'sms.enabled' => true,
        'sms.driver' => 'auto',
        'sms.api_key' => 'test-api-key',
        'sms.username' => 'user',
        'sms.password' => 'secret',
        'sms.from' => '5000',
        'sms.patterns.otp' => '11111',
        'sms.timeout' => 5,
        'sms.connect_timeout' => 2,
    ]);
});

it('sends otp by pattern through the console api and logs success', function () {
    Http::fake([
        'console.melipayamak.com/api/send/shared/*' => Http::response([
            'recId' => 987654321,
            'status' => 'ارسال موفق',
        ]),
    ]);

    $log = app(MelipayamakService::class)->sendOtp('09121234567', '123456');

    expect($log)
        ->recipient->toBe('09121234567')
        ->event_type->toBe(SmsEventType::Otp)
        ->send_status->toBe(SmsSendStatus::Sent)
        ->and($log->content)->toContain('123456');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://console.melipayamak.com/api/send/shared/test-api-key'
        && $request['bodyId'] === 11111
        && $request['to'] === '09121234567'
        && $request['args'] === ['123456']);
});

it('falls back to raw send when the otp pattern is empty', function () {
    config(['sms.patterns.otp' => null]);

    Http::fake([
        'console.melipayamak.com/api/send/simple/*' => Http::response([
            'recId' => 111,
            'status' => 'ارسال موفق',
        ]),
    ]);

    $log = app(MelipayamakService::class)->sendOtp('09121234567', '654321');

    expect($log->send_status)->toBe(SmsSendStatus::Sent);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/send/simple/')
        && $request['text'] === 'کد تأیید شما: 654321');
});

it('sends by pattern through classic rest when only username and password are set', function () {
    config([
        'sms.api_key' => null,
        'sms.driver' => 'rest',
    ]);

    Http::fake([
        'rest.payamak-panel.com/api/SendSMS/BaseServiceNumber' => Http::response([
            'Value' => '555666',
            'RetStatus' => 1,
            'StrRetStatus' => 'Ok',
        ]),
    ]);

    $log = app(MelipayamakService::class)->sendByPattern('09120001122', '22222', ['علی', '1000']);

    expect($log)
        ->send_status->toBe(SmsSendStatus::Sent)
        ->event_type->toBe(SmsEventType::General);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber'
        && (int) $request['bodyId'] === 22222
        && $request['text'] === 'علی;1000'
        && $request['username'] === 'user');
});

it('logs a failed sms when the provider returns an error', function () {
    Http::fake([
        'console.melipayamak.com/api/send/simple/*' => Http::response([
            'recId' => 0,
            'status' => 'اعتبار کافی نیست',
        ], 200),
    ]);

    $log = app(MelipayamakService::class)->sendRaw('09121112233', 'متن آزمایشی', SmsEventType::Invoice);

    expect($log)
        ->send_status->toBe(SmsSendStatus::Failed)
        ->event_type->toBe(SmsEventType::Invoice)
        ->and(SmsLog::query()->count())->toBe(1);
});

it('does not call the provider when sms is disabled', function () {
    config(['sms.enabled' => false]);

    Http::fake();

    $log = app(MelipayamakService::class)->sendRaw('09121112233', 'متن آزمایشی');

    expect($log->send_status)->toBe(SmsSendStatus::Failed)
        ->and($log->service_response)->toBe('ارسال پیامک غیرفعال است.');

    Http::assertNothingSent();
});
