<?php

namespace App\Models;

use App\Enums\CreditTransactionType;
use Database\Factories\CustomerCreditLedgerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'customer_id',
    'discount_request_id',
    'invoice_id',
    'amount',
    'transaction_type',
    'description',
    'balance_after',
])]
class CustomerCreditLedger extends Model
{
    /** @use HasFactory<CustomerCreditLedgerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'transaction_type' => CreditTransactionType::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function discountRequest(): BelongsTo
    {
        return $this->belongsTo(DiscountRequest::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class, 'credit_ledger_id');
    }
}
