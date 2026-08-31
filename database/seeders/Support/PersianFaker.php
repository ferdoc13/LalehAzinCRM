<?php

namespace Database\Seeders\Support;

use Faker\Factory as FakerFactory;
use Faker\Generator;

class PersianFaker
{
    /** @var list<string> */
    public const FIRST_NAMES = [
        'محمد', 'علی', 'حسین', 'رضا', 'مهدی', 'امیر', 'سعید', 'احمد',
        'فاطمه', 'زهرا', 'مریم', 'سارا', 'نرگس', 'الهام', 'پریسا', 'نازنین',
    ];

    /** @var list<string> */
    public const LAST_NAMES = [
        'احمدی', 'محمدی', 'حسینی', 'رضایی', 'کریمی', 'موسوی', 'جعفری', 'نوری',
        'صادقی', 'اکبری', 'رحیمی', 'قاسمی', 'زارعی', 'بهرامی', 'ملکی', 'شریفی',
    ];

    /** @var list<string> */
    public const COMPANY_NAMES = [
        'شرکت بازرگانی پارس نوین',
        'گروه صنعتی آذرخش',
        'شرکت فناوری اطلاعات رایان',
        'مجتمع تولیدی سپهر',
        'شرکت مهندسی پایا',
        'گروه بازرگانی کیمیا',
        'شرکت خدمات مالی نوین',
        'صنایع غذایی طلوع',
    ];

    /** @var list<string> */
    public const BANKS = [
        'ملی', 'ملت', 'صادرات', 'تجارت', 'سپه', 'پاسارگاد', 'سامان', 'اقتصاد نوین',
    ];

    /** @var list<string> */
    public const ADDRESSES = [
        'تهران، خیابان ولیعصر، پلاک ۱۲۳',
        'اصفهان، خیابان چهارباغ، کوچه ۵',
        'مشهد، بلوار سجاد، ساختمان ۴۵',
        'شیراز، خیابان زند، پلاک ۷۸',
        'تبریز، خیابان امام، واحد ۱۲',
        'کرج، مهرشهر، فاز ۳، پلاک ۲۱',
        'رشت، خیابان گلسار، ساختمان ۹',
        'اهواز، کیانپارس، بلوار ۱۵',
    ];

    /** @var list<string> */
    public const PRODUCT_DESCRIPTIONS = [
        'خدمات مشاوره فنی',
        'نصب و راه‌اندازی سیستم',
        'پشتیبانی سالانه',
        'تجهیزات شبکه',
        'لایسنس نرم‌افزار',
        'آموزش کاربران',
        'نگهداری و تعمیرات',
        'طراحی و توسعه سفارشی',
    ];

    private static ?Generator $faker = null;

    public static function faker(): Generator
    {
        return self::$faker ??= FakerFactory::create((string) config('app.faker_locale', 'en_US'));
    }

    public static function firstName(): string
    {
        return self::faker()->randomElement(self::FIRST_NAMES);
    }

    public static function lastName(): string
    {
        return self::faker()->randomElement(self::LAST_NAMES);
    }

    public static function companyName(): string
    {
        return self::faker()->randomElement(self::COMPANY_NAMES);
    }

    public static function bankName(): string
    {
        return 'بانک '.self::faker()->randomElement(self::BANKS);
    }

    public static function address(): string
    {
        return self::faker()->randomElement(self::ADDRESSES);
    }

    public static function mobile(): string
    {
        return '09'.self::faker()->unique()->numerify('#########');
    }

    public static function nationalCode(): string
    {
        return self::faker()->unique()->numerify('##########');
    }

    public static function nationalId(): string
    {
        return self::faker()->unique()->numerify('###########');
    }

    public static function economicCode(): string
    {
        return self::faker()->numerify('############');
    }

    public static function shebaNumber(): string
    {
        return 'IR'.self::faker()->numerify('########################');
    }

    public static function accountNumber(): string
    {
        return self::faker()->numerify('##########');
    }

    public static function otpCode(): string
    {
        return self::faker()->numerify('######');
    }

    public static function productDescription(): string
    {
        return self::faker()->randomElement(self::PRODUCT_DESCRIPTIONS);
    }
}
