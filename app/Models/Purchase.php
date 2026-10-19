<?php

namespace App\Models;

use App\Models\Concerns\TracksPayments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Purchase extends Model
{
    use TracksPayments;

    public const BILL_TYPE = 'purchase';

    public const PAYMENT_TYPE = 'payment_out';

    protected $fillable = ['purchase_number', 'supplier_id', 'purchase_date', 'status', 'payment_status', 'notes'];

    protected $casts = [
        'purchase_date' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
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
        return (float) $this->items->sum(fn (PurchaseItem $item) => $item->quantity * $item->unit_cost);
    }

    public function isPayable(): bool
    {
        return $this->status === 'received';
    }

    /**
     * @param  array{note?: ?string, method?: ?string, reference?: ?string}  $details
     */
    public function recordPayment(float $amount, string $date, array $details = []): Transaction
    {
        $details['note'] = ($details['note'] ?? null) ?: 'Payment made';
        $payment = $this->newPayment($amount, $date, $details, 'supplier_id', $this->supplier_id);
        $this->syncPaymentStatus();

        return $payment;
    }

    protected function afterPaymentStatusSynced(string $status): void
    {
        $this->update(['payment_status' => $status]);
    }
}
