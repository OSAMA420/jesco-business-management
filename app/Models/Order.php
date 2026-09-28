<?php

namespace App\Models;

use App\Models\Concerns\TracksPayments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Order extends Model
{
    use TracksPayments;

    public const BILL_TYPE = 'sale';

    public const PAYMENT_TYPE = 'payment_in';

    protected $fillable = ['order_number', 'customer_id', 'order_date', 'status', 'notes'];

    protected $casts = [
        'order_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'reference');
    }

    public function total(): float
    {
        return (float) $this->items->sum(fn (OrderItem $item) => $item->quantity * $item->unit_price);
    }

    public function isPayable(): bool
    {
        return $this->status !== 'cancelled';
    }

    public function recordPayment(float $amount, string $date, ?string $note = null): Transaction
    {
        $payment = $this->newPayment($amount, $date, $note ?: 'Payment received', 'customer_id', $this->customer_id);
        $this->syncPaymentStatus();

        return $payment;
    }
}
