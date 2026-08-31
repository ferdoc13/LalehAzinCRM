<?php

namespace App\Providers;

use App\Auth\CustomerGuard;
use App\Auth\CustomerUserProvider;
use App\Notifications\Channels\MelipayamakChannel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Notification::extend('melipayamak', fn ($app) => $app->make(MelipayamakChannel::class));

        Auth::provider('customers', function (Application $app, array $config): CustomerUserProvider {
            return new CustomerUserProvider($app['hash'], $config['model']);
        });

        Auth::extend('customer', function (Application $app, string $name, array $config): CustomerGuard {
            $guard = new CustomerGuard(
                $name,
                Auth::createUserProvider($config['provider'] ?? 'customers'),
                $app['session.store'],
                rehashOnLogin: false,
                timeboxDuration: (int) $app['config']->get('auth.timebox_duration', 200000),
                hashKey: $app['config']->get('app.key'),
            );

            $guard->setCookieJar($app['cookie']);
            $guard->setDispatcher($app['events']);
            $guard->setRequest($app->refresh('request', $guard, 'setRequest'));

            return $guard;
        });
    }
}
