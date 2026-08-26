<?php

namespace App\Models;

use App\Enums\WithdrawalRequestStatus;
use Database\Factories\WithdrawalRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customer_id',
    'credit_ledger_id',
    'discount_request_id',
    'amount',
    'bank_account_id',
    'status',
])]
class WithdrawalRequest extends Model
{
    /** @use HasFactory<WithdrawalRequestFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => WithdrawalRequestStatus::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creditLedger(): BelongsTo
    {
        return $this->belongsTo(CustomerCreditLedger::class);
    }

    public function discountRequest(): BelongsTo
    {
        return $this->belongsTo(DiscountRequest::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(CustomerBankAccount::class, 'bank_account_id');
    }
}
