<?php

namespace App\Auth;

use Illuminate\Auth\SessionGuard;

class CustomerGuard extends SessionGuard
{
    public function attemptWithOtp(string $mobile, string $code): bool
    {
        $credentials = [
            'mobile' => $mobile,
            'code' => $code,
        ];

        $this->fireAttemptEvent($credentials, false);

        $this->lastAttempted = $user = $this->provider->retrieveByCredentials([
            'mobile' => $mobile,
        ]);

        if ($this->hasValidCredentials($user, $credentials)) {
            $this->login($user, remember: false);

            return true;
        }

        $this->fireFailedEvent($user, $credentials);

        return false;
    }
}
