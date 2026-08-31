<?php

namespace App\Services;

use App\Exceptions\OtpRateLimitedException;
use App\Models\Customer;
use App\Models\OtpCode;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public const CODE_LENGTH = 6;

    public const TTL_MINUTES = 2;

    public const VERIFY_MAX_ATTEMPTS = 5;

    public const VERIFY_DECAY_SECONDS = 900;

    public const REQUEST_MAX_ATTEMPTS = 5;

    public const REQUEST_DECAY_SECONDS = 900;

    public function send(string $mobile): OtpCode
    {
        $this->hitRequestLimiter($mobile);

        $customer = Customer::query()->where('mobile', $mobile)->first();

        if (! $customer) {
            throw ValidationException::withMessages([
                'data.mobile' => 'شماره موبایل در سیستم ثبت نشده است.',
            ]);
        }

        OtpCode::query()
            ->where('phone', $mobile)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        $code = $this->generateCode();

        $otp = OtpCode::query()->create([
            'customer_id' => $customer->id,
            'phone' => $mobile,
            'code' => $code,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'is_used' => false,
        ]);

        return $otp;
    }

    public function consume(Customer $customer, string $code): bool
    {
        $key = $this->verifyKey($customer->mobile);

        if (RateLimiter::tooManyAttempts($key, self::VERIFY_MAX_ATTEMPTS)) {
            throw new OtpRateLimitedException(RateLimiter::availableIn($key));
        }

        $consumed = OtpCode::query()
            ->where('customer_id', $customer->id)
            ->where('phone', $customer->mobile)
            ->where('code', $code)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->update(['is_used' => true]);

        if ($consumed !== 1) {
            RateLimiter::hit($key, self::VERIFY_DECAY_SECONDS);

            return false;
        }

        RateLimiter::clear($key);

        return true;
    }

    public function tooManyVerifyAttempts(string $mobile): bool
    {
        return RateLimiter::tooManyAttempts($this->verifyKey($mobile), self::VERIFY_MAX_ATTEMPTS);
    }

    public function verifyRetryAvailableIn(string $mobile): int
    {
        return RateLimiter::availableIn($this->verifyKey($mobile));
    }

    private function hitRequestLimiter(string $mobile): void
    {
        $key = $this->requestKey($mobile);

        if (RateLimiter::tooManyAttempts($key, self::REQUEST_MAX_ATTEMPTS)) {
            throw new OtpRateLimitedException(RateLimiter::availableIn($key));
        }

        RateLimiter::hit($key, self::REQUEST_DECAY_SECONDS);
    }

    private function generateCode(): string
    {
        $max = (10 ** self::CODE_LENGTH) - 1;

        return str_pad((string) random_int(0, $max), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    private function requestKey(string $mobile): string
    {
        return 'otp-request:'.$mobile.':'.request()->ip();
    }

    private function verifyKey(string $mobile): string
    {
        return 'otp-verify:'.$mobile.':'.request()->ip();
    }
}
