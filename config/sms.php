<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS sending
    |--------------------------------------------------------------------------
    |
    | When disabled, notifications still queue but Melipayamak is not called.
    | Each attempt is recorded on sms_logs as failed so local/dev stays safe.
    |
    */

    'enabled' => (bool) env('SMS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Melipayamak Smart credentials
    |--------------------------------------------------------------------------
    |
    | Uses the Smart SMS REST API:
    | POST https://rest.payamak-panel.com/api/SmartSMS/Send
    |
    | password may be the panel password or the panel APIKey (not the
    | console.melipayamak.com token). fromSupport lines are optional backups.
    |
    */

    'username' => env('MELIPAYAMAK_USERNAME'),

    'password' => env('MELIPAYAMAK_PASSWORD'),

    'from' => env('MELIPAYAMAK_FROM'),

    'from_support_one' => env('MELIPAYAMAK_FROM_SUPPORT_ONE'),

    'from_support_two' => env('MELIPAYAMAK_FROM_SUPPORT_TWO'),

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
    | Message templates
    |--------------------------------------------------------------------------
    |
    | Sent as the SMS body. Placeholders like {name} are replaced before
    | calling SendSmartSMS.
    |
    */

    'messages' => [

        'otp' => 'کد تأیید شما: {code}',

        'customer_registered' => '{first_name} {last_name} عزیز، ثبت‌نام شما در لاله‌آذین با موفقیت انجام شد.',

        'invoice_created' => '{name} عزیز، فاکتور {invoice_number} به مبلغ {total_amount} ریال صادر شد.',

        'discount_request_created' => 'درخواست تخفیف جدید برای {customer_name} روی فاکتور {invoice_number} به مبلغ {proposed_amount} ریال ثبت شد.',

        'discount_reviewed' => '{name} عزیز، درخواست تخفیف شما «{status}» شد. مبلغ نهایی: {amount} ریال.',

        'discount_applied' => '{name} عزیز، تخفیف {discount_amount} ریال روی فاکتور {invoice_number} اعمال شد. مبلغ قابل پرداخت: {total_amount} ریال.',

        'withdrawal_request_created' => '{name} عزیز، درخواست واریز مبلغ {amount} ریال ثبت شد و در انتظار بررسی است.',

        'withdrawal_completed' => '{name} عزیز، درخواست واریز مبلغ {amount} ریال انجام شد.',

    ],

];
