<?php

use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Support\Facades\App;
use Morilog\Jalali\CalendarUtils;
use Morilog\Jalali\Jalalian;

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

if (! function_exists('jalali_format')) {
    function jalali_format(mixed $date, string $format = 'Y/m/d'): ?string
    {
        if (blank($date)) {
            return null;
        }

        return CalendarUtils::convertNumbers(
            Jalalian::fromDateTime($date)->format($format),
            ! App::isLocale('fa'),
        );
    }
}
