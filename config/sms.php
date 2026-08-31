<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS sending
    |--------------------------------------------------------------------------
    |
    | When disabled, notifications still queue but Melipayamak is not called.
    | Each attempt is recorded on sms_logs as failed so local/dev stays safe
    | until pattern codes are filled in from the Melipayamak panel.
    |
    */

    'enabled' => (bool) env('SMS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Melipayamak credentials
    |--------------------------------------------------------------------------
    |
    | driver: auto | console | rest
    |   auto    — console REST when MELIPAYAMAK_API_KEY is set, otherwise
    |             classic REST (username/password) from the official SDK.
    |   console — https://console.melipayamak.com (API key / token)
    |   rest    — https://rest.payamak-panel.com (username/password)
    |
    */

    'driver' => env('MELIPAYAMAK_DRIVER', 'auto'),

    'api_key' => env('MELIPAYAMAK_API_KEY'),

    'username' => env('MELIPAYAMAK_USERNAME'),

    'password' => env('MELIPAYAMAK_PASSWORD'),

    'from' => env('MELIPAYAMAK_FROM'),

    'timeout' => (int) env('MELIPAYAMAK_TIMEOUT', 10),

    'connect_timeout' => (int) env('MELIPAYAMAK_CONNECT_TIMEOUT', 3),

    /*
    |--------------------------------------------------------------------------
    | Manager recipients
    |--------------------------------------------------------------------------
    |
    | Comma-separated mobiles for staff-only alerts such as a new discount
    | request. Leave empty to skip those optional notifications.
    |
    */

    'manager_mobiles' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('SMS_MANAGER_MOBILES', '')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Pattern codes (bodyId)
    |--------------------------------------------------------------------------
    |
    | Fill each value with the numeric pattern code from the Melipayamak
    | panel. Arguments are sent in the documented order as {0}, {1}, …
    | When a code is empty, the matching fallback_messages text is sent
    | with sendRaw() instead.
    |
    */

    'patterns' => [

        // {0} code
        'otp' => env('SMS_PATTERN_OTP'),

        // {0} first_name  {1} last_name
        'customer_registered' => env('SMS_PATTERN_CUSTOMER_REGISTERED'),

        // {0} full_name  {1} invoice_number  {2} total_amount
        'invoice_created' => env('SMS_PATTERN_INVOICE_CREATED'),

        // {0} customer_name  {1} proposed_amount
        'discount_request_created' => env('SMS_PATTERN_DISCOUNT_REQUEST_CREATED'),

        // {0} full_name  {1} status_label  {2} amount
        'discount_reviewed' => env('SMS_PATTERN_DISCOUNT_REVIEWED'),

        // {0} full_name  {1} invoice_number  {2} discount_amount  {3} total_amount
        'discount_applied' => env('SMS_PATTERN_DISCOUNT_APPLIED'),

        // {0} full_name  {1} amount
        'withdrawal_request_created' => env('SMS_PATTERN_WITHDRAWAL_REQUEST_CREATED'),

        // {0} full_name  {1} amount
        'withdrawal_completed' => env('SMS_PATTERN_WITHDRAWAL_COMPLETED'),

    ],

    'fallback_messages' => [

        'otp' => 'کد تأیید شما: {code}',

        'customer_registered' => '{first_name} {last_name} عزیز، ثبت‌نام شما در لاله‌آذین با موفقیت انجام شد.',

        'invoice_created' => '{name} عزیز، فاکتور {invoice_number} به مبلغ {total_amount} ریال صادر شد.',

        'discount_request_created' => 'درخواست تخفیف جدید برای {customer_name} به مبلغ {proposed_amount} ریال ثبت شد.',

        'discount_reviewed' => '{name} عزیز، درخواست تخفیف شما «{status}» شد. مبلغ نهایی: {amount} ریال.',

        'discount_applied' => '{name} عزیز، تخفیف {discount_amount} ریال روی فاکتور {invoice_number} اعمال شد. مبلغ قابل پرداخت: {total_amount} ریال.',

        'withdrawal_request_created' => '{name} عزیز، درخواست واریز مبلغ {amount} ریال ثبت شد و در انتظار بررسی است.',

        'withdrawal_completed' => '{name} عزیز، درخواست واریز مبلغ {amount} ریال انجام شد.',

    ],

];
