<?php

use App\Filament\Customer\Pages\Auth\CustomerLogin;
use App\Models\Customer;
use App\Models\OtpCode;
use App\Notifications\OtpCodeNotification;
use App\Services\OtpService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('customer');
});

it('redirects the site root to customer login for guests', function () {
    $this->get('/')
        ->assertRedirect('/login');
});

it('renders the customer login page at /login', function () {
    $this->get('/login')
        ->assertOk();
});

it('sends an otp for a registered mobile number via sms notification', function () {
    $customer = Customer::factory()->create(['mobile' => '09121234567']);

    Notification::fake();

    Livewire::test(CustomerLogin::class)
        ->fillForm(['mobile' => $customer->mobile])
        ->call('requestCode')
        ->assertHasNoFormErrors()
        ->assertSet('step', 'code')
        ->assertSet('mobile', $customer->mobile);

    $otp = OtpCode::query()->where('phone', $customer->mobile)->latest('id')->first();

    expect($otp)
        ->not->toBeNull()
        ->is_used->toBeFalse()
        ->and($otp->expires_at->greaterThan(now()->addMinute()))->toBeTrue()
        ->and($otp->expires_at->lessThanOrEqualTo(now()->addMinutes(2)))->toBeTrue();

    Notification::assertSentTo(
        $customer,
        OtpCodeNotification::class,
        fn (OtpCodeNotification $notification): bool => $notification->otpCode->is($otp),
    );
});

it('rejects an unknown mobile number', function () {
    Livewire::test(CustomerLogin::class)
        ->fillForm(['mobile' => '09120000000'])
        ->call('requestCode')
        ->assertHasFormErrors(['mobile']);
});

it('logs a customer in with a valid otp', function () {
    $customer = Customer::factory()->create(['mobile' => '09127654321']);

    $component = Livewire::test(CustomerLogin::class)
        ->fillForm(['mobile' => $customer->mobile])
        ->call('requestCode')
        ->assertSet('step', 'code');

    $otp = OtpCode::query()->where('phone', $customer->mobile)->where('is_used', false)->first();

    $component
        ->fillForm(['code' => $otp->code])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    expect(auth('customer')->id())->toBe($customer->id);
    expect($otp->fresh()->is_used)->toBeTrue();
});

it('rejects an invalid otp code', function () {
    $customer = Customer::factory()->create(['mobile' => '09121112233']);

    $component = Livewire::test(CustomerLogin::class)
        ->fillForm(['mobile' => $customer->mobile])
        ->call('requestCode');

    $component
        ->fillForm(['code' => '000000'])
        ->call('authenticate')
        ->assertHasFormErrors(['code']);

    expect(auth('customer')->check())->toBeFalse();
});

it('rate limits otp verification after five failed attempts in fifteen minutes', function () {
    $customer = Customer::factory()->create(['mobile' => '09123334455']);
    $otp = app(OtpService::class);
    $key = 'otp-verify:'.$customer->mobile.':'.request()->ip();

    foreach (range(1, OtpService::VERIFY_MAX_ATTEMPTS) as $attempt) {
        expect($otp->consume($customer, '000000'))->toBeFalse();
    }

    expect(RateLimiter::tooManyAttempts($key, OtpService::VERIFY_MAX_ATTEMPTS))->toBeTrue();

    $component = Livewire::test(CustomerLogin::class)
        ->fillForm(['mobile' => $customer->mobile])
        ->call('requestCode');

    $valid = OtpCode::query()->where('phone', $customer->mobile)->where('is_used', false)->first();

    $component
        ->fillForm(['code' => $valid->code])
        ->call('authenticate')
        ->assertNotified();

    expect(auth('customer')->check())->toBeFalse();
});

it('shows a two minute countdown and a change-number link after sending the otp', function () {
    $customer = Customer::factory()->create(['mobile' => '09124445566']);

    $component = Livewire::test(CustomerLogin::class)
        ->fillForm(['mobile' => $customer->mobile])
        ->call('requestCode')
        ->assertSet('step', 'code')
        ->assertSee('زمان باقی‌مانده پیامک')
        ->assertSee('2:00')
        ->assertSee('شماره را اشتباه زدید؟ تغییر شماره')
        ->assertSee($customer->mobile);

    expect($component->get('otpExpiresAt'))
        ->toBeGreaterThan(now()->addMinute()->getTimestamp())
        ->toBeLessThanOrEqual(now()->addMinutes(2)->getTimestamp());
});

it('lets the customer go back and edit the mobile number', function () {
    $customer = Customer::factory()->create(['mobile' => '09127778899']);

    Livewire::test(CustomerLogin::class)
        ->fillForm(['mobile' => $customer->mobile])
        ->call('requestCode')
        ->assertSet('step', 'code')
        ->call('resetToMobileStep')
        ->assertSet('step', 'mobile')
        ->assertSet('otpExpiresAt', null)
        ->assertFormSet(['mobile' => $customer->mobile]);
});
