<?php

use Faker\Factory as FakerFactory;
use Faker\Generator;

if (! function_exists('fake') && class_exists(FakerFactory::class)) {
    function fake(?string $locale = null): Generator
    {
        if (function_exists('app') && app()->bound('config')) {
            $locale ??= app('config')->get('app.faker_locale');
        }

        $locale ??= 'en_US';

        $abstract = Generator::class.':'.$locale;

        if (! app()->bound($abstract)) {
            app()->singleton($abstract, fn (): Generator => FakerFactory::create($locale));
        }

        return app()->make($abstract);
    }
}
