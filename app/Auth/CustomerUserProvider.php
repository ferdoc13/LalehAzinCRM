<?php

namespace App\Auth;

use App\Models\Customer;
use App\Services\OtpService;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class CustomerUserProvider extends EloquentUserProvider
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?UserContract
    {
        $mobile = $credentials['mobile'] ?? null;

        if (! is_string($mobile) || $mobile === '') {
            return null;
        }

        return $this->newModelQuery()
            ->where('mobile', $mobile)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(UserContract $user, #[\SensitiveParameter] array $credentials): bool
    {
        $code = $credentials['code'] ?? null;

        if (! is_string($code) || $code === '' || ! $user instanceof Customer) {
            return false;
        }

        return app(OtpService::class)->consume($user, $code);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function rehashPasswordIfRequired(UserContract $user, #[\SensitiveParameter] array $credentials, bool $force = false): void
    {
        //
    }

    public function updateRememberToken(UserContract $user, #[\SensitiveParameter] $token): void
    {
        //
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?UserContract
    {
        return null;
    }
}
