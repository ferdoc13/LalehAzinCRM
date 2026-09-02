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
        'sms.username' => 'panel-user',
        'sms.password' => 'panel-api-key',
        'sms.from' => '50001234',
        'sms.from_support_one' => '50005678',
        'sms.from_support_two' => '',
        'sms.messages.otp' => 'کد تأیید شما: {code}',
        'sms.timeout' => 5,
        'sms.connect_timeout' => 2,
    ]);
});

it('sends otp through the smart sms api and logs success', function () {
    Http::fake([
        'rest.payamak-panel.com/api/SmartSMS/Send' => Http::response([
            'Value' => '987654321',
            'RetStatus' => 1,
            'StrRetStatus' => 'Ok',
        ]),
    ]);

    $log = app(MelipayamakService::class)->sendOtp('09121234567', '123456');

    expect($log)
        ->recipient->toBe('09121234567')
        ->event_type->toBe(SmsEventType::Otp)
        ->send_status->toBe(SmsSendStatus::Sent)
        ->and($log->content)->toBe('کد تأیید شما: 123456');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://rest.payamak-panel.com/api/SmartSMS/Send'
        && $request['username'] === 'panel-user'
        && $request['password'] === 'panel-api-key'
        && $request['from'] === '50001234'
        && $request['fromSupportOne'] === '50005678'
        && $request['fromSupportTwo'] === ''
        && $request['to'] === '09121234567'
        && $request['text'] === 'کد تأیید شما: 123456');
});

it('does not send when the username is empty', function () {
    config(['sms.username' => null]);

    Http::fake();

    $log = app(MelipayamakService::class)->sendOtp('09121234567', '654321');

    expect($log)
        ->send_status->toBe(SmsSendStatus::Failed)
        ->service_response->toBe('نام کاربری ملی پیامک تنظیم نشده است.');

    Http::assertNothingSent();
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
        'rest.payamak-panel.com/api/SmartSMS/Send' => Http::response([
            'Value' => '0',
            'RetStatus' => 2,
            'StrRetStatus' => 'اعتبار کافی نیست',
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
