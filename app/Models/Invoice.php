<?php

namespace App\Models;

use App\Enums\DiscountRequestStatus;
use App\Enums\InvoicePaymentStatus;
use App\Observers\InvoiceObserver;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'customer_id',
    'employee_id',
    'invoice_number',
    'total_amount',
    'discount_amount',
    'invoice_date',
    'payment_status',
])]
#[ObservedBy([InvoiceObserver::class])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    protected $attributes = [
        'payment_status' => 'pending',
        'discount_amount' => 0,
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'invoice_date' => 'date',
            'payment_status' => InvoicePaymentStatus::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function discountRequests(): HasMany
    {
        return $this->hasMany(DiscountRequest::class);
    }

    public function pendingDiscountRequest(): HasOne
    {
        return $this->hasOne(DiscountRequest::class)
            ->where('status', DiscountRequestStatus::Pending);
    }

    public function creditLedgers(): HasMany
    {
        return $this->hasMany(CustomerCreditLedger::class);
    }

    public function itemsTotal(): float
    {
        if ($this->relationLoaded('items')) {
            return round((float) $this->items->sum('total_amount'), 2);
        }

        return round((float) $this->items()->sum('total_amount'), 2);
    }

    public function syncTotalFromItems(): void
    {
        $itemsTotal = $this->itemsTotal();
        $discount = min((float) $this->discount_amount, $itemsTotal);

        $this->update([
            'discount_amount' => $discount,
            'total_amount' => round($itemsTotal - $discount, 2),
        ]);
    }

    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        if ($user->canAccessAllRecords()) {
            return $query;
        }

        return $query->where('employee_id', $user->id);
    }
}
