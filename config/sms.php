<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS sending
    |--------------------------------------------------------------------------
    |
    | When disabled, notifications still queue but Melipayamak is not called.
    | Each attempt is recorded on sms_logs as failed so local/dev stays safe
    | until pattern codes are filled in from the Melipayamak console.
    |
    */

    'enabled' => (bool) env('SMS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Melipayamak console credentials
    |--------------------------------------------------------------------------
    |
    | Uses https://console.melipayamak.com with an API key. Shared patterns
    | (خدماتی) are required; raw/simple SMS is not used.
    |
    */

    'api_key' => env('MELIPAYAMAK_API_KEY'),

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
    | console. Arguments are sent in the documented order as {0}, {1}, …
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

];
