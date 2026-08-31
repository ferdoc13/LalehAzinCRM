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
        'sms.api_key' => 'test-api-key',
        'sms.from' => '2134856',
        'sms.messages.otp' => 'کد تأیید شما: {code}',
        'sms.timeout' => 5,
        'sms.connect_timeout' => 2,
    ]);
});

it('sends otp from the dedicated line through the console simple api', function () {
    Http::fake([
        'console.melipayamak.com/api/send/simple/*' => Http::response([
            'recId' => 987654321,
            'status' => 'ارسال موفق',
        ]),
    ]);

    $log = app(MelipayamakService::class)->sendOtp('09121234567', '123456');

    expect($log)
        ->recipient->toBe('09121234567')
        ->event_type->toBe(SmsEventType::Otp)
        ->send_status->toBe(SmsSendStatus::Sent)
        ->and($log->content)->toBe('کد تأیید شما: 123456');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://console.melipayamak.com/api/send/simple/test-api-key'
        && $request['from'] === '2134856'
        && $request['to'] === '09121234567'
        && $request['text'] === 'کد تأیید شما: 123456');
});

it('does not send when the sender line is empty', function () {
    config(['sms.from' => null]);

    Http::fake();

    $log = app(MelipayamakService::class)->sendOtp('09121234567', '654321');

    expect($log)
        ->send_status->toBe(SmsSendStatus::Failed)
        ->service_response->toBe('شماره خط ارسال‌کننده تنظیم نشده است.');

    Http::assertNothingSent();
});

it('logs a failed sms when the provider returns an error', function () {
    Http::fake([
        'console.melipayamak.com/api/send/simple/*' => Http::response([
            'recId' => 0,
            'status' => 'اعتبار کافی نیست',
        ], 200),
    ]);

    $log = app(MelipayamakService::class)->send(
        '09121112233',
        'متن آزمایشی',
        SmsEventType::Invoice,
    );

    expect($log)
        ->send_status->toBe(SmsSendStatus::Failed)
        ->event_type->toBe(SmsEventType::Invoice)
        ->and(SmsLog::query()->count())->toBe(1);
});

it('does not call the provider when sms is disabled', function () {
    config(['sms.enabled' => false]);

    Http::fake();

    $log = app(MelipayamakService::class)->send('09121112233', 'متن آزمایشی');

    expect($log->send_status)->toBe(SmsSendStatus::Failed)
        ->and($log->service_response)->toBe('ارسال پیامک غیرفعال است.');

    Http::assertNothingSent();
});
