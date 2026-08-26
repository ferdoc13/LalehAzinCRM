<?php

namespace App\Models;

use App\Enums\SmsEventType;
use App\Enums\SmsSendStatus;
use Database\Factories\SmsLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'recipient',
    'event_type',
    'content',
    'send_status',
    'service_response',
])]
class SmsLog extends Model
{
    /** @use HasFactory<SmsLogFactory> */
    use HasFactory;

    protected $attributes = [
        'send_status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => SmsEventType::class,
            'send_status' => SmsSendStatus::class,
        ];
    }
}
